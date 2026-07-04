<?php

namespace App\Http\Controllers\FeasibilityStudy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Imports\EbdItemImport;
use App\Models\MngEbdHeader;
use App\Models\MngEbdItem;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\ProjectModel;

class EbdController extends Controller
{
    // =========================================================================
    // INDEX — List all EBD Headers
    // =========================================================================

    public function index()
    {
        $ebdHeaders = MngEbdHeader::with(['workOrder', 'customer', 'projectModel'])
            ->orderByDesc('id')
            ->get();

        $workOrders = WorkOrder::select('id', 'wo_number')->orderByDesc('id')->get();
        $customers  = Customer::select('id', 'name', 'code')->orderBy('name')->get();
        $models     = ProjectModel::select('id', 'name')->orderBy('name')->get();

        return view('management.ebd.index', compact('ebdHeaders', 'workOrders', 'customers', 'models'));
    }

    // =========================================================================
    // SHOW — Detail page for a single EBD Header with BOM tree
    // =========================================================================

    public function show($id)
    {
        $ebdHeader = MngEbdHeader::with([
            'workOrder',
            'customer',
            'projectModel',
            'rootItems.children.children', // 3 levels deep eager load
        ])->findOrFail($id);

        // Flat list for tooling and add-process tabs
        $allItems = MngEbdItem::with(['toolingProcesses', 'addProcesses'])
            ->where('ebd_header_id', $id)
            ->orderBy('id')
            ->get();

        $workOrders = WorkOrder::select('id', 'wo_number')->orderByDesc('id')->get();
        $customers  = Customer::select('id', 'name', 'code')->orderBy('name')->get();
        $models     = ProjectModel::select('id', 'name')->orderBy('name')->get();

        return view('management.ebd.show', compact('ebdHeader', 'allItems', 'workOrders', 'customers', 'models'));
    }

    // =========================================================================
    // IMPORT — Accept XLSX upload, create header, run import
    // =========================================================================

