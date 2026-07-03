<?php

namespace App\Repositories\FeasibilityStudy;

use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use Illuminate\Support\Facades\DB;

class WorkOrderRepository
{
    public function paginate($perPage = 10, array $filters = [])
    {
        $query = WorkOrder::with(['inquiry.customer', 'inquiry.projectModel', 'ownerDepartment']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('wo_number', 'like', "%{$search}%")
                  ->orWhereHas('inquiry', fn($iq) => $iq->where('inquiry_no', 'like', "%{$search}%")
                                                        ->orWhere('project_name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById($id)
    {
        return WorkOrder::with(['inquiry', 'ownerDepartment', 'processes', 'products.inquiryProduct', 'approvals.department'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return WorkOrder::create($data);
    }

    public function update($id, array $data)
    {
        $workOrder = WorkOrder::findOrFail($id);
        $workOrder->update($data);
        return $workOrder;
    }

    public function delete($id)
    {
        $workOrder = WorkOrder::findOrFail($id);
        return $workOrder->delete();
    }

    public function attachProcessesAndPics($workOrderId, array $processes, array $assignedPics)
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);
        $workOrder->processes()->detach();

        foreach ($processes as $procId) {
            $picsForProc = $assignedPics[$procId] ?? [];
            $assignedDepts = [];
            foreach ($picsForProc as $deptId => $picUserId) {
                if ($picUserId) {
                    $assignedDepts[] = [
                        'department_id' => (int)$deptId,
                        'pic_user_id' => (int)$picUserId,
                        'checked_product_ids' => []
                    ];
                }
            }

            $workOrder->processes()->attach($procId, [
                'assigned_departments' => json_encode($assignedDepts),
            ]);
        }
    }



    public function getPendingApprovals($userId = null)
    {
        $query = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->where('status', 'Pending Approval');
        return $query->get();
    }

    public function getApprovedBy($userName)
    {
        return WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->whereHas('approvals', function($q) use ($userName) {
                $q->where('status', 'Approved')
                  ->where('approver_name', $userName);
            })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getRejectedBy($userName)
    {
        return WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->whereHas('approvals', function($q) use ($userName) {
                $q->where('status', 'Rejected')
                  ->where('approver_name', $userName);
            })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getTasksForUser($userId)
    {
        return WorkOrder::with(['inquiry', 'ownerDepartment', 'processes'])
            ->whereIn('status', ['Released', 'Approved', 'Pending Approval'])
            ->get()
            ->filter(function($wo) use ($userId) {
                foreach ($wo->processes as $proc) {
                    $depts = json_decode($proc->pivot->assigned_departments ?? '[]', true) ?: [];
                    foreach ($depts as $dept) {
                        if (is_array($dept) && ($dept['pic_user_id'] ?? null) == $userId) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->values();
    }
}
