<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;
use App\Models\Department;
use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use App\Models\WorkOrderProcess;
use App\Models\WorkOrderApproval;
use App\Models\ApprovalRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    public function index()
    {
        $workOrders = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->where(function($q) {
                $q->where('is_latest', true)->orWhereNull('is_latest');
            })
            ->orderBy('created_at', 'desc')
            ->get();
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

        $inquiry = ProjectInquiry::with(['products' => function($q) use ($productsParam) {
            if ($productsParam) {
                $ids = array_filter(explode(',', $productsParam));
                $q->whereIn('id', $ids);
            }
        }, 'products.assessment.ranking'])->findOrFail($inquiryId);

        if ($inquiry->products->isEmpty()) {
            return redirect()->route('management.inquiry.show', $inquiryId)
                ->with('error', 'No products selected for the Work Order.');
        }

        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('sort_order', 'asc')->get();
        $approvalRules = ApprovalRule::activeFor('SPK')->get();
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

    public function store(Request $request)
    {
        Log::info('WorkOrderController@store request inputs', $request->all());
        $validated = $request->validate([
            'inquiry_id' => 'required|exists:mng_inquiries,id',
            'work_order_no' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|string|max:50',
            'request_types' => 'nullable|array',
            'support_departments' => 'nullable|array',
            'support_departments.*' => 'exists:departments,id',
            'processes' => 'nullable|array',
            'processes.*' => 'exists:mng_wo_processes,id',
            'remarks' => 'nullable|string',
            'products' => 'required|array',
            'products.*.inquiry_product_id' => 'required|exists:mng_inquiry_products,id',
            'products.*.eo' => 'nullable|string|max:100',
            'products.*.class_id' => 'nullable|string|max:100',
            'products.*.uom' => 'nullable|string|max:50',
            'products.*.remarks' => 'nullable|string',
            'header_id' => 'required|exists:mng_wo_doc_format,id',
            'first_sample_date' => 'nullable|date',
            'due_date_approval' => 'nullable|date',
            'due_date_closed' => 'nullable|date',
            'process_depts' => 'nullable|array',
            'selected_approval_rules' => 'nullable|array',
            'selected_approval_rules.*' => 'exists:mng_approval_rules,id',
        ]);

        DB::beginTransaction();
        try {
            $exists = WorkOrder::where('wo_number', $validated['work_order_no'])
                ->where('revision_no', 0)
                ->exists();
            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Work Order number already exists.');
            }

            $workOrder = WorkOrder::create([
                'inquiry_id' => $validated['inquiry_id'],
                'wo_number' => $validated['work_order_no'],
                'revision_no' => 0,
                'is_latest' => true,
                'header_id' => $validated['header_id'],
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'subject' => $validated['subject'],
                'request_types' => $validated['request_types'] ?? [],
                'status' => 'Draft',
                'remarks' => $validated['remarks'],
                'created_by' => auth()->user() ? auth()->user()->name : 'System',
                'first_sample_date' => $validated['first_sample_date'] ?? null,
                'due_date_approval' => $validated['due_date_approval'] ?? null,
                'due_date_closed' => $validated['due_date_closed'] ?? null,
                'selected_approval_rule_ids' => $validated['selected_approval_rules'] ?? [],
            ]);


            $processDepts = $request->input('process_depts', []);
            if (!empty($validated['processes'])) {
                foreach ($validated['processes'] as $procId) {
                    $depts = isset($processDepts[$procId]) ? array_map('intval', $processDepts[$procId]) : [];
                    $workOrder->processes()->attach($procId, [
                        'assigned_departments' => json_encode($depts)
                    ]);
                }
            }

            $inquiry = ProjectInquiry::findOrFail($validated['inquiry_id']);

            foreach ($validated['products'] as $prodInput) {
                $inqProd = InquiryProduct::findOrFail($prodInput['inquiry_product_id']);

                WorkOrderProduct::create([
                    'work_order_id' => $workOrder->id,
                    'inquiry_product_id' => $inqProd->id,
                    'customer_name' => $inquiry->customer->name ?? '',
                    'model_name' => $inquiry->model->name ?? '',
                    'customer_part_no' => $inqProd->customer_part_no,
                    'customer_part_name' => $inqProd->customer_part_name,
                    'destination' => $inqProd->destination,
                    'sop_date' => $inqProd->sop_date,
                    'eol_date' => $inqProd->eol_date,
                    'model_life' => $inqProd->model_life,
                    'annual_volume' => $inqProd->annual_volume,
                    'first_sample_date' => $validated['first_sample_date'],
                    'due_date_approval' => $validated['due_date_approval'],
                    'due_date_closed' => $validated['due_date_closed'],
                    'variant' => $inqProd->variant,
                    'eo' => $prodInput['eo'] ?? '-',
                    'class_id' => $prodInput['class_id'] ?? 'RM',
                    'uom' => $prodInput['uom'] ?? 'Pcs',
                    'remarks' => $prodInput['remarks'],
                ]);
            }

            // Create initial approval record
            WorkOrderApproval::create([
                'work_order_id' => $workOrder->id,
                'approval_level' => 1,
                'department_id' => $validated['department_id'],
                'status' => 'Pending',
            ]);

            DB::commit();

            return redirect()->route('management.inquiry.show', $validated['inquiry_id'])
                ->with('success', 'Work Order (SPK) successfully created!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store work order', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save Work Order: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('WorkOrderController@update request inputs', $request->all());
        $workOrder = WorkOrder::findOrFail($id);
        
        if ($workOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only draft Work Orders can be updated.');
        }

        $validated = $request->validate([
            'work_order_no' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|string|max:50',
            'support_departments' => 'nullable|array',
            'support_departments.*' => 'exists:departments,id',
            'processes' => 'nullable|array',
            'processes.*' => 'exists:mng_wo_processes,id',
            'remarks' => 'nullable|string',
            'products' => 'required|array',
            'products.*.work_order_product_id' => 'required|exists:mng_wo_products,id',
            'products.*.eo' => 'nullable|string|max:100',
            'products.*.class_id' => 'nullable|string|max:100',
            'products.*.uom' => 'nullable|string|max:50',
            'products.*.remarks' => 'nullable|string',
            'header_id' => 'required|exists:mng_wo_doc_format,id',
            'first_sample_date' => 'nullable|date',
            'due_date_approval' => 'nullable|date',
            'due_date_closed' => 'nullable|date',
            'process_depts' => 'nullable|array',
            'selected_approval_rules' => 'nullable|array',
            'selected_approval_rules.*' => 'exists:mng_approval_rules,id',
        ]);

        DB::beginTransaction();
        try {
            $exists = WorkOrder::where('wo_number', $validated['work_order_no'])
                ->where('revision_no', $workOrder->revision_no)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Work Order number already exists.');
            }

            $workOrder->update([
                'wo_number' => $validated['work_order_no'],
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'subject' => $validated['subject'],
                'remarks' => $validated['remarks'],
                'header_id' => $validated['header_id'],
                'first_sample_date' => $validated['first_sample_date'] ?? null,
                'due_date_approval' => $validated['due_date_approval'] ?? null,
                'due_date_closed' => $validated['due_date_closed'] ?? null,
                'selected_approval_rule_ids' => $validated['selected_approval_rules'] ?? [],
            ]);

            $processDepts = $request->input('process_depts', []);
            $syncData = [];
            if (!empty($validated['processes'])) {
                foreach ($validated['processes'] as $procId) {
                    $depts = isset($processDepts[$procId]) ? array_map('intval', $processDepts[$procId]) : [];
                    $syncData[$procId] = [
                        'assigned_departments' => json_encode($depts)
                    ];
                }
            }

            $workOrder->processes()->sync($syncData);

            foreach ($validated['products'] as $prodInput) {
                $woProduct = WorkOrderProduct::findOrFail($prodInput['work_order_product_id']);
                $woProduct->update([
                    'first_sample_date' => $validated['first_sample_date'],
                    'due_date_approval' => $validated['due_date_approval'],
                    'due_date_closed' => $validated['due_date_closed'],
                    'eo' => $prodInput['eo'] ?? '-',
                    'class_id' => $prodInput['class_id'] ?? 'RM',
                    'uom' => $prodInput['uom'] ?? 'Pcs',
                    'remarks' => $prodInput['remarks'],
                ]);
            }

            DB::commit();

            return redirect()->route('management.work-order.show', $id)
                ->with('success', 'Work Order (SPK) successfully updated!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update work order', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save Work Order: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $workOrder = WorkOrder::with(['inquiry', 'ownerDepartment', 'processes', 'products', 'approvals.department'])->findOrFail($id);
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $approvalRules = ApprovalRule::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();

        $woHeader = $workOrder->docFormat;

        return view('management.work-order.form', compact('workOrder', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'));
    }

    public function submit($id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        if ($workOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only Draft Work Orders can be submitted for approval.');
        }

        $rules = ApprovalRule::activeFor('SPK')->get();

        // Filter based on user's selection
        $selectedRuleIds = $workOrder->selected_approval_rule_ids ?? [];
        if (is_array($selectedRuleIds) && count($selectedRuleIds) > 0) {
            $rules = $rules->filter(function($rule) use ($selectedRuleIds) {
                return in_array($rule->id, $selectedRuleIds);
            });
        }

        if ($rules->isEmpty()) {
            return redirect()->back()->with('error', 'No active approval rules configured or selected. Please select at least one approval level.');
        }

        DB::beginTransaction();
        try {
            // Delete old pending approvals and regenerate from rules
            $workOrder->approvals()->delete();

            // Find lowest approval level to set as Pending
            $minLevel = $rules->min('approval_level');

            foreach ($rules as $rule) {
                WorkOrderApproval::create([
                    'work_order_id'     => $workOrder->id,
                    'approval_level'    => $rule->approval_level,
                    'department_id'     => $rule->department_id,
                    'approver_name'     => $rule->approverUser?->name,
                    'approver_position' => $rule->position_label,
                    'status'            => $rule->approval_level === $minLevel ? 'Pending' : 'Waiting',
                    'remarks'           => null,
                    'approved_at'       => null,
                ]);
            }

            $workOrder->update(['status' => 'Pending Approval']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit work order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to submit: ' . $e->getMessage());
        }

        return redirect()->route('management.work-order.show', $id)
            ->with('success', 'SPK successfully submitted for approval!');
    }

    public function approve(Request $request, $id)
    {
        $workOrder = WorkOrder::with('approvals')->findOrFail($id);

        if ($workOrder->status !== 'Pending Approval') {
            return redirect()->back()->with('error', 'This Work Order is not pending approval.');
        }

        // Find the current pending approvals
        $pendingApprovals = $workOrder->approvals()->where('status', 'Pending')->get();

        if ($pendingApprovals->isEmpty()) {
            return redirect()->back()->with('error', 'No pending approval step found.');
        }

        // Authorize: find the pending approval step that matches the logged-in user
        $user = auth()->user();
        $pendingApproval = null;
        $rule = null;

        foreach ($pendingApprovals as $approval) {
            $checkRule = ApprovalRule::activeFor('SPK')
                ->where('approval_level', $approval->approval_level)
                ->where('department_id', $approval->department_id)
                ->first();
            if ($checkRule && $checkRule->canBeApprovedBy($user)) {
                $pendingApproval = $approval;
                $rule = $checkRule;
                break;
            }
        }

        if (!$pendingApproval) {
            return redirect()->back()->with('error', 'You are not authorized to approve this level.');
        }

        DB::beginTransaction();
        try {
            // Mark this level as Approved
            $pendingApproval->update([
                'status'            => 'Approved',
                'approver_name'     => auth()->user()->name,
                'approver_position' => $rule?->position_label ?? $pendingApproval->approver_position,
                'approved_at'       => now(),
                'remarks'           => $request->input('remarks'),
            ]);

            // OR Logic: Mark other approvals at the same level as "Not Required"
            $workOrder->approvals()
                ->where('approval_level', $pendingApproval->approval_level)
                ->where('status', '!=', 'Approved')
                ->where('id', '!=', $pendingApproval->id)
                ->update([
                    'status'            => 'Not Required',
                    'approver_name'     => 'System',
                    'approved_at'       => now(),
                    'remarks'           => 'Approved by ' . auth()->user()->name . ' (OR Logic)',
                ]);

            // Find next level
            $nextLevel = $workOrder->approvals()
                ->where('approval_level', '>', $pendingApproval->approval_level)
                ->orderBy('approval_level')
                ->value('approval_level');

            if ($nextLevel) {
                // Advance to next level: set all to Pending
                $workOrder->approvals()
                    ->where('approval_level', $nextLevel)
                    ->update(['status' => 'Pending']);
            } else {
                // All levels done — mark SPK as Approved
                $workOrder->update(['status' => 'Approved']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve work order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }

        $nextApproval = $workOrder->fresh()->approvals()->where('status', 'Pending')->first();
        $message = $nextApproval
            ? 'Level ' . $pendingApproval->approval_level . ' approved! Next: Level ' . $nextApproval->approval_level . ' (' . $nextApproval->approver_position . ')'
            : 'Work Order (SPK) fully approved!';

        return redirect()->route('management.work-order.show', $id)->with('success', $message);
    }

    public function reject(Request $request, $id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        if ($workOrder->status !== 'Pending Approval') {
            return redirect()->back()->with('error', 'This Work Order is not pending approval.');
        }

        $pendingApproval = $workOrder->approvals()->where('status', 'Pending')->orderBy('approval_level')->first();

        // Authorize
        if ($pendingApproval) {
            $rule = ApprovalRule::activeFor('SPK')
                ->where('approval_level', $pendingApproval->approval_level)
                ->first();

            if ($rule) {
                $user = auth()->user();
                if (!$rule->canBeApprovedBy($user)) {
                    return redirect()->back()->with('error', 'You are not authorized to reject this level.');
                }
            }

            $pendingApproval->update([
                'status'        => 'Rejected',
                'approver_name' => auth()->user()->name,
                'approved_at'   => now(),
                'remarks'       => $request->input('remarks'),
            ]);
        }

        // Return SPK to Draft
        $workOrder->update(['status' => 'Draft']);

        return redirect()->route('management.work-order.show', $id)
            ->with('success', 'SPK rejected and returned to Draft. The creator can now revise and re-submit.');
    }

    public function revise($id)
    {
        $original = WorkOrder::with(['processes', 'products'])->findOrFail($id);

        if ($original->status !== 'Approved') {
            return redirect()->back()->with('error', 'Only approved Work Orders can be revised.');
        }

        if (!$original->is_latest) {
            return redirect()->back()->with('error', 'Only the latest revision of a Work Order can be revised.');
        }

        DB::beginTransaction();
        try {
            // Set old latest to false
            $original->is_latest = false;
            $original->save();

            // Create new revision WorkOrder
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
                    'remarks' => $proc->pivot->remarks
                ]);
            }

            // Clone products
            foreach ($original->products as $prod) {
                $newProd = $prod->replicate(['work_order_id', 'created_at', 'updated_at']);
                $newProd->work_order_id = $newRevision->id;
                $newProd->save();
            }

            // Create initial approval record for the new draft revision
            WorkOrderApproval::create([
                'work_order_id' => $newRevision->id,
                'approval_level' => 1,
                'department_id' => $newRevision->department_id,
                'status' => 'Pending',
            ]);

            DB::commit();

            return redirect()->route('management.work-order.show', $newRevision->id)
                ->with('success', 'New draft revision ' . sprintf('Rev. %02d', $newRevision->revision_no) . ' has been created.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to revise Work Order', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to create new revision: ' . $e->getMessage());
        }
    }

    public function approvalInbox()
    {
        $user = auth()->user();

        // 1. Recent (Pending Approval assigned to me)
        $pendingSPKs = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->where('status', 'Pending Approval')
            ->get();

        $recent = $pendingSPKs->filter(function($wo) use ($user) {
            return $wo->isApprover($user);
        })->values();

        // 2. Approved (approved by me)
        $approved = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->whereHas('approvals', function($q) use ($user) {
                $q->where('status', 'Approved')
                  ->where('approver_name', $user->name);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Rejected (rejected by me)
        $rejected = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->whereHas('approvals', function($q) use ($user) {
                $q->where('status', 'Rejected')
                  ->where('approver_name', $user->name);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        // 4. All (Recent + Approved + Rejected)
        $all = WorkOrder::with(['inquiry', 'ownerDepartment'])
            ->where(function($q) use ($user, $recent) {
                $q->whereIn('id', $recent->pluck('id'))
                  ->orWhereHas('approvals', function($sub) use ($user) {
                      $sub->where('approver_name', $user->name);
                  });
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('management.work-order.inbox', compact('recent', 'approved', 'rejected', 'all'));
    }

    public function reviewPage($id)
    {
        $workOrder = WorkOrder::with(['inquiry', 'ownerDepartment', 'processes', 'products', 'approvals.department'])->findOrFail($id);
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $approvalRules = ApprovalRule::activeFor('SPK')->get();
        $users = User::orderBy('name', 'asc')->get();
        $woHeader = $workOrder->docFormat;

        return view('management.work-order.review', compact('workOrder', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'));
    }

    public function storeProcess(Request $request)
    {
        $validated = $request->validate([
            'process_code' => 'required|string|max:50|unique:mng_wo_processes,process_code',
            'process_name' => 'required|string|max:255',
            'default_assigned_departments' => 'nullable|array',
            'sort_order' => 'required|integer|min:0',
        ]);

        WorkOrderProcess::create([
            'process_code' => $validated['process_code'],
            'process_name' => $validated['process_name'],
            'default_assigned_departments' => json_encode(array_map('intval', $validated['default_assigned_departments'] ?? [])),
            'sort_order' => $validated['sort_order'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Master Process Checklist berhasil ditambahkan.');
    }

    public function updateProcess(Request $request, $id)
    {
        $process = WorkOrderProcess::findOrFail($id);
        $validated = $request->validate([
            'process_code' => 'required|string|max:50|unique:mng_wo_processes,process_code,' . $id,
            'process_name' => 'required|string|max:255',
            'default_assigned_departments' => 'nullable|array',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $process->update([
            'process_code' => $validated['process_code'],
            'process_name' => $validated['process_name'],
            'default_assigned_departments' => json_encode(array_map('intval', $validated['default_assigned_departments'] ?? [])),
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Master Process Checklist berhasil diubah.');
    }

    public function destroyProcess($id)
    {
        $process = WorkOrderProcess::findOrFail($id);
        $process->delete();

        return redirect()->back()->with('success', 'Master Process Checklist berhasil dihapus.');
    }
}
