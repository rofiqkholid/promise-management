<?php

namespace App\Http\Controllers\FeasibilityStudy;

use App\Http\Controllers\Controller;
use App\Models\MngEbdHeader;
use App\Models\MngEbdItem;
use App\Models\MngEbdToolingProcess;
use App\Models\ToolingQuotation;
use App\Models\ToolingQuotationDetail;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToolingQuotationCompareController extends Controller
{
    /**
     * Halaman Utama Index: List SPK 2 Tooling Cost yang sudah Finish / Approved
     */
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $search = $request->input('search.value');

            // Query hanya SPK 2 Tooling Cost dengan status Approved atau Released
            $query = WorkOrder::where('wo_type', 'SPK_2_TOOLING')
                ->whereIn('status', ['Approved', 'Released'])
                ->with(['inquiry.customer', 'inquiry.projectModel', 'ebdHeader']);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('wo_number', 'LIKE', "%{$search}%")
                      ->orWhereHas('inquiry.customer', fn($c) => $c->where('code', 'LIKE', "%{$search}%"))
                      ->orWhereHas('inquiry.projectModel', fn($m) => $m->where('name', 'LIKE', "%{$search}%"));
                });
            }

            $totalRecords = $query->count();
            $workOrders = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($workOrders as $wo) {
                $ebdId = $wo->ebd_header_id;
                $quotationCount = $ebdId ? ToolingQuotation::where('ebd_header_id', $ebdId)->count() : 0;
                $customerCode = $wo->inquiry->customer->code ?? '—';
                $modelName = $wo->inquiry->projectModel->name ?? '—';

                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'wo_number' => $wo->wo_number,
                    'customer_model' => "<strong>{$customerCode}</strong> • {$modelName}",
                    'ebd_ref' => $ebdId ? "EBD Rev. " . ($wo->ebdHeader->revision ?? '0') : '—',
                    'status' => $wo->status,
                    'quotation_count' => $quotationCount . ' Supplier',
                    'download_template_url' => route('management.work-order-tooling.quotation', $this->encryptId($wo->id)),
                    'compare_url' => route('management.tooling-quotation.show', $this->encryptId($wo->id)),
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ]);
        }

        return view('management.tooling-quotation.index');
    }

    /**
     * Halaman Detail Comparison per SPK Tooling Approved
     */
    public function show($id)
    {
        $decryptedWoId = $this->decryptId($id);
        $workOrder = WorkOrder::with(['ebdHeader.customer', 'ebdHeader.projectModel'])->findOrFail($decryptedWoId);

        $selectedEbd = $workOrder->ebdHeader;
        $quotations = collect();
        $ebdItems = collect();

        if ($selectedEbd) {
            $selectedEbd->load(['items.toolingProcesses', 'items.addProcesses']);
            $quotations = ToolingQuotation::with(['details', 'importer'])
                ->where('ebd_header_id', $selectedEbd->id)
                ->orderBy('id', 'desc')
                ->get();
            $ebdItems = $selectedEbd->items;
        }

        $encryptedWoId = $id;

        return view('management.tooling-quotation.detail', compact(
            'workOrder',
            'selectedEbd',
            'quotations',
            'ebdItems',
            'encryptedWoId'
        ));
    }

    /**
     * Import File Excel Quotation Supplier
     */
    public function import(Request $request)
    {
        $request->validate([
            'ebd_header_id' => 'required|exists:mng_ebd_headers,id',
            'supplier_name' => 'required|string|max:150',
            'currency_name' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0',
            'quotation_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction();
        try {
            $ebdHeaderId = $request->input('ebd_header_id');
            $supplierName = $request->input('supplier_name');
            $currencyName = $request->input('currency_name');
            $exchangeRate = (float) $request->input('exchange_rate', 1);

            // Simpan file Excel yang diupload
            $file = $request->file('quotation_file');
            $filePath = $file->store('quotations', 'public');

            // Hitung revisi quotation berikutnya untuk EBD & Supplier ini
            $lastRev = ToolingQuotation::where('ebd_header_id', $ebdHeaderId)
                ->where('supplier_name', $supplierName)
                ->max('revision');
            $nextRev = $lastRev !== null ? (string)((int)$lastRev + 1) : '0';

            // 1. Buat Record Header Quotation Supplier
            $quotation = ToolingQuotation::create([
                'ebd_header_id' => $ebdHeaderId,
                'supplier_id' => null,
                'quotation_no' => 'QUO-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'revision' => $nextRev,
                'currency_name' => $currencyName,
                'exchange_rate' => $exchangeRate,
                'total_cost_foreign' => 0,
                'total_cost_idr' => 0,
                'file_path' => $filePath,
                'status' => 'IMPORTED',
                'imported_by' => auth()->id(),
                'imported_at' => now(),
            ]);

            // Load EBD Items & Tooling Processes
            $ebdItems = MngEbdItem::with('toolingProcesses')
                ->where('ebd_header_id', $ebdHeaderId)
                ->get();

            $totalCostForeign = 0;
            $totalCostIdr = 0;

            // Membaca file excel menggunakan PhpOffice/PhpSpreadsheet (otomatis disupport Laravel Excel / Spreadsheet)
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Loop membaca baris data Excel (dimulai dari baris data setelah header, misal baris 11)
            // Struktur membaca toleran jika layout mirip dengan quotation.blade.php
            for ($row = 11; $row <= $highestRow; $row++) {
                $partNo = trim((string) $sheet->getCell("C{$row}")->getValue());
                $partName = trim((string) $sheet->getCell("D{$row}")->getValue());
                $procName = trim((string) $sheet->getCell("E{$row}")->getValue());
                $supplierStatus = trim((string) $sheet->getCell("G{$row}")->getValue());
                $opVal = $sheet->getCell("H{$row}")->getValue();
                $opNo = is_numeric($opVal) ? (int)$opVal : null;
                $toolingProcName = trim((string) $sheet->getCell("I{$row}")->getValue());
                $toolingType = trim((string) $sheet->getCell("J{$row}")->getValue());
                $tonnage = trim((string) $sheet->getCell("N{$row}")->getValue());
                $dieHeight = $sheet->getCell("Q{$row}")->getValue();
                $dieCategory = trim((string) $sheet->getCell("R{$row}")->getValue());
                $costForeignVal = $sheet->getCell("S{$row}")->getValue();
                $costIdrVal = $sheet->getCell("T{$row}")->getValue();

                // Skip baris jika kosong total
                if (!$partNo && !$procName && !$toolingProcName && !$costForeignVal && !$costIdrVal) {
                    continue;
                }

                // Cari EBD Item matching (by Part No)
                $matchedEbdItem = $ebdItems->first(fn($item) => strtolower(trim($item->part_no)) === strtolower($partNo));
                $ebdItemId = $matchedEbdItem->id ?? null;

                // Cari EBD Process matching (by OP & Process Name)
                $ebdProcessId = null;
                if ($matchedEbdItem && $opNo) {
                    $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => (int)$tp->op === (int)$opNo);
                    $ebdProcessId = $matchedProc->id ?? null;
                }

                $costForeign = is_numeric($costForeignVal) ? (float)$costForeignVal : 0;
                $costIdr = is_numeric($costIdrVal) ? (float)$costIdrVal : ($costForeign * $exchangeRate);

                $totalCostForeign += $costForeign;
                $totalCostIdr += $costIdr;

                ToolingQuotationDetail::create([
                    'tooling_quotation_id' => $quotation->id,
                    'ebd_item_id' => $ebdItemId,
                    'ebd_tooling_process_id' => $ebdProcessId,
                    'process_type' => $procName ?: 'STAMPING',
                    'homeline' => null,
                    'supplier_status' => $supplierStatus,
                    'op' => $opNo,
                    'tooling_process_name' => $toolingProcName ?: $procName,
                    'tooling_type' => $toolingType ?: 'DIES',
                    'tonnage' => $tonnage,
                    'die_height' => is_numeric($dieHeight) ? (float)$dieHeight : null,
                    'die_category' => $dieCategory,
                    'cost_foreign' => $costForeign,
                    'cost_idr' => $costIdr,
                    'remarks' => null,
                ]);
            }

            // Update Total Header
            $quotation->update([
                'total_cost_foreign' => $totalCostForeign,
                'total_cost_idr' => $totalCostIdr,
                'status' => 'COMPARED',
            ]);

            DB::commit();

            $workOrder = WorkOrder::where('ebd_header_id', $ebdHeaderId)->first();
            $redirectUrl = $workOrder ? route('management.tooling-quotation.show', $this->encryptId($workOrder->id)) : route('management.tooling-quotation.index');

            return redirect($redirectUrl)
                ->with('success', "Quotation supplier {$supplierName} berhasil di-import dan di-compare!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Tooling Quotation Failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Gagal import quotation: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Data Quotation Supplier
     */
    public function destroy($id)
    {
        try {
            $quotation = ToolingQuotation::findOrFail($id);
            $ebdId = $quotation->ebd_header_id;
            $quotation->delete();

            $workOrder = WorkOrder::where('ebd_header_id', $ebdId)->first();
            $redirectUrl = $workOrder ? route('management.tooling-quotation.show', $this->encryptId($workOrder->id)) : route('management.tooling-quotation.index');

            return redirect($redirectUrl)
                ->with('success', 'Data quotation supplier berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus quotation: ' . $e->getMessage());
        }
    }
}
