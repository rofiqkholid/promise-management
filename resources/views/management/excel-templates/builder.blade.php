@extends('layouts.app')

@section('title', 'Visual Mapping Studio · Promise Management')
@section('page_title', 'Visual Mapping Studio')

@push('styles')
    @include('management.excel-templates.partials.builder-styles')
@endpush

@section('content')
<div id="mainStudioCanvas" 
     class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800"
     :class="isFullscreen ? 'fixed inset-0 z-50 bg-white dark:bg-slate-900 w-screen h-screen !mt-0 !h-screen' : ''"
     x-data="visualMapperStudio()">

    {{-- Top Metadata & Action Bar --}}
    @include('management.excel-templates.partials.builder-toolbar')

    {{-- Main Workspace Layout: Left Tree Map Explorer + Spreadsheet Canvas Grid + Right Inspector Panel --}}
    <div class="flex-1 flex overflow-hidden relative">
        {{-- Left Tree Map / Variable Palette & Mapped Items Explorer --}}
        @include('management.excel-templates.partials.builder-treemap')

        {{-- Main Spreadsheet Canvas Grid --}}
        @include('management.excel-templates.partials.builder-canvas')

        {{-- Right Cell Mapping Inspector Panel --}}
        @include('management.excel-templates.partials.builder-inspector')
    </div>
</div>
@endsection

@push('scripts')
    @include('management.excel-templates.partials.builder-scripts')
@endpush
