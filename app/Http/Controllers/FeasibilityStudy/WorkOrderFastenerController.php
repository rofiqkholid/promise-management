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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderFastenerController extends Controller
{
    protected $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display listing for SPK 2 Fastener / Standard Part.
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
            
            $query = WorkOrder::where('wo_type', 'SPK_2_FASTENER')
                ->with(['ebdHeader.customer', 'ebdHeader.projectModel', 'ownerDepartment', 'processes', 'products', 'approvals']);
            
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
                      ->orWhereHas('products', fn($p) => $p->where('std_part_no', 'like', "%{$search}%")
                                                           ->orWhere('std_part_name', 'like', "%{$search}%")
                                                           ->orWhere('customer_part_no', 'like', "%{$search}%")
                                                           ->orWhere('customer_part_name', 'like', "%{$search}%"));
                });
            }
            
            $totalRecords = WorkOrder::where('wo_type', 'SPK_2_FASTENER')->count();
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
                    $stdNo = $p->std_part_no ?: $p->customer_part_no;
                    $stdName = $p->std_part_name ?: $p->customer_part_name;
                    $hiddenProducts .= $stdNo . ' ' . $stdName . ' ';
                }
                
                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'wo_number' => $wo->wo_number,
                    'revision_no' => 'Rev. ' . $wo->revision_no,
                    'inquiry_no' => $wo->inquiry->inquiry_no ?? '—',
                    'inquiry_id' => $wo->inquiry_id ?? 0,
                    'customer_code' => $wo->ebdHeader->customer->code ?? '—',
                    'model_name' => $wo->ebdHeader->projectModel->name ?? '—',
                    'hidden_products' => $hiddenProducts,
                    'priority' => $wo->priority,
                    'dept_progress' => $deptProgress,
                    'display_status' => $wo->status,
                    'status' => $wo->status,
                    'approved_approvals' => $approvedApprovals,
                    'total_approvals' => $totalApprovals,
                    'approval_percent' => $approvalPercent,
                    'hashed_id' => $this->encryptId($wo->id),
                    'show_url' => route('management.work-order-fastener.show', $this->encryptId($wo->id))
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }
        
        $allWorkOrders = WorkOrder::where('wo_type', 'SPK_2_FASTENER')->get();
        $totalWo = $allWorkOrders->count();
        $urgentWo = $allWorkOrders->filter(fn($w) => $w->priority === 'URGENT')->count();
        $standardWo = $allWorkOrders->filter(fn($w) => $w->priority === 'STANDARD')->count();
        $lowWo = $allWorkOrders->filter(fn($w) => $w->priority === 'LOW')->count();
        $finishedCount = $allWorkOrders->filter(fn($w) => in_array($w->status, ['Approved', 'Released']))->count();
        $completionRate = $totalWo > 0 ? round(($finishedCount / $totalWo) * 100) : 0;
        
        $ebdHeaders = MngEbdHeader::with(['customer', 'projectModel', 'items', 'workOrder'])->orderBy('id', 'desc')->get();

        $ebdHeadersData = $ebdHeaders->map(function($ebd) {
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
                'items' => $ebd->items->filter(function($item) {
                    return !empty($item->std_part_no) && trim($item->std_part_no) !== '—';
                })->map(function($item) use ($levelMap) {
                    return [
                        'id' => $item->id,
                        'bom_level' => $levelMap[$item->id] ?? ($item->active_level ? (string)$item->active_level : '1'),
                        'part_no' => $item->part_no,
                        'part_name' => $item->part_name,
                        'std_part_no' => $item->std_part_no,
                        'std_part_name' => $item->std_part_name ?? '—',
                        'std_qty' => $item->std_qty ?? ($item->qty_unit ?? ''),
                        'std_uom' => $item->std_uom ?? '',
                        'status' => $item->status ?? '—',
                    ];
                })->values()
            ];
        });

        return view('management.work-order.wo2-fastener.index', compact(
            'totalWo', 'urgentWo', 'standardWo', 'lowWo', 'completionRate', 'ebdHeaders', 'ebdHeadersData'
        ));
    }

    /**
     * Show form to create SPK 2 Fastener / Standard Part from EBD.
     */
    public function create(Request $request)
    {
        $ebdHeaderId = $request->input('ebd_id');
        if (!$ebdHeaderId) {
            return redirect()->route('management.ebd.index')
                ->with('error', 'Please select an EBD Header to create SPK 2 Fastener.');
        }

        $decryptedEbdId = $this->decryptId($ebdHeaderId);
        $ebdHeader = MngEbdHeader::with(['customer', 'projectModel', 'items'])->findOrFail($decryptedEbdId);

        $selectedItemsParam = $request->input('items');
        $selectedIds = [];
        if ($selectedItemsParam) {
            $selectedIds = is_array($selectedItemsParam) ? $selectedItemsParam : array_filter(explode(',', $selectedItemsParam));
        }

        $targetEbdItems = $ebdHeader->items;
        if (!empty($selectedIds)) {
            $targetEbdItems = $ebdHeader->items->whereIn('id', $selectedIds);
        }

        // Filter ONLY items with non-empty std_part_no
        $targetEbdItems = $targetEbdItems->filter(function($item) {
            return !empty($item->std_part_no) && trim($item->std_part_no) !== '—';
        });

        // Map EBD Standard Part Items
        $itemsData = [];
        foreach ($targetEbdItems as $ebdItem) {
            $itemsData[] = [
                'ebd_item_id'        => $ebdItem->id,
                'customer_part_no'   => $ebdItem->std_part_no ?: '—',
                'customer_part_name' => $ebdItem->std_part_name ?: '—',
                'std_part_no'        => $ebdItem->std_part_no ?: '—',
                'std_part_name'      => $ebdItem->std_part_name ?: '—',
                'std_qty'            => $ebdItem->std_qty ?? ($ebdItem->qty_unit ?? ''),
                'std_uom'            => $ebdItem->std_uom ?? '',
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

        return view('management.work-order.wo2-fastener.form', compact(
            'ebdHeader', 'itemsData', 'departments', 'processes', 'defaultSpkNo', 'approvalRules', 'woHeader', 'users'
        ));
    }

    /**
     * Store SPK 2 Fastener / Standard Part.
     */
    public function store(WorkOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $processes = $validated['processes'] ?? [];
            $assignedPics = $this->parseAssignedPics($request);
            $parsedProducts = $this->parseProducts($request);

            $dataToSave = array_merge($validated, [
                'wo_type'               => 'SPK_2_FASTENER',
                'inquiry_id'            => null,
                'ebd_header_id'         => $request->input('ebd_header_id') ? $this->decryptId($request->input('ebd_header_id')) : null,
                'header_id'             => $request->input('header_id', 1),
                'subject'               => $request->input('subject') ?: 'SPK 2 Fastener / Standard Part',
                'assigned_departments' => $assignedPics,
                'products'             => $parsedProducts,
            ]);

            $workOrder = $this->workOrderService->createWorkOrder($dataToSave, $processes, $assignedPics);

            return response()->json([
                'success'      => true,
                'message'      => 'SPK 2 Fastener / Standard Part successfully saved.',
                'redirect_url' => route('management.work-order-fastener.index')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create SPK 2 Fastener', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SPK 2 Fastener: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display details / preview for SPK 2 Fastener.
     */
    public function show($id)
    {
        return $this->edit($id);
    }

    /**
     * Show form to edit SPK 2 Fastener.
     */
    public function edit($id)
    {
        $decryptedId = $this->decryptId($id);
        $workOrder = WorkOrder::with([
            'ebdHeader.customer', 'ebdHeader.projectModel', 'products', 'processes', 'approvals'
        ])->findOrFail($decryptedId);

        $ebdHeader = $workOrder->ebdHeader;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();
        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();

        $itemsData = $workOrder->products->map(function($p) {
            return [
                'id'                 => $p->id,
                'ebd_item_id'        => $p->ebd_item_id,
                'customer_part_no'   => $p->std_part_no ?: '',
                'customer_part_name' => $p->std_part_name ?: '',
                'std_part_no'        => $p->std_part_no ?: '',
                'std_part_name'      => $p->std_part_name ?: '',
                'std_qty'            => $p->std_qty ?? '',
                'std_uom'            => $p->std_uom ?? '',
                'remarks'            => $p->remarks ?: ''
            ];
        });

        return view('management.work-order.wo2-fastener.form', compact(
            'workOrder', 'ebdHeader', 'itemsData', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'
        ));
    }

    /**
     * Update SPK 2 Fastener.
     */
    public function update(WorkOrderRequest $request, $id)
    {
        $decryptedId = $this->decryptId($id);
        $validated = $request->validated();

        try {
            $parsedProcesses = $this->parseAssignedPics($request);
            $parsedProducts = $this->parseProducts($request);

            $dataToSave = array_merge($validated, [
                'wo_type'               => 'SPK_2_FASTENER',
                'inquiry_id'            => null,
                'ebd_header_id'         => $request->input('ebd_header_id') ? $this->decryptId($request->input('ebd_header_id')) : null,
                'subject'               => $request->input('subject') ?: 'SPK 2 Fastener / Standard Part',
                'assigned_departments' => $parsedProcesses,
                'products'             => $parsedProducts,
            ]);

            $workOrder = $this->workOrderService->updateWorkOrder($decryptedId, $dataToSave);

            return response()->json([
                'success'      => true,
                'message'      => 'SPK 2 Fastener / Standard Part successfully updated.',
                'redirect_url' => route('management.work-order-fastener.index')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update SPK 2 Fastener', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update SPK 2 Fastener: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete SPK 2 Fastener.
     */
    public function destroy($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->workOrderService->deleteWorkOrder($decryptedId);

            return response()->json([
                'success' => true,
                'message' => 'SPK 2 Fastener successfully deleted.',
                'redirect_url' => route('management.work-order-fastener.index')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete SPK 2 Fastener', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete SPK 2 Fastener: ' . $e->getMessage()
            ], 422);
        }
    }

    protected function parseAssignedPics(Request $request): array
    {
        $rawPics = $request->input('assigned_pics', []);
        $parsed = [];

        foreach ($rawPics as $procId => $deptUserMap) {
            if (!is_array($deptUserMap)) continue;

            $deptAssignments = [];
            foreach ($deptUserMap as $deptId => $picUserId) {
                $deptAssignments[] = [
                    'department_id' => (int)$deptId,
                    'pic_user_id'   => !empty($picUserId) ? (int)$picUserId : null,
                ];
            }

            if (!empty($deptAssignments)) {
                $parsed[] = [
                    'process_id'           => (int)$procId,
                    'assigned_departments' => $deptAssignments
                ];
            }
        }

        return $parsed;
    }

    protected function parseProducts(Request $request): array
    {
        $rawProducts = $request->input('products', []);
        $parsed = [];

        foreach ($rawProducts as $p) {
            $parsed[] = [
                'id'                 => $p['id'] ?? null,
                'ebd_item_id'        => !empty($p['ebd_item_id']) ? (int)$p['ebd_item_id'] : null,
                'customer_part_no'   => $p['std_part_no'] ?? ($p['customer_part_no'] ?? ''),
                'customer_part_name' => $p['std_part_name'] ?? ($p['customer_part_name'] ?? ''),
                'std_part_no'        => $p['std_part_no'] ?? ($p['customer_part_no'] ?? ''),
                'std_part_name'      => $p['std_part_name'] ?? ($p['customer_part_name'] ?? ''),
                'std_qty'            => isset($p['std_qty']) && $p['std_qty'] !== '' ? (int)$p['std_qty'] : null,
                'std_uom'            => $p['std_uom'] ?? '',
                'remarks'            => $p['remarks'] ?? '',
            ];
        }

        return $parsed;
    }
}
