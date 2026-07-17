<?php

namespace App\Http\Controllers\FeasibilityStudy;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeasibilityStudy\InquiryRequest;
use App\Services\FeasibilityStudy\InquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exports\InquiryTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class InquiryController extends Controller
{
    protected $inquiryService;

    public function __construct(InquiryService $inquiryService)
    {
        $this->inquiryService = $inquiryService;
    }

    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            // Custom filters & Search
            $status = $request->input('status');
            $search = $request->input('search.value');
            
            $query = \App\Models\ProjectInquiry::with(['customer', 'projectModel', 'products', 'workOrders']);
            
            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }
            
            // Apply search filter (search by inquiry_no, project_name, customer_code, customer_name)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('inquiry_no', 'like', "%{$search}%")
                      ->orWhere('project_name', 'like', "%{$search}%")
                      ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                      ->orWhereHas('projectModel', fn($mq) => $mq->where('name', 'like', "%{$search}%"));
                });
            }
            
            // Get total records
            $totalRecords = \App\Models\ProjectInquiry::count();
            $filteredRecords = $query->count();
            
            // Apply sorting
            $orderColumnIndex = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $sortableColumns = [
                1 => 'inquiry_no',
                2 => 'inquiry_date',
                3 => 'project_name',
                4 => 'status'
            ];
            
            if (isset($sortableColumns[$orderColumnIndex])) {
                $query->orderBy($sortableColumns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            // Paginate
            $inquiries = $query->skip($start)->take($length)->get();
            
            $data = [];
            foreach ($inquiries as $inq) {
                $wos = $inq->workOrders->map(function($wo) {
                    return [
                        'wo_number' => $wo->wo_number,
                        'show_url' => route('management.work-order.show', $this->encryptId($wo->id))
                    ];
                })->unique('wo_number')->values()->all();

                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'id' => $inq->id,
                    'customer_id' => $inq->customer_id,
                    'project_id' => $inq->model_id,
                    'remarks' => $inq->remarks,
                    'inquiry_date_raw' => $inq->inquiry_date ? $inq->inquiry_date->format('Y-m-d') : '',
                    'inquiry_no' => $inq->inquiry_no,
                    'inquiry_date' => $inq->inquiry_date ? $inq->inquiry_date->format('d M Y') : '—',
                    'customer_code' => $inq->customer->code ?? '—',
                    'customer_name' => $inq->customer->name ?? '—',
                    'project_name' => $inq->project_name ?? $inq->projectModel->name ?? '—',
                    'products_count' => $inq->products->count(),
                    'status' => $inq->status,
                    'work_orders' => $wos,
                    'hashed_id' => $this->encryptId($inq->id),
                    'show_url' => route('management.inquiry.show', $this->encryptId($inq->id))
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        $customers = \App\Models\Customer::where('is_active', 1)->orderBy('name', 'asc')->get();
        $models = \App\Models\ProjectModel::orderBy('name', 'asc')->get();
        
        $categories = \App\Models\ScoreCategory::with('options')->orderBy('sort_order', 'asc')->get();
        $rankings = \App\Models\AssessmentRanking::orderBy('sort_order', 'asc')->get();
        
        return view('management.inquiry.index', compact('customers', 'models', 'categories', 'rankings'));
    }

    public function create()
    {
        return redirect()->route('management.inquiry.index');
    }

    public function store(InquiryRequest $request)
    {
        $validated = $request->validated();

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $model = \App\Models\ProjectModel::find($projectId);
                if ($model) {
                    $validated['project_name'] = $model->name;
                }
            }

            // Map form field 'project_id' → DB column 'model_id'
            $validated['model_id'] = $validated['project_id'];
            unset($validated['project_id']);

            $inquiry = $this->inquiryService->createInquiry($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'inquiry' => $inquiry
                ]);
            }
            return redirect()
                ->route('management.inquiry.show', $this->encryptId($inquiry->id))
                ->with('success', 'Project Inquiry successfully created! You can now import products.');
        } catch (\Exception $e) {
            Log::error('Failed to create inquiry', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Failed to create Project Inquiry: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $decryptedId = $this->decryptId($id);
        $inquiry = $this->inquiryService->getInquiryDetails($decryptedId);
        $scoreCategories = \App\Models\ScoreCategory::with('options')->where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $customers = \App\Models\Customer::where('is_active', 1)->orderBy('name', 'asc')->get();
        $models = \App\Models\ProjectModel::orderBy('name', 'asc')->get();
        
        $categories = \App\Models\ScoreCategory::with('options')->orderBy('sort_order', 'asc')->get();
        $rankings = \App\Models\AssessmentRanking::orderBy('sort_order', 'asc')->get();
        $reviewedProductsList = \App\Models\InqReviewedProduct::orderBy('reviewer', 'asc')->get();
        
        return view('management.inquiry.show', compact('inquiry', 'scoreCategories', 'customers', 'models', 'categories', 'rankings', 'reviewedProductsList'));
    }

    public function edit($id)
    {
        return redirect()->route('management.inquiry.index');
    }

    public function update(InquiryRequest $request, $id)
    {
        $validated = $request->validated();

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $model = \App\Models\ProjectModel::find($projectId);
                if ($model) {
                    $validated['project_name'] = $model->name;
                }
            }

            // Map form field 'project_id' → DB column 'model_id'
            $validated['model_id'] = $validated['project_id'];
            unset($validated['project_id']);

            $decryptedId = $this->decryptId($id);
            $this->inquiryService->updateInquiry($decryptedId, $validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Project Inquiry successfully updated.'
                ]);
            }
            return redirect()
                ->route('management.inquiry.show', $this->encryptId($decryptedId))
                ->with('success', 'Project Inquiry successfully updated.');
        } catch (\Exception $e) {
            Log::error('Failed to update inquiry', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Failed to update Project Inquiry: ' . $e->getMessage());
        }
    }

    public function parseExcel(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $append = $request->input('append') === 'true' || $request->input('append') === true;
            $decryptedId = $this->decryptId($id);
            
            if ($append) {
                // Truncate products inside database transaction
                $this->inquiryService->forceDeleteInquiryWithProducts($decryptedId);
            }
            
            $result = $this->inquiryService->importProducts($decryptedId, $file);
            $products = \App\Models\InquiryProduct::with('inquiry.projectModel')->where('inquiry_id', $decryptedId)->get();

            return response()->json([
                'success' => true,
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors'],
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to parse excel', ['inquiry_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function finalize(Request $request, $id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $inquiry = $this->inquiryService->finalizeInquiry($decryptedId);

            return response()->json([
                'success' => true,
                'redirect_url' => route('management.inquiry.show', $this->encryptId($inquiry->id))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function cancel($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->inquiryService->cancelInquiry($decryptedId);
            return redirect()->back()->with('success', 'Project Inquiry successfully cancelled.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel Project Inquiry: ' . $e->getMessage());
        }
    }

    public function close($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->inquiryService->closeInquiry($decryptedId);
            return redirect()->back()->with('success', 'Project Inquiry successfully closed.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to close Project Inquiry: ' . $e->getMessage());
        }
    }

    public function import(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $append = $request->input('append') === 'true' || $request->input('append') === true;
            $decryptedId = $this->decryptId($id);

            if ($append) {
                // Truncate products inside database transaction
                $this->inquiryService->forceDeleteInquiryWithProducts($decryptedId);
            }

            $result = $this->inquiryService->importProducts($decryptedId, $file);
            
            if (!empty($result['errors'])) {
                return redirect()->back()
                    ->with('import_errors', $result['errors'])
                    ->with('import_success', 'Import completed with warnings/errors.');
            }
            
            return redirect()->back()->with('import_success', 'Products successfully imported!');
        } catch (\Exception $e) {
            Log::error('Import products failed', ['inquiry_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new InquiryTemplateExport, 'Sai_Inquiry_Product_Template.xlsx');
    }

    public function addProduct(Request $request, $inquiryId)
    {
        $validated = $request->validate([
            'customer_part_no'   => 'required|string|max:100',
            'customer_part_name' => 'required|string|max:255',
            'part_category'      => 'nullable|string|max:100',
            'destination'        => 'nullable|string|max:100',
            'sop_date'           => 'nullable|date',
            'eol_date'           => 'nullable|date',
            'model_life'         => 'nullable|integer',
            'annual_volume'      => 'nullable|integer',
            'has_2d_data'        => 'nullable|boolean',
            'has_3d_data'        => 'nullable|boolean',
            'has_tech_doc'       => 'nullable|boolean',
            'variant'            => 'nullable|string|max:100',
            'remarks'            => 'nullable|string',
            'forex'              => 'nullable|string|max:100',
            'material_condition' => 'nullable|string|max:100',
            'decision'           => 'nullable|string|max:50',
            'reviewed_product_id'=> 'nullable|integer',
        ]);

        try {
            $decryptedId = $this->decryptId($inquiryId);
            $product = $this->inquiryService->addProduct($decryptedId, $validated);

            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function showProduct($productId)
    {
        try {
            $product = $this->inquiryService->findProductById($productId);
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateProduct(Request $request, $productId)
    {
        $validated = $request->validate([
            'customer_part_no'   => 'sometimes|required|string|max:100',
            'customer_part_name' => 'sometimes|required|string|max:255',
            'part_category'      => 'nullable|string|max:100',
            'destination'        => 'nullable|string|max:100',
            'sop_date'           => 'nullable|date',
            'eol_date'           => 'nullable|date',
            'model_life'         => 'nullable|integer',
            'annual_volume'      => 'nullable|integer',
            'has_2d_data'        => 'nullable|boolean',
            'has_3d_data'        => 'nullable|boolean',
            'has_tech_doc'       => 'nullable|boolean',
            'variant'            => 'nullable|string|max:100',
            'remarks'            => 'nullable|string',
            'forex'              => 'nullable|string|max:100',
            'material_condition' => 'nullable|string|max:100',
            'decision'           => 'nullable|string|max:50',
            'reviewed_product_id'=> 'nullable|integer',
        ]);

        try {
            $product = $this->inquiryService->updateProduct($productId, $validated);

            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            $this->inquiryService->deleteProduct($productId);
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function assessProduct(Request $request, $productId)
    {
        $validated = $request->validate([
            'selections' => 'required|array',
            'remarks'    => 'nullable|string',
        ]);

        try {
            $selections = [];
            foreach ($request->input('selections', []) as $optionId) {
                $option = \App\Models\ScoreOption::findOrFail($optionId);
                $selections[] = [
                    'category_id' => $option->category_id,
                    'option_id'   => $option->id,
                    'score_value' => $option->score_value,
                ];
            }

            $totalScore = array_sum(array_column($selections, 'score_value'));
            $assessmentData = [
                'total_score' => $totalScore,
                'remarks'     => $request->input('remarks'),
            ];

            $assessment = $this->inquiryService->savePriorityAssessment($productId, $assessmentData, $selections);

            return response()->json([
                'success' => true,
                'assessment' => $assessment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function assessPage($id)
    {
        $decryptedId = $this->decryptId($id);
        $product = \App\Models\InquiryProduct::with('inquiry')->findOrFail($decryptedId);
        $inquiry = $product->inquiry;
        $scoreCategories = \App\Models\ScoreCategory::with('options')->where('is_active', true)->orderBy('sort_order', 'asc')->get();
        $rankings = \App\Models\AssessmentRanking::where('is_active', true)->orderBy('min_score', 'asc')->get();

        return view('management.inquiry.assess', compact('product', 'inquiry', 'scoreCategories', 'rankings'));
    }

    public function reorderAll(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        try {
            $this->inquiryService->updateProductsOrder($request->input('ids'));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateDecisionsBatch(Request $request)
    {
        $validated = $request->validate([
            'decisions' => 'nullable|array',
            'decisions.*' => 'nullable|string|max:50',
            'reviewed' => 'nullable|array',
            'reviewed.*' => 'nullable|integer'
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
                if (!empty($validated['decisions'])) {
                    foreach ($validated['decisions'] as $id => $decision) {
                        $product = \App\Models\InquiryProduct::findOrFail($id);
                        $product->update(['decision' => $decision]);
                    }
                }
                if (!empty($validated['reviewed'])) {
                    foreach ($validated['reviewed'] as $id => $reviewedId) {
                        $product = \App\Models\InquiryProduct::findOrFail($id);
                        $product->update(['reviewed_product_id' => $reviewedId ?: null]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Product updates saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $decryptedId = $this->decryptId($id);
            $this->inquiryService->forceDeleteInquiryWithProducts($decryptedId);

            return redirect()->route('management.inquiry.index')->with('success', 'Project Inquiry and its data successfully deleted.');
        } catch (\Exception $e) {
            Log::error('Failed to delete inquiry', ['inquiry_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Project Inquiry: ' . $e->getMessage());
        }
    }
}
