<!-- Slide-over Right Drawer for Inquiry Product Details and Chat -->
<div x-data="inquiryProductChat()"
     x-show="showChatDrawer"
     @open-product-chat.window="openChat($event.detail.id)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="fixed inset-y-0 right-0 z-[9000] w-full max-w-5xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-700 h-full overflow-hidden"
     style="display: none;"
     x-cloak>

    <!-- Drawer Header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex-shrink-0">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-full">
                <i class="fa-solid fa-comments"></i>
            </span>
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white" x-text="chatProductDetail?.customer_part_name || 'Item Detail & Chat'"></h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider mt-0.5" x-text="chatProductDetail?.customer_part_no"></p>
            </div>
        </div>
        <button @click="closeChatDrawer()" 
                class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-xl leading-none">
            &times;
        </button>
    </div>

    <!-- Drawer Body (Split into Left & Right Panels) -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
        
        <!-- Left Panel: Detailed Information of Selected Item -->
        <div class="w-full md:w-[32%] p-5 bg-slate-50/50 dark:bg-slate-900/30 border-r border-slate-200 dark:border-slate-700/80 overflow-y-auto space-y-5 h-full">
            <div class="border-b border-slate-200 dark:border-slate-700 pb-2">
                <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Item Specifications</h4>
            </div>

            <template x-if="!chatProductDetail">
                <div class="space-y-4 animate-pulse p-1">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-2/3"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-5/6"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/2"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-2/3"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/3"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/2"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/2"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-3/4"></div>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 dark:border-slate-700/80 pt-4 space-y-2">
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/3"></div>
                        <div class="flex gap-4">
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-16"></div>
                            <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded-xs w-16"></div>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 dark:border-slate-700/80 pt-4 space-y-3">
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-xs w-1/3"></div>
                        <div class="h-14 bg-slate-200 dark:bg-slate-700 rounded-xs w-full"></div>
                    </div>
                </div>
            </template>

            <template x-if="chatProductDetail">
                <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Part Number</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.customer_part_no || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Part Name</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.customer_part_name || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Variant</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.variant || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Category</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.part_category || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Destination</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.destination || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-400 uppercase">Annual Volume</span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-100" x-text="chatProductDetail?.annual_volume ? Number(chatProductDetail.annual_volume).toLocaleString() : '—'"></span>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700/80 pt-4 space-y-2">
                        <span class="block text-[10px] font-medium text-slate-400 uppercase">Available Design Data</span>
                        <div class="flex gap-4">
                            <span class="inline-flex items-center gap-1.5 text-xs" :class="chatProductDetail?.has_2d_data ? 'text-emerald-600 dark:text-emerald-450 font-semibold' : 'text-slate-400 dark:text-slate-600'">
                                <i class="fa-solid" :class="chatProductDetail?.has_2d_data ? 'fa-square-check' : 'fa-square'"></i> 2D Drawing
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs" :class="chatProductDetail?.has_3d_data ? 'text-emerald-600 dark:text-emerald-450 font-semibold' : 'text-slate-400 dark:text-slate-600'">
                                <i class="fa-solid" :class="chatProductDetail?.has_3d_data ? 'fa-square-check' : 'fa-square'"></i> 3D Model
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-xs" :class="chatProductDetail?.has_tech_doc ? 'text-emerald-600 dark:text-emerald-450 font-semibold' : 'text-slate-400 dark:text-slate-600'">
                                <i class="fa-solid" :class="chatProductDetail?.has_tech_doc ? 'fa-square-check' : 'fa-square'"></i> Tech Doc
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700/80 pt-4 space-y-3">
                        <span class="block text-[10px] font-medium text-slate-400 uppercase">Feasibility Scoring</span>
                        
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Score</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-xl font-bold text-slate-800 dark:text-white" x-text="chatProductDetail?.assessment?.total_score || 0"></span>
                                    <span class="text-[10px] text-slate-400 font-medium">/100</span>
                                </div>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-right">Priority</span>
                                <span class="inline-block mt-1 px-2.5 py-0.5 text-[10px] font-bold border"
                                      :class="chatProductDetail?.assessment?.ranking?.priority_label === 'Review Now' ? 'bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900/30' :
                                              chatProductDetail?.assessment?.ranking?.priority_label === 'Review Next' ? 'bg-amber-50 dark:bg-amber-955/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900/30' :
                                              'bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900/30'"
                                      x-text="chatProductDetail?.assessment?.ranking?.priority_label || 'Pending'">
                                </span>
                            </div>
                        </div>

                        <div x-show="chatProductDetail?.remarks" class="p-3 bg-blue-50/50 dark:bg-slate-800/40 border border-blue-100/50 dark:border-slate-700 text-xs">
                            <span class="block text-[10px] font-bold text-blue-800 dark:text-slate-400 uppercase tracking-wider mb-1">Remarks</span>
                            <p class="text-slate-700 dark:text-slate-350 leading-relaxed" x-text="chatProductDetail?.remarks"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Right Panel: Chat Messages -->
        <div class="w-full md:w-[68%] flex flex-col bg-[#f0f4f9] dark:bg-slate-950 h-full overflow-hidden relative border-l border-slate-200 dark:border-slate-800"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="if ($event.relatedTarget === null || !$el.contains($event.relatedTarget)) { isDragging = false }"
             @drop.prevent>

            <!-- Drag and Drop Overlay -->
            <div x-show="isDragging" 
                 class="absolute inset-0 bg-indigo-600/10 dark:bg-indigo-600/20 backdrop-blur-xs flex flex-col items-center justify-center border-2 border-dashed border-indigo-500 z-[99] transition-all m-4 rounded-xs"
                 @dragover.prevent
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="handleDrop($event); isDragging = false"
                 x-cloak>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-md shadow-lg flex flex-col items-center gap-3 select-none pointer-events-none">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-indigo-600 dark:text-indigo-400 animate-bounce"></i>
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Drop files here to upload</p>
                </div>
            </div>
            
            <!-- Right Panel Header with Filter Segmented Control -->
            <div class="px-4 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-comments text-indigo-500 text-xs"></i>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Discussion Board</span>
                </div>
                <!-- Filter Segmented Tabs -->
                <div class="flex bg-slate-100 dark:bg-slate-800 p-0.5 rounded-xs border border-slate-200/60 dark:border-slate-700">
                    <button @click="showOnlyMediaAndLinks = false" 
                            type="button" 
                            class="px-2.5 py-0.5 text-[9.5px] font-bold rounded-xs transition-all cursor-pointer"
                            :class="!showOnlyMediaAndLinks 
                                ? 'bg-white dark:bg-slate-700 text-indigo-650 dark:text-indigo-400 shadow-xs' 
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'">
                        All
                    </button>
                    <button @click="showOnlyMediaAndLinks = true" 
                            type="button" 
                            class="px-2.5 py-0.5 text-[9.5px] font-bold rounded-xs transition-all cursor-pointer flex items-center gap-1"
                            :class="showOnlyMediaAndLinks 
                                ? 'bg-white dark:bg-slate-700 text-indigo-650 dark:text-indigo-400 shadow-xs' 
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'">
                        <i class="fa-solid fa-paperclip text-[8.5px]"></i> Files & Links
                    </button>
                </div>
            </div>

            <!-- Chat history loading state / indicators -->
            <div x-show="loadingChats" class="flex justify-center py-2 bg-white/75 dark:bg-slate-900/75 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                <div class="flex items-center gap-2 px-3 py-1 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-full shadow-xs text-[10.5px] font-semibold border border-slate-200 dark:border-slate-700">
                    <i class="fa-solid fa-spinner animate-spin text-indigo-600 dark:text-indigo-450 text-xs"></i>
                    <span>Loading history...</span>
                </div>
            </div>

            <!-- Messages list -->
            <div id="chat-messages-container" 
                 @scroll="handleChatScroll($event)"
                 class="flex-1 overflow-y-auto p-4 space-y-3 scroll-smooth flex flex-col">
                
                <template x-if="getFilteredMessages().length === 0">
                    <div class="flex flex-col items-center justify-center h-full py-12 text-slate-400 dark:text-slate-500">
                        <i class="fa-solid text-5xl mb-3 text-slate-300 dark:text-slate-700" :class="showOnlyMediaAndLinks ? 'fa-folder-open' : 'fa-comments'"></i>
                        <p class="text-xs font-semibold" x-text="showOnlyMediaAndLinks ? 'No files or links shared in this chat yet.' : 'No messages yet. Start the conversation!'"></p>
                    </div>
                </template>

                <template x-for="(msg, index) in getFilteredMessages()" :key="msg.id">
                    <div class="flex items-start gap-2 mb-2 max-w-[85%] w-full"
                         :class="msg.user_id == {{ Auth::user()->id }} ? 'self-end flex-row-reverse' : 'self-start flex-row'">
                        
                        <!-- User Initial Avatar (only for others) -->
                        <template x-if="msg.user_id != {{ Auth::user()->id }}">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center font-extrabold text-[10px] flex-shrink-0 select-none shadow-xs border"
                                 :class="getUserColor(msg.user_id).avatar"
                                 x-text="msg.user_name ? msg.user_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'U'">
                            </div>
                        </template>

                        <!-- Chat bubble -->
                        <div class="rounded-xs px-3.5 py-2 pb-5 text-xs shadow-xs relative flex flex-col min-w-[125px] w-auto max-w-full group"
                             :class="msg.user_id == {{ Auth::user()->id }} 
                                 ? 'bg-blue-600 dark:bg-indigo-600 text-white border-none bubble-out' 
                                 : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700 bubble-in'">
                            
                            <!-- Delete Button Overlay (Self messages only, group hover) -->
                            <template x-if="msg.user_id == {{ Auth::user()->id }}">
                                <button @click="deleteMessage(msg.id)" 
                                        type="button"
                                        class="absolute -top-2 -right-2 w-5.5 h-5.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-750 text-slate-400 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 flex items-center justify-center shadow-md opacity-0 group-hover:opacity-100 transition-all duration-200 cursor-pointer z-20"
                                        :title="'Delete Message'">
                                    <i class="fa-solid fa-trash-can text-[8.5px]"></i>
                                </button>
                            </template>

                            <!-- Sender Name (Only if others) -->
                            <span x-show="msg.user_id != {{ Auth::user()->id }}" 
                                  class="block text-[10.5px] font-extrabold mb-1" 
                                  :class="getUserColor(msg.user_id).name"
                                  x-text="msg.user_name"></span>

                            <!-- Image Preview in Chat Bubble (if it is an image file) -->
                            <template x-if="msg.file_path && isImageType(msg.file_type)">
                                <div x-data="{ imageLoaded: false }" class="mt-1 mb-1.5 border border-slate-200/80 dark:border-slate-700 rounded-xs overflow-hidden bg-slate-100 dark:bg-slate-800 max-w-[220px] relative group min-h-[80px] flex items-center justify-center">
                                    <!-- Loader Spinner -->
                                    <div x-show="!imageLoaded" class="absolute inset-0 flex items-center justify-center bg-slate-100/50 dark:bg-slate-800/50">
                                        <i class="fa-solid fa-spinner animate-spin text-indigo-500 text-sm"></i>
                                    </div>
                                    
                                    <img :src="msg.file_url" 
                                          :alt="msg.file_name" 
                                          @load="imageLoaded = true"
                                          class="w-full h-auto object-cover max-h-40 hover:scale-102 transition-all duration-300 chat-image-thumb"
                                          :class="imageLoaded ? 'opacity-100' : 'opacity-0'" />
                                          
                                    <!-- Download button overlay -->
                                    <a x-show="imageLoaded"
                                       :href="msg.download_url" 
                                       download
                                       class="absolute bottom-1.5 right-1.5 w-6.5 h-6.5 rounded-xs bg-slate-900/70 hover:bg-slate-900 text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 cursor-pointer shadow-xs"
                                       :title="'Download Image'">
                                        <i class="fa-solid fa-download text-[9px]"></i>
                                    </a>
                                </div>
                            </template>

                            <!-- Attachment (if non-image file) -->
                            <div x-show="msg.file_path && !isImageType(msg.file_type)" 
                                  class="border rounded-xs p-2.5 flex items-center justify-between gap-3 text-xs mt-1 mb-1.5"
                                  :class="msg.user_id == {{ Auth::user()->id }} 
                                      ? 'bg-blue-700/50 border-blue-500/20 text-white' 
                                      : 'bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100'">
                                
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <span class="text-lg flex-shrink-0" :class="getFileIcon(msg.file_type)"></span>
                                    <div class="overflow-hidden">
                                        <span class="block font-semibold truncate text-[11px] leading-tight" 
                                              x-text="msg.file_name"
                                              :title="msg.file_name">
                                        </span>
                                        <span class="block text-[9px] opacity-75 mt-0.5" x-text="((msg.file_name ? msg.file_name.split('.').pop().toUpperCase() : '') || 'FILE') + ' | ' + formatBytes(msg.file_size)"></span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <!-- Preview Button -->
                                    <template x-if="isFilePreviewable(msg.file_type)">
                                        <button @click="previewAttachment(msg)" 
                                                class="w-7 h-7 flex items-center justify-center rounded-xs transition-colors border shadow-xs"
                                                :class="msg.user_id == {{ Auth::user()->id }}
                                                    ? 'bg-blue-800/90 hover:bg-blue-900 border-blue-700 text-white'
                                                    : 'bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200'"
                                                :title="'Preview File'">
                                            <i class="fa-solid fa-eye text-[10px]"></i>
                                        </button>
                                    </template>
                                    
                                    <!-- Download Button -->
                                    <a :href="msg.download_url" 
                                       download
                                       class="w-7 h-7 flex items-center justify-center rounded-xs transition-colors border shadow-xs"
                                       :class="msg.user_id == {{ Auth::user()->id }}
                                           ? 'bg-blue-800/90 hover:bg-blue-900 border-blue-700 text-white'
                                           : 'bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200'"
                                       :title="'Download File'">
                                        <i class="fa-solid fa-download text-[10px]"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Text Message -->
                            <div x-show="msg.message" 
                                 class="markdown-content text-[12.5px] pr-2 break-words leading-relaxed"
                                 :class="msg.user_id == {{ Auth::user()->id }} ? 'text-white' : 'text-slate-800 dark:text-slate-100'"
                                 x-html="parseMarkdown(msg.message)"></div>

                            <!-- Time Label at Bottom Right of Bubble -->
                            <div class="absolute bottom-0.5 right-2 flex items-center gap-0.5 text-[8.5px] opacity-75 font-semibold select-none"
                                 :class="msg.user_id == {{ Auth::user()->id }} ? 'text-blue-100' : 'text-slate-400 dark:text-slate-500'">
                                <span x-text="msg.time_label.split(', ')[1] || msg.time_label"></span>
                                <template x-if="msg.user_id == {{ Auth::user()->id }}">
                                    <i class="fa-solid fa-check-double text-blue-200 text-[8px] ml-0.5"></i>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Floating Scroll to Bottom Button -->
            <button x-show="showScrollToBottomBtn" 
                    @click="scrollToBottom(); showScrollToBottomBtn = false;" 
                    type="button"
                    class="absolute bottom-22 right-6 z-10 w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 shadow-md flex items-center justify-center text-indigo-600 dark:text-indigo-450 hover:bg-slate-50 dark:hover:bg-slate-750 transition-all cursor-pointer hover:scale-105"
                    x-cloak>
                <i class="fa-solid fa-chevron-down text-xs mt-0.5"></i>
            </button>

            <!-- Chat Input Area with Pre-upload Preview Card inside it -->
            <div class="bg-slate-100 dark:bg-slate-900/60 p-3 flex flex-col border-t border-slate-200 dark:border-slate-800 flex-shrink-0 relative">
                
                <!-- Pre-upload File Preview Cards (Multiple files support) -->
                <div x-show="chatAttachments.length > 0" class="flex flex-col gap-1.5 mb-2.5 max-h-36 overflow-y-auto chat-files-scroll" x-cloak>
                    <template x-for="(file, index) in chatAttachments" :key="index">
                        <div class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-sm flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span class="text-base flex-shrink-0" :class="getFileIcon(file.type)"></span>
                                <div class="overflow-hidden">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate block text-[11px]" x-text="file.name"></span>
                                    <span class="text-[9px] text-slate-400 block mt-0.5" x-text="(file.name.split('.').pop().toUpperCase() || 'FILE') + ' | ' + formatBytes(file.size)"></span>
                                </div>
                            </div>
                            <button @click="removeAttachment(index)" type="button" class="text-rose-500 hover:text-rose-700 font-bold text-sm w-5 h-5 flex items-center justify-center rounded-xs hover:bg-slate-100 dark:hover:bg-slate-855 cursor-pointer">
                                &times;
                            </button>
                        </div>
                    </template>
                </div>
                
                <!-- Formatting Toolbar (shows when text is selected/blocked) -->
                <div x-show="showFormatToolbar" 
                     x-transition 
                     class="absolute bottom-[54px] left-[58px] z-20 p-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-md flex items-center gap-1 w-fit" 
                     x-cloak>
                    <button type="button" @mousedown.prevent="applyFormatting('bold')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs font-extrabold text-[11px] cursor-pointer" 
                             :class="isBoldActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Bold">
                        B
                    </button>
                    <button type="button" @mousedown.prevent="applyFormatting('italic')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs italic font-serif text-[11px] cursor-pointer" 
                             :class="isItalicActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Italic">
                        I
                    </button>
                    <button type="button" @mousedown.prevent="applyFormatting('strike')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs line-through text-[11px] cursor-pointer" 
                             :class="isStrikeActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Strikethrough">
                        S
                    </button>
                    <button type="button" @mousedown.prevent="applyFormatting('code')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs font-mono text-[10px] cursor-pointer" 
                             :class="isCodeActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Inline Code">
                        &lt;/&gt;
                    </button>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-0.5"></div>
                    <button type="button" @mousedown.prevent="applyFormatting('ol')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs cursor-pointer" 
                             :class="isOlActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Ordered List">
                        <i class="fa-solid fa-list-ol text-[9.5px]"></i>
                    </button>
                    <button type="button" @mousedown.prevent="applyFormatting('ul')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs cursor-pointer" 
                             :class="isUlActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Bullet List">
                        <i class="fa-solid fa-list-ul text-[9.5px]"></i>
                    </button>
                    <button type="button" @mousedown.prevent="applyFormatting('quote')" 
                             class="w-6 h-6 flex items-center justify-center rounded-xs cursor-pointer" 
                             :class="isQuoteActive ? 'bg-indigo-100 dark:bg-slate-700 text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                             title="Blockquote">
                        <i class="fa-solid fa-quote-right text-[9.5px]"></i>
                    </button>
                </div>

                <div class="flex items-end gap-2">
                    <!-- Attachment Button -->
                    <input type="file" 
                           id="chat-file-input" 
                           @change="handleFileChange($event)"
                           class="hidden"
                           multiple>
                    <button type="button" 
                            @click="document.getElementById('chat-file-input').click()"
                            class="w-9 h-9 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-350 hover:bg-slate-200/50 dark:hover:bg-slate-800 rounded-xs border border-slate-300 dark:border-slate-700 transition-colors cursor-pointer bg-white dark:bg-slate-800 flex-shrink-0"
                            title="Attach files">
                        <i class="fa-solid fa-paperclip text-sm"></i>
                    </button>

                    <!-- Textarea Message (Auto-height adjustments) -->
                    <textarea x-model="chatInputMessage" 
                              rows="1" 
                              x-init="$el.style.height = '36px'; $watch('chatInputMessage', () => { if (!chatInputMessage.trim()) { $el.style.height = '36px'; } })"
                              @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px';"
                              @keydown.enter="if(!event.shiftKey) { event.preventDefault(); sendChatMessage(); $el.style.height = '36px'; }"
                              @select="handleTextSelection($event)"
                              @keyup="handleTextSelection($event)"
                              @click="handleTextSelection($event)"
                              @blur="setTimeout(() => { showFormatToolbar = false; }, 200)"
                              placeholder="Write your message here... (Shift+Enter for newline)"
                              class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs px-3 py-2 text-xs focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-500 text-slate-800 dark:text-slate-100 resize-none max-h-32 min-h-[36px] overflow-y-auto chat-textarea leading-relaxed"></textarea>

                    <!-- Send Button -->
                    <button type="button" 
                            @click="sendChatMessage(); document.querySelector('.chat-textarea').style.height = '36px';"
                            :disabled="sendingMessage || (!chatInputMessage.trim() && chatAttachments.length === 0)"
                            class="w-9 h-9 flex items-center justify-center bg-indigo-600 hover:bg-indigo-750 text-white rounded-xs transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-sm flex-shrink-0">
                        <i class="fa-solid" :class="sendingMessage ? 'fa-spinner animate-spin text-xs' : 'fa-paper-plane text-xs'"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Attachment Preview Modal -->
    <div x-show="pdfPreviewUrl" 
         class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" 
         x-cloak
         style="display: none;">
        <div class="bg-white dark:bg-slate-800 w-full max-w-6xl h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 rounded-xs overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <span class="text-xs font-bold text-slate-800 dark:text-white truncate flex items-center gap-2">
                    <i class="fa-solid" :class="pdfPreviewName.endsWith('.pdf') ? 'fa-file-pdf text-rose-500 text-sm' : 'fa-file-word text-blue-500 text-sm'"></i>
                    <span x-text="pdfPreviewName">Document Preview</span>
                </span>
                <button @click="pdfPreviewUrl = null" 
                        class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-lg leading-none">
                    &times;
                </button>
            </div>
            <!-- Localhost Office Preview Warning -->
            <div x-show="!pdfPreviewName.endsWith('.pdf') && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')" 
                 class="bg-amber-50 dark:bg-amber-955/20 border-b border-amber-200 dark:border-amber-900/30 px-5 py-2.5 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                    <span><strong>Note:</strong> Microsoft Office Viewer cannot load files from <strong>localhost</strong>. Once deployed to a public server, the preview will render here automatically.</span>
                </div>
                <a :href="pdfPreviewUrl ? pdfPreviewUrl.split('?src=')[1] ? decodeURIComponent(pdfPreviewUrl.split('?src=')[1]) : pdfPreviewUrl : '#'" download class="px-2.5 py-1 bg-amber-600 hover:bg-amber-750 text-white rounded-xs font-bold whitespace-nowrap text-[10px]">
                    Download instead
                </a>
            </div>
            <!-- Modal Content (Iframe) -->
            <iframe :src="pdfPreviewUrl" class="w-full flex-1 border-0 bg-slate-100 dark:bg-slate-900"></iframe>
        </div>
    </div>

