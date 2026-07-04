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

        return view('management.ebd.show', compact('ebdHeader', 'allItems'));
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
            'ebd_id'   => 'nullable|integer',
        ]);

        $ebdId = $request->input('ebd_id');
        $isOverwrite = !empty($ebdId);
        
        \DB::beginTransaction();

        try {
            // 2. Store file to a temporary local path for ZipArchive access
            $file     = $request->file('file_ebd');
            $tempPath = $file->storeAs('temp/ebd', uniqid() . '_' . $file->getClientOriginalName(), 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            // 3. Create or Update EBD Header record
            if ($isOverwrite) {
                $ebdHeader = MngEbdHeader::findOrFail($ebdId);
                $ebdHeader->update([
                    'wo_id'      => $request->input('wo_id') ?: null,
                    'customer_id'=> $request->input('customer_id') ?: null,
                    'model_id'   => $request->input('model_id') ?: null,
                    'date'       => $request->input('date'),
                    'revision'   => $request->input('revision', '0'),
                    'status'     => 'Draft',
                ]);
                // Delete existing BOM items, cascades to tooling and add processes
                MngEbdItem::where('ebd_header_id', $ebdHeader->id)->delete();
            } else {
                $ebdHeader = MngEbdHeader::create([
                    'wo_id'      => $request->input('wo_id') ?: null,
                    'customer_id'=> $request->input('customer_id') ?: null,
                    'model_id'   => $request->input('model_id') ?: null,
                    'date'       => $request->input('date'),
                    'revision'   => $request->input('revision', '0'),
                    'status'     => 'Draft',
                    'created_by' => Auth::user()->name ?? Auth::user()->username ?? 'System',
                ]);
            }

            // 4. Instantiate importer and run (sheet auto-detected inside importer)
            $importer  = new EbdItemImport($ebdHeader->id);
            $isSuccess = $importer->import($fullPath);

            // 5. Clean up temporary file
            if (file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath);
            }

            // 6. Handle failures — rollback transaction
            if (!$isSuccess || !empty($importer->getErrors())) {
                \DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Import completed with errors. Please check the file format.',
                    'errors'  => $importer->getErrors()
                ], 422);
            }

            \DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD data imported successfully!',
                'id'      => $ebdHeader->id,
            ], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            // Cleanup temporary file on crash
            if (isset($fullPath) && file_exists($fullPath)) {
                Storage::disk('local')->delete($tempPath ?? '');
            }

            Log::error('EBD Import Controller crashed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error: ' . $e->getMessage()
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