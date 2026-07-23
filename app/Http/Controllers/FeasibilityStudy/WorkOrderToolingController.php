<?php

namespace App\Http\Controllers\FeasibilityStudy;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeasibilityStudy\WorkOrderRequest;
use App\Services\FeasibilityStudy\WorkOrderService;
use App\Models\Department;
use App\Models\WorkOrderProcess;
use App\Models\ApprovalConfig;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use App\Models\MngEbdHeader;
use App\Models\MngEbdItem;
use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;
use App\Exports\QuotationToolingExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderToolingController extends Controller
{
    protected $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display listing for SPK 2 Tooling Cost.
     */
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            $priority = $request->input('priority');
            $status = $request->input('status');
            $search = $request->input('search.value');
            
            $query = WorkOrder::where('wo_type', 'SPK_2_TOOLING')
                ->with(['inquiry.customer', 'inquiry.projectModel', 'ebdHeader', 'ownerDepartment', 'processes', 'products', 'approvals']);
            
            if ($priority) {
                $query->where('priority', $priority);
            }
            
            if ($status) {
                if ($status === 'Finish') {
                    $query->whereIn('status', ['Approved', 'Released']);
                } elseif ($status === 'In Progress') {
                    $query->where('status', 'Pending Approval');
                } else {
                    $query->where('status', $status);
                }
            }
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('wo_number', 'like', "%{$search}%")
                      ->orWhereHas('inquiry', fn($iq) => $iq->where('inquiry_no', 'like', "%{$search}%")
                                                            ->orWhere('project_name', 'like', "%{$search}%"))
                      ->orWhereHas('products', fn($p) => $p->where('customer_part_no', 'like', "%{$search}%")
                                                           ->orWhere('customer_part_name', 'like', "%{$search}%"));
                });
            }
            
            $totalRecords = WorkOrder::where('wo_type', 'SPK_2_TOOLING')->count();
            $filteredRecords = $query->count();
            
            $query->orderBy('id', 'desc')->skip($start)->take($length);
            $workOrders = $query->get();
            
            $data = [];
            foreach ($workOrders as $wo) {
                $totalApprovals = $wo->approvals->count();
                $approvedApprovals = $wo->approvals->where('status', 'Approved')->count();
                $approvalPercent = $totalApprovals > 0 ? round(($approvedApprovals / $totalApprovals) * 100) : 0;
                
                $deptProgress = [];
                foreach ($wo->getDepartmentProgress() as $dp) {
                    $deptProgress[] = [
                        'code' => $dp['code'],
                        'completed' => $dp['completed'],
                        'total' => $dp['total'],
                        'percent' => $dp['percent']
                    ];
                }
                
                $hiddenProducts = '';
                foreach ($wo->products as $p) {
                    $hiddenProducts .= $p->customer_part_no . ' ' . $p->customer_part_name . ' ';
                }
                
                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'wo_number' => $wo->wo_number,
                    'revision_no' => 'Rev. ' . $wo->revision_no,
                    'inquiry_no' => $wo->inquiry->inquiry_no ?? '—',
                    'inquiry_id' => $wo->inquiry_id,
                    'inquiry_show_url' => route('management.inquiry.show', $this->encryptId($wo->inquiry_id)),
                    'customer_code' => $wo->inquiry->customer->code ?? '—',
                    'model_name' => $wo->inquiry->projectModel->name ?? '—',
                    'hidden_products' => $hiddenProducts,
                    'priority' => $wo->priority,
                    'dept_progress' => $deptProgress,
                    'display_status' => $wo->status,
                    'status' => $wo->status,
                    'approved_approvals' => $approvedApprovals,
                    'total_approvals' => $totalApprovals,
                    'approval_percent' => $approvalPercent,
                    'hashed_id' => $this->encryptId($wo->id),
                    'show_url' => route('management.work-order-tooling.show', $this->encryptId($wo->id))
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }
        
        $allWorkOrders = WorkOrder::where('wo_type', 'SPK_2_TOOLING')->get();
        $totalWo = $allWorkOrders->count();
        $urgentWo = $allWorkOrders->filter(fn($w) => $w->priority === 'URGENT')->count();
        $standardWo = $allWorkOrders->filter(fn($w) => $w->priority === 'STANDARD')->count();
        $lowWo = $allWorkOrders->filter(fn($w) => $w->priority === 'LOW')->count();
        $finishedCount = $allWorkOrders->filter(fn($w) => in_array($w->status, ['Approved', 'Released']))->count();
        $completionRate = $totalWo > 0 ? round(($finishedCount / $totalWo) * 100) : 0;
        
        $ebdHeaders = MngEbdHeader::with(['customer', 'projectModel', 'items', 'workOrder'])->orderBy('id', 'desc')->get();

        $ebdHeadersData = $ebdHeaders->map(function($ebd) {
            // Build BOM Level Strings (1, 1.1, 1.2, etc.)
            $byParent = [];
            foreach ($ebd->items as $item) {
                $parentId = $item->parent_id ?: 0;
                $byParent[$parentId][] = $item;
            }

            $levelMap = [];
            $assignLevels = function($parentId, $prefix) use (&$assignLevels, &$byParent, &$levelMap) {
                if (!isset($byParent[$parentId])) return;
                $counter = 1;
                foreach ($byParent[$parentId] as $item) {
                    $currentLevelStr = $prefix ? ($prefix . '.' . $counter) : (string)$counter;
                    $levelMap[$item->id] = $currentLevelStr;
                    $assignLevels($item->id, $currentLevelStr);
                    $counter++;
                }
            };
            $assignLevels(0, '');

            return [
                'id' => $ebd->id,
                'hashed_id' => $this->encryptId($ebd->id),
                'wo_number' => $ebd->workOrder->wo_number ?? '—',
                'customer_code' => $ebd->customer->code ?? '—',
                'model_name' => $ebd->projectModel->name ?? '—',
                'revision' => $ebd->revision,
                'items' => $ebd->items->map(function($item) use ($levelMap) {
                    return [
                        'id' => $item->id,
                        'bom_level' => $levelMap[$item->id] ?? ($item->active_level ? (string)$item->active_level : '1'),
                        'part_no' => $item->part_no,
                        'part_name' => $item->part_name,
                        'status' => $item->status ?? '—',
                    ];
                })
            ];
        });

        return view('management.work-order.wo2-tooling.index', compact(
            'totalWo', 'urgentWo', 'standardWo', 'lowWo', 'completionRate', 'ebdHeaders', 'ebdHeadersData'
        ));
    }

    /**
     * Show form to create SPK 2 Tooling Cost from EBD.
     */
    public function create(Request $request)
    {
        $ebdHeaderId = $request->input('ebd_id');
        if (!$ebdHeaderId) {
            return redirect()->route('management.ebd.index')
                ->with('error', 'Please select an EBD Header to create SPK 2 Tooling Cost.');
        }

        $decryptedEbdId = $this->decryptId($ebdHeaderId);
        $ebdHeader = MngEbdHeader::with(['workOrder.inquiry.customer', 'workOrder.inquiry.projectModel', 'customer', 'projectModel', 'items'])->findOrFail($decryptedEbdId);

        $inquiry = $ebdHeader->inquiry ?? $ebdHeader->workOrder->inquiry ?? null;

        if (!$inquiry && $ebdHeader->customer_id && $ebdHeader->model_id) {
            $inquiry = ProjectInquiry::where('customer_id', $ebdHeader->customer_id)
                ->where('model_id', $ebdHeader->model_id)
                ->latest()
                ->first();
        }

        if (!$inquiry) {
            $inquiry = new ProjectInquiry([
                'id' => 0,
                'customer_id' => $ebdHeader->customer_id,
                'model_id' => $ebdHeader->model_id,
                'project_name' => $ebdHeader->projectModel->name ?? '—',
                'inquiry_no' => 'EBD-' . ($ebdHeader->workOrder->wo_number ?? $ebdHeader->id),
            ]);
            $inquiry->setRelation('customer', $ebdHeader->customer);
            $inquiry->setRelation('projectModel', $ebdHeader->projectModel);
        }

        $selectedItemsParam = $request->input('items');
        $selectedIds = [];
        if ($selectedItemsParam) {
            $selectedIds = is_array($selectedItemsParam) ? $selectedItemsParam : array_filter(explode(',', $selectedItemsParam));
        }

        $targetEbdItems = $ebdHeader->items;
        if (!empty($selectedIds)) {
            $targetEbdItems = $ebdHeader->items->whereIn('id', $selectedIds);
        }

        // Map EBD BOM Items with Inquiry Product Metadata Auto-matching
        $itemsData = [];
        foreach ($targetEbdItems as $ebdItem) {
            // STEP 1: Match Inquiry Product in the same Inquiry
            $matchedProduct = InquiryProduct::where('inquiry_id', $inquiry->id)
                ->where('customer_part_no', $ebdItem->part_no)
                ->first();

            // STEP 2: Fallback to same Customer & Model if not found in current Inquiry
            if (!$matchedProduct && $inquiry->customer_id && $inquiry->model_id) {
                $matchedProduct = InquiryProduct::whereHas('inquiry', function($q) use ($inquiry) {
                    $q->where('customer_id', $inquiry->customer_id)
                      ->where('model_id', $inquiry->model_id);
                })
                ->where('customer_part_no', $ebdItem->part_no)
                ->first();
            }

            // STEP 3: Fallback to assembly level Inquiry product if child part has no direct match
            if (!$matchedProduct && $inquiry->id > 0) {
                $matchedProduct = InquiryProduct::where('inquiry_id', $inquiry->id)->first();
            }

            $itemsData[] = [
                'ebd_item_id'        => $ebdItem->id,
                'customer_part_no'   => $ebdItem->part_no,
                'customer_part_name' => $ebdItem->part_name,
                'upg'                => $ebdItem->active_level ? ('M50A' . $ebdItem->active_level) : '—',
                'sop_date'           => $matchedProduct->sop_date ?? null,
                'eol_date'           => $matchedProduct->eol_date ?? null,
                'annual_volume'      => $matchedProduct->annual_volume ?? 0,
                'model_life'         => $matchedProduct->model_life ?? 0,
                'has_2d_data'        => $matchedProduct ? (bool)$matchedProduct->has_2d_data : false,
                'has_3d_data'        => $matchedProduct ? (bool)$matchedProduct->has_3d_data : false,
                'has_tech_doc'       => $matchedProduct ? (bool)$matchedProduct->has_tech_doc : false,
                'remarks'            => ''
            ];
        }

        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();

        $currentYear = now()->year;
        $count = WorkOrder::whereYear('created_at', $currentYear)->where('revision_no', 0)->count() + 1;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romans[now()->month] ?? 'I';
        $defaultSpkNo = sprintf("%03d/MKT-SPK/SAI/%s/%02d", $count, $romanMonth, now()->year % 100);

        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();

        return view('management.work-order.wo2-tooling.form', compact(
            'ebdHeader', 'inquiry', 'itemsData', 'departments', 'processes', 'defaultSpkNo', 'approvalRules', 'woHeader', 'users'
        ));
    }

    /**
     * Store SPK 2 Tooling Cost.
     */
    public function store(WorkOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $rawInquiryId = $request->input('inquiry_id');
            $decryptedInquiryId = ($rawInquiryId && $rawInquiryId !== '0' && $rawInquiryId !== 0) ? $this->decryptId($rawInquiryId) : null;
            if ($decryptedInquiryId === 0) {
                $decryptedInquiryId = null;
            }

            $data = [
                'wo_type'           => 'SPK_2_TOOLING',
                'inquiry_id'        => $decryptedInquiryId,
                'ebd_header_id'     => $request->input('ebd_header_id') ? $this->decryptId($request->input('ebd_header_id')) : null,
                'wo_number'         => $validated['wo_number'],
                'released_at'       => $validated['released_at'],
                'first_sample_date' => $validated['first_sample_date'],
                'due_date_plan'     => $validated['due_date_plan'],
                'priority'          => $validated['priority'],
                'department_id'     => $validated['department_id'],
                'header_id'         => $request->input('header_id', 1),
                'subject'           => null,
                'selected_approval_rule_ids' => $validated['selected_approval_rules'] ?? null,
                'remarks'           => $validated['remarks'] ?? null,
            ];

            $processes = $validated['processes'];
            $assignedPics = $this->parseAssignedPics($request);

            $workOrder = $this->workOrderService->createWorkOrder($data, $processes, $assignedPics);

            return response()->json([
                'success' => true,
                'redirect_url' => route('management.work-order-tooling.show', $this->encryptId($workOrder->id)),
                'message' => 'SPK 2 Tooling Cost successfully created!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create SPK 2 Tooling Cost', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save SPK 2 Tooling Cost: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Show SPK 2 Tooling Cost details.
     */
    public function show($id)
    {
        $decryptedId = $this->decryptId($id);
        $workOrder = $this->workOrderService->getWorkOrderDetails($decryptedId);
        $inquiry = $workOrder->inquiry;
        $ebdHeader = $workOrder->ebdHeader;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::where('is_active', true)->orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();
        $woHeader = $workOrder->docFormat;

        return view('management.work-order.wo2-tooling.form', compact('workOrder', 'ebdHeader', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'));
    }

    /**
     * Update SPK 2 Tooling Cost.
     */
    public function update(WorkOrderRequest $request, $id)
    {
        $validated = $request->validated();
        $decryptedId = $this->decryptId($id);

        try {
            $data = [
                'released_at'       => $validated['released_at'],
                'first_sample_date' => $validated['first_sample_date'],
                'due_date_plan'     => $validated['due_date_plan'],
                'priority'          => $validated['priority'],
                'selected_approval_rule_ids' => $validated['selected_approval_rules'] ?? null,
                'remarks'           => $validated['remarks'] ?? null,
            ];

            $processes = $validated['processes'];
            $assignedPics = $this->parseAssignedPics($request);

            $workOrder = $this->workOrderService->updateWorkOrder($decryptedId, $data, $processes, $assignedPics);

            return response()->json([
                'success' => true,
                'redirect_url' => route('management.work-order-tooling.show', $this->encryptId($workOrder->id)),
                'message' => 'SPK 2 Tooling Cost successfully updated!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update SPK 2 Tooling Cost', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SPK 2 Tooling Cost: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified SPK 2 Tooling Cost.
     */
    public function destroy($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->workOrderService->deleteWorkOrder($decryptedId);
            return redirect()->route('management.work-order-tooling.index')
                ->with('success', 'SPK 2 Tooling Cost successfully deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export Quotation Tooling Excel attachment for SPK 2 Tooling Cost.
     */
    public function exportQuotation(Request $request, $id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $workOrder = WorkOrder::findOrFail($decryptedId);
            $sanitizedNo = str_replace(['/', '\\', ' '], '_', $workOrder->wo_number);
            $filename = 'Quotation_Tooling_' . $sanitizedNo . '.xlsx';

            return Excel::download(new QuotationToolingExport($workOrder), $filename);
        } catch (\Exception $e) {
            Log::error('Failed to export Quotation Tooling', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate Quotation Tooling: ' . $e->getMessage());
        }
    }

    private function parseAssignedPics(Request $request): array
    {
        $result = [];
        $processDepts = $request->input('process_departments', []);
        $processPics = $request->input('process_pics', []);
        $rawPics = $request->input('assigned_pics', []);

        if (is_array($processDepts)) {
            foreach ($processDepts as $procId => $deptIds) {
                if (is_array($deptIds)) {
                    foreach ($deptIds as $deptId) {
                        $picKey = $procId . '_' . $deptId;
                        $picUserId = $processPics[$picKey] ?? null;
                        if (is_array($picUserId)) {
                            $picUserId = reset($picUserId);
                        }
                        $result[$procId][$deptId] = $picUserId ? (int)$picUserId : null;
                    }
                }
            }
        }

        if (is_array($rawPics)) {
            foreach ($rawPics as $procId => $deptUsers) {
                if (is_array($deptUsers)) {
                    foreach ($deptUsers as $deptId => $userIds) {
                        $uid = is_array($userIds) ? reset($userIds) : $userIds;
                        $result[$procId][$deptId] = $uid ? (int)$uid : null;
                    }
                }
            }
        }

        return $result;
    }
}
