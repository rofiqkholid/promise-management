<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ScoreCategory;
use App\Models\ScoreOption;
use App\Models\AssessmentRanking;
use Illuminate\Http\Request;

class AssessmentConfigController extends Controller
{
    public function index()
    {
        $categories = ScoreCategory::with('options')->orderBy('sort_order', 'asc')->get();
        $rankings = AssessmentRanking::orderBy('sort_order', 'asc')->get();
        return view('management.assessment-config.index', compact('categories', 'rankings'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|unique:score_categories,category_code',
            'category_name' => 'required|string',
            'sort_order' => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        ScoreCategory::create($validated);
        return redirect()->back()->with('success', 'Score Category successfully created.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = ScoreCategory::findOrFail($id);
        $validated = $request->validate([
            'category_code' => 'required|string|unique:score_categories,category_code,' . $id . ',category_id',
            'category_name' => 'required|string',
            'sort_order' => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $category->update($validated);
        return redirect()->back()->with('success', 'Score Category successfully updated.');
    }

    public function storeOption(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:score_categories,category_id',
            'option_name' => 'required|string',
            'score_value' => 'required|integer',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer',
        ]);

        ScoreOption::create($validated);
        return redirect()->back()->with('success', 'Score Option successfully created.');
    }

    public function updateOption(Request $request, $id)
    {
        $option = ScoreOption::findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'required|exists:score_categories,category_id',
            'option_name' => 'required|string',
            'score_value' => 'required|integer',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer',
        ]);

        $option->update($validated);
        return redirect()->back()->with('success', 'Score Option successfully updated.');
    }

    public function storeRanking(Request $request)
    {
        $validated = $request->validate([
            'rank_code' => 'required|string',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer',
            'priority_label' => 'required|string',
            'recommendation' => 'nullable|string',
            'sort_order' => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        AssessmentRanking::create($validated);
        return redirect()->back()->with('success', 'Assessment Ranking successfully created.');
    }

    public function updateRanking(Request $request, $id)
    {
        $ranking = AssessmentRanking::findOrFail($id);
        $validated = $request->validate([
            'rank_code' => 'required|string',
            'min_score' => 'required|integer',
            'max_score' => 'required|integer',
            'priority_label' => 'required|string',
            'recommendation' => 'nullable|string',
            'sort_order' => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $ranking->update($validated);
        return redirect()->back()->with('success', 'Assessment Ranking successfully updated.');
    }
}