</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.css">
<style>
    /* Sleek custom scrollbar */
    #chat-messages-container::-webkit-scrollbar {
        width: 6px;
    }
    #chat-messages-container::-webkit-scrollbar-track {
        background: transparent;
    }
    #chat-messages-container::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }
    .dark #chat-messages-container::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.15);
    }

    /* Small scrollbar for textarea */
    .chat-textarea::-webkit-scrollbar {
        width: 4px;
    }
    .chat-textarea::-webkit-scrollbar-track {
        background: transparent;
    }
    .chat-textarea::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.15);
        border-radius: 2px;
    }
    .dark .chat-textarea::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.2);
    }

    /* WhatsApp Bubble Tails */
    .bubble-in {
        position: relative;
        border-top-left-radius: 0 !important;
        border-top-right-radius: 12px !important;
        border-bottom-left-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
    }
    .bubble-in::before {
        content: "";
        position: absolute;
        top: -1px;
        left: -7px;
        width: 7px;
        height: 10px;
        background: inherit;
        clip-path: polygon(100% 0, 0 0, 100% 100%);
        border-top: 1.2px solid rgba(226, 232, 240, 1);
    }
    .dark .bubble-in::before {
        border-top-color: rgba(51, 65, 85, 1);
    }
    .bubble-out {
        position: relative;
        border-top-right-radius: 0 !important;
        border-top-left-radius: 12px !important;
        border-bottom-left-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
    }
    .bubble-out::after {
        content: "";
        position: absolute;
        top: 0;
        right: -7px;
        width: 7px;
        height: 10px;
        background: inherit;
        clip-path: polygon(0 0, 100% 0, 0 100%);
    }

    /* Markdown content inside bubbles styling */
    .markdown-content p { margin-bottom: 0.5rem; line-height: 1.5; }
    .markdown-content p:last-child { margin-bottom: 0; }
    .markdown-content strong { font-weight: 700; }
    .markdown-content em { font-style: italic; }
    .markdown-content ul { list-style-type: disc; margin-left: 1.25rem; margin-bottom: 0.5rem; }
    .markdown-content ol { list-style-type: decimal; margin-left: 1.25rem; margin-bottom: 0.5rem; }
    .markdown-content code { background-color: rgba(0, 0, 0, 0.06); padding: 2px 4px; font-family: monospace; font-size: 90%; border-radius: 3px; }
    .dark .markdown-content code { background-color: rgba(255, 255, 255, 0.15); }
    .markdown-content pre { background-color: rgba(0, 0, 0, 0.04); padding: 6px 10px; border-radius: 4px; font-family: monospace; font-size: 85%; overflow-x: auto; margin-bottom: 0.5rem; }
    .dark .markdown-content pre { background-color: rgba(255, 255, 255, 0.08); }
    .markdown-content blockquote {
        border-left: 3px solid rgba(0, 0, 0, 0.2);
        padding-left: 0.5rem;
        margin-left: 0;
        margin-right: 0;
        margin-bottom: 0.5rem;
        font-style: italic;
        opacity: 0.85;
    }
    .dark .markdown-content blockquote {
        border-left-color: rgba(255, 255, 255, 0.3);
    }

    /* Fix Viewer.js z-index to be higher than the drawer */
    .viewer-container {
        z-index: 99999 !important;
    }

    /* Thicker scrollbar style for attachments list preview */
    .chat-files-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .chat-files-scroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.03);
        border-radius: 3px;
    }
    .chat-files-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(100, 116, 139, 0.4);
        border-radius: 3px;
    }
    .dark .chat-files-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(148, 163, 184, 0.4);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
