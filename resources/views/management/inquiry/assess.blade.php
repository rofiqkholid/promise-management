@extends('layouts.app')

@section('title', 'Product Feasibility Assessment - Promise Management')

@section('content')
<div class="flex-1 overflow-y-auto p-4 pt-17.5 space-y-4 transition-colors duration-200"
     x-data="{
         selections: {
             @foreach($scoreCategories as $cat)
                 '{{ $cat->category_id }}': '{{ isset($product->assessment) && $product->assessment->details->where('category_id', $cat->category_id)->first() ? $product->assessment->details->where('category_id', $cat->category_id)->first()->option_id : '' }}',
             @endforeach
         },
         remarks: '{{ isset($product->assessment) ? addslashes($product->assessment->remarks) : '' }}',
         loading: false,
         scoreSum: 0,
         rankLabel: 'Hold',
         rankCode: 'D',

         updateScoreSum() {
             let sum = 0;
             document.querySelectorAll('.opt-select').forEach(el => {
                 if (el.checked) {
                     sum += parseInt(el.getAttribute('data-score'));
                 }
             });
             this.scoreSum = sum;
             this.updateRank();
         },

         updateRank() {
             // Basic local rank preview based on typical boundaries (seeded in AssessmentRankingSeeder)
             if (this.scoreSum >= 400) {
                 this.rankCode = 'A';
                 this.rankLabel = 'Review Now';
             } else if (this.scoreSum >= 300) {
                 this.rankCode = 'B';
                 this.rankLabel = 'Review Next';
             } else if (this.scoreSum >= 200) {
                 this.rankCode = 'C';
                 this.rankLabel = 'Pending';
             } else {
                 this.rankCode = 'D';
                 this.rankLabel = 'Hold';
             }
         },

         submitAssessment() {
             const requiredCatCount = {{ $scoreCategories->count() }};
             const selectedCount = Object.values(this.selections).filter(val => val !== '').length;
             if (selectedCount < requiredCatCount) {
                 alert('Please choose an option for all scoring categories.');
                 return;
             }

             this.loading = true;
             fetch('{{ url('management/inquiry-product') }}/{{ $product->id }}/assess', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                      'Accept': 'application/json'
                 },
                 body: JSON.stringify({
                     selections: Object.values(this.selections),
                     remarks: this.remarks
                 })
             })
             .then(res => res.json())
             .then(data => {
                 this.loading = false;
                 if (data.success) {
                     window.location.href = '{{ route('management.inquiry.show', $inquiry->id) }}';
                 } else {
                     alert('Error: ' + data.message);
                 }
             })
             .catch(err => {
                 this.loading = false;
                 console.error(err);
                 alert('An error occurred while saving the assessment.');
             });
         }
     }"
     x-init="updateScoreSum()">

    <!-- Loader Overlay -->
    <div x-show="loading" class="fixed inset-0 z-50 bg-black/30 flex items-center justify-center" style="display: none;">
        <i class="fa-solid fa-circle-notch fa-spin text-blue-600 text-3xl bg-white dark:bg-slate-800 p-4 rounded-full shadow-lg"></i>
    </div>

    <!-- Header & Back Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <a href="{{ route('management.inquiry.show', $inquiry->id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline mb-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Inquiry Details
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800 dark:text-white">Product Feasibility Assessment</h1>
            <p class="text-xs text-slate-400 mt-1">Part: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $product->customer_part_no }} - {{ $product->customer_part_name }}</span></p>
        </div>
    </div>

    <!-- Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Selection Cards (Col Span 2) -->
        <div class="lg:col-span-2 space-y-4">
            @foreach($scoreCategories as $cat)
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-4 transition-colors duration-200">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-700 pb-2 mb-3 uppercase tracking-wider text-xs text-slate-400">
                        {{ $cat->category_name }}
                    </h3>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($cat->options as $opt)
                            <label class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/60 cursor-pointer hover:border-blue-500 hover:bg-slate-100/50 dark:hover:bg-slate-800/50 transition-colors">
                                <input type="radio" 
                                       name="cat_{{ $cat->category_id }}" 
                                       value="{{ $opt->option_id }}"
                                       data-score="{{ $opt->score_value }}"
                                       class="opt-select mt-1"
                                       x-model="selections['{{ $cat->category_id }}']"
                                       @change="updateScoreSum()"
                                       {{ in_array($opt->option_id, $selectedOptionIds) ? 'checked' : '' }}>
                                <div class="text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-800 dark:text-white">{{ $opt->option_name }}</span>
                                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 text-[10px] font-bold rounded-xs">+{{ $opt->score_value }}</span>
                                    </div>
                                    <span class="block text-[11px] text-slate-400 mt-1">{{ $opt->description }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sidebar / Assessment Summary Panel -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 p-4 transition-colors duration-200 space-y-4 sticky top-20">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white border-b border-slate-100 dark:border-slate-700 pb-2">Assessment Results</h3>
                
                <!-- Live Score Badge -->
                <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700/60 text-center space-y-2">
                    <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Live Assessment Score</span>
                    <span class="text-5xl font-black text-blue-600 dark:text-blue-400 block" x-text="scoreSum">0</span>
                    
                    <div class="pt-2 flex justify-center items-center gap-2">
                        <span class="text-xs text-slate-400">Rank:</span>
                        <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-bold text-sm" x-text="rankCode">D</span>
                    </div>
                    <div class="pt-1">
                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full"
                              :class="{
                                  'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/30': rankCode === 'A',
                                  'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30': rankCode === 'B',
                                  'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-900/30': rankCode === 'C',
                                  'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-850': rankCode === 'D'
                              }"
                              x-text="rankLabel">
                            Hold
                        </span>
                    </div>
                </div>

                <!-- Remarks Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Remarks</label>
                    <textarea x-model="remarks" rows="5" placeholder="Detail notes about capabilities, limitations, or negotiation details"
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 transition-colors"></textarea>
                </div>

                <!-- Actions -->
                <div class="pt-2 flex flex-col gap-2">
                    <button type="button" @click="submitAssessment"
                            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider transition-colors">
                        Save Assessment
                    </button>
                    <a href="{{ route('management.inquiry.show', $inquiry->id) }}"
                       class="w-full py-2.5 text-center border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
