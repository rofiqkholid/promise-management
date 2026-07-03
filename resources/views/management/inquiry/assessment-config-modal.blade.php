<!-- Assessment Configuration Modal -->
<div x-show="showAssessmentConfigModal" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" 
     style="display: none;"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-5xl shadow-2xl flex flex-col max-h-[92vh] relative"
         @click.outside="showAssessmentConfigModal = false"
         x-data="{
            configTab: 'categories',
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
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 flex-shrink-0">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-gears text-blue-600 dark:text-blue-400"></i>
                    <span>Assessment Configuration</span>
                </h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-medium">Manage feasibility study parameters, scoring criteria, and priority rankings</p>
            </div>
            <button @click="showAssessmentConfigModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
        </div>

        <!-- Tabs Header -->
        <div class="px-5 pt-3 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex gap-4">
            <button @click="configTab = 'categories'" 
                    :class="configTab === 'categories' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="pb-2.5 px-1 border-b-2 text-[10px] transition-all focus:outline-none uppercase tracking-wider font-bold">
                Score Categories
            </button>
            <button @click="configTab = 'options'" 
                    :class="configTab === 'options' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="pb-2.5 px-1 border-b-2 text-[10px] transition-all focus:outline-none uppercase tracking-wider font-bold">
                Score Options
            </button>
            <button @click="configTab = 'rankings'" 
                    :class="configTab === 'rankings' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300'"
                    class="pb-2.5 px-1 border-b-2 text-[10px] transition-all focus:outline-none uppercase tracking-wider font-bold">
                Assessment Rankings
            </button>
        </div>

        <!-- Modal Body Content -->
        <div class="flex-grow overflow-y-auto p-5 space-y-4">
            
            <!-- Tab 1: Categories -->
            <div x-show="configTab === 'categories'" class="space-y-4" style="display: none;">
                <div class="flex justify-between items-center">
                    <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Categories List</h4>
                    <button @click="editCatMode = false; catForm = { id: '', category_code: '', category_name: '', sort_order: 1, is_active: true }; showCatModal = true"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-[9px]"></i> Add Category
                    </button>
                </div>

                <x-table id="config-categories-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400">
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Code</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Name</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Sort Order</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Status</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-xs">
                        @foreach($categories as $cat)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 text-slate-800 dark:text-slate-100 transition-colors duration-150">
                                <td class="p-3 font-semibold text-blue-600 dark:text-blue-400">{{ $cat->category_code }}</td>
                                <td class="p-3">{{ $cat->category_name }}</td>
                                <td class="p-3">{{ $cat->sort_order }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold {{ $cat->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-900/30' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200/50 dark:border-rose-900/30' }}">
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
                                        class="w-6 h-6 inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-650 hover:border-blue-450 text-slate-600 dark:text-slate-300 transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            </div>

            <!-- Tab 2: Options -->
            <div x-show="configTab === 'options'" class="space-y-4" style="display: none;">
                <div class="flex justify-between items-center">
                    <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Score Options Grid</h4>
                    <button @click="editOptMode = false; optForm = { id: '', category_id: '', option_name: '', score_value: 0, description: '', sort_order: 1 }; showOptModal = true"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-[9px]"></i> Add Option
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($categories as $cat)
                        <div class="bg-slate-50 dark:bg-slate-905 border border-slate-200 dark:border-slate-800 p-4 space-y-3">
                            <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex justify-between items-center">
                                <h5 class="font-extrabold text-blue-600 dark:text-blue-400 text-xs uppercase tracking-wider">{{ $cat->category_name }}</h5>
                                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Code: {{ $cat->category_code }}</span>
                            </div>
                            <div class="space-y-2">
                                @forelse($cat->options as $opt)
                                    <div class="flex items-center justify-between p-2.5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-800/80 hover:border-blue-500/30 transition-all shadow-xs">
                                        <div>
                                            <div class="font-bold text-slate-800 dark:text-white text-xs">
                                                {{ $opt->option_name }}
                                                <span class="ml-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 font-extrabold border border-blue-200/55 text-[9px] rounded-xs">
                                                    +{{ $opt->score_value }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 leading-relaxed">{{ $opt->description ?: 'No description' }}</p>
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
                                            class="w-6 h-6 flex items-center justify-center hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400">
                                            <i class="fa-solid fa-pen text-[9px]"></i>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-[10px] text-slate-450 dark:text-slate-500 text-center py-4">No options defined for this category.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Tab 3: Rankings -->
            <div x-show="configTab === 'rankings'" class="space-y-4" style="display: none;">
                <div class="flex justify-between items-center">
                    <h4 class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Assessment Rankings List</h4>
                    <button @click="editRankMode = false; rankForm = { id: '', rank_code: '', min_score: 0, max_score: 100, priority_label: '', recommendation: '', sort_order: 1, is_active: true }; showRankModal = true"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold uppercase tracking-wider transition-colors flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-[9px]"></i> Add Rank
                    </button>
                </div>

                <x-table id="config-rankings-table">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400">
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Rank Code</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Priority Label</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Score Bounds</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider">Recommendation</th>
                            <th class="p-3 text-[10px] font-bold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/80 text-xs">
                        @forelse($rankings as $rank)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 text-slate-800 dark:text-slate-100 transition-colors duration-150">
                                <td class="p-3 font-bold text-blue-650 dark:text-blue-450">{{ $rank->rank_code }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-905 text-blue-700 dark:text-blue-300 font-bold border border-blue-200 dark:border-blue-800/60 rounded-xs">
                                        {{ $rank->priority_label }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono">{{ $rank->min_score }} - {{ $rank->max_score }}</td>
                                <td class="p-3 text-slate-500 dark:text-slate-450">{{ $rank->recommendation }}</td>
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
                                        class="w-6 h-6 inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-650 hover:border-blue-450 text-slate-600 dark:text-slate-300 transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400 text-xs">No priority rankings defined yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-table>
            </div>
            
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950/40 flex justify-end flex-shrink-0">
            <button type="button" @click="showAssessmentConfigModal = false"
                    class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider">
                Close
            </button>
        </div>

        <!-- Tab Form Modals (Floating Inner Overlays) -->
        <!-- Category Inner Modal -->
        <div x-show="showCatModal" class="fixed inset-0 z-60 flex items-center justify-center bg-slate-950/60 p-4" style="display: none;" x-cloak>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 relative shadow-xl">
                <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-white mb-4" x-text="editCatMode ? 'Edit Category' : 'Add Category'"></h5>
                <form :action="editCatMode ? `/management/assessment-config/category/${catForm.id}/update` : '/management/assessment-config/category'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Code</label>
                        <input type="text" name="category_code" x-model="catForm.category_code" required :disabled="editCatMode"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Name</label>
                        <input type="text" name="category_name" x-model="catForm.category_name" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                        <input type="number" name="sort_order" x-model="catForm.sort_order" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="cat_is_active" x-model="catForm.is_active" value="1" class="w-3.5 h-3.5 cursor-pointer">
                        <label for="cat_is_active" class="text-xs font-semibold text-slate-800 dark:text-slate-200">Active</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showCatModal = false" class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 text-slate-750 dark:text-slate-200 text-[10px] uppercase tracking-wider font-bold">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] uppercase tracking-wider font-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Option Inner Modal -->
        <div x-show="showOptModal" class="fixed inset-0 z-60 flex items-center justify-center bg-slate-950/60 p-4" style="display: none;" x-cloak>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 relative shadow-xl">
                <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-white mb-4" x-text="editOptMode ? 'Edit Option' : 'Add Option'"></h5>
                <form :action="editOptMode ? `/management/assessment-config/option/${optForm.id}/update` : '/management/assessment-config/option'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Category</label>
                        <select name="category_id" x-model="optForm.category_id" required
                                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Option Name</label>
                        <input type="text" name="option_name" x-model="optForm.option_name" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Score Value</label>
                        <input type="number" name="score_value" x-model="optForm.score_value" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Description</label>
                        <textarea name="description" x-model="optForm.description" rows="2"
                                  class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                        <input type="number" name="sort_order" x-model="optForm.sort_order" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showOptModal = false" class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 text-slate-750 dark:text-slate-200 text-[10px] uppercase tracking-wider font-bold">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] uppercase tracking-wider font-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ranking Inner Modal -->
        <div x-show="showRankModal" class="fixed inset-0 z-60 flex items-center justify-center bg-slate-950/60 p-4" style="display: none;" x-cloak>
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 relative shadow-xl">
                <h5 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 dark:text-white mb-4" x-text="editRankMode ? 'Edit Ranking' : 'Add Ranking'"></h5>
                <form :action="editRankMode ? `/management/assessment-config/ranking/${rankForm.id}/update` : '/management/assessment-config/ranking'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Rank Code</label>
                        <input type="text" name="rank_code" x-model="rankForm.rank_code" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Priority Label</label>
                        <input type="text" name="priority_label" x-model="rankForm.priority_label" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Min Score</label>
                            <input type="number" name="min_score" x-model="rankForm.min_score" required
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Max Score</label>
                            <input type="number" name="max_score" x-model="rankForm.max_score" required
                                   class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Recommendation</label>
                        <textarea name="recommendation" x-model="rankForm.recommendation" rows="2"
                                  class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sort Order</label>
                        <input type="number" name="sort_order" x-model="rankForm.sort_order" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="rank_is_active" x-model="rankForm.is_active" value="1" class="w-3.5 h-3.5 cursor-pointer">
                        <label for="rank_is_active" class="text-xs font-semibold text-slate-800 dark:text-slate-200">Active</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showRankModal = false" class="px-3 py-1.5 border border-slate-300 dark:border-slate-700 text-slate-750 dark:text-slate-200 text-[10px] uppercase tracking-wider font-bold">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-[10px] uppercase tracking-wider font-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
