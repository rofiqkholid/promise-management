<?php

namespace App\Http\Controllers\FeasibilityStudy;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeasibilityStudy\WorkOrderRequest;
use App\Models\ApprovalConfig;
use App\Models\Department;
use App\Models\MngEbdHeader;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderProcess;
use App\Services\FeasibilityStudy\WorkOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkOrderMaterialController extends Controller
{
    protected WorkOrderService $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display a listing of SPK 2 Raw Material Specification Work Orders.
     */
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')
                ->with(['inquiry.customer', 'inquiry.projectModel', 'ebdHeader.customer', 'ebdHeader.projectModel', 'ownerDepartment', 'approvals']);

            // Global search
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('wo_number', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhereHas('inquiry.customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                      })
                      ->orWhereHas('ebdHeader.customer', function ($cq) use ($search) {
                          $cq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                      });
                });
            }

            // Priority Filter
            if ($request->filled('priority')) {
                $query->where('priority', $request->input('priority'));
            }

            // Status Filter
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $totalRecords = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->count();
            $filteredRecords = $query->count();

            $start = (int)$request->input('start', 0);
            $length = (int)$request->input('length', 10);

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

                $transformedData[] = [
                    'index_num' => $start + $idx + 1,
                    'id' => $wo->id,
                    'encrypted_id' => $this->encryptId($wo->id),
                    'hashed_id' => $this->encryptId($wo->id),
                    'wo_number' => $wo->wo_number,
                    'revision_no' => 'Rev. ' . sprintf('%02d', $wo->revision_no ?? 0),
                    'customer_code' => $customerCode,
                    'model_name' => $modelName,
                    'hidden_products' => $hiddenProducts,
                    'priority' => $wo->priority,
                    'display_status' => $wo->status,
                    'status' => $wo->status,
                    'approved_approvals' => $approvedApprovals,
                    'total_approvals' => $totalApprovals,
                    'approval_percent' => $approvalPercent,
                    'subject' => $wo->subject ?? 'SPK 2 Raw Material Specification',
                    'first_sample_date' => $wo->first_sample_date ? $wo->first_sample_date->format('d M Y') : '—',
                    'due_date_plan' => $wo->due_date_plan ? $wo->due_date_plan->format('d M Y') : '—',
                    'products' => $wo->products,
                    'dept_progress' => $deptProgress,
                    'can_edit' => $wo->status === 'Draft',
                    'show_url' => route('management.work-order-material.show', $this->encryptId($wo->id)),
                    'edit_url' => route('management.work-order-material.edit', $this->encryptId($wo->id)),
                    'destroy_url' => route('management.work-order-material.destroy', $this->encryptId($wo->id)),
                ];
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $transformedData,
            ]);
        }

        // Available EBD Headers that have items
        $ebdHeaders = MngEbdHeader::with(['customer', 'projectModel', 'items', 'workOrder'])
            ->whereHas('items')
            ->orderBy('id', 'desc')
            ->get();

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
                'revision' => $ebd->revision ?? 0,
                'items' => $ebd->items->map(function($item) use ($levelMap) {
                    $matSize = $item->mat_size ?? '';
                    if (empty($matSize)) {
                        $parts = [];
                        if (!empty($item->mat_thick) && (float)$item->mat_thick > 0) $parts[] = 't' . (float)$item->mat_thick;
                        if (!empty($item->mat_width) && (float)$item->mat_width > 0) $parts[] = (float)$item->mat_width;
                        if (!empty($item->mat_length) && (float)$item->mat_length > 0) $parts[] = (float)$item->mat_length;
                        if (count($parts) > 0) {
                            $matSize = implode(' x ', $parts);
                        } else {
                            $dimParts = [];
                            if (!empty($item->width) && (float)$item->width > 0) $dimParts[] = (float)$item->width;
                            if (!empty($item->length) && (float)$item->length > 0) $dimParts[] = (float)$item->length;
                            if (!empty($item->height) && (float)$item->height > 0) $dimParts[] = (float)$item->height;
                            if (count($dimParts) > 0) {
                                $matSize = implode(' x ', $dimParts);
                            }
                        }
                    }

                    return [
                        'id' => $item->id,
                        'bom_level' => $levelMap[$item->id] ?? '1',
                        'part_no' => $item->part_no,
                        'part_name' => $item->part_name,
                        'mat_spec' => $item->mat_spec,
                        'mat_size' => $matSize,
                        'mat_weight_pcs' => $item->mat_weight_pcs,
                        'status' => $item->status ?? '—',
                    ];
                })
            ];
        });

        $totalWo = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->count();
        $urgentWo = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->where('priority', 'Urgent')->count();
        $standardWo = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->where('priority', 'Standard')->count();
        $lowWo = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->where('priority', 'Low')->count();
        $finishedWo = WorkOrder::where('wo_type', 'SPK_2_RAW_MATERIAL')->whereIn('status', ['Approved', 'Released'])->count();
        $completionRate = $totalWo > 0 ? round(($finishedWo / $totalWo) * 100) : 0;

        return view('management.work-order.wo2-material.index', compact(
            'ebdHeaders', 'ebdHeadersData', 'totalWo', 'urgentWo', 'standardWo', 'lowWo', 'completionRate'
        ));
    }

    public function create(Request $request)
    {
        $ebdHeaderId = $request->input('ebd_header_id', $request->input('ebd_id'));
        $selectedBomIds = $request->input('selected_bom_ids', $request->input('items', []));
        $ebdHeader = null;
        $itemsData = [];

        if ($ebdHeaderId) {
            $realEbdId = $this->decryptId($ebdHeaderId) ?: $ebdHeaderId;
            $ebdHeader = MngEbdHeader::with(['customer', 'projectModel', 'items'])->find($realEbdId);

            if ($ebdHeader && $ebdHeader->items) {
                $items = $ebdHeader->items;
                if (!empty($selectedBomIds) && is_array($selectedBomIds)) {
                    $items = $items->whereIn('id', array_map('intval', $selectedBomIds));
                }

                $itemsData = $items->map(function ($item) use ($ebdHeader) {
                    $matSize = $item->mat_size ?? '';
                    if (empty($matSize)) {
                        $parts = [];
                        if (!empty($item->mat_thick) && (float)$item->mat_thick > 0) $parts[] = 't' . (float)$item->mat_thick;
                        if (!empty($item->mat_width) && (float)$item->mat_width > 0) $parts[] = (float)$item->mat_width;
                        if (!empty($item->mat_length) && (float)$item->mat_length > 0) $parts[] = (float)$item->mat_length;
                        if (count($parts) > 0) {
                            $matSize = implode(' x ', $parts);
                        } else {
                            $dimParts = [];
                            if (!empty($item->width) && (float)$item->width > 0) $dimParts[] = (float)$item->width;
                            if (!empty($item->length) && (float)$item->length > 0) $dimParts[] = (float)$item->length;
                            if (!empty($item->height) && (float)$item->height > 0) $dimParts[] = (float)$item->height;
                            if (count($dimParts) > 0) {
                                $matSize = implode(' x ', $dimParts);
                            }
                        }
                    }

                    return [
                        'id' => null,
                        'ebd_item_id' => $item->id,
                        'tempId' => 'prod_' . uniqid(),
                        'parent_id' => null,
                        'parentTempId' => null,
                        'work_order_product_id' => null,
                        'inquiry_product_id' => null,
                        'customer_code' => $ebdHeader->customer->code ?? '',
                        'model_name' => $ebdHeader->projectModel->name ?? '',
                        'customer_part_no' => $item->part_no ?? '',
                        'customer_part_name' => $item->part_name ?? '',
                        'mat_spec' => $item->mat_spec ?? '',
                        'mat_size' => $matSize,
                        'mat_weight_pcs' => $item->mat_weight_pcs ?? null,
                        'remarks' => $item->remarks ?? ''
                    ];
                })->values()->toArray();
            }
        }

        $inquiry = null;
        if ($ebdHeader && $ebdHeader->inquiry_id) {
            $inquiry = \App\Models\Inquiry::with(['customer', 'projectModel', 'products'])->find($ebdHeader->inquiry_id);
        }

        $departments = Department::orderBy('name')->get();
        $processes = WorkOrderProcess::where('is_active', true)->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();
        $users = User::orderBy('name')->get();

        // Generate next running sequence SPK number (resets yearly)
        $defaultSpkNo = WorkOrder::generateNextWoNumber();

        return view('management.work-order.wo2-material.form', compact(
            'ebdHeader', 'inquiry', 'itemsData', 'departments', 'processes', 'approvalRules', 'woHeader', 'users', 'defaultSpkNo'
        ));
    }

    /**
     * Store SPK 2 Raw Material Specification.
     */
    public function store(WorkOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $data['wo_type'] = 'SPK_2_RAW_MATERIAL';

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
                    'message' => 'SPK 2 Raw Material Specification created successfully.',
                    'redirect' => route('management.work-order-material.show', $this->encryptId($workOrder->id)),
                    'redirect_url' => route('management.work-order-material.show', $this->encryptId($workOrder->id))
                ]);
            }

            return redirect()->route('management.work-order-material.show', $this->encryptId($workOrder->id))
                ->with('success', 'SPK 2 Raw Material Specification created successfully.');
        } catch (\Exception $e) {
            Log::error('Error storing SPK 2 Raw Material Specification: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create SPK 2 Raw Material Specification: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create SPK 2 Raw Material Specification: ' . $e->getMessage());
        }
    }

    /**
     * Show detail preview of SPK 2 Raw Material Specification.
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
            'products.ebdItem',
            'approvals.department'
        ])->findOrFail($realId);

        $ebdHeader = $workOrder->ebdHeader;
        if ($ebdHeader) {
            $ebdHeader->load(['items']);
        }
        $inquiry = $workOrder->inquiry;
        $departments = Department::orderBy('name')->get();
        $processes = WorkOrderProcess::where('is_active', true)->get();
        $approvalRules = ApprovalConfig::activeFor('SPK')->get();
        $woHeader = DB::table('mng_wo_doc_format')->where('is_current', true)->first() ?: DB::table('mng_wo_doc_format')->first();
        $users = User::orderBy('name')->get();

        return view('management.work-order.wo2-material.form', compact(
            'workOrder', 'ebdHeader', 'inquiry', 'departments', 'processes', 'approvalRules', 'woHeader', 'users'
        ));
    }

    /**
     * Edit form for SPK 2 Raw Material Specification.
     */
    public function edit($id)
    {
        return $this->show($id);
    }

    /**
     * Update SPK 2 Raw Material Specification.
     */
    public function update(WorkOrderRequest $request, $id)
    {
        try {
            $realId = $this->decryptId($id) ?: $id;
            $data = $request->validated();
            $data['wo_type'] = 'SPK_2_RAW_MATERIAL';

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
                    'message' => 'SPK 2 Raw Material Specification updated successfully.',
                    'redirect' => route('management.work-order-material.show', $this->encryptId($workOrder->id)),
                    'redirect_url' => route('management.work-order-material.show', $this->encryptId($workOrder->id))
                ]);
            }

            return redirect()->route('management.work-order-material.show', $this->encryptId($workOrder->id))
                ->with('success', 'SPK 2 Raw Material Specification updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating SPK 2 Raw Material Specification: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update SPK 2 Raw Material Specification: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update SPK 2 Raw Material Specification: ' . $e->getMessage());
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
     * Delete SPK 2 Raw Material Specification.
     */
    public function destroy($id)
    {
        try {
            $realId = $this->decryptId($id) ?: $id;
            $this->workOrderService->deleteWorkOrder($realId);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SPK 2 Raw Material Specification deleted successfully.'
                ]);
            }

            return redirect()->route('management.work-order-material.index')
                ->with('success', 'SPK 2 Raw Material Specification deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting SPK 2 Raw Material Specification: ' . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete SPK 2 Raw Material Specification: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('management.work-order-material.index')
                ->with('error', 'Failed to delete SPK 2 Raw Material Specification: ' . $e->getMessage());
        }
    }
}
