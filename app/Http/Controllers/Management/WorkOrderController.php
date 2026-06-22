<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;
use App\Models\Department;
use App\Models\WorkOrder;
use App\Models\WorkOrderProduct;
use App\Models\WorkOrderPart;
use App\Models\WorkOrderProcess;
use App\Models\WorkOrderApproval;
use App\Models\ApprovalRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    public function index()
    {
        $workOrders = WorkOrder::with(['inquiry', 'ownerDepartment'])->orderBy('created_at', 'desc')->get();
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
                $q->whereIn('inquiry_product_id', $ids);
            }
        }, 'products.assessment.ranking'])->findOrFail($inquiryId);

        if ($inquiry->products->isEmpty()) {
            return redirect()->route('management.inquiry.show', $inquiryId)
                ->with('error', 'No products selected for the Work Order.');
        }

        $departments = Department::orderBy('name', 'asc')->get();
        $processes = WorkOrderProcess::orderBy('sort_order', 'asc')->get();
        $approvalRules = ApprovalRule::activeFor('SPK')->get();

        // Generate dynamic SPK No. (matching No. 002/MKT-SPK/SAI/V/26 format)
        $count = WorkOrder::count() + 1;
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romans[now()->month] ?? 'I';
        $defaultSpkNo = sprintf("%03d/MKT-SPK/SAI/%s/%02d", $count, $romanMonth, now()->year % 100);

        return view('management.work-order.form', compact('inquiry', 'departments', 'processes', 'defaultSpkNo', 'approvalRules'));
    }

    public function store(Request $request)
    {
        Log::info('WorkOrderController@store request inputs', $request->all());
        $validated = $request->validate([
            'inquiry_id' => 'required|exists:mng_project_inquiries,inquiry_id',
            'work_order_no' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|string|max:50',
            'request_types' => 'nullable|array',
            'support_departments' => 'nullable|array',
            'support_departments.*' => 'exists:departments,id',
            'processes' => 'nullable|array',
            'processes.*' => 'exists:mng_work_order_processes,process_id',
            'remarks' => 'nullable|string',
            'products' => 'required|array',
            'products.*.inquiry_product_id' => 'required|exists:mng_inquiry_products,inquiry_product_id',
            'products.*.first_sample_date' => 'nullable|date',
            'products.*.due_date_approval' => 'nullable|date',
            'products.*.due_date_closed' => 'nullable|date',
            'products.*.remarks' => 'nullable|string',
            'parts' => 'nullable|array',
            'document_no' => 'nullable|string|max:100',
            'doc_department' => 'nullable|string|max:100',
            'publish_date' => 'nullable|date',
            'page_hal' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $spkParts = explode('/', $validated['work_order_no']);
            $revNo = isset($spkParts[0]) ? (int)$spkParts[0] : 0;

            $exists = WorkOrder::where('work_order_no', $validated['work_order_no'])
                ->where('revision_no', $revNo)
                ->exists();
            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Work Order number already exists for this revision.');
            }

            $workOrder = WorkOrder::create([
                'inquiry_id' => $validated['inquiry_id'],
                'work_order_no' => $validated['work_order_no'],
                'revision_no' => $revNo,
                'is_latest' => true,
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'subject' => $validated['subject'],
                'request_types' => $validated['request_types'] ?? [],
                'status' => 'Draft',
                'remarks' => $validated['remarks'],
                'created_by' => auth()->user() ? auth()->user()->name : 'System',
                'document_no' => $validated['document_no'] ?? 'FO-13-02',
                'doc_department' => $validated['doc_department'] ?? 'Sales',
                'publish_date' => $validated['publish_date'] ?? now()->toDateString(),
                'page_hal' => $validated['page_hal'] ?? '1',
            ]);

            if (!empty($validated['support_departments'])) {
                foreach ($validated['support_departments'] as $deptId) {
                    $workOrder->supportDepartments()->attach($deptId);
                }
            }

            if (!empty($validated['processes'])) {
                foreach ($validated['processes'] as $procId) {
                    $workOrder->processes()->attach($procId);
                }
            }

            $inquiry = ProjectInquiry::findOrFail($validated['inquiry_id']);

            foreach ($validated['products'] as $prodInput) {
                $inqProd = InquiryProduct::findOrFail($prodInput['inquiry_product_id']);

                $woProduct = WorkOrderProduct::create([
                    'work_order_id' => $workOrder->work_order_id,
                    'inquiry_product_id' => $inqProd->inquiry_product_id,
                    'customer_name' => $inquiry->customer_name,
                    'model_name' => $inqProd->model_name,
                    'customer_part_no' => $inqProd->customer_part_no,
                    'customer_part_name' => $inqProd->customer_part_name,
                    'destination' => $inqProd->destination,
                    'sop_date' => $inqProd->sop_date,
                    'eol_date' => $inqProd->eol_date,
                    'model_life' => $inqProd->model_life,
                    'annual_volume' => $inqProd->annual_volume,
                    'first_sample_date' => $prodInput['first_sample_date'],
                    'due_date_approval' => $prodInput['due_date_approval'],
                    'due_date_closed' => $prodInput['due_date_closed'],
                    'remarks' => $prodInput['remarks'],
                ]);

                $prodIdKey = $inqProd->inquiry_product_id;
                if (!empty($validated['parts'][$prodIdKey])) {
                    foreach ($validated['parts'][$prodIdKey] as $partInput) {
                        if (empty($partInput['part_no']) || empty($partInput['part_name'])) {
                            continue;
                        }
                        WorkOrderPart::create([
                            'work_order_product_id' => $woProduct->work_order_product_id,
                            'eo' => $partInput['eo'] ?: '-',
                            'part_no' => $partInput['part_no'],
                            'part_name' => $partInput['part_name'],
                            'class_id' => $partInput['class_id'] ?: 'RM',
                            'uom' => $partInput['uom'] ?: 'Pcs',
                            'remarks' => $partInput['remarks'],
                        ]);
                    }
                }
            }

            // Create initial approval record
            \App\Models\WorkOrderApproval::create([
                'work_order_id' => $workOrder->work_order_id,
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
            'processes.*' => 'exists:mng_work_order_processes,process_id',
            'remarks' => 'nullable|string',
            'products' => 'required|array',
            'products.*.work_order_product_id' => 'required|exists:mng_work_order_products,work_order_product_id',
            'products.*.first_sample_date' => 'nullable|date',
            'products.*.due_date_approval' => 'nullable|date',
            'products.*.due_date_closed' => 'nullable|date',
            'products.*.remarks' => 'nullable|string',
            'parts' => 'nullable|array',
            'document_no' => 'nullable|string|max:100',
            'doc_department' => 'nullable|string|max:100',
            'publish_date' => 'nullable|date',
            'page_hal' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $spkParts = explode('/', $validated['work_order_no']);
            $revNo = isset($spkParts[0]) ? (int)$spkParts[0] : 0;

            $exists = WorkOrder::where('work_order_no', $validated['work_order_no'])
                ->where('revision_no', $revNo)
                ->where('work_order_id', '!=', $id)
                ->exists();
            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Work Order number already exists.');
            }

            $workOrder->update([
                'work_order_no' => $validated['work_order_no'],
                'revision_no' => $revNo,
                'department_id' => $validated['department_id'],
                'priority' => $validated['priority'],
                'subject' => $validated['subject'],
                'remarks' => $validated['remarks'],
                'document_no' => $validated['document_no'] ?? 'FO-13-02',
                'doc_department' => $validated['doc_department'] ?? 'Sales',
                'publish_date' => $validated['publish_date'] ?? now()->toDateString(),
                'page_hal' => $validated['page_hal'] ?? '1',
            ]);

            $workOrder->supportDepartments()->sync($validated['support_departments'] ?? []);
            $workOrder->processes()->sync($validated['processes'] ?? []);

            foreach ($validated['products'] as $prodInput) {
                $woProduct = WorkOrderProduct::findOrFail($prodInput['work_order_product_id']);
                $woProduct->update([
                    'first_sample_date' => $prodInput['first_sample_date'],
                    'due_date_approval' => $prodInput['due_date_approval'],
                    'due_date_closed' => $prodInput['due_date_closed'],
                    'remarks' => $prodInput['remarks'],
                ]);

                // Delete existing parts and insert new
                $woProduct->parts()->delete();

                $prodIdKey = $woProduct->inquiry_product_id;
                if (!empty($validated['parts'][$prodIdKey])) {
                    foreach ($validated['parts'][$prodIdKey] as $partInput) {
                        if (empty($partInput['part_no']) || empty($partInput['part_name'])) {
                            continue;
                        }
                        WorkOrderPart::create([
                            'work_order_product_id' => $woProduct->work_order_product_id,
                            'eo' => $partInput['eo'] ?: '-',
                            'part_no' => $partInput['part_no'],
                            'part_name' => $partInput['part_name'],
                            'class_id' => $partInput['class_id'] ?: 'RM',
                            'uom' => $partInput['uom'] ?: 'Pcs',
                            'remarks' => $partInput['remarks'],
                        ]);
                    }
                }
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
        $workOrder = WorkOrder::with(['inquiry', 'ownerDepartment', 'supportDepartments', 'processes', 'products.parts', 'approvals.department'])->findOrFail($id);
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name', 'asc')->get();
        $processes = \App\Models\WorkOrderProcess::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $approvalRules = ApprovalRule::activeFor('SPK')->get();
        return view('management.work-order.form', compact('workOrder', 'inquiry', 'departments', 'processes', 'approvalRules'));
    }

    /**
     * Submit SPK for Approval.
     * Generates approval rows based on active approval rules (sequential).
     */
    public function submit($id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        if ($workOrder->status !== 'Draft') {
            return redirect()->back()->with('error', 'Only Draft Work Orders can be submitted for approval.');
        }

        $rules = ApprovalRule::activeFor('SPK')->get();

        if ($rules->isEmpty()) {
            return redirect()->back()->with('error', 'No active approval rules configured. Please set up the Approval Matrix in Settings before submitting.');
        }

        DB::beginTransaction();
        try {
            // Delete old pending approvals and regenerate from rules
            $workOrder->approvals()->delete();

            foreach ($rules as $rule) {
                WorkOrderApproval::create([
                    'work_order_id'     => $workOrder->work_order_id,
                    'approval_level'    => $rule->approval_level,
                    'department_id'     => $rule->department_id,
                    'approver_name'     => $rule->approverUser?->name,
                    'approver_position' => $rule->position_label,
                    'status'            => $rule->approval_level === 1 ? 'Pending' : 'Waiting', // Only Level 1 starts as Pending
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

    /**
     * Approve the current pending level.
     * Sequential: advances to next level, or marks SPK as Approved if last level.
     */
    public function approve(Request $request, $id)
    {
        $workOrder = WorkOrder::with('approvals')->findOrFail($id);

        if ($workOrder->status !== 'Pending Approval') {
            return redirect()->back()->with('error', 'This Work Order is not pending approval.');
        }

        // Find the current pending approval level
        $pendingApproval = $workOrder->approvals()->where('status', 'Pending')->orderBy('approval_level')->first();

        if (!$pendingApproval) {
            return redirect()->back()->with('error', 'No pending approval step found.');
        }

        // Authorize: check if the logged-in user matches the rule for this level
        $rule = ApprovalRule::activeFor('SPK')
            ->where('approval_level', $pendingApproval->approval_level)
            ->first();

        if ($rule) {
            $user = auth()->user();
            if (!$rule->canBeApprovedBy($user)) {
                return redirect()->back()->with('error', 'You are not authorized to approve this level. Required: ' . $rule->position_label . ' (' . $rule->department->name . ')');
            }
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

            // Find next level (Waiting)
            $nextApproval = $workOrder->approvals()->where('status', 'Waiting')->orderBy('approval_level')->first();

            if ($nextApproval) {
                // Advance to next level
                $nextApproval->update(['status' => 'Pending']);
                // SPK still Pending Approval
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

    /**
     * Reject the SPK — returns to Draft.
     */
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
}
