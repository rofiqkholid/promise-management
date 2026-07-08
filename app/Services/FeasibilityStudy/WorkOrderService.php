<?php

namespace App\Services\FeasibilityStudy;

use App\Repositories\FeasibilityStudy\WorkOrderRepository;
use App\Models\WorkOrder;
use App\Models\WorkOrderApproval;
use App\Models\ApprovalConfig;
use Illuminate\Support\Facades\DB;

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

            $workOrder = $this->workOrderRepo->create($data);
            
            $this->workOrderRepo->attachProcessesAndPics($workOrder->id, $processes, $assignedPics);

            // Sync work order products - read from JSON body
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
                        'customer_name' => $workOrder->inquiry->customer->name ?? '',
                        'model_name' => $inqProduct->variant ?? $workOrder->inquiry->projectModel->name ?? '',
                        'variant' => $inqProduct->variant ?? $rp['variant'] ?? '',
                        'customer_part_no' => $inqProduct->customer_part_no ?? $rp['customer_part_no'] ?? '',
                        'customer_part_name' => $inqProduct->customer_part_name ?? $rp['customer_part_name'] ?? '',
                        'destination' => $inqProduct->destination ?? $rp['destination'] ?? '',
                        'sop_date' => $inqProduct->sop_date ?? (!empty($rp['sop_date']) ? $rp['sop_date'] : null),
                        'eol_date' => $inqProduct->eol_date ?? (!empty($rp['eol_date']) ? $rp['eol_date'] : null),
                        'model_life' => $inqProduct->model_life ?? (!empty($rp['model_life']) ? (int)$rp['model_life'] : null),
                        'annual_volume' => $inqProduct->annual_volume ?? (!empty($rp['annual_volume']) ? (int)$rp['annual_volume'] : null),
                        'eo' => $rp['eo'] ?? '',
                        'class_id' => $rp['class_id'] ?? 'FG',
                        'uom' => $rp['uom'] ?? 'Kg',
                        'has_2d_data' => $inqProduct->has_2d_data ?? (bool)($rp['has_2d_data'] ?? false),
                        'has_3d_data' => $inqProduct->has_3d_data ?? (bool)($rp['has_3d_data'] ?? false),
                        'has_tech_doc' => $inqProduct->has_tech_doc ?? (bool)($rp['has_tech_doc'] ?? false),
                        'remarks' => $rp['remarks'] ?? ($inqProduct->remarks ?? ''),
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
                        'customer_name' => $workOrder->inquiry->customer->name ?? '',
                        'model_name' => $inqProduct->variant ?? $workOrder->inquiry->projectModel->name ?? '',
                        'variant' => $inqProduct->variant ?? $rp['variant'] ?? '',
                        'customer_part_no' => $inqProduct->customer_part_no ?? $rp['customer_part_no'] ?? '',
                        'customer_part_name' => $inqProduct->customer_part_name ?? $rp['customer_part_name'] ?? '',
                        'destination' => $inqProduct->destination ?? $rp['destination'] ?? '',
                        'sop_date' => $inqProduct->sop_date ?? (!empty($rp['sop_date']) ? $rp['sop_date'] : null),
                        'eol_date' => $inqProduct->eol_date ?? (!empty($rp['eol_date']) ? $rp['eol_date'] : null),
                        'model_life' => $inqProduct->model_life ?? (!empty($rp['model_life']) ? (int)$rp['model_life'] : null),
                        'annual_volume' => $inqProduct->annual_volume ?? (!empty($rp['annual_volume']) ? (int)$rp['annual_volume'] : null),
                        'eo' => $rp['eo'] ?? '',
                        'class_id' => $rp['class_id'] ?? 'FG',
                        'uom' => $rp['uom'] ?? 'Kg',
                        'has_2d_data' => $inqProduct->has_2d_data ?? (bool)($rp['has_2d_data'] ?? false),
                        'has_3d_data' => $inqProduct->has_3d_data ?? (bool)($rp['has_3d_data'] ?? false),
                        'has_tech_doc' => $inqProduct->has_tech_doc ?? (bool)($rp['has_tech_doc'] ?? false),
                        'remarks' => $rp['remarks'] ?? ($inqProduct->remarks ?? ''),
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
            if ($workOrder->status !== 'Draft') {
                throw new \Exception('Only Draft Work Orders can be deleted.');
            }
            return $workOrder->delete();
        });
    }

    public function submitWorkOrder($id)
    {
        return DB::transaction(function () use ($id) {
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
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Clone products
            foreach ($original->products as $prod) {
                DB::table('mng_wo_products')->insert([
                    'work_order_id' => $newRevision->id,
                    'inquiry_product_id' => $prod->inquiry_product_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
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
