<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MngCfgSystemField;
use Illuminate\Http\Request;

class SystemFieldController extends Controller
{
    public function index()
    {
        $fields = MngCfgSystemField::orderBy('group')->orderBy('label')->get();
        $groups = MngCfgSystemField::select('group')->distinct()->whereNotNull('group')->where('group', '!=', '')->pluck('group');
        $targetTables = MngCfgSystemField::select('target_table')->distinct()->whereNotNull('target_table')->where('target_table', '!=', '')->pluck('target_table');
        return view('management.system-fields.index', compact('fields', 'groups', 'targetTables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'field_key'     => 'required|string|unique:mng_cfg_system_fields,field_key|regex:/^[a-zA-Z0-9_]+$/',
            'label'         => 'required|string',
            'group'         => 'required|string',
            'data_type'     => 'required|in:string,numeric,decimal,date,boolean',
            'target_table'  => 'nullable|string',
            'target_column' => 'nullable|string',
            'is_required'   => 'boolean'
        ]);

        MngCfgSystemField::create($validated);

        return redirect()->back()->with('success', 'System field registered successfully!');
    }
}