window.inquiryProductChat = function() {
    return {
        showChatDrawer: false,
        chatProductDetail: null,
        chatMessages: [],
        chatInputMessage: '',
        chatAttachments: [],
        isDragging: false,
        loadingChats: false,
        sendingMessage: false,
        hasMoreChats: true,
        oldestChatId: null,
        echoChannel: null,
        viewerInstance: null,
        pdfPreviewUrl: null,
        pdfPreviewName: '',
        showOnlyMediaAndLinks: false,
        showScrollToBottomBtn: false,
        showFormatToolbar: false,
        selectedTextStart: 0,
        selectedTextEnd: 0,
        isBoldActive: false,
        isItalicActive: false,
        isStrikeActive: false,
        isCodeActive: false,
        isOlActive: false,
        isUlActive: false,
        isQuoteActive: false,

        openChat(prodId) {
            this.chatProductDetail = null;
            this.chatMessages = [];
            this.chatInputMessage = '';
            this.chatAttachments = [];
            this.hasMoreChats = true;
            this.oldestChatId = null;
            this.showChatDrawer = true;

            // Fetch details from API
            fetch(`{{ url('management/inquiry-product') }}/${prodId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                this.chatProductDetail = data.product || data;
            });

            // Load chats history
            this.loadChats(prodId);

            // Setup Reverb listener
            if (typeof window.Echo !== 'undefined') {
                this.echoChannel = window.Echo.private(`inquiry-product-chat.${prodId}`)
                    .listen('InquiryProductChatMessageSent', (e) => {
                        this.chatMessages.push(e);
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.initViewer();
                        });
                    })
                    .listen('InquiryProductChatMessageDeleted', (e) => {
                        this.chatMessages = this.chatMessages.filter(msg => Number(msg.id) !== Number(e.chatId));
                    });
            }
        },

        closeChatDrawer() {
            this.showChatDrawer = false;
            // Leave Reverb channel
            if (this.echoChannel && typeof window.Echo !== 'undefined') {
                window.Echo.leave(`inquiry-product-chat.${this.chatProductDetail.id}`);
                this.echoChannel = null;
            }
            if (this.viewerInstance) {
                this.viewerInstance.destroy();
                this.viewerInstance = null;
            }
            this.chatProductDetail = null;
            this.chatMessages = [];
        },

        loadChats(prodId, beforeId = null) {
            const activeId = prodId || (this.chatProductDetail ? this.chatProductDetail.id : null);
            if (!activeId) return;
            this.loadingChats = true;

            let url = `{{ url('management/inquiry-product') }}/${activeId}/chats`;
            if (beforeId) {
                url += `?before_id=${beforeId}`;
            }

            const container = document.getElementById('chat-messages-container');
            const oldScrollHeight = container ? container.scrollHeight : 0;

            fetch(url, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.loadingChats = false;
                if (data.success && data.messages) {
                    if (data.messages.length < 20) {
                        this.hasMoreChats = false;
                    }
                    if (beforeId) {
                        this.chatMessages = [...data.messages, ...this.chatMessages];
                        this.$nextTick(() => {
                            if (container) {
                                container.scrollTop = container.scrollHeight - oldScrollHeight;
                            }
                            this.initViewer();
                        });
                    } else {
                        this.chatMessages = data.messages;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.initViewer();
                        });
                    }

                    if (this.chatMessages.length > 0) {
                        this.oldestChatId = this.chatMessages[0].id;
                    }
                }
            })
            .catch(() => {
                this.loadingChats = false;
            });
        },

        sendChatMessage() {
            if (!this.chatProductDetail) return;
            if (!this.chatInputMessage.trim() && this.chatAttachments.length === 0) return;

            this.sendingMessage = true;

            if (this.chatAttachments.length === 0) {
                const formData = new FormData();
                formData.append('message', this.chatInputMessage);
                this.chatInputMessage = '';
                this.postSingleMessage(formData, true);
                return;
            }

            let promiseChain = Promise.resolve();
            const total = this.chatAttachments.length;
            const messageToSend = this.chatInputMessage;

            this.chatInputMessage = '';

            this.chatAttachments.forEach((file, index) => {
                promiseChain = promiseChain.then(() => {
                    const formData = new FormData();
                    formData.append('file', file);
                    if (index === 0) {
                        formData.append('message', messageToSend);
                    } else {
                        formData.append('message', '');
                    }
                    const isLast = (index === total - 1);
                    return this.postSingleMessage(formData, isLast);
                });
            });
        },

        postSingleMessage(formData, isFinalRequest) {
            return fetch(`{{ url('management/inquiry-product') }}/${this.chatProductDetail.id}/chats`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.message) {
                    this.chatMessages.push(data.message);
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        this.initViewer();
                    });
                } else {
                    showToast(data.message || 'Failed to send file.', 'error');
                }
                
                if (isFinalRequest) {
                    this.sendingMessage = false;
                    this.clearAttachment();
                }
            })
            .catch(() => {
                if (isFinalRequest) {
                    this.sendingMessage = false;
                    this.clearAttachment();
                }
                showToast('Failed to send message due to network error.', 'error');
            });
        },

        deleteMessage(chatId) {
            if (typeof confirmDialog === 'function') {
                confirmDialog({
                    title: 'Delete Message?',
                    text: 'Are you sure you want to delete this message? This action cannot be undone.',
                    icon: 'warning',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#ef4444',
                    onConfirm: () => {
                        this.executeDeleteMessage(chatId);
                    }
                });
            } else {
                if (confirm('Are you sure you want to delete this message?')) {
                    this.executeDeleteMessage(chatId);
                }
            }
        },

        executeDeleteMessage(chatId) {
            fetch(`{{ url('management/inquiry-product-chat') }}/${chatId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.chatMessages = this.chatMessages.filter(msg => msg.id !== chatId);
                    showToast('Message deleted successfully.', 'success');
                } else {
                    showToast(data.message || 'Failed to delete message.', 'error');
                }
            })
            .catch(() => {
                showToast('Failed to delete message due to network error.', 'error');
            });
        },

        handleChatScroll(e) {
            const container = e.target;
            const isScrolledUp = container.scrollTop + container.clientHeight < container.scrollHeight - 200;
            this.showScrollToBottomBtn = isScrolledUp;
            if (container.scrollTop === 0 && this.hasMoreChats && !this.loadingChats) {
                this.loadChats(null, this.oldestChatId);
            }
        },

        handleFileChange(e) {
            const files = Array.from(e.target.files);
            this.addFiles(files);
        },

        handleDrop(e) {
            const files = Array.from(e.dataTransfer.files);
            this.addFiles(files);
        },

        addFiles(files) {
            const validFiles = [];
            files.forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    showToast(`File "${file.name}" exceeds the 10MB limit.`, 'warning');
                    return;
                }
                validFiles.push(file);
            });
            this.chatAttachments = [...this.chatAttachments, ...validFiles];
            const input = document.getElementById('chat-file-input');
            if (input) input.value = '';
        },

        removeAttachment(index) {
            this.chatAttachments = this.chatAttachments.filter((_, i) => i !== index);
        },

        clearAttachment() {
            this.chatAttachments = [];
            const input = document.getElementById('chat-file-input');
            if (input) input.value = '';
        },

        isFilePreviewable(mime) {
            if (!mime) return false;
            return this.isImageType(mime) || 
                   mime === 'application/pdf' ||
                   mime === 'application/msword' ||
                   mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
                   mime === 'application/vnd.ms-excel' ||
                   mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ||
                   mime === 'application/vnd.ms-powerpoint' ||
                   mime === 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        },

        isImageType(mime) {
            if (!mime) return false;
            return mime.startsWith('image/');
        },

        previewAttachment(msg) {
            const url = msg.file_url;
            const mime = msg.file_type;
            if (mime === 'application/pdf') {
                this.pdfPreviewName = msg.file_name;
                this.pdfPreviewUrl = url;
            } else if (this.isImageType(mime)) {
                const img = document.querySelector(`.chat-image-thumb[src="${url}"]`);
                if (img) {
                    img.click();
                }
            } else {
                this.pdfPreviewName = msg.file_name;
                const absoluteFileUrl = window.location.origin + url;
                this.pdfPreviewUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(absoluteFileUrl);
            }
        },

        getUserColor(userId) {
            const colors = [
                {
                    avatar: 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                    name: 'text-emerald-600 dark:text-emerald-400'
                },
                {
                    avatar: 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                    name: 'text-indigo-600 dark:text-indigo-400'
                },
                {
                    avatar: 'bg-rose-100 dark:bg-rose-900 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                    name: 'text-rose-600 dark:text-rose-400'
                },
                {
                    avatar: 'bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    name: 'text-amber-600 dark:text-amber-400'
                },
                {
                    avatar: 'bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800',
                    name: 'text-violet-600 dark:text-violet-400'
                },
                {
                    avatar: 'bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800',
                    name: 'text-teal-600 dark:text-teal-400'
                },
                {
                    avatar: 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
                    name: 'text-cyan-600 dark:text-cyan-400'
                }
            ];
            const index = Math.abs(parseInt(userId || 0)) % colors.length;
            return colors[index];
        },

        getAssetUrl(path) {
            if (!path) return '';
            return '/storage/' + path.replace('public/', '');
        },

        getFileIcon(mime) {
            if (!mime) return '📄';
            if (mime.startsWith('image/')) return '🖼️';
            if (mime === 'application/pdf') return '📕';
            if (mime.includes('excel') || mime.includes('spreadsheet') || mime.includes('sheet')) return '📊';
            if (mime.includes('word') || mime.includes('document')) return '📝';
            if (mime.includes('zip') || mime.includes('rar') || mime.includes('compressed')) return '📦';
            return '📄';
        },

        formatBytes(bytes) {
            if (bytes === 0 || !bytes) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
                this.showScrollToBottomBtn = false;
            });
        },

        initViewer() {
            this.$nextTick(() => {
                const container = document.getElementById('chat-messages-container');
                if (container && typeof Viewer !== 'undefined') {
                    if (this.viewerInstance) {
                        this.viewerInstance.destroy();
                    }
                    this.viewerInstance = new Viewer(container, {
                        button: true,
                        navbar: false,
                        title: true,
                        toolbar: true,
                        tooltip: true,
                        movable: true,
                        zoomable: true,
                        rotatable: true,
                        scalable: true,
                        transition: true,
                        fullscreen: true,
                        keyboard: true
                      });
                  }
              });
          },

          getFilteredMessages() {
              if (!this.showOnlyMediaAndLinks) {
                  return this.chatMessages;
              }
              return this.chatMessages.filter(msg => {
                  const hasAttachment = !!msg.file_path;
                  const hasLink = /https?:\/\/[^\s]+/.test(msg.message || '') || /www\.[^\s]+/.test(msg.message || '');
                  return hasAttachment || hasLink;
              });
          },

          parseMarkdown(text) {
              if (!text) return '';
              let html = text;
              if (typeof marked !== 'undefined') {
                  html = marked.parse(text);
              } else {
                  html = html.replace(/\n/g, '<br>');
              }
              const urlRegex = /(?<!href=")(https?:\/\/[^\s<]+)/g;
              html = html.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-sky-100 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 rounded-xs border border-sky-200 dark:border-sky-800/30 hover:bg-sky-200 transition-colors font-semibold text-[11px] my-0.5 break-all max-w-full"><i class="fa-solid fa-link text-[9px]"></i>$1</a>');
              return html;
          },

          handleTextSelection(e) {
              const el = e.target;
              const start = el.selectionStart;
              const end = el.selectionEnd;
              if (start !== end && el.value.substring(start, end).trim().length > 0) {
                  this.selectedTextStart = start;
                  this.selectedTextEnd = end;
                  this.showFormatToolbar = true;
                  
                  const text = el.value.substring(start, end);
                  this.isBoldActive = text.startsWith('**') && text.endsWith('**');
                  this.isItalicActive = text.startsWith('*') && text.endsWith('*') && !text.startsWith('**');
                  this.isStrikeActive = text.startsWith('~~') && text.endsWith('~~');
                  this.isCodeActive = text.startsWith('`') && text.endsWith('`');
                  this.isOlActive = /^\d+\.\s/.test(text);
                  this.isUlActive = text.startsWith('- ');
                  this.isQuoteActive = text.startsWith('> ');
              } else {
                  this.showFormatToolbar = false;
              }
          },

          applyFormatting(formatType) {
              const el = document.querySelector('.chat-textarea');
              if (!el) return;
              const start = this.selectedTextStart;
              const end = this.selectedTextEnd;
              const originalText = this.chatInputMessage;
              const selectedText = originalText.substring(start, end);
              let formattedText = '';
              switch (formatType) {
                  case 'bold':
                      if (selectedText.startsWith('**') && selectedText.endsWith('**')) {
                          formattedText = selectedText.slice(2, -2);
                      } else {
                          formattedText = `**${selectedText}**`;
                      }
                      break;
                  case 'italic':
                      if (selectedText.startsWith('*') && selectedText.endsWith('*') && !selectedText.startsWith('**')) {
                          formattedText = selectedText.slice(1, -1);
                      } else {
                          formattedText = `*${selectedText}*`;
                      }
                      break;
                  case 'strike':
                      if (selectedText.startsWith('~~') && selectedText.endsWith('~~')) {
                          formattedText = selectedText.slice(2, -2);
                      } else {
                          formattedText = `~~${selectedText}~~`;
                      }
                      break;
                  case 'code':
                      if (selectedText.startsWith('`') && selectedText.endsWith('`')) {
                          formattedText = selectedText.slice(1, -1);
                      } else {
                          formattedText = `\`${selectedText}\``;
                      }
                      break;
                  case 'ol':
                      if (/^\d+\.\s/.test(selectedText)) {
                          formattedText = selectedText.split('\n').map(line => line.replace(/^\d+\.\s/, '')).join('\n');
                      } else {
                          formattedText = selectedText.split('\n').map((line, index) => `${index + 1}. ${line}`).join('\n');
                      }
                      break;
                  case 'ul':
                      if (selectedText.startsWith('- ')) {
                          formattedText = selectedText.split('\n').map(line => line.replace(/^- /, '')).join('\n');
                      } else {
                          formattedText = selectedText.split('\n').map(line => `- ${line}`).join('\n');
                      }
                      break;
                  case 'quote':
                      if (selectedText.startsWith('> ')) {
                          formattedText = selectedText.split('\n').map(line => line.replace(/^>\s?/, '')).join('\n');
                      } else {
                          formattedText = selectedText.split('\n').map(line => `> ${line}`).join('\n');
                      }
                      break;
              }
              this.chatInputMessage = originalText.substring(0, start) + formattedText + originalText.substring(end);
              this.$nextTick(() => {
                  el.focus();
                  const newEnd = start + formattedText.length;
                  el.setSelectionRange(start, newEnd);
                  this.selectedTextStart = start;
                  this.selectedTextEnd = newEnd;
                  
                  const text = el.value.substring(start, newEnd);
                  this.isBoldActive = text.startsWith('**') && text.endsWith('**');
                  this.isItalicActive = text.startsWith('*') && text.endsWith('*') && !text.startsWith('**');
                  this.isStrikeActive = text.startsWith('~~') && text.endsWith('~~');
                  this.isCodeActive = text.startsWith('`') && text.endsWith('`');
                  this.isOlActive = /^\d+\.\s/.test(text);
                  this.isUlActive = text.startsWith('- ');
                  this.isQuoteActive = text.startsWith('> ');
              });
          }
    };
};
</script>
@endpush
