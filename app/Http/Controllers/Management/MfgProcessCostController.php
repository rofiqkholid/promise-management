<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\MfgProcessCost;
use Illuminate\Http\Request;

class MfgProcessCostController extends Controller
{
    /**
     * Display a listing of Manufacturing Process Costs.
     */
    public function index(Request $request)
    {
        $categories = ['Product', 'Tooling'];
        $processGroups = MfgProcessCost::select('process_group')->distinct()->pluck('process_group')->filter()->values();
        $rateSources = ['Engineering', 'Sales'];

        // Calculate KPI summary stats
        $totalItems = MfgProcessCost::count();
        $productCount = MfgProcessCost::where('category', 'Product')->count();
        $toolingCount = MfgProcessCost::where('category', 'Tooling')->count();
        $groupCount = $processGroups->count();

        return view('management.mfg-process-cost.index', compact(
            'categories',
            'processGroups',
            'rateSources',
            'totalItems',
            'productCount',
            'toolingCount',
            'groupCount'
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

        $totalRecords = MfgProcessCost::count();

        $query = MfgProcessCost::query();

        // Dropdown Filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('process_group')) {
            $query->where('process_group', $request->process_group);
        }

        if ($request->filled('rate_source')) {
            $query->where('rate_source', $request->rate_source);
        }

        // Global Search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('category', 'like', "%{$searchValue}%")
                  ->orWhere('process_group', 'like', "%{$searchValue}%")
                  ->orWhere('process_name', 'like', "%{$searchValue}%")
                  ->orWhere('control_point', 'like', "%{$searchValue}%")
                  ->orWhere('uom', 'like', "%{$searchValue}%")
                  ->orWhere('rate_unit', 'like', "%{$searchValue}%")
                  ->orWhere('rate_source', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        // Sorting map matching DataTables column indices (0-10)
        $columnsMap = [
            0 => 'id',
            1 => 'category',
            2 => 'process_group',
            3 => 'process_name',
            4 => 'control_point',
            5 => 'uom',
            6 => 'rate_unit',
            7 => 'min_cost_rate',
            8 => 'std_cost_rate',
            9 => 'rate_source',
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
     * Store a newly created Manufacturing Process Cost.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:Product,Tooling',
            'process_group' => 'required|string|max:100',
            'process_name' => 'required|string|max:150',
            'control_point' => 'nullable|string|max:150',
            'uom' => 'nullable|string|max:50',
            'rate_unit' => 'nullable|string|max:50',
            'min_cost_rate' => 'nullable|numeric|min:0',
            'std_cost_rate' => 'required|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;
        if (empty($validated['rate_unit']) && !empty($validated['uom'])) {
            $validated['rate_unit'] = 'Idr / ' . strtolower($validated['uom']);
        }

        MfgProcessCost::create($validated);

        return redirect()->route('management.mfg-process-cost.index')
            ->with('success', 'Manufacturing Process Cost successfully created!');
    }

    /**
     * Update the specified Manufacturing Process Cost.
     */
    public function update(Request $request, $id)
    {
        $item = MfgProcessCost::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string|in:Product,Tooling',
            'process_group' => 'required|string|max:100',
            'process_name' => 'required|string|max:150',
            'control_point' => 'nullable|string|max:150',
            'uom' => 'nullable|string|max:50',
            'rate_unit' => 'nullable|string|max:50',
            'min_cost_rate' => 'nullable|numeric|min:0',
            'std_cost_rate' => 'required|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;
        if (empty($validated['rate_unit']) && !empty($validated['uom'])) {
            $validated['rate_unit'] = 'Idr / ' . strtolower($validated['uom']);
        }

        $item->update($validated);

        return redirect()->route('management.mfg-process-cost.index')
            ->with('success', 'Manufacturing Process Cost successfully updated!');
    }

    /**
     * Remove the specified Manufacturing Process Cost.
     */
    public function destroy($id)
    {
        $item = MfgProcessCost::findOrFail($id);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Manufacturing Process Cost record deleted successfully.']);
        }

        return redirect()->route('management.mfg-process-cost.index')
            ->with('success', 'Manufacturing Process Cost record deleted successfully!');
    }

    /**
     * Export Manufacturing Process Costs as CSV.
     */
    public function export()
    {
        $fileName = 'Mfg_Process_Cost_Master_' . date('Y-m-d_H-i-s') . '.csv';
        $items = MfgProcessCost::orderBy('id', 'asc')->get();

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('No', 'Category', 'Group Mfg Process', 'Mfg Process Name', 'Control Point', 'UOM', 'Rate Unit (Idr/Units)', 'Min Cost Rate', 'Std Cost Rate', 'Rate Source');

        $callback = function () use ($items, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($items as $item) {
                fputcsv($file, array(
                    $no++,
                    $item->category,
                    $item->process_group,
                    $item->process_name,
                    $item->control_point,
                    $item->uom,
                    $item->rate_unit,
                    $item->min_cost_rate !== null ? number_format($item->min_cost_rate, 1, '.', '') : '',
                    number_format($item->std_cost_rate, 1, '.', ''),
                    $item->rate_source
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
