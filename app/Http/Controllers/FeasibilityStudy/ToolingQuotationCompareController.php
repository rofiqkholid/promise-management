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
    public function apiGetSuppliers(Request $request)
    {
        $search = $request->input('search', '');
        $query = \App\Models\Suppliers::where('is_active', 1);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->take(20)->get(['id', 'name', 'code']);

        return response()->json([
            'results' => $suppliers->map(fn($s) => [
                'id' => $s->id,
                'text' => $s->name . ($s->code ? " ({$s->code})" : '')
            ])
        ]);
    }

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
            $quotations = ToolingQuotation::with(['details', 'importer', 'supplier'])
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
            'supplier_id' => 'required|exists:suppliers,id',
            'currency_name' => 'required|string|max:50',
            'exchange_rate' => 'required|numeric|min:0',
            'quotation_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction();
        try {
            $ebdHeaderId = $request->input('ebd_header_id');
            $supplierId = $request->input('supplier_id');
            $supplier = \App\Models\Suppliers::findOrFail($supplierId);
            $supplierName = $supplier->name;
            $currencyName = $request->input('currency_name');
            $exchangeRate = (float) $request->input('exchange_rate', 1);

            // Simpan file Excel yang diupload
            $file = $request->file('quotation_file');
            $filePath = $file->store('quotations', 'public');

            // Cek apakah data quotation untuk EBD & Supplier ini sudah pernah di-import (Overwrite Mode)
            $existingQuotation = ToolingQuotation::where('ebd_header_id', $ebdHeaderId)
                ->where('supplier_id', $supplierId)
                ->first();

            if ($existingQuotation) {
                // Hapus detail quotation lama
                ToolingQuotationDetail::where('tooling_quotation_id', $existingQuotation->id)->delete();
                
                // Hapus file Excel lama jika ada
                if ($existingQuotation->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($existingQuotation->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingQuotation->file_path);
                }

                $quotation = $existingQuotation;
                $quotation->update([
                    'currency_name' => $currencyName,
                    'exchange_rate' => $exchangeRate,
                    'total_cost_foreign' => 0,
                    'total_cost_idr' => 0,
                    'file_path' => $filePath,
                    'status' => 'IMPORTED',
                    'imported_by' => auth()->user() ? auth()->user()->id : null,
                    'imported_at' => now(),
                ]);
            } else {
                // Buat Record Header Quotation Baru jika belum pernah di-import
                $quotation = ToolingQuotation::create([
                    'ebd_header_id' => $ebdHeaderId,
                    'supplier_id' => $supplierId,
                    'quotation_no' => 'QUO-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'revision' => '0',
                    'currency_name' => $currencyName,
                    'exchange_rate' => $exchangeRate,
                    'total_cost_foreign' => 0,
                    'total_cost_idr' => 0,
                    'file_path' => $filePath,
                    'status' => 'IMPORTED',
                    'imported_by' => auth()->user() ? auth()->user()->id : null,
                    'imported_at' => now(),
                ]);
            }

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

            $importedRowsCount = 0;
            $currentPartNo = null;
            $currentPartName = null;

            // Loop membaca baris data Excel (Data dimulai pada baris 11 di mana Row 1-10 adalah Header & Filter)
            for ($row = 11; $row <= $highestRow; $row++) {
                // Kolom di Excel:
                // A (1) : No
                // B-G (2-7): Status Item
                // H (8) : Part No. (dapat berupa sel merged / hanya terisi di baris pertama per Part)
                // I (9) : Part Name (dapat berupa sel merged / hanya terisi di baris pertama per Part)
                // J (10): Process Name (e.g. DRAW (TIC), TRIM, FLG (TIC)...)
                // K (11): Homeline / Process (e.g. SAI, SUBCONT)
                // L (12): Tooling Status (NEW DIES / MODIF / COMMON)
                // M (13): OP (5, 10, 20, 30...)
                // N (14): Tooling / Additional Process Name
                // S (19): Tonnage
                // V (22): Die Height
                // W (23): Category
                // X (24): Currency Amount (Valas)
                // Y (25): IDR Amount

                $partNoRaw = trim((string) $sheet->getCell("H{$row}")->getCalculatedValue());
                $partNameRaw = trim((string) $sheet->getCell("I{$row}")->getCalculatedValue());

                // Jika Part No di baris ini terisi, perbarui $currentPartNo
                if (!empty($partNoRaw)) {
                    $currentPartNo = $partNoRaw;
                    $currentPartName = $partNameRaw;
                }

                $procName = trim((string) $sheet->getCell("J{$row}")->getCalculatedValue());
                $homeline = trim((string) $sheet->getCell("K{$row}")->getCalculatedValue());
                $supplierStatus = trim((string) $sheet->getCell("L{$row}")->getCalculatedValue());
                
                $opVal = $sheet->getCell("M{$row}")->getCalculatedValue();
                $opNo = is_numeric($opVal) ? (int)$opVal : null;
                
                $toolingProcName = trim((string) $sheet->getCell("N{$row}")->getCalculatedValue());
                $tonnage = trim((string) $sheet->getCell("S{$row}")->getCalculatedValue());
                $dieHeightVal = $sheet->getCell("V{$row}")->getCalculatedValue();
                $dieCategory = trim((string) $sheet->getCell("W{$row}")->getCalculatedValue());
                
                $costForeignVal = $sheet->getCell("X{$row}")->getCalculatedValue();
                $costIdrVal = $sheet->getCell("Y{$row}")->getCalculatedValue();

                // Skip baris kosong/footer jika tidak ada nama proses, OP, maupun harga
                if (!$procName && !$toolingProcName && $opNo === null && $costForeignVal === null && $costIdrVal === null) {
                    continue;
                }

                // Cari EBD Item matching (by $currentPartNo atau $partNoRaw)
                $targetPartNo = $currentPartNo ?: $partNoRaw;
                $matchedEbdItem = $ebdItems->first(fn($item) => strtolower(trim($item->part_no)) === strtolower(trim($targetPartNo)));
                $ebdItemId = $matchedEbdItem->id ?? null;

                // Cari EBD Process matching (by OP atau Process Name dari Kolom J)
                $ebdProcessId = null;
                if ($matchedEbdItem) {
                    if ($opNo !== null) {
                        $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => (int)$tp->op === (int)$opNo);
                        $ebdProcessId = $matchedProc->id ?? null;
                    } elseif (!empty($procName)) {
                        $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => strtolower(trim($tp->process_name)) === strtolower(trim($procName)));
                        $ebdProcessId = $matchedProc->id ?? null;
                    }
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
                    'homeline' => $homeline,
                    'supplier_status' => $supplierStatus,
                    'op' => $opNo,
                    'tooling_process_name' => $procName ?: ($toolingProcName ?: 'STAMPING'),
                    'tooling_type' => 'DIE',
                    'tonnage' => $tonnage,
                    'die_height' => is_numeric($dieHeightVal) ? (float)$dieHeightVal : null,
                    'die_category' => $dieCategory,
                    'cost_foreign' => $costForeign,
                    'cost_idr' => $costIdr,
                    'remarks' => null,
                ]);

                $importedRowsCount++;
            }

            if ($importedRowsCount === 0) {
                throw new \Exception("Tidak ada baris data quotation yang terbaca dari file Excel. Pastikan data dimulai pada baris 11 dengan kolom Part No (kolom H) atau OP (kolom M) yang terisi.");
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

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Quotation supplier {$supplierName} berhasil di-import! {$importedRowsCount} baris terproses.",
                    'redirect_url' => $redirectUrl
                ]);
            }

            return redirect($redirectUrl)
                ->with('success', "Quotation supplier {$supplierName} berhasil di-import dan di-compare!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Tooling Quotation Failed', ['error' => $e->getMessage()]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal import quotation: ' . $e->getMessage()
                ], 422);
            }

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
