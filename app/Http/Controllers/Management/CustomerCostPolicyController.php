<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CustomerCostPolicy;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerCostPolicyController extends Controller
{
    /**
     * Display a listing of Customer Cost Policies.
     */
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name', 'asc')->get();
        $rateSources = ['Sales', 'Engineering'];

        // KPI summary statistics
        $totalItems = CustomerCostPolicy::count();
        $generalCount = CustomerCostPolicy::whereNull('customer_id')->count();
        $customCount = CustomerCostPolicy::whereNotNull('customer_id')->count();
        $avgMargin = CustomerCostPolicy::avg('min_std_margin_pct') ?? 12.0;

        return view('management.cost-policy.index', compact(
            'customers',
            'rateSources',
            'totalItems',
            'generalCount',
            'customCount',
            'avgMargin'
        ));
    }

    /**
     * Server-side DataTables JSON endpoint.
     */
    public function data(Request $request)
    {
        $draw = intval($request->input('draw', 1));
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 10));

        $totalRecords = CustomerCostPolicy::count();

        $query = CustomerCostPolicy::with('customer');

        // Dropdown Filters
        if ($request->filled('customer_id')) {
            if ($request->customer_id === 'general') {
                $query->whereNull('customer_id');
            } else {
                $query->where('customer_id', $request->customer_id);
            }
        }

        if ($request->filled('rate_source')) {
            $query->where('rate_source', $request->rate_source);
        }

        // Global Search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('admin_matrl_pct', 'like', "%{$searchValue}%")
                  ->orWhere('admin_mfg_pct', 'like', "%{$searchValue}%")
                  ->orWhere('oh_profit_pct', 'like', "%{$searchValue}%")
                  ->orWhere('min_std_margin_pct', 'like', "%{$searchValue}%")
                  ->orWhere('rate_source', 'like', "%{$searchValue}%")
                  ->orWhere('notes', 'like', "%{$searchValue}%")
                  ->orWhereHas('customer', function ($cq) use ($searchValue) {
                      $cq->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('code', 'like', "%{$searchValue}%");
                  });
            });
        }

        $filteredRecords = $query->count();

        // Sorting map
        $columnsMap = [
            0 => 'id',
            1 => 'customer_id',
            2 => 'admin_matrl_pct',
            3 => 'admin_mfg_pct',
            4 => 'oh_profit_pct',
            5 => 'min_std_margin_pct',
            6 => 'rate_source',
            7 => 'notes',
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
     * Store a newly created Customer Cost Policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'admin_matrl_pct' => 'required|numeric|min:0|max:100',
            'admin_mfg_pct' => 'required|numeric|min:0|max:100',
            'oh_profit_pct' => 'required|numeric|min:0|max:100',
            'min_std_margin_pct' => 'required|numeric|min:0|max:100',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        CustomerCostPolicy::create($validated);

        return redirect()->route('management.cost-policy.index')
            ->with('success', 'Cost Policy & Markup successfully created!');
    }

    /**
     * Update the specified Customer Cost Policy.
     */
    public function update(Request $request, $id)
    {
        $item = CustomerCostPolicy::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'admin_matrl_pct' => 'required|numeric|min:0|max:100',
            'admin_mfg_pct' => 'required|numeric|min:0|max:100',
            'oh_profit_pct' => 'required|numeric|min:0|max:100',
            'min_std_margin_pct' => 'required|numeric|min:0|max:100',
            'rate_source' => 'required|string|in:Engineering,Sales',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $item->update($validated);

        return redirect()->route('management.cost-policy.index')
            ->with('success', 'Cost Policy & Markup successfully updated!');
    }

    /**
     * Remove the specified Customer Cost Policy.
     */
    public function destroy($id)
    {
        $item = CustomerCostPolicy::findOrFail($id);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Cost Policy record deleted successfully.']);
        }

        return redirect()->route('management.cost-policy.index')
            ->with('success', 'Cost Policy record deleted successfully!');
    }

    /**
     * Export Cost Policies as CSV.
     */
    public function export()
    {
        $fileName = 'Cost_Policy_Markup_Master_' . date('Y-m-d_H-i-s') . '.csv';
        $items = CustomerCostPolicy::with('customer')->orderBy('id', 'asc')->get();

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array(
            'No', 'Customer Context', 'Admin Matrl (%)', 'Admin Mfg (%)',
            'Overhead + Profit (%)', 'Min Std Margin (%)', 'Rate Source', 'Notes'
        );

        $callback = function () use ($items, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            $no = 1;
            foreach ($items as $item) {
                fputcsv($file, array(
                    $no++,
                    $item->customer ? $item->customer->name : 'Global (General Standard)',
                    number_format($item->admin_matrl_pct, 2, '.', '') . '%',
                    number_format($item->admin_mfg_pct, 2, '.', '') . '%',
                    number_format($item->oh_profit_pct, 2, '.', '') . '%',
                    number_format($item->min_std_margin_pct, 2, '.', '') . '%',
                    $item->rate_source,
                    $item->notes ?? '-'
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
