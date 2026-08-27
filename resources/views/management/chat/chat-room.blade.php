{{-- Reusable Universal Chat Room Component --}}
<div class="flex-1 flex flex-col bg-[#f0f4f9] dark:bg-slate-950 h-full min-h-0 overflow-hidden relative select-auto w-full"
     @dragover.prevent="isDragging = true"
     @dragleave.prevent="if ($event.relatedTarget === null || !$el.contains($event.relatedTarget)) { isDragging = false }"
     @drop.prevent="handleDrop($event)">

    <!-- Drag and Drop Overlay -->
    <div x-show="isDragging" 
         class="absolute inset-0 bg-indigo-600/10 dark:bg-indigo-600/20 backdrop-blur-xs flex flex-col items-center justify-center border-2 border-dashed border-indigo-500 z-[99] transition-all m-4 rounded-sm"
         @dragover.prevent
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)"
         x-cloak>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-md shadow-lg flex flex-col items-center gap-3 select-none pointer-events-none">
            <i class="fa-solid fa-cloud-arrow-up text-4xl text-indigo-600 dark:text-indigo-400 animate-bounce"></i>
            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Drop files here to upload</p>
        </div>
    </div>
    
    <!-- Chat Header with Filter Segmented Control -->
    <div class="px-4 py-2.5 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between flex-shrink-0 z-10">
        <div class="flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-full text-xs">
                <i class="fa-solid fa-comments"></i>
            </span>
            <div>
                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider block" x-text="chatTitle || 'Discussion Room'"></span>
                <span x-show="chatSubtitle" class="text-[10px] text-slate-500 dark:text-slate-400 block -mt-0.5" x-text="chatSubtitle"></span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Date Filter Popover Dropdown -->
            <div class="relative" x-data="{ openDateMenu: false }" @click.outside="openDateMenu = false">
                <button @click="openDateMenu = !openDateMenu" 
                        type="button" 
                        class="px-2.5 py-1 text-[9.5px] font-bold rounded-sm border transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        :class="selectedDateFilter !== 'all' 
                            ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border-indigo-300 dark:border-indigo-800' 
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750'">
                    <i class="fa-solid fa-calendar-days text-[9px] text-indigo-500"></i>
                    <span x-text="getDateFilterLabel()"></span>
                    <i class="fa-solid fa-chevron-down text-[7.5px] opacity-60"></i>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="openDateMenu" 
                     class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-xl z-50 py-1 text-xs"
                     x-cloak>
                    <button type="button" @click="selectedDateFilter = 'all'; openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] cursor-pointer">
                        <span>All Dates</span>
                        <i x-show="selectedDateFilter === 'all'" class="fa-solid fa-check text-indigo-600 dark:text-indigo-400 text-[10px]"></i>
                    </button>
                    <button type="button" @click="selectedDateFilter = 'today'; openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] cursor-pointer">
                        <span>Today</span>
                        <i x-show="selectedDateFilter === 'today'" class="fa-solid fa-check text-indigo-600 dark:text-indigo-400 text-[10px]"></i>
                    </button>
                    <button type="button" @click="selectedDateFilter = 'yesterday'; openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] cursor-pointer">
                        <span>Yesterday</span>
                        <i x-show="selectedDateFilter === 'yesterday'" class="fa-solid fa-check text-indigo-600 dark:text-indigo-400 text-[10px]"></i>
                    </button>
                    <button type="button" @click="selectedDateFilter = '7days'; openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] cursor-pointer">
                        <span>Last 7 Days</span>
                        <i x-show="selectedDateFilter === '7days'" class="fa-solid fa-check text-indigo-600 dark:text-indigo-400 text-[10px]"></i>
                    </button>
                    <div class="border-t border-slate-200 dark:border-slate-700 my-1"></div>
                    <div class="px-3 py-1.5">
                        <label class="block text-[9px] font-bold text-slate-500 uppercase mb-1">Specific Date</label>
                        <input type="date" 
                               x-model="customDateFilter" 
                               @change="selectedDateFilter = 'custom'; openDateMenu = false;"
                               class="w-full text-[11px] p-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm text-slate-800 dark:text-slate-200">
                    </div>
                </div>
            </div>

            <!-- Filter Segmented Tabs -->
            <div class="flex bg-slate-100 dark:bg-slate-800 p-0.5 rounded-sm border border-slate-300/60 dark:border-slate-700">
                <button @click="showOnlyMediaAndLinks = false" 
                        type="button" 
                        class="px-2.5 py-0.5 text-[9.5px] font-bold rounded-sm transition-all cursor-pointer"
                        :class="!showOnlyMediaAndLinks 
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'">
                    All
                </button>
                <button @click="showOnlyMediaAndLinks = true" 
                        type="button" 
                        class="px-2.5 py-0.5 text-[9.5px] font-bold rounded-sm transition-all cursor-pointer flex items-center gap-1"
                        :class="showOnlyMediaAndLinks 
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'">
                    <i class="fa-solid fa-paperclip text-[8.5px]"></i> Files &amp; Links
                </button>
            </div>
        </div>
    </div>

    <!-- Chat history loading state -->
    <div x-show="loadingChats" class="flex justify-center py-2 bg-white/75 dark:bg-slate-900/75 border-b border-slate-200 dark:border-slate-800 flex-shrink-0 z-10" x-cloak>
        <div class="flex items-center gap-2 px-3 py-1 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-full shadow-xs text-[10.5px] font-semibold border border-slate-200 dark:border-slate-700">
            <i class="fa-solid fa-spinner animate-spin text-indigo-600 dark:text-indigo-400 text-xs"></i>
            <span>Loading history...</span>
        </div>
    </div>

    <!-- Messages Container -->
    <div id="chat-messages-container" 
         x-ref="chatContainer"
         @scroll="handleChatScroll($event)"
         class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-1 flex flex-col chat-scroll min-h-0 h-full select-text"
         style="overscroll-behavior-y: contain; touch-action: pan-y;">
        
        <!-- Empty Placeholder -->
        <template x-if="getFilteredMessages().length === 0 && !loadingChats">
            <div class="flex flex-col items-center justify-center h-full py-12 text-slate-400 dark:text-slate-500 select-none">
                <i class="fa-solid text-4xl mb-2 text-slate-300 dark:text-slate-700" :class="showOnlyMediaAndLinks ? 'fa-folder-open' : 'fa-comments'"></i>
                <p class="text-xs font-semibold" x-text="showOnlyMediaAndLinks ? 'No files or links match the active filter.' : 'No messages match the active filter.'"></p>
                <p class="text-[10px] text-slate-400 mt-1">Tip: Clear filters or send a message to start chatting.</p>
            </div>
        </template>

        <!-- Message List Loop with Date Dividers -->
        <template x-for="(msg, index) in getFilteredMessages()" :key="msg.id">
            <div class="flex flex-col w-full">
                
                <!-- Date Divider (Compact horizontal divider with centered date) -->
                <template x-if="shouldShowDateDivider(getFilteredMessages(), index)">
                    <div class="flex items-center justify-center my-2.5 select-none w-full">
                        <div class="w-20 sm:w-32 border-t border-slate-300/70 dark:border-slate-800"></div>
                        <span class="mx-3 text-[11px] font-semibold text-slate-400 dark:text-slate-500 tracking-wide"
                              x-text="getDateDivider(msg.created_at)"></span>
                        <div class="w-20 sm:w-32 border-t border-slate-300/70 dark:border-slate-800"></div>
                    </div>
                </template>

                <!-- Message Row (Top-aligned with Avatar on Top) -->
                <div class="flex items-start gap-2 max-w-[85%] w-full"
                     :id="'chat-bubble-' + msg.id"
                     :class="[
                         msg.user_id == currentUserId ? 'self-end flex-row-reverse' : 'self-start flex-row',
                         shouldShowMessageFooter(getFilteredMessages(), index) ? 'mb-2' : 'mb-0.5'
                     ]">
                    
                    <!-- Avatar (Top aligned next to bubble, shown on top/first of message group) -->
                    <template x-if="shouldShowAvatar(getFilteredMessages(), index)">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[10px] flex-shrink-0 select-none shadow-xs border mt-0.5"
                             :class="getUserColor(msg.user_id).avatar"
                             x-text="msg.user_name ? msg.user_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : (msg.user_id == currentUserId ? 'ME' : 'U')">
                        </div>
                    </template>
                    <template x-if="!shouldShowAvatar(getFilteredMessages(), index)">
                        <div class="w-7 h-7 flex-shrink-0"></div>
                    </template>

                    <!-- Bubble Column (Bubble + Outside Timestamp/Check) -->
                    <div class="flex flex-col min-w-0 max-w-full"
                         :class="msg.user_id == currentUserId ? 'items-end' : 'items-start'">
                        
                        <!-- Sender Name (Placed Outside and ABOVE the Bubble, real name always) -->
                        <template x-if="shouldShowAvatar(getFilteredMessages(), index)">
                            <span class="block text-[10.5px] font-extrabold mb-1 px-1 tracking-wide select-none" 
                                  :class="msg.user_id == currentUserId ? (getUserColor(msg.user_id).name + ' text-right') : getUserColor(msg.user_id).name"
                                  x-text="msg.user_name || (msg.user_id == currentUserId ? 'Me' : 'User')"></span>
                        </template>

                        <!-- Bubble & Hover Action Container (Action buttons appear in front of bubble) -->
                        <div class="flex items-center gap-1.5 group max-w-full"
                             :class="msg.user_id == currentUserId ? 'flex-row-reverse' : 'flex-row'">
                            
                            <!-- Bubble Box -->
                            <div class="px-3.5 py-2 text-xs shadow-xs relative flex flex-col w-auto max-w-full"
                                 :class="msg.user_id == currentUserId ? 'chat-bubble-out' : 'chat-bubble-in'">
                                
                                <!-- Quoted / Reply Preview Box (Clickable to jump to original message) -->
                                <template x-if="msg.reply_to">
                                    <div @click.stop="jumpToMessage(msg.reply_to.id)"
                                         class="mb-1.5 px-2.5 py-1.5 rounded-sm border-l-4 text-[11px] select-none transition-all duration-150 cursor-pointer hover:opacity-90 active:scale-[0.98]"
                                         :class="msg.user_id == currentUserId 
                                             ? 'bg-black/20 dark:bg-black/35 border-l-amber-300 dark:border-l-amber-400 text-white' 
                                             : 'bg-black/5 dark:bg-white/10 border-l-indigo-600 dark:border-l-indigo-400 text-slate-800 dark:text-slate-100'"
                                         title="Click to jump to quoted message">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-reply text-[9px]" :class="msg.user_id == currentUserId ? 'text-amber-300' : 'text-indigo-600 dark:text-indigo-400'"></i>
                                            <span class="font-extrabold text-[10.5px] leading-tight"
                                                  :class="msg.user_id == currentUserId ? 'text-amber-200 dark:text-amber-300' : 'text-indigo-600 dark:text-indigo-400'"
                                                  x-text="msg.reply_to.user_name"></span>
                                        </div>
                                        <p class="text-[10.5px] truncate mt-0.5"
                                           :class="msg.user_id == currentUserId ? 'text-white/90 font-medium' : 'text-slate-600 dark:text-slate-300 font-medium'"
                                           x-text="msg.reply_to.message || (msg.reply_to.file_name ? '📎 ' + msg.reply_to.file_name : 'Quoted Message')"></p>
                                    </div>
                                </template>

                                <!-- Multi-attachment Header Action (Download All ZIP) -->
                                <template x-if="(getImageAttachments(msg).length + getDocAttachments(msg).length) > 1">
                                    <div class="flex items-center justify-between mb-2 pb-1.5 border-b"
                                         :class="msg.user_id == currentUserId ? 'border-white/25 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                        <div class="flex items-center gap-1.5 text-[11px] font-bold">
                                            <i class="fa-solid fa-layer-group text-xs" :class="msg.user_id == currentUserId ? 'text-white/80' : 'text-indigo-500'"></i>
                                            <span x-text="(getImageAttachments(msg).length + getDocAttachments(msg).length) + ' files attached'"></span>
                                        </div>
                                        <a :href="'{{ url('management/chats/download-all') }}/' + msg.id" 
                                           download
                                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm text-[11px] font-bold shadow-xs transition-colors duration-150 cursor-pointer"
                                           :class="msg.user_id == currentUserId 
                                               ? 'bg-white/20 hover:bg-white/30 text-white border border-white/35 shadow-xs' 
                                               : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs'"
                                           title="Download all files as a ZIP archive">
                                            <i class="fa-solid fa-file-zipper text-xs" :class="msg.user_id == currentUserId ? 'text-amber-300' : 'text-amber-200'"></i>
                                            <span>Download All (.zip)</span>
                                        </a>
                                    </div>
                                </template>

                                <!-- Image Gallery Grid (Maks 3 gambar lebarnya) -->
                                <template x-if="getImageAttachments(msg).length > 0">
                                    <div class="mt-1 mb-1"
                                         :class="getImageAttachments(msg).length === 1 ? 'max-w-[280px]' : ('grid gap-1.5 ' + getImageGridClass(getImageAttachments(msg).length))">
                                        <template x-for="(img, imgIdx) in getImageAttachments(msg)" :key="img.f || img.file_url || (msg.id + '-img-' + (img.index !== undefined ? img.index : imgIdx))">
                                            <div class="relative overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800 group/img"
                                                 :class="getImageAttachments(msg).length === 1 ? 'w-auto' : 'aspect-square h-24 sm:h-28 w-full'">
                                                <img :src="img.file_url" 
                                                     :alt="img.file_name" 
                                                     :data-original="img.file_url"
                                                     class="w-full object-cover chat-image-thumb cursor-zoom-in transition-opacity duration-150 hover:opacity-90 rounded-lg"
                                                     :class="getImageAttachments(msg).length === 1 ? 'max-h-64 h-auto' : 'h-full'">
                                                
                                                <!-- Soft dark vignette on hover -->
                                                <div class="absolute inset-0 bg-black/25 opacity-0 group-hover/img:opacity-100 transition-opacity duration-150 pointer-events-none rounded-lg"></div>

                                                <!-- Image Hover Action Buttons (Download & Delete) -->
                                                <div class="absolute top-2 right-2 opacity-0 group-hover/img:opacity-100 transition-opacity duration-150 flex items-center gap-1.5 z-10 select-none">
                                                    <!-- Download Image Button -->
                                                    <a :href="img.download_url" 
                                                       download
                                                       @click.stop
                                                       class="w-7 h-7 rounded-full bg-black/60 hover:bg-black/85 backdrop-blur-xs text-white flex items-center justify-center shadow-md cursor-pointer transition-colors duration-150 border border-white/30"
                                                       title="Download Image">
                                                        <i class="fa-solid fa-download text-[10px]"></i>
                                                    </a>
                                                    <!-- Delete Single Image Button (Self Only) -->
                                                    <button x-show="msg.user_id == currentUserId"
                                                            @click.stop="deleteAttachment(msg.id, img.index !== undefined ? img.index : imgIdx, img.file_path, img.f)" 
                                                            type="button"
                                                            class="w-7 h-7 rounded-full bg-rose-600/80 hover:bg-rose-600 backdrop-blur-xs text-white flex items-center justify-center shadow-md cursor-pointer transition-colors duration-150 border border-white/30"
                                                            title="Delete this image">
                                                        <i class="fa-solid fa-trash-can text-[9.5px]"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Document / File Attachments -->
                                <template x-if="getDocAttachments(msg).length > 0">
                                    <div class="flex flex-col gap-1 mt-1 mb-1">
                                        <template x-for="(doc, docIdx) in getDocAttachments(msg)" :key="doc.f || doc.file_url || (msg.id + '-doc-' + (doc.index !== undefined ? doc.index : docIdx))">
                                            <div class="flex items-center gap-2 p-2 rounded-lg border transition-colors duration-150"
                                                 :class="msg.user_id == currentUserId 
                                                     ? 'bg-black/15 hover:bg-black/25 dark:bg-black/25 border-white/20 text-white' 
                                                     : 'bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-700/60 dark:hover:bg-slate-700/90 border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-100'">
                                                <div class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0"
                                                     :class="isPdfType(doc.file_type, doc.file_name) 
                                                         ? 'bg-rose-500 text-white shadow-xs' 
                                                         : (msg.user_id == currentUserId 
                                                             ? 'bg-white/20 text-white border border-white/30 backdrop-blur-xs' 
                                                             : 'bg-indigo-50 dark:bg-slate-600 text-indigo-600 dark:text-indigo-300 border border-indigo-100 dark:border-slate-500')">
                                                    <i class="fa-solid text-sm" :class="isPdfType(doc.file_type, doc.file_name) ? 'fa-file-pdf' : 'fa-file-lines'"></i>
                                                </div>
                                                <div class="flex-1 min-w-0 pr-1">
                                                    <span class="block text-xs font-semibold truncate leading-tight" x-text="doc.file_name"></span>
                                                    <span class="block text-[9.5px] opacity-75 font-mono" x-text="doc.file_size ? (doc.file_size > 1048576 ? (doc.file_size/1048576).toFixed(1) + ' MB' : (doc.file_size/1024).toFixed(0) + ' KB') : '—'"></span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <!-- Only PDF allows preview -->
                                                    <button x-show="isPdfType(doc.file_type, doc.file_name)"
                                                            @click="previewDoc(doc.file_url, doc.file_name)" 
                                                            type="button"
                                                            class="w-7 h-7 rounded-sm flex items-center justify-center bg-black/15 hover:bg-black/30 dark:bg-white/15 dark:hover:bg-white/30 transition-colors duration-150 cursor-pointer border border-black/10 dark:border-white/10"
                                                            title="Preview PDF">
                                                        <i class="fa-solid fa-eye text-[11px]"></i>
                                                    </button>
                                                    <!-- Download File Button -->
                                                    <a :href="doc.download_url" 
                                                       class="w-7 h-7 rounded-sm flex items-center justify-center bg-black/15 hover:bg-black/30 dark:bg-white/15 dark:hover:bg-white/30 transition-colors duration-150 cursor-pointer border border-black/10 dark:border-white/10"
                                                       title="Download File">
                                                        <i class="fa-solid fa-download text-[11px]"></i>
                                                    </a>
                                                    <!-- Delete File Attachment Button -->
                                                    <button x-show="msg.user_id == currentUserId"
                                                            @click="deleteAttachment(msg.id, doc.index !== undefined ? doc.index : docIdx, doc.file_path, doc.f)" 
                                                            type="button"
                                                            class="w-7 h-7 rounded-sm flex items-center justify-center bg-rose-500/30 hover:bg-rose-500/70 text-rose-100 transition-colors duration-150 cursor-pointer border border-rose-400/30"
                                                            title="Delete this file">
                                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Message Body -->
                                <template x-if="msg.message">
                                    <div class="chat-markdown break-words leading-relaxed select-text text-[12.5px]" 
                                         x-html="renderFormattedMessage(msg.message)"></div>
                                </template>
                            </div>

                            <!-- Action Buttons In Front of Bubble (Reply & Delete on Hover) -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex-shrink-0 select-none">
                                <!-- Reply Button -->
                                <button @click="setReply(msg)" 
                                        type="button"
                                        class="w-6 h-6 rounded-full bg-white dark:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 flex items-center justify-center shadow-xs cursor-pointer border border-slate-200 dark:border-slate-700 transition-colors duration-150"
                                        title="Reply">
                                    <i class="fa-solid fa-reply text-[9px]"></i>
                                </button>
                                <!-- Delete Button (Self only) -->
                                <template x-if="msg.user_id == currentUserId">
                                    <button @click="deleteMessage(msg.id)" 
                                            type="button"
                                            class="w-6 h-6 rounded-full bg-white dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 flex items-center justify-center shadow-xs cursor-pointer border border-slate-200 dark:border-slate-700 transition-colors duration-150"
                                            title="Delete">
                                        <i class="fa-solid fa-trash-can text-[8.5px]"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Outside Timestamp and Checkmark Status (Shown only on last message of group) -->
                        <template x-if="shouldShowMessageFooter(getFilteredMessages(), index)">
                            <div class="flex items-center gap-1 mt-0.5 px-1.5 text-[9.5px] select-none font-medium text-slate-400 dark:text-slate-500"
                                 :class="msg.user_id == currentUserId ? 'justify-end' : 'justify-start'">
                                <span x-text="formatTime(msg.created_at)"></span>
                                <template x-if="msg.user_id == currentUserId">
                                    <i class="fa-solid fa-check-double text-[9px] text-[#007aff] dark:text-blue-400"></i>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Scroll To Bottom Floating Button -->
    <button x-show="showScrollToBottomBtn" 
            @click="scrollToBottom()"
            type="button" 
            class="absolute right-6 bottom-24 w-8 h-8 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg flex items-center justify-center cursor-pointer transition-all z-20"
            x-cloak>
        <i class="fa-solid fa-chevron-down text-xs"></i>
    </button>

    <!-- Quoted / Replying Banner Above Input -->
    <div x-show="replyingTo" 
         class="px-4 py-2 bg-slate-200/90 dark:bg-slate-800 border-t border-slate-300 dark:border-slate-700 flex items-center justify-between z-20 flex-shrink-0"
         x-cloak>
        <div class="flex items-center gap-2 min-w-0">
            <i class="fa-solid fa-reply text-indigo-600 dark:text-indigo-400 text-xs"></i>
            <div class="text-xs truncate">
                <span class="font-bold text-slate-800 dark:text-slate-200">Replying to </span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="replyingTo?.user_name"></span>:
                <span class="text-slate-600 dark:text-slate-400 italic ml-1" x-text="replyingTo?.message || replyingTo?.file_name"></span>
            </div>
        </div>
        <button @click="cancelReply()" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold ml-2">
            &times;
        </button>
    </div>

    <!-- Attachments Preview List Chips (Before Sending) -->
    <div x-show="chatAttachments.length > 0" 
         class="px-4 py-2 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex flex-wrap gap-2 z-20 flex-shrink-0"
         x-cloak>
        <template x-for="(att, aIdx) in chatAttachments" :key="aIdx">
            <div class="flex items-center gap-1.5 pl-2 pr-1.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm text-xs max-w-[240px] group/chip shadow-2xs">
                <!-- Image / PDF Thumbnail or Icon with Preview Click -->
                <template x-if="isImageType(att.type, att.name) && att.previewUrl">
                    <img :src="att.previewUrl" 
                         @click="previewDoc(att.previewUrl, att.name)"
                         class="w-6 h-6 object-cover rounded-xs cursor-pointer hover:opacity-80 transition-opacity" 
                         title="Click to preview image">
                </template>
                <template x-if="isPdfType(att.type, att.name)">
                    <div @click="previewDoc(att.previewUrl, att.name)"
                         class="w-6 h-6 rounded-xs bg-rose-500 text-white flex items-center justify-center flex-shrink-0 cursor-pointer hover:bg-rose-600 transition-colors"
                         title="Click to preview PDF">
                        <i class="fa-solid fa-file-pdf text-[10px]"></i>
                    </div>
                </template>
                <template x-if="!isImageType(att.type, att.name) && !isPdfType(att.type, att.name)">
                    <i class="fa-solid fa-paperclip text-slate-400 text-[10px]"></i>
                </template>

                <span class="truncate flex-1 text-[11px] font-medium text-slate-700 dark:text-slate-300" x-text="att.name"></span>

                <!-- Quick Preview Action for Image / PDF -->
                <template x-if="(isImageType(att.type, att.name) || isPdfType(att.type, att.name)) && att.previewUrl">
                    <button @click="previewDoc(att.previewUrl, att.name)" 
                            type="button" 
                            class="w-4 h-4 rounded-full flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer"
                            title="Preview file">
                        <i class="fa-solid fa-eye text-[9px]"></i>
                    </button>
                </template>

                <!-- Remove Chip Button -->
                <button @click="removeAttachment(aIdx)" 
                        type="button" 
                        class="w-4 h-4 rounded-full flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer text-xs"
                        title="Remove">
                    &times;
                </button>
            </div>
        </template>
    </div>

    <!-- Mention / Tag Autocomplete Popover Dropdown -->
    <div x-show="showMentionDropdown && getFilteredMentions().length > 0" 
         class="absolute left-6 bottom-20 w-64 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-xl z-50 overflow-hidden py-1 max-h-48 overflow-y-auto"
         x-cloak>
        <div class="px-2.5 py-1 bg-slate-100 dark:bg-slate-900 text-[9.5px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 dark:border-slate-700"
             x-text="mentionMode === '@' ? 'Mention Person' : 'Tag Item / Product'"></div>
        
        <template x-for="(item, mIdx) in getFilteredMentions()" :key="mIdx">
            <button @click="selectMention(item)" 
                    type="button"
                    class="w-full px-3 py-1.5 text-left text-xs hover:bg-indigo-50 dark:hover:bg-slate-700 flex items-center gap-2 transition-colors cursor-pointer border-b border-slate-100 dark:border-slate-750 last:border-none">
                <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[9px]"
                      :class="mentionMode === '@' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600'">
                    <i class="fa-solid" :class="mentionMode === '@' ? 'fa-user text-[9px]' : 'fa-box text-[9px]'"></i>
                </span>
                <div class="truncate flex-1">
                    <span class="font-bold text-slate-800 dark:text-slate-200 block truncate" x-text="mentionMode === '@' ? item.name : item.code"></span>
                    <span x-show="item.email || item.name" class="text-[9.5px] text-slate-400 block truncate" x-text="mentionMode === '@' ? item.email : item.name"></span>
                </div>
            </button>
        </template>
    </div>

    <!-- Rich Formatting Toolbar -->
    <div x-show="showFormatToolbar" 
         class="px-4 py-1.5 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex items-center gap-1 z-20 flex-shrink-0 select-none"
         x-cloak>
        <button @click="applyFormat('bold')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs" title="Bold (**text**)">B</button>
        <button @click="applyFormat('italic')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 italic text-xs font-serif" title="Italic (*text*)">I</button>
        <button @click="applyFormat('strike')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 line-through text-xs" title="Strikethrough (~~text~~)">S</button>
        <div class="h-3.5 w-px bg-slate-300 dark:bg-slate-700 mx-1"></div>
        <button @click="applyFormat('code')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px]" title="Inline Code (`code`)">&lt;/&gt;</button>
        <button @click="applyFormat('quote')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Quote block (&gt; quote)"><i class="fa-solid fa-quote-left text-[10px]"></i></button>
        <button @click="applyFormat('ul')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Bulleted List"><i class="fa-solid fa-list-ul text-[10px]"></i></button>
        <button @click="applyFormat('ol')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Numbered List"><i class="fa-solid fa-list-ol text-[10px]"></i></button>
    </div>

    <!-- Chat Input Box & Action Row -->
    <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-end gap-2 flex-shrink-0 z-20">
        
        <!-- File Picker Input (Hidden) -->
        <input type="file" 
               x-ref="chatFileInput" 
               @change="handleFileSelect($event)" 
               multiple 
               class="hidden">

        <!-- Attachment Button -->
        <button @click="$refs.chatFileInput.click()" 
                type="button" 
                class="w-9 h-9 flex items-center justify-center rounded-sm bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors flex-shrink-0 cursor-pointer"
                title="Attach Files / Photos">
            <i class="fa-solid fa-paperclip text-sm"></i>
        </button>

        <!-- Formatting Toggle Button -->
        <button @click="showFormatToolbar = !showFormatToolbar" 
                type="button" 
                class="w-9 h-9 flex items-center justify-center rounded-sm transition-colors flex-shrink-0 cursor-pointer"
                :class="showFormatToolbar ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'"
                title="Toggle Text Formatting Toolbar">
            <i class="fa-solid fa-font text-xs"></i>
        </button>

        <!-- Textarea Input Box (Aligned to 36px buttons, auto-grows up to 120px) -->
        <div class="flex-1 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500/30 transition-all flex items-center min-h-[36px] px-3 py-1.5 box-border">
            <textarea x-ref="chatInput"
                      x-model="chatInputMessage"
                      rows="1"
                      x-init="$el.style.height = '20px'; $watch('chatInputMessage', val => { if (!val || !val.trim()) { $el.style.height = '20px'; } })"
                      @input="handleInputChange($event); $el.style.height = '20px'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px';"
                      @keydown.enter="if(!event.shiftKey) { event.preventDefault(); sendMessage(); $el.style.height = '20px'; }"
                      placeholder="Type a message... (@ mention, # item, Shift+Enter for newline)"
                      class="w-full text-xs bg-transparent border-none outline-none resize-none chat-textarea text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 max-h-30 p-0 leading-normal block overflow-y-auto"></textarea>
        </div>

        <!-- Send Button -->
        <button @click="sendMessage(); if ($refs.chatInput) $refs.chatInput.style.height = '20px';" 
                :disabled="sendingMessage || (!chatInputMessage.trim() && chatAttachments.length === 0)"
                type="button" 
                class="w-9 h-9 flex items-center justify-center rounded-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white shadow-xs transition-colors flex-shrink-0 cursor-pointer"
                title="Send Message">
            <template x-if="!sendingMessage">
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </template>
            <template x-if="sendingMessage">
                <i class="fa-solid fa-spinner animate-spin text-xs"></i>
            </template>
        </button>
    </div>

    <!-- Document / Image Modal Preview -->
    <div x-show="pdfPreviewUrl" 
         class="fixed inset-0 z-[99999] bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        <div class="bg-white dark:bg-slate-800 w-full max-w-5xl h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 rounded-sm overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex-shrink-0">
                <span class="text-xs font-bold text-slate-800 dark:text-white truncate flex items-center gap-2">
                    <i class="fa-solid" :class="isPdfType('', pdfPreviewName) ? 'fa-file-pdf text-rose-500 text-sm' : (isImageType('', pdfPreviewName) ? 'fa-image text-emerald-500 text-sm' : 'fa-file text-slate-500 text-sm')"></i>
                    <span x-text="pdfPreviewName">File Preview</span>
                </span>
                <button @click="pdfPreviewUrl = null" 
                        class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-lg leading-none cursor-pointer">
                    &times;
                </button>
            </div>
            <!-- Modal Content (Iframe / Image) -->
            <div class="w-full flex-1 border-0 bg-slate-100 dark:bg-slate-900 overflow-hidden flex items-center justify-center p-2">
                <template x-if="isImageType('', pdfPreviewName)">
                    <img :src="pdfPreviewUrl" class="max-w-full max-h-full object-contain rounded-sm shadow-sm">
                </template>
                <template x-if="!isImageType('', pdfPreviewName)">
                    <iframe :src="pdfPreviewUrl" class="w-full h-full border-0"></iframe>
                </template>
            </div>
        </div>
    </div>

</div>
