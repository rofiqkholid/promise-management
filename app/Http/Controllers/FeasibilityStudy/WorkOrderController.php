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
use App\Models\ProjectInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    protected $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'department_id']);
        $workOrders = $this->workOrderService->paginateWorkOrders(1000, $filters);
        return view('management.work-order.index', compact('workOrders'));
    }

    public function create(Request $request)
    {
        $inquiryId = $request->input('inquiry_id');
        $productsParam = $request->input('products');

        if (!$inquiryId) {
            return redirect()->route('management.inquiry.index')
                ->with('error', 'Please select an Inquiry to create a Work Order.');
        }

        $decryptedInquiryId = $this->decryptId($inquiryId);

        $inquiry = ProjectInquiry::with(['products' => function($q) use ($productsParam) {
            if ($productsParam) {
                $rawIds = is_array($productsParam) ? $productsParam : array_filter(explode(',', $productsParam));
                $decryptedIds = array_map(function($id) {
                    if (is_array($id)) {
                        $id = $id['id'] ?? ($id['inquiry_product_id'] ?? null);
                    }
                    return $id ? $this->decryptId($id) : null;
                }, $rawIds);
                $q->whereIn('id', array_filter($decryptedIds));
            }
        }, 'products.assessment.ranking'])->findOrFail($decryptedInquiryId);

        if ($inquiry->products->isEmpty()) {
            return redirect()->route('management.inquiry.show', $inquiryId)
                ->with('error', 'No products selected for the Work Order.');
        }

        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('WO')->get();
        $users = User::orderBy('name', 'asc')->get();

        // Reset counter per year based on created_at year
        $currentYear = now()->year;
        $count = WorkOrder::whereYear('created_at', $currentYear)->count() + 1;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romans[now()->month] ?? 'I';
        $defaultSpkNo = sprintf("%03d/MKT-SPK/SAI/%s/%02d", $count, $romanMonth, now()->year % 100);

        // Fetch master QEMS header
        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();

        return view('management.work-order.form', compact('inquiry', 'departments', 'processes', 'defaultSpkNo', 'approvalRules', 'woHeader', 'users'));
    }

    public function store(WorkOrderRequest $request)
    {
        $validated = $request->validated();

        try {
            $data = [
                'inquiry_id'        => $this->decryptId($request->input('inquiry_id')),
                'wo_number'         => $validated['wo_number'],
                'publish_date'      => $validated['publish_date'],
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
                'redirect_url' => route('management.work-order.show', $this->encryptId($workOrder->id)),
                'message' => 'Work Order (SPK) successfully created!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create work order', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Work Order: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show($id)
    {
        $decryptedId = $this->decryptId($id);
        $workOrder = $this->workOrderService->getWorkOrderDetails($decryptedId);
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::where('is_active', true)->orderBy('id', 'asc')->get();
        $approvalRules = ApprovalConfig::activeFor('WO')->get();
        $users = User::orderBy('name', 'asc')->get();
        $woHeader = $workOrder->docFormat;

        return view('management.work-order.form', compact('workOrder', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'));
    }

    public function edit($id)
    {
        return redirect()->route('management.work-order.show', $id);
    }

    public function update(WorkOrderRequest $request, $id)
    {
        $validated = $request->validated();
        $decryptedId = $this->decryptId($id);

        try {
            $data = [
                'publish_date'      => $validated['publish_date'],
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
                'redirect_url' => route('management.work-order.show', $this->encryptId($workOrder->id)),
                'message' => 'Work Order (SPK) successfully updated!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update work order', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Work Order: ' . $e->getMessage()
            ], 422);
        }
    }

    public function submit($id)
    {
        $decryptedId = $this->decryptId($id);

        try {
            $workOrder = $this->workOrderService->submitWorkOrder($decryptedId);
            return redirect()->route('management.work-order.show', $this->encryptId($workOrder->id))
                ->with('success', 'SPK successfully submitted for approval!');
        } catch (\Exception $e) {
            Log::error('Failed to submit work order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to submit: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, $id)
    {
        $decryptedId = $this->decryptId($id);
        $user = auth()->user();
        $remarks = $request->input('remarks');

        try {
            $workOrder = $this->workOrderService->approveWorkOrder($decryptedId, $remarks, $user);
            return redirect()->route('management.work-order.show', $this->encryptId($workOrder->id))
                ->with('success', 'Work Order approved.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $decryptedId = $this->decryptId($id);
        $user = auth()->user();
        $remarks = $request->input('remarks');

        try {
            $workOrder = $this->workOrderService->rejectWorkOrder($decryptedId, $remarks, $user);
            return redirect()->route('management.work-order.show', $this->encryptId($workOrder->id))
                ->with('success', 'Work Order rejected and returned to Draft.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function revise($id)
    {
        $decryptedId = $this->decryptId($id);

        try {
            $newRevision = $this->workOrderService->reviseWorkOrder($decryptedId);
            return redirect()->route('management.work-order.show', $this->encryptId($newRevision->id))
                ->with('success', 'New draft revision ' . sprintf('Rev. %02d', $newRevision->revision_no) . ' has been created.');
        } catch (\Exception $e) {
            Log::error('Failed to revise Work Order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to create new revision: ' . $e->getMessage());
        }
    }

    public function approvalInbox()
    {
        $user = auth()->user();

        // 1. Recent (Pending Approval assigned to me)
        $pendingSPKs = $this->workOrderService->paginateWorkOrders(1000, ['status' => 'Pending Approval']);
        $recent = $pendingSPKs->filter(function($wo) use ($user) {
            return $wo->isApprover($user);
        })->values();

        // 2. Approved (approved by me)
        $approved = $this->workOrderService->getWorkOrdersApprovedBy($user->name);

        // 3. Rejected (rejected by me)
        $rejected = $this->workOrderService->getWorkOrdersRejectedBy($user->name);

        // 4. My Tasks (assigned as PIC in any process)
        $myTasks = $this->workOrderService->getWorkOrdersTasksForUser($user->id);

        // 5. All (Recent + Approved + Rejected + My Tasks)
        $all = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->where(function($q) use ($user, $recent) {
                $q->whereIn('id', $recent->pluck('id'))
                  ->orWhereHas('approvals', function($sub) use ($user) {
                      $sub->where('approver_name', $user->name);
                  });
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        $recent->load(['inquiry.customer', 'inquiry.projectModel', 'products', 'ownerDepartment', 'processes']);
        $approved->load(['inquiry.customer', 'inquiry.projectModel', 'products', 'ownerDepartment', 'processes']);
        $rejected->load(['inquiry.customer', 'inquiry.projectModel', 'products', 'ownerDepartment', 'processes']);
        $myTasks->load(['inquiry.customer', 'inquiry.projectModel', 'products', 'ownerDepartment', 'processes']);
        $all->load(['inquiry.customer', 'inquiry.projectModel', 'products', 'ownerDepartment', 'processes']);

        $approvalRules = ApprovalConfig::activeFor('WO')->get();

        return view('management.work-order.inbox', compact('recent', 'approved', 'rejected', 'myTasks', 'all', 'approvalRules'));
    }

    public function reviewPage($id)
    {
        return redirect()->route('management.work-order.approval-inbox', ['select' => $id]);
    }

    public function updateProgress(Request $request, $id)
    {
        $request->validate([
            'process_id' => 'required|exists:mng_wo_processes,id',
            'department_id' => 'required|exists:departments,id',
            'checked_product_ids' => 'nullable|array',
        ]);

        try {
            $decryptedId = $this->decryptId($id);
            $processId = $request->input('process_id');
            $deptId = $request->input('department_id');
            $checkedProductIds = $request->input('checked_product_ids', []);

            $this->workOrderService->updateProcessProgress($decryptedId, $processId, $deptId, $checkedProductIds);

            return redirect()->back()->with('success', 'Progress successfully updated!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->workOrderService->deleteWorkOrder($decryptedId);
            return redirect()->route('management.work-order.index')->with('success', 'Work Order successfully deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function apiGetDetails($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $workOrder = $this->workOrderService->getWorkOrderDetails($decryptedId);
            
            // Compute target departments
            $deptCodes = collect();
            $deptNames = collect();
            if ($workOrder->ownerDepartment) {
                $deptCodes->push($workOrder->ownerDepartment->code);
                $deptNames->push($workOrder->ownerDepartment->name);
            }
            foreach ($workOrder->supportDepartments as $sd) {
                if ($sd->code) $deptCodes->push($sd->code);
                if ($sd->name) $deptNames->push($sd->name);
            }
            $targetDepts = $deptCodes->unique()->filter()->implode(' / ') ?: '—';
            $targetDeptsFull = $deptNames->unique()->filter()->implode(' / ') ?: '—';

            // Format Approvals
            $approvals = $workOrder->approvals->sortBy('approval_level')->map(function($a) {
                return [
                    'approval_level' => $a->approval_level,
                    'approver_position' => $a->approver_position,
                    'status' => $a->status,
                    'approver_name' => $a->approver_name,
                    'remarks' => $a->remarks,
                    'department_code' => $a->department->code ?? ($a->department->name ?? '—'),
                    'due_date_closed' => $a->due_date_closed ? $a->due_date_closed->format('Y-m-d') : null,
                    'approved_at' => $a->approved_at ? \Carbon\Carbon::parse($a->approved_at)->format('d M Y H:i') : null
                ];
            })->values();

            // Format Processes Tasks for PICs
            $processes = $workOrder->processes->map(function($proc) use ($workOrder) {
                $deptsData = json_decode($proc->pivot->assigned_departments ?? '[]', true) ?: [];
                $mappedDepts = [];
                
                foreach ($deptsData as $d) {
                    $deptId = is_array($d) ? ($d['department_id'] ?? null) : $d;
                    $picId = is_array($d) ? ($d['pic_user_id'] ?? null) : null;
                    $checkedProductIds = is_array($d) ? ($d['checked_product_ids'] ?? []) : [];
                    
                    $deptObj = \App\Models\Department::find($deptId);
                    $picObj = \App\Models\User::find($picId);
                    
                    $mappedDepts[] = [
                        'department_id' => $deptId,
                        'department_code' => $deptObj->code ?? ($deptObj->name ?? 'Dept'),
                        'pic_id' => $picId,
                        'pic_name' => $picObj->name ?? 'None',
                        'checked_product_ids' => array_map('intval', $checkedProductIds),
                        'is_my_pic_task' => (auth()->check() && (int)auth()->user()->id === (int)$picId)
                    ];
                }
                
                return [
                    'process_id' => $proc->id,
                    'process_name' => $proc->process_name,
                    'assigned_departments' => $mappedDepts
                ];
            })->values();

            // Format Products
            $products = $workOrder->products->map(function($prod) use ($workOrder) {
                return [
                    'id' => $prod->id,
                    'customer_part_no' => $prod->inquiryProduct->customer_part_no ?? '—',
                    'customer_part_name' => $prod->inquiryProduct->customer_part_name ?? '—',
                    'eo' => $prod->eo ?: '-',
                    'class_id' => $prod->class_id,
                    'uom' => $prod->uom,
                    'remarks' => $prod->remarks ?: '—',
                    'customer_code' => $workOrder->inquiry->customer->code ?? '',
                    'model_name' => $workOrder->inquiry->projectModel->name ?? '—',
                    'model_life' => $prod->model_life ?? '',
                    'variant' => $prod->variant ?? '',
                    'annual_volume' => $prod->annual_volume ?? '',
                    'sop_date' => $prod->sop_date ? $prod->sop_date->format('Y-m-d') : '',
                    'has_2d_data' => $prod->has_2d_data ?? false,
                    'has_3d_data' => $prod->has_3d_data ?? false,
                    'has_tech_doc' => $prod->has_tech_doc ?? false
                ];
            })->values();

            $dueDatesClosedData = [];
            foreach ($workOrder->approvals as $app) {
                $rule = \App\Models\ApprovalConfig::activeFor('WO')
                    ->where('approval_level', $app->approval_level)
                    ->where('department_id', $app->department_id)
                    ->first();
                if ($rule && $app->due_date_closed) {
                    $dateVal = is_string($app->due_date_closed) ? substr($app->due_date_closed, 0, 10) : $app->due_date_closed->format('Y-m-d');
                    $dueDatesClosedData[$rule->id] = $dateVal;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $workOrder->id,
                    'hashed_id' => $workOrder->hashed_id,
                    'wo_number' => $workOrder->wo_number,
                    'subject' => $workOrder->subject,
                    'status' => $workOrder->status,
                    'priority' => $workOrder->priority,
                    'approvals' => $approvals,
                    'processes' => $processes,
                    'products' => $products,
                    'document_no' => $workOrder->docFormat->document_no ?? 'FO-13-02',
                    'doc_department' => $workOrder->docFormat->doc_department ?? 'Sales',
                    'doc_publish_date' => $workOrder->docFormat->doc_publish_date ? \Carbon\Carbon::parse($workOrder->docFormat->doc_publish_date)->format('d-M-Y') : '01-Jan-2024',
                    'publish_date' => $workOrder->docFormat->doc_publish_date ? \Carbon\Carbon::parse($workOrder->docFormat->doc_publish_date)->format('Y-m-d') : '2024-01-01',
                    'released_at' => $workOrder->released_at ? $workOrder->released_at->format('Y-m-d') : ($workOrder->status === 'Approved' ? ($workOrder->updated_at ? $workOrder->updated_at->format('Y-m-d') : null) : null),
                    'doc_revision_no' => $workOrder->docFormat->revision_no ?? 0,
                    'page_hal' => $workOrder->docFormat->page_hal ?? '1',
                    'revision_no' => $workOrder->revision_no,
                    'department_id' => $workOrder->department_id,
                    'department_name' => $workOrder->ownerDepartment->name ?? '—',
                    'target_departments' => $targetDepts,
                    'target_departments_full' => $targetDeptsFull,
                    'first_sample_date' => $workOrder->first_sample_date ? $workOrder->first_sample_date->format('Y-m-d') : '',
                    'due_date_plan' => $workOrder->due_date_plan ? $workOrder->due_date_plan->format('Y-m-d') : '',
                    'due_dates_closed' => (object)$dueDatesClosedData,
                    'remarks' => $workOrder->remarks ?? '',
                    'selected_approval_rule_ids' => $workOrder->selected_approval_rule_ids ?: [],
                    'created_by' => $workOrder->created_by,
                    'created_at' => $workOrder->created_at->format('d-M-Y H:i'),
                    'can_approve' => (function() use ($workOrder) {
                        if ($workOrder->status !== 'Pending Approval') return false;
                        $pending = $workOrder->approvals()->where('status', 'Pending')->get();
                        foreach ($pending as $approval) {
                            $rule = \App\Models\ApprovalConfig::activeFor('WO')
                                ->where('approval_level', $approval->approval_level)
                                ->where('department_id', $approval->department_id)
                                ->first();
                            if ($rule && $rule->canBeApprovedBy(auth()->user())) {
                                return true;
                            }
                        }
                        return false;
                    })(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch details: ' . $e->getMessage()
            ], 422);
        }
    }

    public function apiGetGlobalProgress()
    {
        try {
            $workOrders = WorkOrder::with(['approvals', 'processes', 'products', 'ownerDepartment'])
                ->where('is_latest', true)
                ->whereNotIn('status', ['Draft'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $workOrders->map(function($wo) {
                // Calculate approval progress
                $totalApprovals = $wo->approvals->count();
                $approvedApprovals = $wo->approvals->where('status', 'Approved')->count();
                
                // Calculate process checklist progress
                $totalTaskItems = 0;
                $completedTaskItems = 0;
                $productsCount = $wo->products->count();

                foreach ($wo->processes as $proc) {
                    $deptsData = json_decode($proc->pivot->assigned_departments ?? '[]', true) ?: [];
                    foreach ($deptsData as $d) {
                        $checkedProductIds = is_array($d) ? ($d['checked_product_ids'] ?? []) : [];
                        $totalTaskItems += $productsCount;
                        $completedTaskItems += count($checkedProductIds);
                    }
                }

                $processPercent = $totalTaskItems > 0 ? round(($completedTaskItems / $totalTaskItems) * 100) : 0;
                
                // Determine if fully complete (both approvals and all processes checklist are 100%)
                $isCompleted = ($wo->status === 'Released' || $wo->status === 'Approved') && ($totalTaskItems > 0 && $completedTaskItems === $totalTaskItems);

                return [
                    'id' => $wo->id,
                    'hashed_id' => $wo->hashed_id,
                    'wo_number' => $wo->wo_number,
                    'subject' => $wo->subject,
                    'status' => $wo->status,
                    'priority' => $wo->priority,
                    'owner_dept' => $wo->ownerDepartment->code ?? ($wo->ownerDepartment->name ?? '—'),
                    'total_approvals' => $totalApprovals,
                    'approved_approvals' => $approvedApprovals,
                    'total_tasks' => $totalTaskItems,
                    'completed_tasks' => $completedTaskItems,
                    'process_percent' => $processPercent,
                    'is_completed' => $isCompleted,
                    'created_at' => $wo->created_at->format('d M Y')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch global progress: ' . $e->getMessage()
            ], 422);
        }
    }

    public function storeProcess(Request $request)
    {
        $validated = $request->validate([
            'process_code' => 'required|string|unique:mng_wo_processes,process_code',
            'process_name' => 'required|string',
            'default_assigned_departments' => 'required|array',
            'default_pics' => 'nullable|array',
        ]);

        $depts = $request->input('default_assigned_departments', []);
        $pics = $request->input('default_pics', []);

        $assignedData = [];
        foreach ($depts as $deptId) {
            $assignedData[] = [
                'department_id' => (int)$deptId,
                'default_pic_user_id' => isset($pics[$deptId]) && $pics[$deptId] !== '' ? (int)$pics[$deptId] : null
            ];
        }

        \App\Models\WorkOrderProcess::create([
            'process_code' => $validated['process_code'],
            'process_name' => $validated['process_name'],
            'default_assigned_departments' => json_encode($assignedData),
            'is_active' => $request->has('is_active') ? true : true,
        ]);

        return redirect()->back()->with('success', 'Master Process successfully created.');
    }

    public function updateProcess(Request $request, $id)
    {
        $process = \App\Models\WorkOrderProcess::findOrFail($id);

        $validated = $request->validate([
            'process_code' => 'required|string|unique:mng_wo_processes,process_code,' . $id,
            'process_name' => 'required|string',
            'default_assigned_departments' => 'required|array',
            'default_pics' => 'nullable|array',
        ]);

        $depts = $request->input('default_assigned_departments', []);
        $pics = $request->input('default_pics', []);

        $assignedData = [];
        foreach ($depts as $deptId) {
            $assignedData[] = [
                'department_id' => (int)$deptId,
                'default_pic_user_id' => isset($pics[$deptId]) && $pics[$deptId] !== '' ? (int)$pics[$deptId] : null
            ];
        }

        $process->update([
            'process_code' => $validated['process_code'],
            'process_name' => $validated['process_name'],
            'default_assigned_departments' => json_encode($assignedData),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Master Process successfully updated.');
    }

    public function destroyProcess($id)
    {
        try {
            $process = \App\Models\WorkOrderProcess::findOrFail($id);
            $process->delete();
            return redirect()->back()->with('success', 'Master Process successfully deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete process: ' . $e->getMessage());
        }
    }

    private function parseAssignedPics(Request $request)
    {
        $assignedPics = $request->input('process_pics') ?? [];
        if ($request->has('process_pics_json')) {
            $flatPics = json_decode($request->input('process_pics_json'), true) ?: [];
            $assignedPics = [];
            foreach ($flatPics as $key => $picUserId) {
                if (strpos($key, '_') !== false) {
                    [$procId, $deptId] = explode('_', $key);
                    $assignedPics[$procId][$deptId] = $picUserId;
                }
            }
        }
        return $assignedPics;
    }
}
