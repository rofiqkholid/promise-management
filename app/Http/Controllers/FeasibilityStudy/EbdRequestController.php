<?php

namespace App\Http\Controllers\FeasibilityStudy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\MngEbdRequest;
use App\Models\MngEbdHeader;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\ProjectModel;

class EbdRequestController extends Controller
{
    // =========================================================================
    // INDEX — List all EBD Requests
    // =========================================================================

    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $search = $request->input('search.value');
            $statusFilter = $request->input('status');

            $query = MngEbdRequest::with([
                'workOrder',
                'customer',
                'projectModel',
                'baseEbd',
                'revisedEbd'
            ]);

            if ($statusFilter && $statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('request_no', 'like', "%{$search}%")
                      ->orWhere('request_type', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('requested_by', 'like', "%{$search}%")
                      ->orWhereHas('workOrder', fn($wq) => $wq->where('wo_number', 'like', "%{$search}%"))
                      ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                      ->orWhereHas('projectModel', fn($mq) => $mq->where('name', 'like', "%{$search}%"));
                });
            }

            $totalRecords = MngEbdRequest::count();
            $filteredRecords = $query->count();

            $orderColumnIndex = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');

            $sortableColumns = [
                1 => 'request_no',
                2 => 'request_date',
                6 => 'request_type',
                7 => 'status',
            ];

            if (isset($sortableColumns[$orderColumnIndex])) {
                $query->orderBy($sortableColumns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $requests = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($requests as $item) {
                $attachmentUrl = null;
                if ($item->attachment_path) {
                    $attachmentUrl = asset('storage/' . $item->attachment_path);
                }

                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'id' => $item->id,
                    'request_no' => $item->request_no,
                    'request_date' => $item->request_date ? $item->request_date->format('d M Y') : '—',
                    'request_date_raw' => $item->request_date ? $item->request_date->format('Y-m-d') : '',
                    'wo_id' => $item->wo_id,
                    'wo_number' => $item->workOrder->wo_number ?? '—',
                    'customer_id' => $item->customer_id,
                    'customer_code' => $item->customer->code ?? '—',
                    'customer_name' => $item->customer->name ?? '—',
                    'model_id' => $item->model_id,
                    'model_name' => $item->projectModel->name ?? '—',
                    'base_ebd_id' => $item->ebd_header_id,
                    'base_ebd_revision' => $item->baseEbd ? 'Rev. ' . $item->baseEbd->revision : '—',
                    'base_ebd_url' => $item->ebd_header_id ? route('management.ebd.show', $item->ebd_header_id) : null,
                    'revised_ebd_id' => $item->revised_ebd_id,
                    'revised_ebd_revision' => $item->revisedEbd ? 'Rev. ' . $item->revisedEbd->revision : null,
                    'revised_ebd_url' => $item->revised_ebd_id ? route('management.ebd.show', $item->revised_ebd_id) : null,
                    'request_type' => $item->request_type,
                    'description' => $item->description,
                    'attachment_path' => $item->attachment_path,
                    'attachment_url' => $attachmentUrl,
                    'status' => $item->status,
                    'rejection_note' => $item->rejection_note,
                    'requested_by' => $item->requested_by ?? '—',
                    'processed_by' => $item->processed_by ?? '—',
                    'processed_at' => $item->processed_at ? $item->processed_at->format('d M Y H:i') : '—',
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        $workOrders = WorkOrder::with(['inquiry' => function($q) {
            $q->select('id', 'customer_id', 'model_id');
        }])->select('id', 'wo_number', 'inquiry_id')->orderByDesc('id')->get();
        
        $customers  = Customer::select('id', 'name', 'code')->orderBy('name')->get();
        $models     = ProjectModel::select('id', 'name')->orderBy('name')->get()->unique('name');

        $counts = [
            'all'         => MngEbdRequest::count(),
            'submitted'   => MngEbdRequest::where('status', 'Submitted')->count(),
            'in_progress' => MngEbdRequest::where('status', 'In Progress')->count(),
            'completed'   => MngEbdRequest::where('status', 'Completed')->count(),
            'rejected'    => MngEbdRequest::where('status', 'Rejected')->count(),
        ];

        $defaultRequestNo = MngEbdRequest::generateNextRequestNo();

        return view('management.ebd-request.index', compact('workOrders', 'customers', 'models', 'counts', 'defaultRequestNo'));
    }

    // =========================================================================
    // GET EBD BY WO / CUSTOMER / MODEL — AJAX Lookup
    // =========================================================================

    public function getEbdByWo(Request $request, $woId = null)
    {
        $customerId = $request->input('customer_id');
        $modelId = $request->input('model_id');

        $activeEbd = null;
        $workOrder = null;

        if ($woId && $woId !== 'null') {
            $workOrder = WorkOrder::with(['inquiry.customer', 'inquiry.projectModel'])->find($woId);
            $activeEbd = MngEbdHeader::with(['customer', 'projectModel'])
                ->where('wo_id', $woId)
                ->where('is_latest', true)
                ->first();

            if (!$activeEbd && $workOrder && $workOrder->inquiry) {
                $customerId = $customerId ?: $workOrder->inquiry->customer_id;
                $modelId = $modelId ?: $workOrder->inquiry->model_id;
            }
        }

        if (!$activeEbd && $customerId && $modelId) {
            $activeEbd = MngEbdHeader::with(['customer', 'projectModel'])
                ->where('customer_id', $customerId)
                ->where('model_id', $modelId)
                ->where('is_latest', true)
                ->first();
        }

        return response()->json([
            'success' => true,
            'has_ebd' => !empty($activeEbd),
            'ebd_id' => $activeEbd->id ?? null,
            'ebd_revision' => $activeEbd ? 'Rev. ' . $activeEbd->revision : null,
            'ebd_date' => $activeEbd && $activeEbd->date ? $activeEbd->date->format('d M Y') : null,
            'customer_id' => $activeEbd->customer_id ?? ($workOrder->inquiry->customer_id ?? null),
            'customer_code' => $activeEbd->customer->code ?? ($workOrder->inquiry->customer->code ?? null),
            'customer_name' => $activeEbd->customer->name ?? ($workOrder->inquiry->customer->name ?? null),
            'model_id' => $activeEbd->model_id ?? ($workOrder->inquiry->model_id ?? null),
            'model_name' => $activeEbd->projectModel->name ?? ($workOrder->inquiry->projectModel->name ?? null),
        ]);
    }

    // =========================================================================
    // STORE — Create New EBD Request
    // =========================================================================

    public function store(Request $request)
    {
        $request->validate([
            'wo_id'           => 'nullable|integer',
            'customer_id'     => 'required|integer',
            'model_id'        => 'required|integer',
            'ebd_header_id'   => 'nullable|integer',
            'request_date'    => 'required|date',
            'request_type'    => 'required|string|max:100',
            'description'     => 'required|string|max:2000',
            'attachment_file' => 'nullable|file|max:20480',
        ]);

        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment_file')) {
                $file = $request->file('attachment_file');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('ebd-requests', $filename, 'public');
            }

            $requestNo = MngEbdRequest::generateNextRequestNo();

            // If ebd_header_id not provided, attempt to find latest matching EBD
            $ebdHeaderId = $request->input('ebd_header_id');
            if (!$ebdHeaderId) {
                $latest = MngEbdHeader::where('customer_id', $request->input('customer_id'))
                    ->where('model_id', $request->input('model_id'))
                    ->where('is_latest', true)
                    ->first();
                $ebdHeaderId = $latest->id ?? null;
            }

            $ebdReq = MngEbdRequest::create([
                'request_no'      => $requestNo,
                'wo_id'           => $request->input('wo_id') ?: null,
                'customer_id'     => $request->input('customer_id'),
                'model_id'        => $request->input('model_id'),
                'ebd_header_id'   => $ebdHeaderId,
                'request_date'    => $request->input('request_date'),
                'request_type'    => $request->input('request_type'),
                'description'     => $request->input('description'),
                'attachment_path' => $attachmentPath,
                'status'          => 'Submitted',
                'requested_by'    => Auth::user()->name ?? Auth::user()->username ?? 'Sales / Marketing',
            ]);

            return response()->json([
                'success' => true,
                'message' => "EBD Request {$requestNo} submitted successfully!",
                'id' => $ebdReq->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create EBD Request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE — Edit Request (Only if still Submitted)
    // =========================================================================

    public function update(Request $request, $id)
    {
        $request->validate([
            'wo_id'           => 'nullable|exists:mng_wo_orders,id',
            'customer_id'     => 'required|exists:mng_customers,id',
            'model_id'        => 'required|exists:mng_models,id',
            'request_date'    => 'required|date',
            'request_type'    => 'required|string|max:100',
            'description'     => 'required|string|max:2000',
            'attachment_file' => 'nullable|file|max:20480',
        ]);

        try {
            $ebdReq = MngEbdRequest::findOrFail($id);

            if ($ebdReq->status !== 'Submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a request that is already in progress or completed.'
                ], 422);
            }

            if ($request->hasFile('attachment_file')) {
                $file = $request->file('attachment_file');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
                $ebdReq->attachment_path = $file->storeAs('ebd-requests', $filename, 'public');
            }

            $ebdHeaderId = $request->input('ebd_header_id');
            if (!$ebdHeaderId) {
                $latest = MngEbdHeader::where('customer_id', $request->input('customer_id'))
                    ->where('model_id', $request->input('model_id'))
                    ->where('is_latest', true)
                    ->first();
                $ebdHeaderId = $latest->id ?? null;
            }

            $ebdReq->wo_id         = $request->input('wo_id') ?: null;
            $ebdReq->customer_id   = $request->input('customer_id');
            $ebdReq->model_id      = $request->input('model_id');
            $ebdReq->ebd_header_id = $ebdHeaderId;
            $ebdReq->request_date  = $request->input('request_date');
            $ebdReq->request_type  = $request->input('request_type');
            $ebdReq->description   = $request->input('description');
            $ebdReq->save();

            return response()->json([
                'success' => true,
                'message' => "EBD Request {$ebdReq->request_no} updated successfully!"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update EBD Request: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update request: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE STATUS — Change Request Status
    // =========================================================================

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'         => 'required|in:Submitted,In Progress,Completed,Rejected',
            'rejection_note' => 'required_if:status,Rejected|nullable|string|max:1000',
        ]);

        try {
            $ebdReq = MngEbdRequest::findOrFail($id);
            $ebdReq->status = $request->input('status');
            $ebdReq->processed_by = Auth::user()->name ?? Auth::user()->username ?? 'Engineering';
            $ebdReq->processed_at = now();

            if ($request->input('status') === 'Rejected') {
                $ebdReq->rejection_note = $request->input('rejection_note');
            }

            $ebdReq->save();

            return response()->json([
                'success' => true,
                'message' => "Request status updated to {$ebdReq->status} successfully!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — Delete Request
    // =========================================================================

    public function destroy($id)
    {
        try {
            $ebdReq = MngEbdRequest::findOrFail($id);
            $ebdReq->delete();

            return response()->json([
                'success' => true,
                'message' => 'EBD Request deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete request: ' . $e->getMessage()
            ], 500);
        }
    }
}
