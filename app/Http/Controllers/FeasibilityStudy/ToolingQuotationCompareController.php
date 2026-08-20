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
     * Halaman Utama Index: List EBD Header (Engineering Breakdown Data) siap untuk Quotation Compare
     */
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $search = $request->input('search.value');

            // Query seluruh EBD Header
            $query = MngEbdHeader::with(['customer', 'projectModel', 'workOrder', 'quotations'])
                ->orderBy('id', 'desc');

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('revision', 'LIKE', "%{$search}%")
                      ->orWhereHas('customer', fn($c) => $c->where('code', 'LIKE', "%{$search}%")->orWhere('name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('projectModel', fn($m) => $m->where('name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('workOrder', fn($w) => $w->where('wo_number', 'LIKE', "%{$search}%"));
                });
            }

            $totalRecords = $query->count();
            $ebdHeaders = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($ebdHeaders as $ebd) {
                $customerCode = $ebd->customer->code ?? ($ebd->customer->name ?? '—');
                $modelName = $ebd->projectModel->name ?? '—';
                
                $suppCount = $ebd->quotations->where('source_type', 'supplier')->count();
                $custCount = $ebd->quotations->where('source_type', 'customer')->count();

                $quotesSummary = [];
                if ($custCount > 0) {
                    $quotesSummary[] = "<span class='px-1.5 py-0.5 text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 rounded-sm border border-amber-200 dark:border-amber-800'>Target Customer</span>";
                }
                if ($suppCount > 0) {
                    $quotesSummary[] = "<span class='font-semibold text-slate-700 dark:text-slate-300'>{$suppCount} Supplier</span>";
                }
                if (empty($quotesSummary)) {
                    $quotesSummary[] = "<span class='text-slate-400 italic text-[11px]'>0 Quote</span>";
                }

                $woNumber = $ebd->workOrder ? $ebd->workOrder->wo_number : null;

                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'ebd_ref' => "<strong>EBD Rev. {$ebd->revision}</strong>" . ($ebd->date ? "<div class='text-[10px] text-slate-400 font-mono'>" . $ebd->date->format('d/m/Y') . "</div>" : ""),
                    'customer_model' => "<strong>{$customerCode}</strong> • {$modelName}",
                    'wo_ref' => $woNumber ? "<span class='font-mono font-medium text-indigo-600 dark:text-indigo-400'>{$woNumber}</span>" : "<span class='px-1.5 py-0.5 text-[9px] text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 rounded-sm font-mono'>No WO</span>",
                    'status' => $ebd->status ?? 'Draft',
                    'quotation_count' => implode(' • ', $quotesSummary),
                    'download_template_url' => $ebd->wo_id ? route('management.work-order-tooling.quotation', $this->encryptId($ebd->wo_id)) : '#',
                    'compare_url' => route('management.tooling-quotation.show', $this->encryptId($ebd->id)),
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data
            ]);
        }

        $templates = \App\Models\MngCfgTemplate::where('is_active', true)
            ->where(function($q) {
                $q->where('direction', 'export')->orWhereNull('direction');
            })
            ->get();

        return view('management.tooling-quotation.index', compact('templates'));
    }

    /**
     * Halaman Detail Comparison per EBD / SPK Tooling
     */
    public function show($id, Request $request)
    {
        $decryptedId = $this->decryptId($id);
        
        // Coba load sebagai MngEbdHeader terlebih dahulu, jika bukan fallback ke WorkOrder
        $selectedEbd = MngEbdHeader::with(['customer', 'projectModel', 'workOrder'])->find($decryptedId);
        $workOrder = null;

        if ($selectedEbd) {
            $workOrder = $selectedEbd->workOrder;
        } else {
            $workOrder = WorkOrder::with(['ebdHeader.customer', 'ebdHeader.projectModel'])->find($decryptedId);
            if ($workOrder) {
                $selectedEbd = $workOrder->ebdHeader;
            }
        }

        if (!$selectedEbd && !$workOrder) {
            abort(404, 'Data EBD atau Work Order tidak ditemukan.');
        }

        $defaultEbd = $selectedEbd;
        $availableEbdRevisions = collect();

        if ($selectedEbd) {
            // Find all EBD headers for this Customer & Model (or tied to this WorkOrder)
            $availableEbdRevisions = MngEbdHeader::where(function($q) use ($selectedEbd) {
                    if ($selectedEbd->customer_id && $selectedEbd->model_id) {
                        $q->where('customer_id', $selectedEbd->customer_id)
                          ->where('model_id', $selectedEbd->model_id);
                    } else {
                        $q->where('id', $selectedEbd->id);
                    }
                    if ($selectedEbd->wo_id) {
                        $q->orWhere('wo_id', $selectedEbd->wo_id);
                    }
                })
                ->orderBy('revision', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        }

        $selectedEbdId = $request->query('ebd_id');
        if ($selectedEbdId) {
            $selectedEbd = $availableEbdRevisions->firstWhere('id', $selectedEbdId) ?? MngEbdHeader::find($selectedEbdId);
        }

        $quotations = collect();
        $ebdItems = collect();
        $activeCustomerQuote = null;
        $supplierQuotations = collect();

        if ($selectedEbd) {
            $selectedEbd->load(['items.toolingProcesses', 'items.addProcesses', 'customer', 'projectModel', 'workOrder']);
            
            // Get all quotations for this EBD sorted by revision
            $allQuotations = ToolingQuotation::with(['details', 'importer', 'supplier', 'customer'])
                ->where('ebd_header_id', $selectedEbd->id)
                ->orderBy('revision', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            // 1. Separate Customer Quotations vs Supplier Quotations
            $customerQuotes = $allQuotations->filter(function($q) {
                return $q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id));
            });

            $supplierQuotes = $allQuotations->filter(function($q) {
                return $q->source_type === 'supplier' || !empty($q->supplier_id);
            });

            // 2. Active Customer Quote (with revision switcher if multiple revisions exist)
            if ($customerQuotes->isNotEmpty()) {
                $selectedCustQuoteId = $request->query("cust_quote");
                $activeCustomerQuote = $selectedCustQuoteId ? ($customerQuotes->firstWhere('id', $selectedCustQuoteId) ?? $customerQuotes->first()) : $customerQuotes->first();
                $activeCustomerQuote->all_revisions = $customerQuotes;
            }

            // 3. Group supplier quotations by supplier_id and attach all_revisions to activeQuote for dropdown switcher
            $groupedBySupplier = $supplierQuotes->groupBy('supplier_id');
            foreach ($groupedBySupplier as $supplierId => $suppQuotes) {
                $selectedQuoteId = $request->query("supp_quote_{$supplierId}");
                $activeQuote = $selectedQuoteId ? ($suppQuotes->firstWhere('id', $selectedQuoteId) ?? $suppQuotes->first()) : $suppQuotes->first();
                $activeQuote->all_revisions = $suppQuotes;
                $supplierQuotations->push($activeQuote);
            }

            // 4. Sort supplier columns by user selection ('worth' / 'cheapest' / best price first, 'highest', 'name')
            $sortMode = $request->query('sort', 'worth');
            if ($sortMode === 'highest') {
                $supplierQuotations = $supplierQuotations->sortByDesc('total_cost_idr')->values();
            } elseif ($sortMode === 'name') {
                $supplierQuotations = $supplierQuotations->sortBy(fn($q) => strtolower($q->supplier_name))->values();
            } else {
                // Default: 'worth' (Lowest Total Cost IDR first / Best Price)
                $supplierQuotations = $supplierQuotations->sortBy('total_cost_idr')->values();
            }

            foreach ($supplierQuotations as $rankIdx => $q) {
                $q->worth_rank = $rankIdx + 1;
            }

            // 5. Sequence columns strictly: EBD > Customer (if imported) > Suppliers (Ranked by Best Price)
            $quotations = collect();
            if ($activeCustomerQuote) {
                $quotations->push($activeCustomerQuote);
            }
            foreach ($supplierQuotations as $sq) {
                $quotations->push($sq);
            }

            $ebdItems = $selectedEbd->items;
        }

        $encryptedWoId = $selectedEbd ? $this->encryptId($selectedEbd->id) : $id;

        // Query active Import templates for tooling quotation & auto-select based on Customer
        $customerId = $selectedEbd->customer_id ?? $workOrder->ebdHeader->customer_id ?? $workOrder->inquiry->customer_id ?? null;

        $importTemplates = \App\Models\MngCfgTemplate::where('is_active', true)
            ->where('direction', 'import')
            ->whereIn('template_type', ['tooling_quotation', 'quotation'])
            ->with('customer')
            ->get()
            ->sortByDesc(function($tpl) use ($customerId) {
                $isMatchedCustomer = ($customerId && $tpl->customer_id == $customerId) ? 1 : 0;
                $revNum = (int)preg_replace('/[^0-9]/', '', $tpl->revision ?? '0');
                return sprintf('%d_%05d_%010d', $isMatchedCustomer, $revNum, $tpl->id);
            })
            ->values();

        $defaultImportTemplateId = null;
        if ($customerId) {
            $custTemplate = $importTemplates->firstWhere('customer_id', $customerId);
            if ($custTemplate) {
                $defaultImportTemplateId = $custTemplate->id;
            }
        }
        if (!$defaultImportTemplateId && $importTemplates->isNotEmpty()) {
            $defaultImportTemplateId = $importTemplates->first()->id;
        }

        // Available suppliers for import dropdown
        $suppliers = \App\Models\Suppliers::orderBy('name', 'asc')->get();

        return view('management.tooling-quotation.detail', compact(
            'workOrder',
            'selectedEbd',
            'availableEbdRevisions',
            'quotations',
            'activeCustomerQuote',
            'supplierQuotations',
            'ebdItems',
            'encryptedWoId',
            'importTemplates',
            'defaultImportTemplateId',
            'suppliers'
        ));
    }

    /**
     * Import File Excel Quotation Supplier or Customer
     */
    public function import(Request $request)
    {
        $request->validate([
            'ebd_header_id'  => 'required|exists:mng_ebd_headers,id',
            'source_type'    => 'nullable|in:supplier,customer',
            'supplier_id'    => 'nullable|required_if:source_type,supplier|exists:suppliers,id',
            'customer_id'    => 'nullable|required_if:source_type,customer|exists:customers,id',
            'quotation_file' => 'required|file|mimes:xlsx,xls,csv',
            'template_id'    => 'nullable|exists:mng_cfg_templates,id',
        ]);

        DB::beginTransaction();
        try {
            $ebdHeaderId = $request->input('ebd_header_id');
            $sourceType  = $request->input('source_type', 'supplier');
            $supplierId  = $request->input('supplier_id');
            $customerId  = $request->input('customer_id');
            $templateId  = $request->input('template_id');
            $importMode  = $request->input('import_mode', 'new_revision'); // 'new_revision' or 'overwrite'

            // If customer source, fallback customer_id to EBD customer if not explicitly passed
            if ($sourceType === 'customer') {
                if (empty($customerId)) {
                    $ebdHead = MngEbdHeader::findOrFail($ebdHeaderId);
                    $customerId = $ebdHead->customer_id;
                }
                $customer = \App\Models\Customer::findOrFail($customerId);
                $entityName = "Customer ({$customer->code} - {$customer->name})";
                $supplierId = null;
            } else {
                $supplier = \App\Models\Suppliers::findOrFail($supplierId);
                $entityName = "Supplier ({$supplier->name})";
                $customerId = null;
            }
            
            $file = $request->file('quotation_file');

            // Load EBD Items & Tooling Processes
            $ebdItems = MngEbdItem::with('toolingProcesses')
                ->where('ebd_header_id', $ebdHeaderId)
                ->get();

            $totalCostForeign = 0;
            $totalCostIdr     = 0;
            $importedRowsCount = 0;
            $currencyCode     = 'IDR';
            $exchangeRate     = 1.0;

            // Check if user selected a dynamic mapping template from MngCfgTemplate
            $templateConfig = $templateId ? \App\Models\MngCfgTemplate::find($templateId) : null;

            if ($templateConfig && !empty($templateConfig->mapping_config)) {
                // ── A. DYNAMIC EXCEL ENGINE IMPORT ─────────────────────────
                $importEngine = new \App\Services\ExcelEngine\ExcelImportEngineService();
                $extracted = $importEngine->import($file->getPathname(), $templateConfig->mapping_config);

                // 1. Extract Single Fields (Currency & Exchange Rate)
                $singleFields = $extracted['single_fields'] ?? [];
                $rawCurrency = $singleFields['currency_code'] ?? $singleFields['currency'] ?? $extracted['currency_code'] ?? $extracted['currency'] ?? null;
                $rawRate     = $singleFields['exchange_rate'] ?? $singleFields['rate'] ?? $extracted['exchange_rate'] ?? $extracted['rate'] ?? null;

                if (!empty($rawCurrency)) {
                    $currencyCode = \App\Helpers\CurrencyHelper::getCode(trim((string)$rawCurrency)) ?: strtoupper(substr(trim((string)$rawCurrency), 0, 10));
                }

                if (is_numeric($rawRate) && (float)$rawRate > 0) {
                    $exchangeRate = (float)$rawRate;
                }

                // 2. Prepare Quotation Header Record
                $quotation = $this->resolveQuotationHeader($ebdHeaderId, $sourceType, $supplierId, $customerId, $importMode, $currencyCode, $exchangeRate);

                // 3. Process Table Loops Data
                $tableLoops = $extracted['table_loops'] ?? [];
                $recordsList = [];

                if (!empty($tableLoops)) {
                    // Gather all records from all table loop groups (e.g. 'items', 'ebd_items', 'processes', 'parts')
                    foreach ($tableLoops as $groupKey => $groupRows) {
                        if (is_array($groupRows)) {
                            $recordsList = array_merge($recordsList, $groupRows);
                        }
                    }
                } elseif (!empty($extracted['items']) && is_array($extracted['items'])) {
                    $recordsList = $extracted['items'];
                } elseif (!empty($extracted['processes']) && is_array($extracted['processes'])) {
                    $recordsList = $extracted['processes'];
                }

                foreach ($recordsList as $row) {
                    $partNo = trim((string)($row['part_no'] ?? $row['part_number'] ?? $row['ebd_part_no'] ?? ''));
                    $matchedEbdItem = null;

                    if (!empty($partNo)) {
                        $matchedEbdItem = $ebdItems->first(fn($item) => strtolower(trim($item->part_no)) === strtolower($partNo));
                    }

                    // Check if this row contains child processes array (nested block loop)
                    $childProcesses = $row['processes'] ?? $row['children'] ?? null;

                    if (is_array($childProcesses) && !empty($childProcesses)) {
                        foreach ($childProcesses as $procRow) {
                            $res = $this->createQuotationDetailFromRow($quotation->id, $matchedEbdItem, $procRow, $exchangeRate);
                            if ($res) {
                                $totalCostForeign += $res['cost_foreign'];
                                $totalCostIdr     += $res['cost_idr'];
                                $importedRowsCount++;
                            }
                        }
                    } else {
                        // Flat row structure
                        $res = $this->createQuotationDetailFromRow($quotation->id, $matchedEbdItem, $row, $exchangeRate);
                        if ($res) {
                            $totalCostForeign += $res['cost_foreign'];
                            $totalCostIdr     += $res['cost_idr'];
                            $importedRowsCount++;
                        }
                    }
                }

            } else {
                // ── B. DEFAULT / LEGACY EXCEL PARSER ───────────────────────
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

                $quotation = $this->resolveQuotationHeader($ebdHeaderId, $sourceType, $supplierId, $customerId, $importMode, $currencyCode, $exchangeRate);

                $currentPartNo = null;
                $currentPartName = null;

                for ($row = 11; $row <= $highestRow; $row++) {
                    $partNoRaw       = trim((string) $sheet->getCell("H{$row}")->getCalculatedValue());
                    $partNameRaw     = trim((string) $sheet->getCell("I{$row}")->getCalculatedValue());

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

                    $fullRowText = '';
                    foreach (range('A', 'Z') as $colLetter) {
                        $cellVal = trim((string) $sheet->getCell("{$colLetter}{$row}")->getCalculatedValue());
                        if (!empty($cellVal)) {
                            $fullRowText .= ' ' . $cellVal;
                        }
                    }
                    $fullRowTextUpper = strtoupper($fullRowText);

                    if (str_contains($fullRowTextUpper, 'TOTAL') || str_contains($fullRowTextUpper, 'SUM')) {
                        continue;
                    }

                    if ($opNo === null && empty($toolingProcName) && empty($mainProcName)) {
                        continue;
                    }

                    if ($costForeignVal === null && $costIdrVal === null) {
                        continue;
                    }

                    $targetPartNo = $currentPartNo ?: $partNoRaw;
                    $matchedEbdItem = $ebdItems->first(fn($item) => strtolower(trim($item->part_no)) === strtolower(trim($targetPartNo)));
                    $ebdItemId = $matchedEbdItem->id ?? null;

                    if (!$ebdItemId) {
                        continue;
                    }

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
            }

            if ($importedRowsCount === 0) {
                throw new \Exception("Tidak ada baris data quotation yang terbaca dari file Excel. Pastikan data file sesuai dengan template mapping yang dipilih.");
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
                    'message' => "Quotation {$entityName} berhasil di-import! {$importedRowsCount} baris terproses.",
                    'redirect_url' => $redirectUrl
                ]);
            }

            return redirect($redirectUrl)
                ->with('success', "Quotation {$entityName} berhasil di-import dan di-compare!");

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
     * Resolve or Create Tooling Quotation Header (Overwrite or New Revision)
     */
    private function resolveQuotationHeader($ebdHeaderId, $sourceType, $supplierId, $customerId, $importMode, $currencyCode, $exchangeRate)
    {
        $query = ToolingQuotation::where('ebd_header_id', $ebdHeaderId)
            ->where('source_type', $sourceType);

        if ($sourceType === 'customer') {
            $query->where('customer_id', $customerId);
        } else {
            $query->where('supplier_id', $supplierId);
        }

        $existingQuotation = $query->orderBy('id', 'desc')->first();

        if ($existingQuotation && $importMode === 'overwrite') {
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
            return $quotation;
        }

        $nextRevision = $existingQuotation ? (string)((int)$existingQuotation->revision + 1) : '0';
        $prefix = ($sourceType === 'customer') ? 'CUST-' : 'QUO-';

        return ToolingQuotation::create([
            'ebd_header_id'      => $ebdHeaderId,
            'source_type'        => $sourceType,
            'supplier_id'        => $sourceType === 'supplier' ? $supplierId : null,
            'customer_id'        => $sourceType === 'customer' ? $customerId : null,
            'quotation_no'       => $prefix . strtoupper(substr(md5(uniqid()), 0, 8)),
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

    /**
     * Create ToolingQuotationDetail record from generic extracted row
     */
    private function createQuotationDetailFromRow($quotationId, $matchedEbdItem, array $row, $exchangeRate)
    {
        $opRaw = $row['op'] ?? $row['ebd_tool_op'] ?? null;
        $opNo = is_numeric($opRaw) ? (int)$opRaw : null;

        $procName = trim((string)($row['tooling_process_name'] ?? $row['process_name'] ?? $row['ebd_tool_process_name'] ?? ''));
        $mainProcName = trim((string)($row['main_process_name'] ?? ''));

        $costForeignVal = $row['cost_foreign'] ?? $row['currency_amount'] ?? $row['price_foreign'] ?? null;
        $costIdrVal = $row['cost_idr'] ?? $row['ebd_tool_price_idr'] ?? $row['price_idr'] ?? $row['cost'] ?? null;

        if ($costForeignVal === null && $costIdrVal === null && $opNo === null && empty($procName)) {
            return null;
        }

        $ebdItemId = $matchedEbdItem->id ?? null;
        $ebdProcessId = null;

        if ($matchedEbdItem) {
            if ($opNo !== null) {
                $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => (int)$tp->op === (int)$opNo);
                $ebdProcessId = $matchedProc->id ?? null;
            }
            if (!$ebdProcessId && !empty($procName)) {
                $matchedProc = $matchedEbdItem->toolingProcesses->first(fn($tp) => strtolower(trim($tp->process_name)) === strtolower(trim($procName)));
                $ebdProcessId = $matchedProc->id ?? null;
            }
        }

        $toolCategory = trim((string)($row['tooling_type'] ?? $row['tool_category'] ?? $row['category'] ?? $row['ebd_tool_category'] ?? ''));
        $toolType = 'DIE';
        $combinedType = strtoupper("{$toolCategory} {$procName}");
        if (str_contains($combinedType, 'JIG') || !empty($row['qty_jig'])) {
            $toolType = 'JIG';
        } elseif (str_contains($combinedType, 'CF') || !empty($row['qty_cf'])) {
            $toolType = 'CF';
        }

        $costForeign = is_numeric($costForeignVal) ? (float)$costForeignVal : 0;
        $costIdr = is_numeric($costIdrVal) ? (float)$costIdrVal : ($costForeign * $exchangeRate);

        ToolingQuotationDetail::create([
            'tooling_quotation_id'   => $quotationId,
            'ebd_item_id'            => $ebdItemId,
            'ebd_tooling_process_id' => $ebdProcessId,
            'homeline'               => trim((string)($row['homeline'] ?? $row['ebd_tool_homeline'] ?? '')),
            'supplier_status'        => trim((string)($row['supplier_status'] ?? $row['ebd_tool_status'] ?? '')),
            'op'                     => $opNo,
            'tooling_process_name'   => $procName ?: ($mainProcName ?: 'STAMPING'),
            'tooling_type'           => $toolType,
            'tonnage'                => trim((string)($row['tonnage'] ?? $row['ebd_tool_tonnage'] ?? '')),
            'die_height'             => is_numeric($row['die_height'] ?? null) ? (float)$row['die_height'] : null,
            'die_category'           => trim((string)($row['die_category'] ?? $row['supplier_category'] ?? $toolCategory)),
            'cost_foreign'           => $costForeign,
            'cost_idr'               => $costIdr,
            'remarks'                => trim((string)($row['remarks'] ?? $row['information'] ?? $row['ebd_tool_information'] ?? '')),
        ]);

        return [
            'cost_foreign' => $costForeign,
            'cost_idr'     => $costIdr,
        ];
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
