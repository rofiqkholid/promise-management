<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ApprovalConfig;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalConfigController extends Controller
{
    public function index()
    {
        $rules = ApprovalConfig::with(['department'])
            ->orderBy('approval_level', 'asc')
            ->orderBy('sort_order', 'asc')
            ->get();

        $departments = Department::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('management.approval-config.index', compact('rules', 'departments', 'users'));
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
