<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ApprovalConfig;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalConfigController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);

            $query = ApprovalConfig::with(['department']);
            
            $search = $request->input('search.value');
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('position_label', 'like', "%{$search}%")
                      ->orWhere('action_label', 'like', "%{$search}%")
                      ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            }
            
            $totalRecords = ApprovalConfig::count();
            $filteredRecords = $query->count();
            
            $rules = $query->orderBy('approval_level', 'asc')
                           ->orderBy('sort_order', 'asc')
                           ->skip($start)->take($length)->get();
                           
            $data = [];
            foreach ($rules as $rule) {
                $approvers = [];
                foreach ($rule->approver_users as $u) {
                    $approvers[] = [
                        'name' => $u->name,
                        'nik' => $u->nik,
                        'id' => $u->id
                    ];
                }
                
                $data[] = [
                    'id' => $rule->id,
                    'rule_id' => $rule->rule_id,
                    'approval_level' => $rule->approval_level,
                    'position_label' => $rule->position_label,
                    'action_label' => $rule->action_label ?? 'Checked',
                    'department_id' => $rule->department_id,
                    'department_name' => $rule->department->name ?? '—',
                    'department_code' => $rule->department->code ?? '',
                    'approver_users' => $approvers,
                    'sort_order' => $rule->sort_order,
                    'is_active' => $rule->is_active,
                    'destroy_url' => route('management.approval-config.destroy', $rule->rule_id),
                    'raw_rule' => $rule->toArray()
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        }

        $departments = Department::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('management.approval-config.index', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type'      => 'required|string|max:50',
            'approval_level'     => 'required|integer|min:1',
            'department_id'      => 'required|exists:departments,id',
            'approver_user_ids'  => 'nullable|array',
            'approver_user_ids.*'=> 'exists:users,id',
            'position_label'     => 'required|string|max:100',
            'action_label'       => 'nullable|string|max:50',
            'sort_order'         => 'required|integer',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['action_label'] = $request->input('action_label') ?: 'Checked';

        ApprovalConfig::create($validated);

        return redirect()->back()->with('success', 'Approval configuration created successfully.');
    }

    public function update(Request $request, $id)
    {
        $rule = ApprovalConfig::findOrFail($id);

        $validated = $request->validate([
            'document_type'      => 'required|string|max:50',
            'approval_level'     => 'required|integer|min:1',
            'department_id'      => 'required|exists:departments,id',
            'approver_user_ids'  => 'nullable|array',
            'approver_user_ids.*'=> 'exists:users,id',
            'position_label'     => 'required|string|max:100',
            'action_label'       => 'nullable|string|max:50',
            'sort_order'         => 'required|integer',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['action_label'] = $request->input('action_label') ?: 'Checked';

        // Ensure empty array is saved properly if no users are selected
        if (!isset($validated['approver_user_ids'])) {
            $validated['approver_user_ids'] = null;
        }

        $rule->update($validated);

        return redirect()->back()->with('success', 'Approval configuration updated successfully.');
    }

    public function destroy($id)
    {
        $rule = ApprovalConfig::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Approval configuration deleted.');
    }
}
