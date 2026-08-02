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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function apiGetExchangeRate(Request $request)
    {
        $input = trim($request->input('currency', 'CNY'));
        $apiKey = config('services.exchangerate.key', env('EXCHANGE_RATE_API_KEY', 'f4c7d2674c0a5637721aad53'));

        // 1. Check local global CurrencyHelper dictionary first
        $foundLocal = \App\Helpers\CurrencyHelper::find($input);
        if ($foundLocal) {
            $currencyCode = $foundLocal['code'];
            $currencyName = $foundLocal['name'];
        } else {
            // 2. Fetch supported codes dynamically from ExchangeRate-API (cached for 24 hours)
            $supportedCodes = Cache::remember('exchangerate_supported_codes', 86400, function () use ($apiKey) {
                try {
                    $res = Http::timeout(5)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/codes");
                    if ($res->successful() && ($res->json()['result'] ?? '') === 'success') {
                        return $res->json()['supported_codes'] ?? [];
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch exchangerate codes', ['error' => $e->getMessage()]);
                }
                return [];
            });

            $currencyCode = strtoupper($input);
            $currencyName = $currencyCode;

            if (!empty($supportedCodes)) {
                $searchStr = strtolower($input);
                foreach ($supportedCodes as [$code, $name]) {
                    if (strtolower($code) === $searchStr || strtolower($name) === $searchStr || str_contains(strtolower($name), $searchStr)) {
                        $currencyCode = $code;
                        $currencyName = $name;
                        break;
                    }
                }
            }

            if ($currencyName === $currencyCode && preg_match('/\b([A-Za-z]{3})\b/', $input, $matches)) {
                $extractedCode = strtoupper($matches[1]);
                foreach ($supportedCodes as [$code, $name]) {
                    if ($code === $extractedCode) {
                        $currencyCode = $code;
                        $currencyName = $name;
                        break;
                    }
                }
            }
        }

        try {
            $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$currencyCode}/IDR");

            if ($response->successful()) {
                $data = $response->json();
                if (($data['result'] ?? '') === 'success' && isset($data['conversion_rate'])) {
                    return response()->json([
                        'status' => 'success',
                        'currency' => $currencyCode,
                        'currency_name' => $currencyName,
                        'rate' => $data['conversion_rate']
                    ]);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => "Could not fetch rate for '{$input}'. Please enter a valid currency code or name."
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Connection failed: ' . $e->getMessage()
            ], 500);
        }
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
    public function show($id, Request $request)
    {
        $decryptedWoId = $this->decryptId($id);
        $workOrder = WorkOrder::with(['ebdHeader.customer', 'ebdHeader.projectModel'])->findOrFail($decryptedWoId);

        $defaultEbd = $workOrder->ebdHeader;
        $availableEbdRevisions = collect();

        if ($defaultEbd) {
            // Find all EBD headers for this Customer & Model (or tied to this WorkOrder)
            $availableEbdRevisions = \App\Models\MngEbdHeader::where(function($q) use ($workOrder, $defaultEbd) {
                    $q->where('wo_id', $workOrder->id);
                    if ($defaultEbd->customer_id && $defaultEbd->model_id) {
                        $q->orWhere(function($sub) use ($defaultEbd) {
                            $sub->where('customer_id', $defaultEbd->customer_id)
                                ->where('model_id', $defaultEbd->model_id);
                        });
                    }
                })
                ->orderBy('revision', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        $selectedEbdId = $request->query('ebd_id');
        $selectedEbd   = $selectedEbdId 
            ? ($availableEbdRevisions->firstWhere('id', $selectedEbdId) ?? \App\Models\MngEbdHeader::find($selectedEbdId))
            : $defaultEbd;

        $quotations = collect();
        $ebdItems = collect();

        if ($selectedEbd) {
            $selectedEbd->load(['items.toolingProcesses', 'items.addProcesses', 'customer', 'projectModel', 'workOrder']);
            
            // Get all supplier quotations for this EBD sorted by revision
            $allQuotations = ToolingQuotation::with(['details', 'importer', 'supplier'])
                ->where('ebd_header_id', $selectedEbd->id)
                ->orderBy('revision', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            // Group quotations by supplier_id and attach all_revisions to activeQuote for dropdown switcher
            $groupedBySupplier = $allQuotations->groupBy('supplier_id');
            foreach ($groupedBySupplier as $supplierId => $suppQuotes) {
                $selectedQuoteId = $request->query("supp_quote_{$supplierId}");
                $activeQuote = $selectedQuoteId ? ($suppQuotes->firstWhere('id', $selectedQuoteId) ?? $suppQuotes->first()) : $suppQuotes->first();
                $activeQuote->all_revisions = $suppQuotes;
                $quotations->push($activeQuote);
            }

            // Sort supplier columns by user selection ('worth' / 'cheapest', 'highest', 'name')
            $sortMode = $request->query('sort', 'worth');
            if ($sortMode === 'highest') {
                $quotations = $quotations->sortByDesc('total_cost_idr')->values();
            } elseif ($sortMode === 'name') {
                $quotations = $quotations->sortBy(fn($q) => strtolower($q->supplier_name))->values();
            } else {
                // Default: 'worth' (Lowest Total Cost IDR first / Best Value)
                $quotations = $quotations->sortBy('total_cost_idr')->values();
                foreach ($quotations as $rankIdx => $q) {
                    $q->worth_rank = $rankIdx + 1;
                }
            }

            $ebdItems = $selectedEbd->items;
        }

        $encryptedWoId = $id;

        return view('management.tooling-quotation.detail', compact(
            'workOrder',
            'selectedEbd',
            'availableEbdRevisions',
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
            'ebd_header_id'  => 'required|exists:mng_ebd_headers,id',
            'supplier_id'    => 'required|exists:suppliers,id',
            'quotation_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        DB::beginTransaction();
        try {
            $ebdHeaderId = $request->input('ebd_header_id');
            $supplierId  = $request->input('supplier_id');
            $importMode  = $request->input('import_mode', 'new_revision'); // 'new_revision' or 'overwrite'
            
            $supplier     = \App\Models\Suppliers::findOrFail($supplierId);
            $supplierName = $supplier->name;

            // Membaca file excel yang diupload secara langsung tanpa menyimpan file ke disk storage
            $file        = $request->file('quotation_file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();

            // Read Currency & Exchange Rate directly from Excel Header Cells X4 and X5
            $excelCurrency = trim((string) $sheet->getCell('X4')->getCalculatedValue());
            $excelRate     = $sheet->getCell('X5')->getCalculatedValue();

            if (!empty($excelCurrency)) {
                $currencyCode = \App\Helpers\CurrencyHelper::getCode($excelCurrency) ?: strtoupper(substr($excelCurrency, 0, 10));
            } else {
                $currencyCode = 'IDR';
            }

            if (is_numeric($excelRate) && (float)$excelRate > 0) {
                $exchangeRate = (float) $excelRate;
            } else {
                $exchangeRate = 1.0;
            }

            // Cek apakah data quotation untuk EBD & Supplier ini sudah pernah di-import
            $existingQuotation = ToolingQuotation::where('ebd_header_id', $ebdHeaderId)
                ->where('supplier_id', $supplierId)
                ->orderBy('id', 'desc')
                ->first();

            if ($existingQuotation && $importMode === 'overwrite') {
                // Overwrite Mode: Hapus detail quotation lama & update record yang ada
                ToolingQuotationDetail::where('tooling_quotation_id', $existingQuotation->id)->delete();

                $quotation = $existingQuotation;
                $quotation->update([
                    'currency_code'      => $currencyCode,
                    'exchange_rate'      => $exchangeRate,
                    'total_cost_foreign' => 0,
                    'total_cost_idr'     => 0,
                    'file_path'          => null,
                    'status'             => 'IMPORTED',
                    'imported_by'        => auth()->user() ? auth()->user()->id : null,
                    'imported_at'        => now(),
                ]);
            } else {
                // New Revision Mode: Buat record revisi baru (Rev 0, Rev 1, Rev 2...)
                $nextRevision = $existingQuotation ? (string)((int)$existingQuotation->revision + 1) : '0';

                $quotation = ToolingQuotation::create([
                    'ebd_header_id'      => $ebdHeaderId,
                    'supplier_id'        => $supplierId,
                    'quotation_no'       => 'QUO-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'revision'           => $nextRevision,
                    'currency_code'      => $currencyCode,
                    'exchange_rate'      => $exchangeRate,
                    'total_cost_foreign' => 0,
                    'total_cost_idr'     => 0,
                    'file_path'          => null,
                    'status'             => 'IMPORTED',
                    'imported_by'        => auth()->user() ? auth()->user()->id : null,
                    'imported_at'        => now(),
                ]);
            }

            // Load EBD Items & Tooling Processes
            $ebdItems = MngEbdItem::with('toolingProcesses')
                ->where('ebd_header_id', $ebdHeaderId)
                ->get();

            $totalCostForeign = 0;
            $totalCostIdr = 0;

            $importedRowsCount = 0;
            $currentPartNo = null;
            $currentPartName = null;

            // Loop membaca baris data Excel (Data dimulai pada baris 11 di mana Row 1-10 adalah Header & Filter)
            for ($row = 11; $row <= $highestRow; $row++) {
                // Kolom di Excel:
                // A (1)  : No
                // B-G (2-7): Status Item
                // H (8)  : Part No.
                // I (9)  : Part Name
                // J (10) : Material Spec
                // K (11) : Thickness
                // L (12) : Main Process Name
                // M (13) : Homeline / Process
                // N (14) : Tooling Status & Info (NEW DIES / MODIF / COMMON - INFORMATION)
                // O (15) : OP (10, 20, 30, 40...)
                // P (16) : Tooling Process Name (DRAW, TRIM, FLG, JIG ALIGNMENT...)
                // Q (17) : Category (TOOL RANK / Category e.g. DIE, JIG, CF)
                // R (18) : Dies Qty
                // S (19) : Jig Qty
                // T (20) : CF Qty
                // U (21) : Tonnage
                // V (22) : Die Height
                // W (23) : Supplier Category
                // X (24) : Currency Amount (Valas)
                // Y (25) : IDR Amount

                $partNoRaw       = trim((string) $sheet->getCell("H{$row}")->getCalculatedValue());
                $partNameRaw     = trim((string) $sheet->getCell("I{$row}")->getCalculatedValue());

                // Jika Part No di baris ini terisi, perbarui $currentPartNo
                if (!empty($partNoRaw)) {
                    $currentPartNo   = $partNoRaw;
                    $currentPartName = $partNameRaw;
                }

                $mainProcName    = trim((string) $sheet->getCell("L{$row}")->getCalculatedValue());
                $homeline        = trim((string) $sheet->getCell("M{$row}")->getCalculatedValue());
                $supplierStatus  = trim((string) $sheet->getCell("N{$row}")->getCalculatedValue());
                
                $opVal           = $sheet->getCell("O{$row}")->getCalculatedValue();
                $opNo            = is_numeric($opVal) ? (int)$opVal : null;
                
                $toolingProcName = trim((string) $sheet->getCell("P{$row}")->getCalculatedValue());
                $toolCategory    = trim((string) $sheet->getCell("Q{$row}")->getCalculatedValue());
                $qtyDies         = trim((string) $sheet->getCell("R{$row}")->getCalculatedValue());
                $qtyJig          = trim((string) $sheet->getCell("S{$row}")->getCalculatedValue());
                $qtyCf           = trim((string) $sheet->getCell("T{$row}")->getCalculatedValue());
                
                $tonnage         = trim((string) $sheet->getCell("U{$row}")->getCalculatedValue());
                $dieHeightVal    = $sheet->getCell("V{$row}")->getCalculatedValue();
                $dieCategory     = trim((string) $sheet->getCell("W{$row}")->getCalculatedValue());
                $costForeignVal  = $sheet->getCell("X{$row}")->getCalculatedValue();
                $costIdrVal      = $sheet->getCell("Y{$row}")->getCalculatedValue();

                // 1. Baca seluruh teks di seluruh kolom (A s/d Z) pada baris ini untuk mendeteksi kata TOTAL / SUM
                $fullRowText = '';
                foreach (range('A', 'Z') as $colLetter) {
                    $cellVal = trim((string) $sheet->getCell("{$colLetter}{$row}")->getCalculatedValue());
                    if (!empty($cellVal)) {
                        $fullRowText .= ' ' . $cellVal;
                    }
                }
                $fullRowTextUpper = strtoupper($fullRowText);

                // Skip baris jika terdapat kata TOTAL / SUB TOTAL / GRAND TOTAL / SUM di manapun dalam baris ini
                if (str_contains($fullRowTextUpper, 'TOTAL') || str_contains($fullRowTextUpper, 'SUM')) {
                    continue;
                }

                // 2. Skip jika baris ini tidak memiliki OP No, Tooling Process Name, maupun Main Process Name
                if ($opNo === null && empty($toolingProcName) && empty($mainProcName)) {
                    continue;
                }

                // 3. Skip jika harga kosong / null
                if ($costForeignVal === null && $costIdrVal === null) {
                    continue;
                }

                // Cari EBD Item matching (by $currentPartNo atau $partNoRaw)
                $targetPartNo = $currentPartNo ?: $partNoRaw;
                $matchedEbdItem = $ebdItems->first(fn($item) => strtolower(trim($item->part_no)) === strtolower(trim($targetPartNo)));
                $ebdItemId = $matchedEbdItem->id ?? null;

                // Jika baris ini tidak cocok dengan Part No EBD manapun, skip
                if (!$ebdItemId) {
                    continue;
                }

                // Cari EBD Process matching (by OP atau Tooling Process Name dari Kolom P)
                $ebdProcessId = null;
                if ($matchedEbdItem) {
                    if ($opNo !== null) {
                        $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => (int)$tp->op === (int)$opNo);
                        $ebdProcessId = $matchedProc->id ?? null;
                    }
                    if (!$ebdProcessId && !empty($toolingProcName)) {
                        $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => strtolower(trim($tp->process_name)) === strtolower(trim($toolingProcName)));
                        $ebdProcessId = $matchedProc->id ?? null;
                    }
                }

                // Deteksi Tooling Type (DIE, JIG, CF)
                $toolType = 'DIE';
                $combinedType = strtoupper("{$toolCategory} {$toolingProcName}");
                if (!empty($qtyJig) || str_contains($combinedType, 'JIG')) {
                    $toolType = 'JIG';
                } elseif (!empty($qtyCf) || str_contains($combinedType, 'CF')) {
                    $toolType = 'CF';
                }

                $costForeign = is_numeric($costForeignVal) ? (float)$costForeignVal : 0;
                $costIdr = is_numeric($costIdrVal) ? (float)$costIdrVal : ($costForeign * $exchangeRate);

                $totalCostForeign += $costForeign;
                $totalCostIdr += $costIdr;

                ToolingQuotationDetail::create([
                    'tooling_quotation_id'   => $quotation->id,
                    'ebd_item_id'            => $ebdItemId,
                    'ebd_tooling_process_id' => $ebdProcessId,
                    'homeline'               => $homeline,
                    'supplier_status'        => $supplierStatus,
                    'op'                     => $opNo,
                    'tooling_process_name'   => $toolingProcName ?: ($mainProcName ?: 'STAMPING'),
                    'tooling_type'           => $toolType,
                    'tonnage'                => $tonnage,
                    'die_height'             => is_numeric($dieHeightVal) ? (float)$dieHeightVal : null,
                    'die_category'           => $dieCategory ?: $toolCategory,
                    'cost_foreign'           => $costForeign,
                    'cost_idr'               => $costIdr,
                    'remarks'                => null,
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
