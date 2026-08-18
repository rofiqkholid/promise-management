<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\MaterialCost;
use App\Models\Customer;
use Illuminate\Http\Request;

class MaterialCostController extends Controller
{
    /**
     * Display a listing of Material Costs.
     */
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name', 'asc')->get();
        $materialTypes = MaterialCost::select('material_type')->distinct()->pluck('material_type')->filter()->values();
        if ($materialTypes->isEmpty()) {
            $materialTypes = collect(['Sheet', 'Coil']);
        }

        $rateSources = ['Engineering', 'Sales'];

        // Calculate KPI summary stats
        $totalItems = MaterialCost::count();
        $generalCount = MaterialCost::whereNull('customer_id')->count();
        $customCount = MaterialCost::whereNotNull('customer_id')->count();
        $sheetCount = MaterialCost::where('material_type', 'Sheet')->count();

        return view('management.material-cost.index', compact(
            'customers',
            'materialTypes',
            'rateSources',
            'totalItems',
            'generalCount',
            'customCount',
            'sheetCount'
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

        $totalRecords = MaterialCost::count();

        $query = MaterialCost::with('customer');

        // Dropdown Filters
        if ($request->filled('customer_id')) {
            if ($request->customer_id === 'general') {
                $query->whereNull('customer_id');
            } else {
                $query->where('customer_id', $request->customer_id);
            }
        }

        if ($request->filled('material_type')) {
            $query->where('material_type', $request->material_type);
        }

        if ($request->filled('rate_source')) {
            $query->where('rate_source', $request->rate_source);
        }

        // Global Search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('material_spec', 'like', "%{$searchValue}%")
                  ->orWhere('material_type', 'like', "%{$searchValue}%")
                  ->orWhere('thickness', 'like', "%{$searchValue}%")
                  ->orWhere('price_per_kg', 'like', "%{$searchValue}%")
                  ->orWhere('scrap_price_per_kg', 'like', "%{$searchValue}%")
                  ->orWhere('rate_source', 'like', "%{$searchValue}%")
                  ->orWhereHas('customer', function ($cq) use ($searchValue) {
                      $cq->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('code', 'like', "%{$searchValue}%");
                  });
            });
        }

        $filteredRecords = $query->count();

        // Sorting map matching DataTables column indices
        $columnsMap = [
            0 => 'id',
            1 => 'customer_id',
            2 => 'material_spec',
            3 => 'material_type',
            4 => 'thickness',
            5 => 'price_per_kg',
            6 => 'scrap_price_per_kg',
            7 => 'rate_source',
            8 => 'valid_from',
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
     * Store a newly created Material Cost.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'material_spec' => 'required|string|max:150',
            'material_type' => 'required|string|max:100',
            'thickness' => 'nullable|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
            'scrap_price_per_kg' => 'nullable|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'valid_from' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;
        if (!isset($validated['scrap_price_per_kg'])) {
            $validated['scrap_price_per_kg'] = 0;
        }

        MaterialCost::create($validated);

        return redirect()->route('management.material-cost.index')
            ->with('success', 'Master Data Material Cost successfully created!');
    }

    /**
     * Update the specified Material Cost.
     */
    public function update(Request $request, $id)
    {
        $item = MaterialCost::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'material_spec' => 'required|string|max:150',
            'material_type' => 'required|string|max:100',
            'thickness' => 'nullable|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
            'scrap_price_per_kg' => 'nullable|numeric|min:0',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'valid_from' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;
        if (!isset($validated['scrap_price_per_kg'])) {
            $validated['scrap_price_per_kg'] = 0;
        }

        $item->update($validated);

        return redirect()->route('management.material-cost.index')
            ->with('success', 'Master Data Material Cost successfully updated!');
    }

    /**
     * Remove the specified Material Cost.
     */
    public function destroy($id)
    {
        $item = MaterialCost::findOrFail($id);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Material Cost record deleted successfully.']);
        }

        return redirect()->route('management.material-cost.index')
            ->with('success', 'Material Cost record deleted successfully!');
    }

    /**
     * Export Material Costs as CSV.
     */
    public function export()
    {
        $fileName = 'Material_Cost_Master_' . date('Y-m-d_H-i-s') . '.csv';
        $items = MaterialCost::with('customer')->orderBy('id', 'asc')->get();

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array(
            'No', 'Customer Context', 'Material Spec', 'Material Type',
            'Thickness (mm)', 'Price per Kg (IDR)', 'Scrap Price per Kg (IDR)',
            'Rate Source', 'Valid From'
        );

        $callback = function () use ($items, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($items as $item) {
                fputcsv($file, array(
                    $no++,
                    $item->customer ? $item->customer->name : 'Umum (Standard)',
                    $item->material_spec,
                    $item->material_type,
                    $item->thickness !== null ? number_format($item->thickness, 2, '.', '') : '-',
                    number_format($item->price_per_kg, 2, '.', ''),
                    number_format($item->scrap_price_per_kg, 2, '.', ''),
                    $item->rate_source,
                    $item->valid_from ? $item->valid_from->format('Y-m-d') : '-'
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
