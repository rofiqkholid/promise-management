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
use App\Models\MngEbdToolingProcess;
use App\Models\MngEbdAddProcess;
use App\Models\WorkOrder;
use App\Models\Customer;
use App\Models\ProjectModel;

class EbdController extends Controller
{
    // =========================================================================
    // INDEX — List all EBD Headers
    // =========================================================================

    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            
            $search = $request->input('search.value');
            
            $query = MngEbdHeader::with(['workOrder', 'customer', 'projectModel']);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('revision', 'like', "%{$search}%")
                      ->orWhereHas('workOrder', fn($wq) => $wq->where('wo_number', 'like', "%{$search}%"))
                      ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                      ->orWhereHas('projectModel', fn($mq) => $mq->where('name', 'like', "%{$search}%"));
                });
            }
            
            $totalRecords = MngEbdHeader::count();
            $filteredRecords = $query->count();
            
            $orderColumnIndex = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $sortableColumns = [
                1 => 'date',
                2 => 'revision',
                5 => 'status'
            ];
            
            if (isset($sortableColumns[$orderColumnIndex])) {
                $query->orderBy($sortableColumns[$orderColumnIndex], $orderDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            $ebdHeaders = $query->skip($start)->take($length)->get();
            
            $data = [];
            foreach ($ebdHeaders as $ebd) {
                $data[] = [
                    'index_num' => $start + count($data) + 1,
                    'id' => $ebd->id,
                    'date' => $ebd->date ? $ebd->date->format('d M Y') : '—',
                    'date_raw' => $ebd->date ? $ebd->date->format('Y-m-d') : '',
                    'revision' => $ebd->revision ?? '0',
                    'wo_number' => $ebd->workOrder->wo_number ?? '—',
                    'wo_id' => $ebd->wo_id,
                    'customer_code' => $ebd->customer->code ?? '—',
                    'customer_name' => $ebd->customer->name ?? '—',
                    'customer_id' => $ebd->customer_id,
                    'model_name' => $ebd->projectModel->name ?? '—',
                    'model_id' => $ebd->model_id,
                    'status' => $ebd->status,
                    'created_by' => $ebd->created_by ?? '—',
                    'hashed_id' => $ebd->hashed_id,
                    'show_url' => route('management.ebd.show', $ebd->id),
                    'delete_url' => route('management.ebd.destroy', $ebd->id)
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

        return view('management.ebd.index', compact('workOrders', 'customers', 'models'));
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
            'file_ebd'    => ['required', 'file', 'max:20480', new \App\Rules\SecureFileExtension('excel')],
            'wo_id'       => 'nullable|integer',
            'customer_id' => 'required|integer',
            'model_id'    => 'required|integer',
            'date'        => 'required|date',
            'revision'    => 'nullable|string|max:20',
            'ebd_id'      => 'nullable|integer',
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
    // UPDATE HEADER — Edit header metadata without file re-import
    // =========================================================================
    public function updateHeader(Request $request, $id)
    {
        $request->validate([
            'wo_id'       => 'nullable|integer',
            'customer_id' => 'required|integer',
            'model_id'    => 'required|integer',
            'date'        => 'required|date',
            'revision'    => 'required|string|max:20',
        ]);

        try {
            $ebdHeader = MngEbdHeader::findOrFail($id);
            $ebdHeader->update([
                'wo_id'       => $request->input('wo_id') ?: null,
                'customer_id' => $request->input('customer_id'),
                'model_id'    => $request->input('model_id'),
                'date'        => $request->input('date'),
                'revision'    => $request->input('revision'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'EBD header updated successfully.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('EBD Header Update failed: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update EBD header: ' . $e->getMessage()
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

    // =========================================================================
    // STORE ITEM — Add new root or sub item
    // =========================================================================
    public function storeItem(Request $request, $ebdHeaderId)
    {
        $request->validate([
            'part_no'   => 'required|string|max:255',
            'part_name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
        ]);

        try {
            $parentId = $request->input('parent_id');
            $level = 1;

            if ($parentId) {
                $parent = MngEbdItem::findOrFail($parentId);
                $level = ($parent->bom_level ?? $parent->active_level ?? 1) + 1;
            }

            $sketchPath = null;
            if ($request->hasFile('sketch')) {
                $file = $request->file('sketch');
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $sketchPath = $file->storeAs('ebd/sketches', $filename, 'public');
            }

            $item = MngEbdItem::create([
                'ebd_header_id'   => $ebdHeaderId,
                'parent_id'       => $parentId ?: null,
                'active_level'    => $level,
                'part_no'         => $request->input('part_no'),
                'part_name'         => $request->input('part_name'),
                'pcs_month'       => (int) str_replace('.', '', $request->input('pcs_month', 0)),
                'qty_unit'        => (int) $request->input('qty_unit', 1),
                'width'           => (float) $request->input('width', 0),
                'length'          => (float) $request->input('length', 0),
                'height'          => (float) $request->input('height', 0),
                'weight'          => (float) $request->input('weight', 0),
                'status'          => $request->input('status', 'NEW PART'),
                'part_rank'       => $request->input('part_rank'),
                'mat_spec'        => $request->input('mat_spec'),
                'mat_thick'       => (float) $request->input('mat_thick', 0),
                'mat_width'       => (float) $request->input('mat_width', 0),
                'mat_length'      => (float) $request->input('mat_length', 0),
                'mat_pcs_sheet'   => (int) $request->input('mat_pcs_sheet', 0),
                'mat_weight_pcs'  => (float) $request->input('mat_weight_pcs', 0),
                'mat_yield_ratio' => (float) $request->input('mat_yield_ratio', 0),
                'std_part_no'     => $request->input('std_part_no'),
                'std_part_name'   => $request->input('std_part_name'),
                'std_qty'         => (int) $request->input('std_qty', 0),
                'std_uom'         => $request->input('std_uom'),
                'packing_type'    => $request->input('packing_type'),
                'pcs_packing'     => (int) $request->input('pcs_packing', 0),
                'sketch'          => $sketchPath,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Part added successfully.',
                'item'    => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add part: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE ITEM — Edit single item specifications
    // =========================================================================
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'part_no'   => 'required|string|max:255',
            'part_name' => 'required|string|max:255',
        ]);

        try {
            $item = MngEbdItem::findOrFail($itemId);
            
            $sketchPath = $item->sketch;
            if ($request->hasFile('sketch')) {
                if ($sketchPath && Storage::disk('public')->exists($sketchPath)) {
                    Storage::disk('public')->delete($sketchPath);
                }
                $file = $request->file('sketch');
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $sketchPath = $file->storeAs('ebd/sketches', $filename, 'public');
            }

            $item->update([
                'part_no'         => $request->input('part_no'),
                'part_name'       => $request->input('part_name'),
                'pcs_month'       => (int) str_replace('.', '', $request->input('pcs_month', 0)),
                'qty_unit'        => (int) $request->input('qty_unit', 1),
                'width'           => (float) $request->input('width', 0),
                'length'          => (float) $request->input('length', 0),
                'height'          => (float) $request->input('height', 0),
                'weight'          => (float) $request->input('weight', 0),
                'status'          => $request->input('status', 'NEW PART'),
                'part_rank'       => $request->input('part_rank'),
                'mat_spec'        => $request->input('mat_spec'),
                'mat_thick'       => (float) $request->input('mat_thick', 0),
                'mat_width'       => (float) $request->input('mat_width', 0),
                'mat_length'      => (float) $request->input('mat_length', 0),
                'mat_pcs_sheet'   => (int) $request->input('mat_pcs_sheet', 0),
                'mat_weight_pcs'  => (float) $request->input('mat_weight_pcs', 0),
                'mat_yield_ratio' => (float) $request->input('mat_yield_ratio', 0),
                'std_part_no'     => $request->input('std_part_no'),
                'std_part_name'   => $request->input('std_part_name'),
                'std_qty'         => (int) $request->input('std_qty', 0),
                'std_uom'         => $request->input('std_uom'),
                'packing_type'    => $request->input('packing_type'),
                'pcs_packing'     => (int) $request->input('pcs_packing', 0),
                'sketch'          => $sketchPath,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Part specifications updated successfully.',
                'item'    => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update part: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY ITEM — Delete single item and cascades
    // =========================================================================
    public function destroyItem($itemId)
    {
        try {
            $item = MngEbdItem::findOrFail($itemId);
            
            // Delete sketch file
            if ($item->sketch && Storage::disk('public')->exists($item->sketch)) {
                Storage::disk('public')->delete($item->sketch);
            }

            // Sub items delete (recursive)
            $this->deleteChildItems($item->id);

            $item->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Part and its descendants deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete part: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function deleteChildItems($parentId)
    {
        $children = MngEbdItem::where('parent_id', $parentId)->get();
        foreach ($children as $child) {
            if ($child->sketch && Storage::disk('public')->exists($child->sketch)) {
                Storage::disk('public')->delete($child->sketch);
            }
            $this->deleteChildItems($child->id);
            $child->delete();
        }
    }

    // =========================================================================
    // TOOLING PROCESS CRUD
    // =========================================================================
    public function storeToolingProcess(Request $request, $itemId)
    {
        $request->validate([
            'process_name' => 'required|string|max:255',
            'op'           => 'nullable|integer',
        ]);

        try {
            $tp = MngEbdToolingProcess::create([
                'ebd_item_id'    => $itemId,
                'tool_rank'      => $request->input('tool_rank') ?: null,
                'category'       => $request->input('category') ?: null,
                'op'             => $request->filled('op') ? (int) $request->input('op') : null,
                'process_name'   => $request->input('process_name'),
                'machine_type'   => $request->input('machine_type') ?: null,
                'prod_homeline'  => $request->input('prod_homeline') ?: null,
                'tonnage'        => $request->input('tonnage') ? (int) $request->input('tonnage') : null,
                'die_height'     => $request->input('die_height') ? (float) $request->input('die_height') : null,
                'output'         => $request->filled('output') ? (int) $request->input('output') : null,
                'output_type'    => $request->input('output_type') ?: null,
                'stroke'         => $request->filled('stroke') ? (float) $request->input('stroke') : null,
                'qty'            => (int) $request->input('qty', 1),
                'price_idr'      => $request->input('price_idr') ? (float) str_replace('.', '', $request->input('price_idr')) : null,
                'tooling_status' => $request->input('tooling_status') ?: null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Tooling process added successfully.',
                'process' => $tp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add tooling process: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateToolingProcess(Request $request, $id)
    {
        $request->validate([
            'process_name' => 'required|string|max:255',
            'op'           => 'nullable|integer',
        ]);

        try {
            $tp = MngEbdToolingProcess::findOrFail($id);
            $tp->update([
                'tool_rank'      => $request->input('tool_rank') ?: null,
                'category'       => $request->input('category') ?: null,
                'op'             => $request->filled('op') ? (int) $request->input('op') : null,
                'process_name'   => $request->input('process_name'),
                'machine_type'   => $request->input('machine_type') ?: null,
                'prod_homeline'  => $request->input('prod_homeline') ?: null,
                'tonnage'        => $request->input('tonnage') ? (int) $request->input('tonnage') : null,
                'die_height'     => $request->input('die_height') ? (float) $request->input('die_height') : null,
                'output'         => $request->filled('output') ? (int) $request->input('output') : null,
                'output_type'    => $request->input('output_type') ?: null,
                'stroke'         => $request->filled('stroke') ? (float) $request->input('stroke') : null,
                'qty'            => (int) $request->input('qty', 1),
                'price_idr'      => $request->input('price_idr') ? (float) str_replace('.', '', $request->input('price_idr')) : null,
                'tooling_status' => $request->input('tooling_status') ?: null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Tooling process updated successfully.',
                'process' => $tp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update tooling process: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyToolingProcess($id)
    {
        try {
            $tp = MngEbdToolingProcess::findOrFail($id);
            $tp->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Tooling process deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete tooling process: ' . $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // ADDITIONAL PROCESS CRUD
    // =========================================================================
    public function storeAddProcess(Request $request, $itemId)
    {
        $request->validate([
            'process_name' => 'required|string|max:255',
        ]);

        try {
            $ap = MngEbdAddProcess::create([
                'ebd_item_id'  => $itemId,
                'process_name' => $request->input('process_name'),
                'qty'          => (int) $request->input('qty', 1),
                'unit'         => $request->input('unit', 'pcs'),
                'cost_idr'     => $request->input('cost_idr') ? (float) str_replace('.', '', $request->input('cost_idr')) : null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Additional process added successfully.',
                'process' => $ap
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to add additional process: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateAddProcess(Request $request, $id)
    {
        $request->validate([
            'process_name' => 'required|string|max:255',
        ]);

        try {
            $ap = MngEbdAddProcess::findOrFail($id);
            $ap->update([
                'process_name' => $request->input('process_name'),
                'qty'          => (int) $request->input('qty', 1),
                'unit'         => $request->input('unit', 'pcs'),
                'cost_idr'     => $request->input('cost_idr') ? (float) str_replace('.', '', $request->input('cost_idr')) : null,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Additional process updated successfully.',
                'process' => $ap
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update additional process: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyAddProcess($id)
    {
        try {
            $ap = MngEbdAddProcess::findOrFail($id);
            $ap->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Additional process deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete additional process: ' . $e->getMessage()
            ], 500);
        }
    }
}