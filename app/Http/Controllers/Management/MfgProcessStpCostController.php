<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\MfgProcessStpCost;
use Illuminate\Http\Request;

class MfgProcessStpCostController extends Controller
{
    /**
     * Display a listing of Stamping Manufacturing Process Costs.
     */
    public function index(Request $request)
    {
        $machineTypes = MfgProcessStpCost::select('machine_type')->distinct()->pluck('machine_type')->filter()->values();
        if ($machineTypes->isEmpty()) {
            $machineTypes = collect(['Tandem', 'Transfer', 'Progressive', 'Manual']);
        }

        $machineCategories = MfgProcessStpCost::select('machine_category')->distinct()->pluck('machine_category')->filter()->values();
        if ($machineCategories->isEmpty()) {
            $machineCategories = collect(['Small', 'Medium', 'Large']);
        }

        $outputTypes = MfgProcessStpCost::select('output_type')->distinct()->pluck('output_type')->filter()->values();
        if ($outputTypes->isEmpty()) {
            $outputTypes = collect(['Part', 'Cavity', 'Process']);
        }

        $complexities = MfgProcessStpCost::select('process_complexity')->distinct()->pluck('process_complexity')->filter()->values();
        if ($complexities->isEmpty()) {
            $complexities = collect(['Inner', 'Deep Draw', 'Outer Panel']);
        }

        $rateSources = ['Engineering', 'Sales'];

        // Calculate KPI summary stats
        $totalItems = MfgProcessStpCost::count();
        $smallCount = MfgProcessStpCost::where('machine_category', 'Small')->count();
        $mediumCount = MfgProcessStpCost::where('machine_category', 'Medium')->count();
        $largeCount = MfgProcessStpCost::where('machine_category', 'Large')->count();

        return view('management.mfg-process-stp-cost.index', compact(
            'machineTypes',
            'machineCategories',
            'outputTypes',
            'complexities',
            'rateSources',
            'totalItems',
            'smallCount',
            'mediumCount',
            'largeCount'
        ));
    }

    /**
     * Server-side Datatables JSON endpoint.
     */
    public function data(Request $request)
    {
        $draw = intval($request->input('draw', 1));
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));

        $totalRecords = MfgProcessStpCost::count();

        $query = MfgProcessStpCost::query();

        // Filters
        if ($request->filled('machine_type')) {
            $query->where('machine_type', $request->machine_type);
        }

        if ($request->filled('machine_category')) {
            $query->where('machine_category', $request->machine_category);
        }

        if ($request->filled('output_type')) {
            $query->where('output_type', $request->output_type);
        }

        if ($request->filled('process_complexity')) {
            $query->where('process_complexity', $request->process_complexity);
        }

        if ($request->filled('rate_source')) {
            $query->where('rate_source', $request->rate_source);
        }

        // Global Search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('machine_type', 'like', "%{$searchValue}%")
                  ->orWhere('tonnage', 'like', "%{$searchValue}%")
                  ->orWhere('machine_category', 'like', "%{$searchValue}%")
                  ->orWhere('output_type', 'like', "%{$searchValue}%")
                  ->orWhere('process_complexity', 'like', "%{$searchValue}%")
                  ->orWhere('complexity_alias', 'like', "%{$searchValue}%")
                  ->orWhere('rate_source', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // Sorting map
        $columnsMap = [
            0 => 'id',
            1 => 'machine_type',
            2 => 'tonnage',
            3 => 'machine_category',
            4 => 'output_type',
            5 => 'output_qty',
            6 => 'stroke',
            7 => 'process_complexity',
            8 => 'complexity_alias',
            9 => 'min_cost_rate',
            10 => 'std_cost_rate',
            11 => 'rate_source',
        ];

        $orderColumnIndex = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'asc');

        if (isset($columnsMap[$orderColumnIndex])) {
            $query->orderBy($columnsMap[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('id', 'asc');
        }

        // Pagination
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $items = $query->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created Stamping Manufacturing Process Cost.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_type' => 'required|string|max:100',
            'tonnage' => 'required|integer|min:1',
            'machine_category' => 'nullable|string|max:100',
            'output_type' => 'required|string|max:100',
            'output_qty' => 'required|integer|min:1',
            'stroke' => 'required|numeric|min:0.01',
            'process_complexity' => 'required|string|max:100',
            'complexity_alias' => 'nullable|string|max:100',
            'min_cost_rate' => 'nullable|numeric|min:0',
            'std_cost_rate' => 'required|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        MfgProcessStpCost::create($validated);

        return redirect()->route('management.mfg-process-stp-cost.index')
            ->with('success', 'Stamping Process Cost Rate successfully created!');
    }

    /**
     * Update the specified Stamping Process Cost.
     */
    public function update(Request $request, $id)
    {
        $item = MfgProcessStpCost::findOrFail($id);

        $validated = $request->validate([
            'machine_type' => 'required|string|max:100',
            'tonnage' => 'required|integer|min:1',
            'machine_category' => 'nullable|string|max:100',
            'output_type' => 'required|string|max:100',
            'output_qty' => 'required|integer|min:1',
            'stroke' => 'required|numeric|min:0.01',
            'process_complexity' => 'required|string|max:100',
            'complexity_alias' => 'nullable|string|max:100',
            'min_cost_rate' => 'nullable|numeric|min:0',
            'std_cost_rate' => 'required|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $item->update($validated);

        return redirect()->route('management.mfg-process-stp-cost.index')
            ->with('success', 'Stamping Process Cost Rate successfully updated!');
    }

    /**
     * Remove the specified Stamping Process Cost.
     */
    public function destroy($id)
    {
        $item = MfgProcessStpCost::findOrFail($id);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Stamping Process Cost Rate deleted successfully.']);
        }

        return redirect()->route('management.mfg-process-stp-cost.index')
            ->with('success', 'Stamping Process Cost Rate deleted successfully!');
    }

    /**
     * Export Stamping Manufacturing Process Costs as CSV.
     */
    public function export()
    {
        $fileName = 'Mfg_Stamping_Process_Cost_' . date('Y-m-d_H-i-s') . '.csv';
        $items = MfgProcessStpCost::orderBy('id', 'asc')->get();

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array(
            'No', 'Machine Type', 'Tonnage', 'Machine Category',
            'Output Type', 'Output Qty', 'Stroke', 'Process Complexity',
            'Complexity Alias (Part Rank)', 'Min Cost Rate', 'Std Cost Rate', 'Rate Source'
        );

        $callback = function () use ($items, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($items as $item) {
                fputcsv($file, array(
                    $no++,
                    $item->machine_type,
                    $item->tonnage,
                    $item->machine_category,
                    $item->output_type,
                    $item->output_qty,
                    $item->stroke,
                    $item->process_complexity,
                    $item->complexity_alias ?? '-',
                    $item->min_cost_rate !== null ? number_format($item->min_cost_rate, 2, '.', '') : '-',
                    number_format($item->std_cost_rate, 2, '.', ''),
                    $item->rate_source
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
