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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderAddProcessController extends Controller
{
    protected $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display listing for SPK 2 Additional Process.
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
            
            $query = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')
                ->with(['inquiry.customer', 'inquiry.projectModel', 'ebdHeader.customer', 'ebdHeader.projectModel', 'ownerDepartment', 'processes', 'products', 'approvals']);
            
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
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhereHas('products', function($pq) use ($search) {
                          $pq->where('customer_part_no', 'like', "%{$search}%")
                            ->orWhere('customer_part_name', 'like', "%{$search}%")
                            ->orWhere('add_process_name', 'like', "%{$search}%");
                      });
                });
            }
            
            $totalRecords = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->count();
            $filteredRecords = $query->count();
            
            $data = $query->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($length)
                ->get();
                
            $transformedData = [];
            foreach ($data as $idx => $wo) {
                $totalApprovals = $wo->approvals ? $wo->approvals->count() : 0;
                $approvedApprovals = $wo->approvals ? $wo->approvals->where('status', 'Approved')->count() : 0;
                $approvalPercent = $totalApprovals > 0 ? round(($approvedApprovals / $totalApprovals) * 100) : 0;

                $deptProgressRaw = $wo->getDepartmentProgress();
                $deptProgress = [];
                foreach ($deptProgressRaw as $dp) {
                    $deptProgress[] = [
                        'code' => $dp['code'],
                        'completed' => $dp['completed'],
                        'total' => $dp['total'],
                        'percent' => $dp['percent']
                    ];
                }

                $hiddenProducts = '';
                if ($wo->products) {
                    foreach ($wo->products as $p) {
                        $hiddenProducts .= ($p->customer_part_no ?? '') . ' ' . ($p->customer_part_name ?? '') . ' ';
                    }
                }

                $customerCode = $wo->inquiry->customer->code ?? $wo->inquiry->customer->name ?? $wo->ebdHeader->customer->code ?? $wo->ebdHeader->customer->name ?? '—';
                $modelName = $wo->inquiry->projectModel->name ?? $wo->ebdHeader->projectModel->name ?? '—';
                $inquiryNo = $wo->inquiry->inquiry_no ?? '—';
                $inquiryShowUrl = $wo->inquiry_id ? route('management.inquiry.show', $this->encryptId($wo->inquiry_id)) : '#';

                $transformedData[] = [
                    'index_num' => $start + $idx + 1,
                    'id' => $wo->id,
                    'encrypted_id' => $this->encryptId($wo->id),
                    'hashed_id' => $this->encryptId($wo->id),
                    'wo_number' => $wo->wo_number,
                    'revision_no' => 'Rev. ' . sprintf('%02d', $wo->revision_no ?? 0),
                    'inquiry_no' => $inquiryNo,
                    'inquiry_id' => $wo->inquiry_id,
                    'inquiry_show_url' => $inquiryShowUrl,
                    'customer_code' => $customerCode,
                    'model_name' => $modelName,
                    'customer' => $customerCode,
                    'model' => $modelName,
                    'hidden_products' => $hiddenProducts,
                    'priority' => $wo->priority,
                    'display_status' => $wo->status,
                    'status' => $wo->status,
                    'approved_approvals' => $approvedApprovals,
                    'total_approvals' => $totalApprovals,
                    'approval_percent' => $approvalPercent,
                    'subject' => $wo->subject ?? 'SPK 2 Additional Process',
                    'first_sample_date' => $wo->first_sample_date ? $wo->first_sample_date->format('d M Y') : '—',
                    'due_date_plan' => $wo->due_date_plan ? $wo->due_date_plan->format('d M Y') : '—',
                    'products' => $wo->products,
                    'dept_progress' => $deptProgress,
                    'can_edit' => $wo->status === 'Draft',
                    'can_approve' => $wo->isApprover(auth()->user()),
                    'show_url' => route('management.work-order-add-process.show', $this->encryptId($wo->id)),
                    'edit_url' => route('management.work-order-add-process.edit', $this->encryptId($wo->id)),
                    'destroy_url' => route('management.work-order-add-process.destroy', $this->encryptId($wo->id)),
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $transformedData
            ]);
        }
        
        $totalWo = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->count();
        $urgentWo = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->where('priority', 'Urgent')->count();
        $standardWo = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->where('priority', 'Standard')->count();
        $lowWo = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->where('priority', 'Low')->count();
        $finishedWo = WorkOrder::where('wo_type', 'SPK_2_ADD_PROCESS')->whereIn('status', ['Approved', 'Released'])->count();
        $completionRate = $totalWo > 0 ? round(($finishedWo / $totalWo) * 100) : 0;
        
        $ebdHeaders = MngEbdHeader::with(['customer', 'projectModel', 'items.addProcesses', 'workOrder'])->orderBy('id', 'desc')->get();

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
                'hashed_id' => $ebd->hashed_id,
                'wo_number' => $ebd->workOrder->wo_number ?? '—',
                'customer_code' => $ebd->customer->code ?? '—',
                'model_name' => $ebd->projectModel->name ?? '—',
                'revision' => $ebd->revision,
                'items' => $ebd->items->map(function($item) use ($levelMap) {
                    return [
                        'id' => $item->id,
                        'parent_id' => $item->parent_id,
                        'active_level' => $item->active_level,
                        'bom_level' => $levelMap[$item->id] ?? ($item->active_level ? (string)$item->active_level : '1'),
                        'part_no' => $item->part_no,
                        'part_name' => $item->part_name,
                        'status' => $item->status ?? '—',
                        'add_processes' => $item->addProcesses->map(function($proc) {
                            return [
                                'id' => $proc->id,
                                'process_name' => $proc->process_name,
                                'qty' => $proc->qty,
                                'unit' => $proc->unit,
                                'cost_idr' => $proc->cost_idr,
                            ];
                        })
                    ];
                })
            ];
        });
            
        return view('management.work-order.wo2-add-process.index', compact(
            'totalWo', 'urgentWo', 'standardWo', 'lowWo', 'completionRate', 'ebdHeaders', 'ebdHeadersData'
        ));
    }

    /**
     * Show creation form for SPK 2 Additional Process.
     */
    public function create(Request $request)
    {
        $ebdHeaderId = $request->input('ebd_id') ?? $request->input('ebd_header_id');
        $addProcessIds = $request->input('add_process_ids', []);
        
        $ebdHeader = null;
        $inquiry = null;
        $selectedAddProcesses = collect();
        $itemsData = [];
        
        if ($ebdHeaderId) {
            $decryptedEbdId = $this->decryptId($ebdHeaderId) ?: $ebdHeaderId;
            $ebdHeader = MngEbdHeader::with([
                'customer',
                'projectModel',
                'items' => function($q) {
                    $q->with(['addProcesses']);
                }
            ])->find($decryptedEbdId);

            if ($ebdHeader) {
                if ($ebdHeader->inquiry_id) {
                    $inquiry = ProjectInquiry::with(['customer', 'projectModel', 'products'])->find($ebdHeader->inquiry_id);
                }

                if (!empty($addProcessIds)) {
                    $selectedAddProcesses = \App\Models\MngEbdAddProcess::with('ebdItem')
                        ->whereIn('id', $addProcessIds)
                        ->get();
                } else {
                    $itemIds = $ebdHeader->items->pluck('id');
                    $selectedAddProcesses = \App\Models\MngEbdAddProcess::with('ebdItem')
                        ->whereIn('ebd_item_id', $itemIds)
                        ->get();
                }

                if ($selectedAddProcesses->count() > 0) {
                    $itemsData = $selectedAddProcesses->map(function($proc) use ($ebdHeader) {
                        $item = $proc->ebdItem;
                        return [
                            'ebd_item_id' => $item->id ?? null,
                            'ebd_add_process_id' => $proc->id,
                            'add_process_name' => $proc->process_name ?? '',
                            'add_process_qty' => $proc->qty ?? 0,
                            'add_process_unit' => $proc->unit ?? 'Pcs',
                            'customer_part_no' => $item->part_no ?? '',
                            'customer_part_name' => $item->part_name ?? '',
                            'customer_code' => $ebdHeader->customer->code ?? '',
                            'model_name' => $ebdHeader->projectModel->name ?? '',
                            'remarks' => '',
                        ];
                    })->values()->toArray();
                } else {
                    $itemsData = $ebdHeader->items->map(function($item) use ($ebdHeader) {
                        return [
                            'ebd_item_id' => $item->id,
                            'ebd_add_process_id' => null,
                            'add_process_name' => '',
                            'add_process_qty' => 0,
                            'add_process_unit' => 'Pcs',
                            'customer_part_no' => $item->part_no ?? '',
                            'customer_part_name' => $item->part_name ?? '',
                            'customer_code' => $ebdHeader->customer->code ?? '',
                            'model_name' => $ebdHeader->projectModel->name ?? '',
                            'remarks' => '',
                        ];
                    })->values()->toArray();
                }
            }
        }

        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();

        $currentYear = now()->year;
        $count = WorkOrder::withTrashed()->whereYear('created_at', $currentYear)->where('revision_no', 0)->count() + 1;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romans[now()->month] ?? 'I';
        do {
            $defaultSpkNo = sprintf("%03d/MKT-SPK/SAI/%s/%02d", $count, $romanMonth, now()->year % 100);
            $exists = WorkOrder::withTrashed()->where('wo_number', $defaultSpkNo)->where('revision_no', 0)->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();

        return view('management.work-order.wo2-add-process.form', compact(
            'ebdHeader', 'inquiry', 'departments', 'processes', 'defaultSpkNo', 'approvalRules', 'woHeader', 'users', 'selectedAddProcesses', 'itemsData'
        ));
    }

    /**
     * Store new SPK 2 Additional Process.
     */
    public function store(WorkOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $data['wo_type'] = 'SPK_2_ADD_PROCESS';

            $rawEbdId = $request->input('ebd_header_id');
            $ebdHeaderId = $rawEbdId ? ($this->decryptId($rawEbdId) ?: $rawEbdId) : null;
            if ($ebdHeaderId) {
                $data['ebd_header_id'] = $ebdHeaderId;
                $ebdHeader = MngEbdHeader::find($ebdHeaderId);
                if ($ebdHeader && $ebdHeader->inquiry_id) {
                    $data['inquiry_id'] = $ebdHeader->inquiry_id;
                }
            }

            $rawInquiryId = $request->input('inquiry_id');
            if ($rawInquiryId && empty($data['inquiry_id'])) {
                $data['inquiry_id'] = $this->decryptId($rawInquiryId) ?: $rawInquiryId;
            }

            $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();
            $data['header_id'] = $request->input('header_id', $woHeader->id ?? 1);

            if (!empty($request->input('selected_approval_rules'))) {
                $data['selected_approval_rule_ids'] = $request->input('selected_approval_rules');
            }

            $processes = $request->input('processes', []);
            if (is_array($processes)) {
                $processes = array_values(array_unique(array_filter($processes)));
            }
            $assignedPics = $this->parseAssignedPics($request);

            $workOrder = $this->workOrderService->createWorkOrder($data, $processes, $assignedPics);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SPK 2 Additional Process created successfully.',
                    'redirect' => route('management.work-order-add-process.show', $this->encryptId($workOrder->id)),
                    'redirect_url' => route('management.work-order-add-process.show', $this->encryptId($workOrder->id))
                ]);
            }

            return redirect()->route('management.work-order-add-process.show', $this->encryptId($workOrder->id))
                ->with('success', 'SPK 2 Additional Process created successfully.');
        } catch (\Exception $e) {
            Log::error('Error storing SPK 2 Additional Process: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create SPK 2 Additional Process: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create SPK 2 Additional Process: ' . $e->getMessage());
        }
    }

    /**
     * Show detail preview of SPK 2 Additional Process.
     */
    public function show($id)
    {
        $realId = $this->decryptId($id) ?: $id;
        $workOrder = WorkOrder::with([
            'inquiry.customer',
            'inquiry.projectModel',
            'ebdHeader.customer',
            'ebdHeader.projectModel',
            'ownerDepartment',
            'processes',
            'products.ebdAddProcess',
            'products.ebdItem',
            'approvals.department'
        ])->findOrFail($realId);

        $ebdHeader = $workOrder->ebdHeader;
        if ($ebdHeader) {
            $ebdHeader->load(['items.addProcesses']);
        }
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name')->get();
        $processes = WorkOrderProcess::where('is_active', true)->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();
        $users = User::orderBy('name')->get();

        return view('management.work-order.wo2-add-process.form', compact(
            'workOrder', 'ebdHeader', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'
        ));
    }

    /**
     * Edit form for SPK 2 Additional Process.
     */
    public function edit($id)
    {
        return $this->show($id);
    }

    /**
     * Update SPK 2 Additional Process.
     */
    public function update(WorkOrderRequest $request, $id)
    {
        try {
            $realId = $this->decryptId($id) ?: $id;
            $data = $request->validated();
            $data['wo_type'] = 'SPK_2_ADD_PROCESS';

            $rawEbdId = $request->input('ebd_header_id');
            $ebdHeaderId = $rawEbdId ? ($this->decryptId($rawEbdId) ?: $rawEbdId) : null;
            if ($ebdHeaderId) {
                $data['ebd_header_id'] = $ebdHeaderId;
            }

            $rawInquiryId = $request->input('inquiry_id');
            if ($rawInquiryId) {
                $data['inquiry_id'] = $this->decryptId($rawInquiryId) ?: $rawInquiryId;
            }

            $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();
            $data['header_id'] = $request->input('header_id', $woHeader->id ?? 1);

            if (!empty($request->input('selected_approval_rules'))) {
                $data['selected_approval_rule_ids'] = $request->input('selected_approval_rules');
            }

            $processes = $request->input('processes', []);
            if (is_array($processes)) {
                $processes = array_values(array_unique(array_filter($processes)));
            }
            $assignedPics = $this->parseAssignedPics($request);

            $workOrder = $this->workOrderService->updateWorkOrder($realId, $data, $processes, $assignedPics);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SPK 2 Additional Process updated successfully.',
                    'redirect' => route('management.work-order-add-process.show', $this->encryptId($workOrder->id)),
                    'redirect_url' => route('management.work-order-add-process.show', $this->encryptId($workOrder->id))
                ]);
            }

            return redirect()->route('management.work-order-add-process.show', $this->encryptId($workOrder->id))
                ->with('success', 'SPK 2 Additional Process updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating SPK 2 Additional Process: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update SPK 2 Additional Process: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update SPK 2 Additional Process: ' . $e->getMessage());
        }
    }

    /**
     * Parse assigned PICs from request payload.
     */
    private function parseAssignedPics(Request $request): array
    {
        $raw = $request->json('process_pics') ?? $request->input('process_pics_json');
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (is_array($raw)) {
            $firstKey = array_key_first($raw);
            if ($firstKey !== null && strpos((string)$firstKey, '_') !== false) {
                $nested = [];
                foreach ($raw as $key => $picUserId) {
                    [$procId, $deptId] = explode('_', (string)$key, 2);
                    $nested[$procId][$deptId] = $picUserId;
                }
                return $nested;
            }
            return $raw;
        }
        return $request->input('process_pics') ?? [];
    }

    /**
     * Delete SPK 2 Additional Process.
     */
    public function destroy($id)
    {
        try {
            $realId = $this->decryptId($id) ?: $id;
            $this->workOrderService->deleteWorkOrder($realId);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SPK 2 Additional Process deleted successfully.'
                ]);
            }

            return redirect()->route('management.work-order-add-process.index')
                ->with('success', 'SPK 2 Additional Process deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting SPK 2 Additional Process: ' . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete SPK 2 Additional Process: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('management.work-order-add-process.index')
                ->with('error', 'Failed to delete SPK 2 Additional Process: ' . $e->getMessage());
        }
    }
}
