<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRule;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalRuleController extends Controller
{
    public function index()
    {
        $rules = ApprovalRule::with(['department', 'approverUser'])
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
            'document_type'    => 'required|string|max:50',
            'approval_level'   => 'required|integer|min:1',
            'department_id'    => 'required|exists:departments,id',
            'approver_user_id' => 'nullable|exists:users,id',
            'position_label'   => 'required|string|max:100',
            'sort_order'       => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        ApprovalRule::create($validated);

        return redirect()->back()->with('success', 'Approval rule created successfully.');
    }

    public function update(Request $request, $id)
    {
        $rule = ApprovalRule::findOrFail($id);

        $validated = $request->validate([
            'document_type'    => 'required|string|max:50',
            'approval_level'   => 'required|integer|min:1',
            'department_id'    => 'required|exists:departments,id',
            'approver_user_id' => 'nullable|exists:users,id',
            'position_label'   => 'required|string|max:100',
            'sort_order'       => 'required|integer',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $rule->update($validated);

        return redirect()->back()->with('success', 'Approval rule updated successfully.');
    }

    public function destroy($id)
    {
        $rule = ApprovalRule::findOrFail($id);
        $rule->delete();

        return redirect()->back()->with('success', 'Approval rule deleted.');
    }
}
