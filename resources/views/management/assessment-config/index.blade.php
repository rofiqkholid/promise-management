@extends('layouts.app')

@section('title', 'Assessment Configuration - Promise Management')

@section('content')
<x-sweetalert />
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200" 
     x-data="{ 
        activeTab: 'categories', 
        showCatModal: false, 
        editCatMode: false,
        catForm: { id: '', category_code: '', category_name: '', sort_order: 1, is_active: true },
        
        showOptModal: false,
        editOptMode: false,
        optForm: { id: '', category_id: '', option_name: '', score_value: 0, description: '', sort_order: 1 },
        
        showRankModal: false,
        editRankMode: false,
        rankForm: { id: '', rank_code: '', min_score: 0, max_score: 100, priority_label: '', recommendation: '', sort_order: 1, is_active: true }
     }">
    
    <!-- Title & Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Assessment Configuration</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage feasibility study parameters, scoring criteria, and priority rankings</p>
        </div>
    </div>

    <!-- Sessions Alert -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabs Header -->
    <div class="border-b border-slate-200 dark:border-slate-700/80 flex gap-4">
        <button @click="activeTab = 'categories'" 
                :class="activeTab === 'categories' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-sm transition-all focus:outline-none">
            Score Categories
        </button>
        <button @click="activeTab = 'options'" 
                :class="activeTab === 'options' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-sm transition-all focus:outline-none">
            Score Options
        </button>
        <button @click="activeTab = 'rankings'" 
                :class="activeTab === 'rankings' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                class="pb-3 px-1 border-b-2 text-sm transition-all focus:outline-none">
            Assessment Rankings
        </button>
    </div>

    <!-- Tab 1: Categories -->
    <div x-show="activeTab === 'categories'" class="space-y-4">
        <div class="flex justify-between items-center bg-white dark:bg-slate-800 p-4 border border-slate-200 dark:border-slate-700/80 transition-colors">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Categories</h2>
            <button @click="editCatMode = false; catForm = { id: '', category_code: '', category_name: '', sort_order: 1, is_active: true }; showCatModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold uppercase tracking-wider transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Add Category
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400">
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Code</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Name</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Sort Order</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Status</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 text-slate-800 dark:text-slate-100 transition-colors duration-150">
                                <td class="p-3 font-semibold text-blue-600 dark:text-blue-400">{{ $cat->category_code }}</td>
                                <td class="p-3">{{ $cat->category_name }}</td>
                                <td class="p-3">{{ $cat->sort_order }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-xs font-semibold {{ $cat->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $cat->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <button @click="
                                        editCatMode = true;
                                        catForm = { 
                                            id: '{{ $cat->category_id }}', 
                                            category_code: '{{ $cat->category_code }}', 
                                            category_name: '{{ $cat->category_name }}', 
                                            sort_order: {{ $cat->sort_order }}, 
                                            is_active: {{ $cat->is_active ? 'true' : 'false' }} 
                                        };
                                        showCatModal = true;"
                                        class="w-6 h-6 inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-300 transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Options -->
    <div x-show="activeTab === 'options'" class="space-y-4">
        <div class="flex justify-between items-center bg-white dark:bg-slate-800 p-4 border border-slate-200 dark:border-slate-700/80 transition-colors">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Score Options</h2>
            <button @click="editOptMode = false; optForm = { id: '', category_id: '', option_name: '', score_value: 0, description: '', sort_order: 1 }; showOptModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold uppercase tracking-wider transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Add Option
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($categories as $cat)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 transition-colors p-4 space-y-3">
                    <div class="border-b border-slate-100 dark:border-slate-700 pb-2 flex justify-between items-center">
                        <h3 class="font-bold text-blue-600 dark:text-blue-400 text-sm uppercase tracking-wide">{{ $cat->category_name }}</h3>
                        <span class="text-xs text-slate-400">Code: {{ $cat->category_code }}</span>
                    </div>
                    <div class="space-y-2">
                        @forelse($cat->options as $opt)
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-blue-500/30 transition-all">
                                <div>
                                    <div class="font-semibold text-slate-800 dark:text-white text-xs">
                                        {{ $opt->option_name }}
                                        <span class="ml-1 px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 font-bold rounded-xs">
                                            +{{ $opt->score_value }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $opt->description ?: 'No description' }}</p>
                                </div>
                                <button @click="
                                    editOptMode = true;
                                    optForm = {
                                        id: '{{ $opt->option_id }}',
                                        category_id: '{{ $opt->category_id }}',
                                        option_name: '{{ $opt->option_name }}',
                                        score_value: {{ $opt->score_value }},
                                        description: '{{ $opt->description }}',
                                        sort_order: {{ $opt->sort_order }}
                                    };
                                    showOptModal = true;"
                                    class="p-1 hover:text-blue-600 text-slate-400">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">No options defined for this category.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tab 3: Rankings -->
    <div x-show="activeTab === 'rankings'" class="space-y-4">
        <div class="flex justify-between items-center bg-white dark:bg-slate-800 p-4 border border-slate-200 dark:border-slate-700/80 transition-colors">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">Assessment Rankings</h2>
            <button @click="editRankMode = false; rankForm = { id: '', rank_code: '', min_score: 0, max_score: 100, priority_label: '', recommendation: '', sort_order: 1, is_active: true }; showRankModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold uppercase tracking-wider transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Add Rank definition
            </button>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400">
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Rank Code</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Priority Label</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Score Bounds</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider">Recommendation</th>
                            <th class="p-3 text-xs font-semibold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-sm">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 text-slate-800 dark:text-slate-100 transition-colors duration-150">
                                <td class="p-3 font-semibold text-blue-600 dark:text-blue-400">{{ $rank->rank_code }}</td>
                                <td class="p-3">
                                    <span class="px-2.5 py-0.5 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800/60 rounded-xs">
                                        {{ $rank->priority_label }}
                                    </span>
                                </td>
                                <td class="p-3">{{ $rank->min_score }} - {{ $rank->max_score }}</td>
                                <td class="p-3 text-xs text-slate-500 dark:text-slate-400">{{ $rank->recommendation }}</td>
                                <td class="p-3 text-right">
                                    <button @click="
                                        editRankMode = true;
                                        rankForm = {
                                            id: '{{ $rank->ranking_id }}',
                                            rank_code: '{{ $rank->rank_code }}',
                                            min_score: {{ $rank->min_score }},
                                            max_score: {{ $rank->max_score }},
                                            priority_label: '{{ $rank->priority_label }}',
                                            recommendation: '{{ $rank->recommendation }}',
                                            sort_order: {{ $rank->sort_order }},
                                            is_active: {{ $rank->is_active ? 'true' : 'false' }}
                                        };
                                        showRankModal = true;"
                                        class="w-6 h-6 inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-300 transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400">No priority rankings defined yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Category Modal -->
    <div x-show="showCatModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-md p-6 relative">
            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-4" x-text="editCatMode ? 'Edit Category' : 'Add Category'"></h3>
            <form :action="editCatMode ? `/management/assessment-config/category/${catForm.id}/update` : '/management/assessment-config/category'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Code</label>
                    <input type="text" name="category_code" x-model="catForm.category_code" required :disabled="editCatMode"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Name</label>
                    <input type="text" name="category_name" x-model="catForm.category_name" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                    <input type="number" name="sort_order" x-model="catForm.sort_order" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="cat_is_active" x-model="catForm.is_active" value="1">
                    <label for="cat_is_active" class="text-sm font-semibold text-slate-800 dark:text-slate-200">Active</label>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showCatModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs uppercase tracking-wider font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs uppercase tracking-wider font-semibold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Option Modal -->
    <div x-show="showOptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 w-full max-w-md p-6 relative">
            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-4" x-text="editOptMode ? 'Edit Option' : 'Add Option'"></h3>
            <form :action="editOptMode ? `/management/assessment-config/option/${optForm.id}/update` : '/management/assessment-config/option'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Category</label>
                    <select name="category_id" x-model="optForm.category_id" required
                            class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Option Name</label>
                    <input type="text" name="option_name" x-model="optForm.option_name" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Score Value</label>
                    <input type="number" name="score_value" x-model="optForm.score_value" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Description</label>
                    <textarea name="description" x-model="optForm.description" rows="2"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                    <input type="number" name="sort_order" x-model="optForm.sort_order" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showOptModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs uppercase tracking-wider font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs uppercase tracking-wider font-semibold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ranking Modal -->
    <div x-show="showRankModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" style="display: none;">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-md p-6 relative">
            <h3 class="text-base font-bold text-slate-800 dark:text-white mb-4" x-text="editRankMode ? 'Edit Ranking' : 'Add Ranking'"></h3>
            <form :action="editRankMode ? `/management/assessment-config/ranking/${rankForm.id}/update` : '/management/assessment-config/ranking'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Rank Code</label>
                    <input type="text" name="rank_code" x-model="rankForm.rank_code" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Priority Label</label>
                    <input type="text" name="priority_label" x-model="rankForm.priority_label" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Min Score</label>
                        <input type="number" name="min_score" x-model="rankForm.min_score" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Max Score</label>
                        <input type="number" name="max_score" x-model="rankForm.max_score" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Recommendation</label>
                    <textarea name="recommendation" x-model="rankForm.recommendation" rows="2"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                    <input type="number" name="sort_order" x-model="rankForm.sort_order" required
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="rank_is_active" x-model="rankForm.is_active" value="1">
                    <label for="rank_is_active" class="text-sm font-semibold text-slate-800 dark:text-slate-200">Active</label>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="showRankModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs uppercase tracking-wider font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs uppercase tracking-wider font-semibold">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
