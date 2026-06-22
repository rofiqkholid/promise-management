<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\Management\InquiryService;
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
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $inquiries = $this->inquiryService->paginateInquiries(1000, $filters);
        
        return view('management.inquiry.index', compact('inquiries', 'filters'));
    }

    public function create()
    {
        return redirect()->route('management.inquiry.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'inquiry_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        try {
            $inquiry = $this->inquiryService->createInquiry($validated);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'inquiry' => $inquiry
                ]);
            }
            return redirect()
                ->route('management.inquiry.show', $inquiry->inquiry_id)
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
        $inquiry = $this->inquiryService->getInquiryDetails($id);
        $scoreCategories = \App\Models\ScoreCategory::with('options')->where('is_active', true)->orderBy('sort_order', 'asc')->get();
        return view('management.inquiry.show', compact('inquiry', 'scoreCategories'));
    }

    public function edit($id)
    {
        return redirect()->route('management.inquiry.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'inquiry_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        try {
            $this->inquiryService->updateInquiry($id, $validated);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Project Inquiry successfully updated.'
                ]);
            }
            return redirect()
                ->route('management.inquiry.show', $id)
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
            
            if (!$append) {
                // Truncate existing products and assessments for this inquiry to allow clean re-imports
                $productIds = \App\Models\InquiryProduct::withTrashed()->where('inquiry_id', $id)->pluck('inquiry_product_id')->all();
                if (!empty($productIds)) {
                    $assessmentIds = \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->pluck('assessment_id')->all();
                    if (!empty($assessmentIds)) {
                        \App\Models\PriorityAssessmentDetail::whereIn('assessment_id', $assessmentIds)->delete();
                        \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->delete();
                    }
                    \App\Models\InquiryProduct::withTrashed()->where('inquiry_id', $id)->forceDelete();
                }
            }
            
            $result = $this->inquiryService->importProducts($id, $file);
            
            $products = \App\Models\InquiryProduct::where('inquiry_id', $id)->get();

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
            $inquiry = \App\Models\ProjectInquiry::findOrFail($id);
            $inquiry->status = 'Active';
            $inquiry->save();

            return response()->json([
                'success' => true,
                'redirect_url' => route('management.inquiry.show', $id)
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
            $this->inquiryService->cancelInquiry($id);
            return redirect()->back()->with('success', 'Project Inquiry successfully cancelled.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel Project Inquiry: ' . $e->getMessage());
        }
    }

    public function close($id)
    {
        try {
            $this->inquiryService->closeInquiry($id);
            return redirect()->back()->with('success', 'Project Inquiry successfully closed.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to close Project Inquiry: ' . $e->getMessage());
        }
    }

    /**
     * Handle products Excel file import.
     */
    public function import(Request $request, $id)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $append = $request->input('append') === 'true' || $request->input('append') === true;

            if (!$append) {
                // Truncate existing products and assessments for this inquiry to allow clean re-imports
                $productIds = \App\Models\InquiryProduct::withTrashed()->where('inquiry_id', $id)->pluck('inquiry_product_id')->all();
                if (!empty($productIds)) {
                    $assessmentIds = \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->pluck('assessment_id')->all();
                    if (!empty($assessmentIds)) {
                        \App\Models\PriorityAssessmentDetail::whereIn('assessment_id', $assessmentIds)->delete();
                        \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->delete();
                    }
                    \App\Models\InquiryProduct::withTrashed()->where('inquiry_id', $id)->forceDelete();
                }
            }

            $result = $this->inquiryService->importProducts($id, $file);

            if (!empty($result['errors'])) {
                return redirect()
                    ->back()
                    ->with('import_errors', $result['errors'])
                    ->with('success', "Import completed with some skipped rows. Imported count: {$result['imported_count']}");
            }

            return redirect()
                ->back()
                ->with('success', "All products successfully imported! Total imported count: {$result['imported_count']}");
        } catch (\Exception $e) {
            Log::error('Failed to import products', ['inquiry_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function assessProduct(Request $request, $id)
    {
        $request->validate([
            'selections' => 'required|array',
            'selections.*' => 'required|exists:score_options,option_id',
            'remarks' => 'nullable|string',
            'action' => 'nullable|string',
            'action_override' => 'nullable|string',
        ]);

        try {
            $product = \App\Models\InquiryProduct::findOrFail($id);
            
            // Calculate total score
            $scoreValueSum = 0;
            $options = \App\Models\ScoreOption::whereIn('option_id', $request->input('selections'))->get();
            foreach ($options as $option) {
                $scoreValueSum += $option->score_value;
            }

            // Find ranking
            $ranking = \App\Models\AssessmentRanking::where('min_score', '<=', $scoreValueSum)
                ->where('max_score', '>=', $scoreValueSum)
                ->where('is_active', true)
                ->first();

            // Create or update priority assessment
            $assessment = \App\Models\PriorityAssessment::updateOrCreate(
                ['inquiry_product_id' => $id],
                [
                    'total_score' => $scoreValueSum,
                    'ranking_id' => $ranking ? $ranking->ranking_id : null,
                    'action' => $request->input('action', 'Accept'),
                    'action_override' => $request->input('action_override'),
                    'remarks' => $request->input('remarks'),
                    'assessed_by' => auth()->user() ? auth()->user()->name : 'System',
                    'assessed_at' => now(),
                ]
            );

            // Save details
            \App\Models\PriorityAssessmentDetail::where('assessment_id', $assessment->assessment_id)->delete();
            foreach ($options as $option) {
                \App\Models\PriorityAssessmentDetail::create([
                    'assessment_id' => $assessment->assessment_id,
                    'category_id' => $option->category_id,
                    'option_id' => $option->option_id,
                    'score_snapshot' => $option->score_value,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assessment successfully completed!',
                'score' => $scoreValueSum,
                'rank' => $ranking ? $ranking->priority_label : 'N/A',
                'rank_code' => $ranking ? $ranking->rank_code : '-'
            ]);
        } catch (\Exception $e) {
            Log::error('Product assessment failed', ['product_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $validated = $request->validate([
            'model_name' => 'nullable|string|max:255',
            'customer_part_no' => 'nullable|string|max:100',
            'customer_part_name' => 'nullable|string|max:255',
            'part_category' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:100',
            'sop_date' => 'nullable|date',
            'eol_date' => 'nullable|date',
            'model_life' => 'nullable|integer',
            'annual_volume' => 'nullable|integer',
            'has_2d_data' => 'nullable|boolean',
            'has_3d_data' => 'nullable|boolean',
            'has_tech_doc' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        try {
            $product = \App\Models\InquiryProduct::findOrFail($id);
            
            // Map boolean fields explicitly in case they are sent as 1/0 or true/false
            if ($request->has('has_2d_data')) {
                $validated['has_2d_data'] = filter_var($request->input('has_2d_data'), FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->has('has_3d_data')) {
                $validated['has_3d_data'] = filter_var($request->input('has_3d_data'), FILTER_VALIDATE_BOOLEAN);
            }
            if ($request->has('has_tech_doc')) {
                $validated['has_tech_doc'] = filter_var($request->input('has_tech_doc'), FILTER_VALIDATE_BOOLEAN);
            }

            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Product update failed', ['product_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function assessPage($id)
    {
        $product = \App\Models\InquiryProduct::findOrFail($id);
        $inquiry = $product->inquiry;
        $scoreCategories = \App\Models\ScoreCategory::with('options')->where('is_active', true)->orderBy('sort_order', 'asc')->get();
        
        $selectedOptionIds = [];
        if ($product->assessment) {
            $selectedOptionIds = $product->assessment->details->pluck('option_id')->toArray();
        }

        return view('management.inquiry.assess', compact('product', 'inquiry', 'scoreCategories', 'selectedOptionIds'));
    }

    public function addProduct(Request $request, $inquiryId)
    {
        $validated = $request->validate([
            'model_name' => 'required|string|max:255',
            'customer_part_no' => 'required|string|max:100',
            'customer_part_name' => 'required|string|max:255',
            'part_category' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:100',
            'sop_date' => 'nullable|date',
            'eol_date' => 'nullable|date',
            'model_life' => 'nullable|integer',
            'annual_volume' => 'nullable|integer',
            'has_2d_data' => 'nullable|boolean',
            'has_3d_data' => 'nullable|boolean',
            'has_tech_doc' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        try {
            $validated['inquiry_id'] = $inquiryId;
            $validated['has_2d_data'] = filter_var($request->input('has_2d_data', false), FILTER_VALIDATE_BOOLEAN);
            $validated['has_3d_data'] = filter_var($request->input('has_3d_data', false), FILTER_VALIDATE_BOOLEAN);
            $validated['has_tech_doc'] = filter_var($request->input('has_tech_doc', false), FILTER_VALIDATE_BOOLEAN);

            $product = \App\Models\InquiryProduct::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product added successfully!',
                    'product' => $product
                ]);
            }

            return redirect()->back()->with('success', 'Product added successfully!');
        } catch (\Exception $e) {
            Log::error('Manual product creation failed', ['inquiry_id' => $inquiryId, 'error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
            return redirect()->back()->with('error', 'Failed to add product: ' . $e->getMessage());
        }
    }

    public function reorderProduct(Request $request, $id)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        try {
            $product = \App\Models\InquiryProduct::findOrFail($id);
            $inquiryId = $product->inquiry_id;
            
            $inquiry = $this->inquiryService->getInquiryDetails($inquiryId);
            $products = $inquiry->products;
            
            $currentIndex = null;
            foreach ($products as $index => $p) {
                if ($p->inquiry_product_id == $id) {
                    $currentIndex = $index;
                    break;
                }
            }

            if ($currentIndex === null) {
                throw new \Exception("Product not found in inquiry.");
            }

            $direction = $request->input('direction');
            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if ($targetIndex < 0 || $targetIndex >= count($products)) {
                return response()->json(['success' => true]);
            }

            $targetProduct = $products[$targetIndex];

            $scoreCurrent = $product->assessment ? $product->assessment->total_score : 0;
            $scoreTarget = $targetProduct->assessment ? $targetProduct->assessment->total_score : 0;

            if ($scoreCurrent !== $scoreTarget) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual ordering is only allowed for products with the same score.'
                ], 422);
            }

            $currentSort = $product->sort_order;
            $targetSort = $targetProduct->sort_order;

            if ($currentSort === $targetSort) {
                foreach ($products as $idx => $p) {
                    $p->sort_order = $idx * 10;
                    $p->save();
                }
                $product = \App\Models\InquiryProduct::find($id);
                $targetProduct = \App\Models\InquiryProduct::find($targetProduct->inquiry_product_id);
                $currentSort = $product->sort_order;
                $targetSort = $targetProduct->sort_order;
            }

            $product->sort_order = $targetSort;
            $product->save();

            $targetProduct->sort_order = $currentSort;
            $targetProduct->save();

            return response()->json([
                'success' => true,
                'message' => 'Reordered successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new InquiryTemplateExport, 'inquiry_products_template.xlsx');
    }

    public function destroy($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function() use ($id) {
                $inquiry = \App\Models\ProjectInquiry::withTrashed()->findOrFail($id);
                
                // Get related product IDs (including soft-deleted ones)
                $productIds = $inquiry->products()->withTrashed()->pluck('inquiry_product_id')->all();

                if (!empty($productIds)) {
                    // Delete related assessment details & assessments
                    $assessmentIds = \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->pluck('assessment_id')->all();
                    if (!empty($assessmentIds)) {
                        \App\Models\PriorityAssessmentDetail::whereIn('assessment_id', $assessmentIds)->delete();
                        \App\Models\PriorityAssessment::whereIn('inquiry_product_id', $productIds)->delete();
                    }
                    // Hard delete products
                    $inquiry->products()->withTrashed()->forceDelete();
                }

                // Hard delete inquiry header
                $inquiry->forceDelete();
            });

            return redirect()->route('management.inquiry.index')->with('success', 'Project Inquiry and its data successfully deleted.');
        } catch (\Exception $e) {
            Log::error('Failed to delete inquiry', ['inquiry_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to delete Project Inquiry: ' . $e->getMessage());
        }
    }
}
