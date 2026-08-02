<?php

namespace App\Services\FeasibilityStudy;

use App\Repositories\FeasibilityStudy\WorkOrderRepository;
use App\Models\WorkOrder;
use App\Models\WorkOrderApproval;
use App\Models\ApprovalConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WorkOrderSubmittedMail;

class WorkOrderService
{
    protected $workOrderRepo;

    public function __construct(WorkOrderRepository $workOrderRepo)
    {
        $this->workOrderRepo = $workOrderRepo;
    }

    public function paginateWorkOrders($perPage = 10, array $filters = [])
    {
        return $this->workOrderRepo->paginate($perPage, $filters);
    }

    public function getWorkOrderDetails($id)
    {
        return $this->workOrderRepo->findById($id);
    }

    public function getWorkOrdersApprovedBy($userName)
    {
        return $this->workOrderRepo->getApprovedBy($userName);
    }

    public function getWorkOrdersRejectedBy($userName)
    {
        return $this->workOrderRepo->getRejectedBy($userName);
    }

    public function getWorkOrdersTasksForUser($userId)
    {
        return $this->workOrderRepo->getTasksForUser($userId);
    }

    public function createWorkOrder(array $data, array $processes, array $assignedPics)
    {
        return DB::transaction(function () use ($data, $processes, $assignedPics) {
            $data['status'] = 'Draft';
            $data['is_latest'] = true;
            $data['revision_no'] = 0;
            $data['created_by'] = auth()->user() ? auth()->user()->name : 'System';

            if (empty($data['inquiry_id']) || $data['inquiry_id'] == 0 || $data['inquiry_id'] === '0') {
                $data['inquiry_id'] = null;
            }

            // Ensure wo_number is unique for revision_no = 0
            if (empty($data['wo_number']) || \App\Models\WorkOrder::withTrashed()->where('wo_number', $data['wo_number'])->where('revision_no', 0)->exists()) {
                $currentYear = now()->year;
                $count = \App\Models\WorkOrder::withTrashed()->whereYear('created_at', $currentYear)->where('revision_no', 0)->count() + 1;
                $romans = [
                    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                    7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
                ];
                $romanMonth = $romans[now()->month] ?? 'I';
                do {
                    $uniqueNo = sprintf("%03d/MKT-SPK/SAI/%s/%02d", $count, $romanMonth, now()->year % 100);
                    $exists = \App\Models\WorkOrder::withTrashed()->where('wo_number', $uniqueNo)->where('revision_no', 0)->exists();
                    if ($exists) {
                        $count++;
                    }
                } while ($exists);
                $data['wo_number'] = $uniqueNo;
            }

            $workOrder = $this->workOrderRepo->create($data);
            
            $this->workOrderRepo->attachProcessesAndPics($workOrder->id, $processes, $assignedPics);

            // Sync work order products - read from JSON body
            $reqProducts = request()->input('products', []);
            if (!empty($reqProducts) && is_array($reqProducts)) {
                $insertedIds = []; // tempId => database ID
                
                foreach ($reqProducts as $idx => $rp) {
                    $inqProductId = isset($rp['inquiry_product_id']) && is_numeric($rp['inquiry_product_id']) && $rp['inquiry_product_id'] > 0 ? (int)$rp['inquiry_product_id'] : null;
                    $ebdItemId = isset($rp['ebd_item_id']) && is_numeric($rp['ebd_item_id']) && $rp['ebd_item_id'] > 0 ? (int)$rp['ebd_item_id'] : null;
                    
                    $inqProduct = null;
                    if ($inqProductId) {
                        $inqProduct = DB::table('mng_inquiry_products')->where('id', $inqProductId)->first();
                    }

                    $insertData = [
                        'work_order_id' => $workOrder->id,
                        'inquiry_product_id' => $inqProductId,
                        'ebd_item_id' => $ebdItemId,
                        'customer_name' => !empty($rp['customer_code']) ? $rp['customer_code'] : ($workOrder->inquiry->customer->code ?? $workOrder->ebdHeader->customer->code ?? ''),
                        'model_name' => $rp['model_name'] ?? $inqProduct->variant ?? $workOrder->inquiry->projectModel->name ?? $workOrder->ebdHeader->projectModel->name ?? '',
                        'variant' => $inqProduct->variant ?? $rp['variant'] ?? '',
                        'customer_part_no' => $inqProduct->customer_part_no ?? $rp['customer_part_no'] ?? '',
                        'customer_part_name' => $inqProduct->customer_part_name ?? $rp['customer_part_name'] ?? '',
                        'destination' => $inqProduct->destination ?? $rp['destination'] ?? '',
                        'sop_date' => $inqProduct->sop_date ?? (!empty($rp['sop_date']) ? $rp['sop_date'] : null),
                        'eol_date' => $inqProduct->eol_date ?? (!empty($rp['eol_date']) ? $rp['eol_date'] : null),
                        'model_life' => $inqProduct->model_life ?? (!empty($rp['model_life']) ? (int)$rp['model_life'] : null),
                        'annual_volume' => $inqProduct->annual_volume ?? (!empty($rp['annual_volume']) ? (int)$rp['annual_volume'] : null),
                        'eo' => $rp['eo'] ?? '',
                        'class_id' => !empty($rp['class_id']) ? $rp['class_id'] : null,
                        'uom' => !empty($rp['uom']) ? $rp['uom'] : null,
                        'has_2d_data' => $inqProduct->has_2d_data ?? (bool)($rp['has_2d_data'] ?? false),
                        'has_3d_data' => $inqProduct->has_3d_data ?? (bool)($rp['has_3d_data'] ?? false),
                        'has_tech_doc' => $inqProduct->has_tech_doc ?? (bool)($rp['has_tech_doc'] ?? false),
                        'remarks' => $rp['remarks'] ?? ($inqProduct->remarks ?? ''),
                        'ebd_add_process_id' => isset($rp['ebd_add_process_id']) && is_numeric($rp['ebd_add_process_id']) ? (int)$rp['ebd_add_process_id'] : null,
                        'add_process_name' => !empty($rp['add_process_name']) ? $rp['add_process_name'] : null,
                        'add_process_qty' => (isset($rp['add_process_qty']) && $rp['add_process_qty'] !== '') ? (int)$rp['add_process_qty'] : null,
                        'add_process_unit' => !empty($rp['add_process_unit']) ? $rp['add_process_unit'] : null,
                        'mat_spec' => !empty($rp['mat_spec']) ? $rp['mat_spec'] : null,
                        'mat_size' => !empty($rp['mat_size']) ? $rp['mat_size'] : null,
                        'mat_weight_pcs' => (isset($rp['mat_weight_pcs']) && $rp['mat_weight_pcs'] !== '') ? (float)$rp['mat_weight_pcs'] : null,
                        'sort_order' => $idx,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    $newId = DB::table('mng_wo_products')->insertGetId($insertData);
                    
                    if (isset($rp['tempId'])) {
                        $insertedIds[$rp['tempId']] = $newId;
                    }
                }

                // Second pass: update parent_id based on client parentTempId mapping
                foreach ($reqProducts as $rp) {
                    if (isset($rp['tempId']) && isset($rp['parentTempId']) && isset($insertedIds[$rp['parentTempId']])) {
                        DB::table('mng_wo_products')
                            ->where('id', $insertedIds[$rp['tempId']])
                            ->update(['parent_id' => $insertedIds[$rp['parentTempId']]]);
                    }
                }
            }

            return $workOrder;
        });
    }

    public function updateWorkOrder($id, array $data, array $processes, array $assignedPics)
    {
        return DB::transaction(function () use ($id, $data, $processes, $assignedPics) {
            $workOrder = $this->workOrderRepo->findById($id);
            if ($workOrder->status !== 'Draft') {
                throw new \Exception('Only Draft Work Orders can be updated.');
            }

            $workOrder->update($data);
            $this->workOrderRepo->attachProcessesAndPics($workOrder->id, $processes, $assignedPics);

            // Sync mng_wo_products: delete existing and insert new
            DB::table('mng_wo_products')->where('work_order_id', $workOrder->id)->delete();

            $reqProducts = request()->input('products', []);
            if (!empty($reqProducts) && is_array($reqProducts)) {
                $insertedIds = []; // tempId => database ID
                
                foreach ($reqProducts as $idx => $rp) {
                    $inqProductId = isset($rp['inquiry_product_id']) && is_numeric($rp['inquiry_product_id']) ? (int)$rp['inquiry_product_id'] : null;
                    
                    $inqProduct = null;
                    if ($inqProductId) {
                        $inqProduct = DB::table('mng_inquiry_products')->where('id', $inqProductId)->first();
                    }

                    $insertData = [
                        'work_order_id' => $workOrder->id,
                        'inquiry_product_id' => $inqProductId,
                        'customer_name' => !empty($rp['customer_code']) ? $rp['customer_code'] : ($workOrder->inquiry->customer->code ?? $workOrder->ebdHeader->customer->code ?? ''),
                        'model_name' => !empty($rp['model_name']) ? $rp['model_name'] : ($inqProduct->variant ?? $workOrder->inquiry->projectModel->name ?? $workOrder->ebdHeader->projectModel->name ?? ''),
                        'variant' => $inqProduct->variant ?? $rp['variant'] ?? '',
                        'customer_part_no' => $inqProduct->customer_part_no ?? $rp['customer_part_no'] ?? '',
                        'customer_part_name' => $inqProduct->customer_part_name ?? $rp['customer_part_name'] ?? '',
                        'destination' => $inqProduct->destination ?? $rp['destination'] ?? '',
                        'sop_date' => $inqProduct->sop_date ?? (!empty($rp['sop_date']) ? $rp['sop_date'] : null),
                        'eol_date' => $inqProduct->eol_date ?? (!empty($rp['eol_date']) ? $rp['eol_date'] : null),
                        'model_life' => $inqProduct->model_life ?? (!empty($rp['model_life']) ? (int)$rp['model_life'] : null),
                        'annual_volume' => $inqProduct->annual_volume ?? (!empty($rp['annual_volume']) ? (int)$rp['annual_volume'] : null),
                        'eo' => $rp['eo'] ?? '',
                        'class_id' => !empty($rp['class_id']) ? $rp['class_id'] : null,
                        'uom' => !empty($rp['uom']) ? $rp['uom'] : null,
                        'has_2d_data' => $inqProduct->has_2d_data ?? (bool)($rp['has_2d_data'] ?? false),
                        'has_3d_data' => $inqProduct->has_3d_data ?? (bool)($rp['has_3d_data'] ?? false),
                        'has_tech_doc' => $inqProduct->has_tech_doc ?? (bool)($rp['has_tech_doc'] ?? false),
                        'remarks' => $rp['remarks'] ?? ($inqProduct->remarks ?? ''),
                        'ebd_item_id' => isset($rp['ebd_item_id']) && is_numeric($rp['ebd_item_id']) && $rp['ebd_item_id'] > 0 ? (int)$rp['ebd_item_id'] : null,
                        'ebd_add_process_id' => isset($rp['ebd_add_process_id']) && is_numeric($rp['ebd_add_process_id']) ? (int)$rp['ebd_add_process_id'] : null,
                        'add_process_name' => !empty($rp['add_process_name']) ? $rp['add_process_name'] : null,
                        'add_process_qty' => (isset($rp['add_process_qty']) && $rp['add_process_qty'] !== '') ? (int)$rp['add_process_qty'] : null,
                        'add_process_unit' => !empty($rp['add_process_unit']) ? $rp['add_process_unit'] : null,
                        'mat_spec' => !empty($rp['mat_spec']) ? $rp['mat_spec'] : null,
                        'mat_size' => !empty($rp['mat_size']) ? $rp['mat_size'] : null,
                        'mat_weight_pcs' => (isset($rp['mat_weight_pcs']) && $rp['mat_weight_pcs'] !== '') ? (float)$rp['mat_weight_pcs'] : null,
                        'sort_order' => $idx,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    $newId = DB::table('mng_wo_products')->insertGetId($insertData);
                    
                    if (isset($rp['tempId'])) {
                        $insertedIds[$rp['tempId']] = $newId;
                    }
                }

                // Second pass: update parent_id based on client parentTempId mapping
                foreach ($reqProducts as $rp) {
                    if (isset($rp['tempId']) && isset($rp['parentTempId']) && isset($insertedIds[$rp['parentTempId']])) {
                        DB::table('mng_wo_products')
                            ->where('id', $insertedIds[$rp['tempId']])
                            ->update(['parent_id' => $insertedIds[$rp['parentTempId']]]);
                    }
                }
            }

            return $workOrder;
        });
    }

    public function deleteWorkOrder($id)
    {
        return DB::transaction(function () use ($id) {
            $workOrder = $this->workOrderRepo->findById($id);
            if (!in_array($workOrder->status, ['Draft', 'Pending Approval'])) {
                throw new \Exception('Only Draft or Pending Approval Work Orders can be deleted.');
            }
            
            // Restore previous revision's is_latest status if applicable
            if ($workOrder->revised_from_id) {
                $previousWo = WorkOrder::find($workOrder->revised_from_id);
                if ($previousWo) {
                    $previousWo->is_latest = true;
                    $previousWo->save();
                }
            }

            return $workOrder->delete();
        });
    }

    public function submitWorkOrder($id)
    {
        $workOrder = DB::transaction(function () use ($id) {
            $workOrder = $this->workOrderRepo->findById($id);
            if ($workOrder->status !== 'Draft') {
                throw new \Exception('Only Draft Work Orders can be submitted.');
            }

            $rules = ApprovalConfig::activeFor('SPK')->get();
            if ($rules->isEmpty()) {
                throw new \Exception('No approval rules configured for Work Order (WO). Please contact administrator.');
            }

            $workOrder->status = 'Pending Approval';
            $workOrder->save();

            // Clear old approvals
            $workOrder->approvals()->delete();

            // Generate approval steps only for selected/checked rules
            $selectedRuleIds = is_array($workOrder->selected_approval_rule_ids) 
                ? array_map('intval', $workOrder->selected_approval_rule_ids) 
                : [];

            $filteredRules = $rules;
            if (!empty($selectedRuleIds)) {
                $filteredRules = $rules->filter(fn($r) => in_array($r->id, $selectedRuleIds));
            }

            // Determine what is the lowest level among the filtered rules to set as initially Pending
            $minLevel = $filteredRules->min('approval_level') ?: 1;

            foreach ($filteredRules as $rule) {
                WorkOrderApproval::create([
                    'work_order_id' => $workOrder->id,
                    'approval_level' => $rule->approval_level,
                    'department_id' => $rule->department_id,
                    'approver_position' => $rule->position_label,
                    'status' => $rule->approval_level === $minLevel ? 'Pending' : 'Waiting',
                ]);
            }

            return $workOrder;
        });

        // Dispatch notification emails to involved people outside transaction
        try {
            // 1. Send to currently pending level approvers
            $pendingApprovals = $workOrder->approvals()->where('status', 'Pending')->get();
            foreach ($pendingApprovals as $approval) {
                $rule = ApprovalConfig::activeFor('SPK')
                    ->where('approval_level', $approval->approval_level)
                    ->where('department_id', $approval->department_id)
                    ->first();
                if ($rule) {
                    $approverUsers = $rule->approver_users;
                    if ($approverUsers->isEmpty()) {
                        // If no specific users, get all active users in that department
                        $approverUsers = User::where('id_dept', $rule->department_id)
                            ->where('is_active', true)
                            ->get();
                    }
                    foreach ($approverUsers as $approver) {
                        if (!empty($approver->email) && $approver->is_active) {
                            try {
                                Mail::to($approver->email)->send(new WorkOrderSubmittedMail($workOrder, 'approver', $approver->name));
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send Work Order approval email to {$approver->email}: " . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // 2. Send to PICs (assigned departments / process progress checklist)
            $picAssignments = []; // pic_user_id => [process names]
            $picUsersMap = [];    // pic_user_id => User model
            foreach ($workOrder->processes as $process) {
                $depts = json_decode($process->pivot->assigned_departments ?? '[]', true) ?: [];
                foreach ($depts as $dept) {
                    $picUserId = isset($dept['pic_user_id']) ? (int)$dept['pic_user_id'] : null;
                    if ($picUserId) {
                        if (!isset($picAssignments[$picUserId])) {
                            $picAssignments[$picUserId] = [];
                        }
                        $picAssignments[$picUserId][] = $process->process_name;
                        
                        if (!isset($picUsersMap[$picUserId])) {
                            $picUsersMap[$picUserId] = User::find($picUserId);
                        }
                    }
                }
            }

            foreach ($picAssignments as $picUserId => $processesList) {
                $picUser = $picUsersMap[$picUserId] ?? null;
                if ($picUser && !empty($picUser->email) && $picUser->is_active) {
                    try {
                        Mail::to($picUser->email)->send(new WorkOrderSubmittedMail($workOrder, 'pic', $picUser->name, array_unique($processesList)));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send Work Order PIC email to {$picUser->email}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('General error during Work Order submission email dispatch: ' . $e->getMessage());
        }

        return $workOrder;
    }

    public function approveWorkOrder($id, $remarks, $user)
    {
        return DB::transaction(function () use ($id, $remarks, $user) {
            $workOrder = WorkOrder::with('approvals')->findOrFail($id);
            if ($workOrder->status !== 'Pending Approval') {
                throw new \Exception('This Work Order is not pending approval.');
            }

            $pendingApprovals = $workOrder->approvals()->where('status', 'Pending')->get();
            if ($pendingApprovals->isEmpty()) {
                throw new \Exception('No pending approval step found.');
            }

            $pendingApproval = null;
            foreach ($pendingApprovals as $approval) {
                $checkRule = ApprovalConfig::activeFor('SPK')
                    ->where('approval_level', $approval->approval_level)
                    ->where('department_id', $approval->department_id)
                    ->first();
                if ($checkRule && $checkRule->canBeApprovedBy($user)) {
                    $pendingApproval = $approval;
                    break;
                }
            }

            if (!$pendingApproval) {
                throw new \Exception('You are not authorized to approve this level.');
            }

            $updateData = [
                'status' => 'Approved',
                'approver_name' => $user->name,
                'remarks' => $remarks,
                'approved_at' => now(),
            ];

            if (request()->has('due_date_closed')) {
                $updateData['due_date_closed'] = request()->input('due_date_closed');
            }

            $pendingApproval->update($updateData);

            if (request()->filled('urgent_reason') && $workOrder->priority === 'URGENT') {
                $workOrder->update([
                    'urgent_reason' => request()->input('urgent_reason'),
                    'urgent_confirmed_by' => $user->name,
                    'urgent_confirmed_at' => now(),
                ]);
            }

            // Re-fetch pending approvals at the current level to see if any are left
            $remainingPendingAtCurrentLevel = $workOrder->approvals()
                ->where('approval_level', $pendingApproval->approval_level)
                ->where('status', 'Pending')
                ->count();

            if ($remainingPendingAtCurrentLevel === 0) {
                // Activate next lowest level dynamically in case some levels are skipped
                $nextApprovalsQuery = $workOrder->approvals()
                    ->where('approval_level', '>', $pendingApproval->approval_level)
                    ->where('status', 'Waiting')
                    ->orderBy('approval_level', 'asc');
                
                $nextLevelToActivate = $nextApprovalsQuery->value('approval_level');

                if ($nextLevelToActivate) {
                    $nextApprovals = $workOrder->approvals()
                        ->where('approval_level', $nextLevelToActivate)
                        ->get();
                    foreach ($nextApprovals as $next) {
                        $next->update(['status' => 'Pending']);
                    }
                } else {
                    // Check if all steps in this work order are Approved
                    $hasAnyUnapproved = $workOrder->approvals()
                        ->where('status', '!=', 'Approved')
                        ->exists();

                    if (!$hasAnyUnapproved) {
                        $workOrder->update([
                            'status' => 'Approved',
                            'released_at' => now()
                        ]);
                    }
                }
            }

            return $workOrder;
        });
    }

    public function rejectWorkOrder($id, $remarks, $user)
    {
        return DB::transaction(function () use ($id, $remarks, $user) {
            $workOrder = WorkOrder::with('approvals')->findOrFail($id);
            if ($workOrder->status !== 'Pending Approval') {
                throw new \Exception('This Work Order is not pending approval.');
            }

            $pendingApprovals = $workOrder->approvals()->where('status', 'Pending')->get();
            if ($pendingApprovals->isEmpty()) {
                throw new \Exception('No pending approval step found.');
            }

            $pendingApproval = null;
            foreach ($pendingApprovals as $approval) {
                $checkRule = ApprovalConfig::activeFor('SPK')
                    ->where('approval_level', $approval->approval_level)
                    ->where('department_id', $approval->department_id)
                    ->first();
                if ($checkRule && $checkRule->canBeApprovedBy($user)) {
                    $pendingApproval = $approval;
                    break;
                }
            }

            if (!$pendingApproval) {
                throw new \Exception('You are not authorized to reject this level.');
            }

            $pendingApproval->update([
                'status' => 'Rejected',
                'approver_name' => $user->name,
                'remarks' => $remarks,
                'approved_at' => now(),
            ]);

            $workOrder->update(['status' => 'Rejected']);

            return $workOrder;
        });
    }

    public function reviseWorkOrder($id)
    {
        return DB::transaction(function () use ($id) {
            $original = WorkOrder::with(['processes', 'products'])->findOrFail($id);
            if ($original->status !== 'Approved') {
                throw new \Exception('Only approved Work Orders can be revised.');
            }

            if (!$original->is_latest) {
                throw new \Exception('Only the latest revision of a Work Order can be revised.');
            }

            $original->is_latest = false;
            $original->save();

            $newRevision = $original->replicate([
                'status',
                'created_by',
                'is_latest',
                'revision_no',
                'revised_from_id',
                'created_at',
                'updated_at'
            ]);

            $newRevision->status = 'Draft';
            $newRevision->created_by = auth()->user() ? auth()->user()->name : 'System';
            $newRevision->is_latest = true;
            $newRevision->revision_no = $original->revision_no + 1;
            $newRevision->revised_from_id = $original->id;
            $newRevision->save();

            // Clone processes
            foreach ($original->processes as $proc) {
                $newRevision->processes()->attach($proc->id, [
                    'assigned_departments' => $proc->pivot->assigned_departments,
                ]);
            }

            // Clone products
            foreach ($original->products as $prod) {
                $newProd = $prod->replicate(['work_order_id', 'created_at', 'updated_at']);
                $newProd->work_order_id = $newRevision->id;
                $newProd->save();
            }



            // Sync initial approval step
            WorkOrderApproval::create([
                'work_order_id' => $newRevision->id,
                'approval_level' => 1,
                'department_id' => $newRevision->department_id,
                'status' => 'Pending',
            ]);

            return $newRevision;
        });
    }

    public function updateProcessProgress($id, $processId, $deptId, array $checkedProductIds)
    {
        return DB::transaction(function () use ($id, $processId, $deptId, $checkedProductIds) {
            $workOrder = WorkOrder::findOrFail($id);
            $pivot = DB::table('mng_wo_process_details')
                ->where('work_order_id', $id)
                ->where('process_id', $processId)
                ->first();

            if (!$pivot) {
                throw new \Exception('Process not associated with this Work Order.');
            }

            $currentDepts = json_decode($pivot->assigned_departments, true) ?: [];
            $updatedDepts = [];

            foreach ($currentDepts as $cDept) {
                if ((int)$cDept['department_id'] === (int)$deptId) {
                    $updatedDepts[] = [
                        'department_id' => (int)$cDept['department_id'],
                        'pic_user_id' => (int)$cDept['pic_user_id'],
                        'checked_product_ids' => array_map('intval', $checkedProductIds)
                    ];
                } else {
                    $updatedDepts[] = $cDept;
                }
            }

            DB::table('mng_wo_process_details')
                ->where('work_order_id', $id)
                ->where('process_id', $processId)
                ->update([
                    'assigned_departments' => json_encode($updatedDepts)
                ]);

            return $workOrder;
        });
    }
}