    public function import(Request $request)
    {
        // 1. Validate form input
        $request->validate([
            'file_ebd' => 'required|file|max:20480', // Relax mimes check to prevent local server MIME detection issues
            'wo_id'    => 'nullable|integer',
            'date'     => 'required|date',
            'revision' => 'nullable|string|max:20',
        ]);

        try {
            // 2. Store file to a temporary local path for ZipArchive access
            $file     = $request->file('file_ebd');
            $tempPath = $file->storeAs('temp/ebd', uniqid() . '_' . $file->getClientOriginalName(), 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            // 3. Create EBD Header record
            $ebdHeader = MngEbdHeader::create([
                'wo_id'      => $request->input('wo_id') ?: null,
                'customer_id'=> $request->input('customer_id') ?: null,
                'model_id'   => $request->input('model_id') ?: null,
                'date'       => $request->input('date'),
                'revision'   => $request->input('revision', '0'),
                'status'     => 'Draft',
                'created_by' => Auth::user()->name ?? Auth::user()->username ?? 'System',
            ]);

            // 4. Instantiate importer and run (sheet auto-detected inside importer)
            $importer  = new EbdItemImport($ebdHeader->id);
            $isSuccess = $importer->import($fullPath);

            // 5. Clean up temporary file
            if (file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath);
            }

            // 6. Handle failures — rollback header if import failed
            if (!$isSuccess || !empty($importer->getErrors())) {
                $ebdHeader->delete(); // remove orphan header
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Import completed with errors. Please check the file format.',
                    'errors'  => $importer->getErrors()
                ], 422);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD data imported successfully!',
                'id'      => $ebdHeader->id,
            ], 200);

        } catch (\Exception $e) {
            // Cleanup on crash
            if (isset($fullPath) && file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath ?? '');
            }
            // Cleanup orphan header on crash
            if (isset($ebdHeader) && $ebdHeader->exists) {
                $ebdHeader->forceDelete();
            }

            Log::error('EBD Import Controller crashed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // STORE — Create empty EBD Header manually
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'wo_id'       => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'model_id'    => 'nullable|integer',
            'date'        => 'required|date',
            'revision'    => 'nullable|string|max:20',
        ]);

        $ebdHeader = MngEbdHeader::create([
            'wo_id'       => $request->input('wo_id') ?: null,
            'customer_id' => $request->input('customer_id') ?: null,
            'model_id'    => $request->input('model_id') ?: null,
            'date'        => $request->input('date'),
            'revision'    => $request->input('revision', '0'),
            'status'      => 'Draft',
            'created_by'  => Auth::user()->name ?? Auth::user()->username ?? 'System',
        ]);

        return redirect()->route('management.ebd.show', $ebdHeader->id)->with('success', 'EBD Document created successfully.');
    }

    // =========================================================================
    // UPDATE — Update EBD Header metadata
    // =========================================================================
    public function update(Request $request, $id)
    {
        $ebdHeader = MngEbdHeader::findOrFail($id);
        $request->validate([
            'wo_id'       => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'model_id'    => 'nullable|integer',
            'date'        => 'required|date',
            'revision'    => 'nullable|string|max:20',
            'status'      => 'required|string|max:20',
        ]);

        $ebdHeader->update([
            'wo_id'       => $request->input('wo_id') ?: null,
            'customer_id' => $request->input('customer_id') ?: null,
            'model_id'    => $request->input('model_id') ?: null,
            'date'        => $request->input('date'),
            'revision'    => $request->input('revision', '0'),
            'status'      => $request->input('status'),
        ]);

        return redirect()->back()->with('success', 'EBD Header details updated successfully.');
    }

    // =========================================================================
    // IMPORT ITEMS — Overwrite BOM items under an EBD Header
    // =========================================================================
    public function importItems(Request $request, $id)
    {
        $ebdHeader = MngEbdHeader::findOrFail($id);
        $request->validate([
            'file_ebd' => 'required|file|max:20480',
        ]);

        try {
            $file     = $request->file('file_ebd');
            $tempPath = $file->storeAs('temp/ebd', uniqid() . '_' . $file->getClientOriginalName(), 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            // Delete existing items to allow clean overwrite
            MngEbdItem::where('ebd_header_id', $id)->delete();

            $importer  = new EbdItemImport($id);
            $isSuccess = $importer->import($fullPath);

            if (file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath);
            }

            if (!$isSuccess || !empty($importer->getErrors())) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Import completed with errors. Please check the file format.',
                    'errors'  => $importer->getErrors()
                ], 422);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD items imported successfully!',
            ], 200);

        } catch (\Exception $e) {
            if (isset($fullPath) && file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath ?? '');
            }
            Log::error('EBD Items Import crashed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // STORE ITEM — Add single EBD item manually
    // =========================================================================
    public function storeItem(Request $request, $id)
    {
        $request->validate([
            'part_no'     => 'required|string|max:100',
            'part_name'   => 'required|string|max:200',
            'level_aktif' => 'required|integer',
            'parent_id'   => 'nullable|integer',
            'qty_unit'    => 'nullable|integer',
            'pcs_month'   => 'nullable|integer',
            'part_rank'   => 'nullable|string|max:50',
            'status'      => 'nullable|string|max:50',
            'width'       => 'nullable|numeric',
            'length'      => 'nullable|numeric',
            'height'      => 'nullable|numeric',
            'weight'      => 'nullable|numeric',
            'mat_spec'    => 'nullable|string|max:100',
            'mat_thick'   => 'nullable|numeric',
            'mat_width'   => 'nullable|numeric',
            'mat_length'  => 'nullable|numeric',
            'mat_pcs_sheet'=> 'nullable|integer',
            'mat_weight_pcs'=> 'nullable|numeric',
            'mat_yield_ratio'=> 'nullable|numeric',
            'std_part_no' => 'nullable|string|max:100',
            'std_qty'     => 'nullable|integer',
            'packing_type'=> 'nullable|string|max:100',
            'pcs_packing' => 'nullable|integer',
            'part_vol_m2' => 'nullable|numeric',
            'truck_vol_m2'=> 'nullable|numeric',
        ]);

        $item = MngEbdItem::create(array_merge($request->all(), [
            'ebd_header_id' => $id,
            'parent_id'     => $request->input('parent_id') ?: null,
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'EBD item added successfully!',
            'item'    => $item
        ], 200);
    }

    // =========================================================================
    // UPDATE ITEM — Update single EBD item
    // =========================================================================
    public function updateItem(Request $request, $itemId)
    {
        $item = MngEbdItem::findOrFail($itemId);
        $request->validate([
            'part_no'     => 'required|string|max:100',
            'part_name'   => 'required|string|max:200',
            'level_aktif' => 'required|integer',
            'parent_id'   => 'nullable|integer',
            'qty_unit'    => 'nullable|integer',
            'pcs_month'   => 'nullable|integer',
            'part_rank'   => 'nullable|string|max:50',
            'status'      => 'nullable|string|max:50',
            'width'       => 'nullable|numeric',
            'length'      => 'nullable|numeric',
            'height'      => 'nullable|numeric',
            'weight'      => 'nullable|numeric',
            'mat_spec'    => 'nullable|string|max:100',
            'mat_thick'   => 'nullable|numeric',
            'mat_width'   => 'nullable|numeric',
            'mat_length'  => 'nullable|numeric',
            'mat_pcs_sheet'=> 'nullable|integer',
            'mat_weight_pcs'=> 'nullable|numeric',
            'mat_yield_ratio'=> 'nullable|numeric',
            'std_part_no' => 'nullable|string|max:100',
            'std_qty'     => 'nullable|integer',
            'packing_type'=> 'nullable|string|max:100',
            'pcs_packing' => 'nullable|integer',
            'part_vol_m2' => 'nullable|numeric',
            'truck_vol_m2'=> 'nullable|numeric',
        ]);

        $item->update(array_merge($request->all(), [
            'parent_id' => $request->input('parent_id') ?: null,
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'EBD item updated successfully!',
            'item'    => $item
        ], 200);
    }

    // =========================================================================
    // DESTROY ITEM — Delete single EBD item
    // =========================================================================
    public function destroyItem($itemId)
    {
        try {
            $item = MngEbdItem::findOrFail($itemId);
            $item->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD item deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete EBD item: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — Delete an EBD Header (cascades to items & processes)
    // =========================================================================
    public function destroy($id)
    {
        try {
            $ebdHeader = MngEbdHeader::findOrFail($id);
            $ebdHeader->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD record deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('EBD Delete failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete EBD record: ' . $e->getMessage()
            ], 500);
        }
    }
}