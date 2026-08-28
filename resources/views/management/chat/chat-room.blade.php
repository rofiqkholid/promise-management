{{-- Reusable Universal Chat Room Component --}}
<div class="flex-1 flex flex-col bg-[#f0f4f9] dark:bg-slate-950 h-full min-h-0 overflow-hidden relative select-auto w-full"
     @dragover.prevent="isDragging = true"
     @dragleave.prevent="if ($event.relatedTarget === null || !$el.contains($event.relatedTarget)) { isDragging = false }"
     @drop.prevent="handleDrop($event)">

    <!-- Simple Clean Drag and Drop Overlay -->
    <div x-show="isDragging" 
         class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/75 flex flex-col items-center justify-center gap-2.5 z-[99] select-none transition-opacity duration-150"
         @dragover.prevent
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)"
         x-cloak>
        <i class="fa-solid fa-cloud-arrow-up text-4xl text-white opacity-95 pointer-events-none"></i>
        <p class="text-sm font-bold text-white tracking-wide pointer-events-none">Drop files to send</p>
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

        <div class="flex items-center gap-1 sm:gap-1.5">
            <!-- 1. Search Button (Icon-only) -->
            <button @click="showSearchBar = !showSearchBar; if (showSearchBar) { $nextTick(() => { $refs.chatSearchInput?.focus(); }); }"
                    type="button" 
                    class="h-[28px] w-[28px] flex items-center justify-center rounded-md border transition-all cursor-pointer shadow-2xs select-none"
                    :class="showSearchBar || searchQuery 
                        ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 border-indigo-300 dark:border-indigo-700 shadow-xs' 
                        : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 hover:text-slate-900 dark:hover:text-white'"
                    title="Search messages">
                <i class="fa-solid fa-magnifying-glass text-[11px] text-indigo-500"></i>
            </button>

            <!-- 2. Jump to Date Dropdown (Icon-only with mini chevron) -->
            <div class="relative" x-data="{ openDateMenu: false }" @click.outside="openDateMenu = false">
                <button @click="openDateMenu = !openDateMenu" 
                        type="button" 
                        class="h-[28px] px-1.5 flex items-center justify-center gap-1 rounded-md border transition-all cursor-pointer shadow-2xs select-none"
                        :class="openDateMenu 
                            ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 border-indigo-300 dark:border-indigo-700 shadow-xs' 
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 hover:text-slate-900 dark:hover:text-white'"
                        title="Jump to date">
                    <i class="fa-solid fa-calendar-days text-[11px] text-indigo-500"></i>
                    <i class="fa-solid fa-chevron-down text-[7.5px] opacity-60"></i>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="openDateMenu" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute left-0 sm:right-0 sm:left-auto mt-1 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl z-50 py-1 text-xs"
                     x-cloak>
                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        Jump to Date
                    </div>
                    <button type="button" @click="jumpToDate('today'); openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-indigo-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] font-medium cursor-pointer transition-colors">
                        <span class="flex items-center gap-2"><i class="fa-regular fa-calendar text-[10px] text-indigo-500"></i> Today</span>
                    </button>
                    <button type="button" @click="jumpToDate('yesterday'); openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-indigo-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] font-medium cursor-pointer transition-colors">
                        <span class="flex items-center gap-2"><i class="fa-regular fa-calendar-minus text-[10px] text-indigo-500"></i> Yesterday</span>
                    </button>
                    <button type="button" @click="jumpToDate('7days'); openDateMenu = false;" class="w-full text-left px-3 py-1.5 hover:bg-indigo-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-between text-[11px] font-medium cursor-pointer transition-colors">
                        <span class="flex items-center gap-2"><i class="fa-solid fa-clock-rotate-left text-[10px] text-indigo-500"></i> Last 7 Days</span>
                    </button>
                    <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                    <div class="px-3 py-1.5">
                        <label class="block text-[9.5px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Pick a Date</label>
                        <input type="date" 
                               x-model="customDateFilter" 
                               @change="jumpToDate('custom', customDateFilter); openDateMenu = false;"
                               class="w-full text-[11px] px-2 py-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md text-slate-800 dark:text-slate-200 focus:outline-hidden focus:border-indigo-500 transition-colors">
                    </div>
                </div>
            </div>

            <!-- 3. Segmented Pill Toggle (Right) -->
            <div class="h-[28px] flex items-center bg-slate-100 dark:bg-slate-800 p-0.5 rounded-md border border-slate-200 dark:border-slate-700 select-none">
                <button @click="showOnlyMediaAndLinks = false" 
                        type="button" 
                        class="h-[22px] px-2.5 text-[11px] font-semibold rounded-sm transition-all cursor-pointer flex items-center justify-center"
                        :class="!showOnlyMediaAndLinks 
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    All
                </button>
                <button @click="showOnlyMediaAndLinks = true" 
                        type="button" 
                        class="h-[22px] px-2.5 text-[11px] font-semibold rounded-sm transition-all cursor-pointer flex items-center gap-1.5 justify-center"
                        :class="showOnlyMediaAndLinks 
                            ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    <i class="fa-solid fa-paperclip text-[9.5px]"></i>
                    <span>Files &amp; Links</span>
                </button>
            </div>

            <!-- 4. Dynamic Detail Panel Toggle Button -->
            <template x-if="hasDetailPanel">
                <button @click="toggleDetailPanel()" 
                        type="button" 
                        class="h-[28px] px-2 flex items-center justify-center gap-1.5 rounded-md border transition-all cursor-pointer shadow-2xs select-none"
                        :class="showDetailPanel 
                            ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 border-indigo-300 dark:border-indigo-700 shadow-xs' 
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 hover:text-slate-900 dark:hover:text-white'"
                        :title="showDetailPanel ? 'Hide Room Details' : 'Show Room Details'">
                    <i class="fa-solid fa-circle-info text-[11px] text-indigo-500"></i>
                    <span class="text-[11px] font-semibold hidden md:inline">Details</span>
                </button>
            </template>
        </div>
    </div>

    <!-- Search Bar Banner (WhatsApp-style Direct Jump Navigation) -->
    <div x-show="showSearchBar" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="px-4 py-2 bg-slate-50 dark:bg-slate-850 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 z-10 flex-shrink-0"
         x-cloak>
        <div class="relative flex-1 flex items-center">
            <i class="fa-solid fa-magnifying-glass absolute left-2.5 text-slate-400 text-xs"></i>
            <input type="text"
                   x-ref="chatSearchInput"
                   x-model="searchQuery"
                   @input="onSearchInput()"
                   @keydown.enter.prevent="searchNext()"
                   placeholder="Find in chat..."
                   class="w-full pl-8 pr-8 py-1 text-xs bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm focus:outline-hidden focus:border-indigo-500 text-slate-800 dark:text-slate-200 shadow-2xs">
            <button x-show="searchQuery" 
                    @click="clearSearch()" 
                    type="button" 
                    class="absolute right-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-bold cursor-pointer">
                &times;
            </button>
        </div>

        <div class="flex items-center gap-1.5 flex-shrink-0">
            <!-- WhatsApp Match Counter (e.g., 2 of 5 or 0 results) -->
            <span class="text-[11px] font-semibold px-1.5 py-0.5 text-slate-600 dark:text-slate-300 font-mono"
                  x-show="searchQuery">
                <template x-if="searchResults.length > 0">
                    <span x-text="(currentSearchIndex + 1) + ' of ' + searchResults.length"></span>
                </template>
                <template x-if="searchResults.length === 0">
                    <span class="text-rose-500 font-sans">0 found</span>
                </template>
            </span>

            <!-- Up Arrow: Previous (Older) Match -->
            <button @click="searchPrev()" 
                    :disabled="searchResults.length === 0 || currentSearchIndex <= 0"
                    type="button" 
                    class="w-7 h-7 rounded-sm flex items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-2xs cursor-pointer"
                    title="Previous match (Older)">
                <i class="fa-solid fa-chevron-up text-[10px]"></i>
            </button>

            <!-- Down Arrow: Next (Newer) Match -->
            <button @click="searchNext()" 
                    :disabled="searchResults.length === 0 || currentSearchIndex >= searchResults.length - 1"
                    type="button" 
                    class="w-7 h-7 rounded-sm flex items-center justify-center border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors shadow-2xs cursor-pointer"
                    title="Next match (Newer)">
                <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </button>

            <!-- Close Search Bar -->
            <button @click="showSearchBar = false; clearSearch();" 
                    type="button" 
                    class="w-7 h-7 rounded-sm flex items-center justify-center text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition-colors cursor-pointer ml-1"
                    title="Close search">
                <i class="fa-solid fa-xmark text-xs font-bold"></i>
            </button>
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
        
        <!-- Top Loading Indicator for Infinite Scroll Up -->
        <div x-show="loadingChats && chatMessages.length > 0" 
             class="flex items-center justify-center py-2 flex-shrink-0" 
             x-cloak>
            <div class="flex items-center gap-2 px-3 py-1 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xs rounded-full shadow-xs border border-slate-200 dark:border-slate-700 text-[11px] font-semibold text-slate-600 dark:text-slate-300 select-none">
                <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400 text-xs"></i>
                <span>Loading older messages...</span>
            </div>
        </div>

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
                                           x-html="getReplySnippet(msg.reply_to)"></p>
                                    </div>
                                </template>

                                <!-- Multi-attachment Header Action (Download All ZIP) -->
                                <template x-if="(getImageAttachments(msg).length + getDocAttachments(msg).length) > 1">
                                    <div class="flex items-center justify-between mb-2 pb-1.5 border-b"
                                         :class="msg.user_id == currentUserId ? 'border-white/25 text-white' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                                        <div class="flex items-center gap-1.5 text-[11px] font-bold">
                                            <i class="fa-solid fa-layer-group text-xs" :class="msg.user_id == currentUserId ? 'text-white/90' : 'text-indigo-500'"></i>
                                            <span x-text="(getImageAttachments(msg).length + getDocAttachments(msg).length) + ' files attached'"></span>
                                        </div>
                                        <!-- Crisp White Badge for ZIP Download -->
                                        <a :href="'{{ url('management/chats/download-all') }}/' + msg.id" 
                                           download
                                           class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold shadow-sm transition-all duration-150 cursor-pointer bg-white text-blue-700 hover:bg-slate-50 active:scale-95 border border-slate-200/80"
                                           title="Download all files as a ZIP archive">
                                            <i class="fa-solid fa-file-zipper text-xs text-amber-500"></i>
                                            <span class="tracking-tight">Download All (.zip)</span>
                                        </a>
                                    </div>
                                </template>

                                <!-- Image Gallery Grid (Maks 3 gambar lebarnya) -->
                                <template x-if="getImageAttachments(msg).length > 0">
                                    <div class="mt-1 mb-1"
                                         :class="getImageAttachments(msg).length === 1 ? 'max-w-[280px]' : ('grid gap-1.5 sm:gap-2 ' + getImageGridClass(getImageAttachments(msg).length))">
                                        <template x-for="(img, imgIdx) in getImageAttachments(msg)" :key="img.f || img.file_url || (msg.id + '-img-' + (img.index !== undefined ? img.index : imgIdx))">
                                            <div class="relative overflow-hidden rounded-lg group/img border shadow-2xs transition-all"
                                                 :class="[
                                                     getImageAttachments(msg).length === 1 ? 'w-auto' : 'aspect-square h-24 sm:h-28 w-full',
                                                     msg.user_id == currentUserId 
                                                         ? 'border-white/35 dark:border-white/25 bg-white/10 ring-1 ring-black/10' 
                                                         : 'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 ring-1 ring-black/5'
                                                 ]">
                                                <img :src="img.file_url" 
                                                     :alt="img.file_name" 
                                                     :data-original="img.file_url"
                                                     class="w-full object-cover chat-image-thumb cursor-zoom-in transition-opacity duration-150 hover:opacity-90 rounded-md"
                                                     :class="getImageAttachments(msg).length === 1 ? 'max-h-64 h-auto' : 'h-full'">
                                                
                                                <!-- Soft dark vignette on hover -->
                                                <div class="absolute inset-0 bg-black/25 opacity-0 group-hover/img:opacity-100 transition-opacity duration-150 pointer-events-none rounded-lg"></div>

                                                <!-- Image Hover Action Buttons (Download & Delete) -->
                                                <div class="absolute top-2 right-2 opacity-0 group-hover/img:opacity-100 transition-all duration-150 flex items-center gap-1.5 z-10 select-none">
                                                    <!-- Download Image Button -->
                                                    <a :href="img.download_url" 
                                                       download
                                                       @click.stop
                                                       class="w-7 h-7 rounded-full bg-slate-900/60 hover:bg-slate-900/90 backdrop-blur-md text-white flex items-center justify-center shadow-sm cursor-pointer transition-colors duration-150 border border-white/25"
                                                       title="Download Image">
                                                        <i class="fa-solid fa-download text-[10px]"></i>
                                                    </a>
                                                    <!-- Delete Single Image Button (Self Only) -->
                                                    <button x-show="msg.user_id == currentUserId"
                                                            @click.stop="deleteAttachment(msg.id, img.index !== undefined ? img.index : imgIdx, img.file_path, img.f)" 
                                                            type="button"
                                                            class="w-7 h-7 rounded-full bg-slate-900/60 hover:bg-rose-600/90 backdrop-blur-md text-white flex items-center justify-center shadow-sm cursor-pointer transition-colors duration-150 border border-white/25 hover:border-rose-400/50"
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
                                                    <!-- Delete File Attachment Button (Harmonized styling with hover alert) -->
                                                    <button x-show="msg.user_id == currentUserId"
                                                            @click="deleteAttachment(msg.id, doc.index !== undefined ? doc.index : docIdx, doc.file_path, doc.f)" 
                                                            type="button"
                                                            class="w-7 h-7 rounded-sm flex items-center justify-center bg-black/15 hover:bg-rose-600/85 text-white/90 hover:text-white transition-colors duration-150 cursor-pointer border border-black/10 dark:border-white/10 hover:border-rose-400/40"
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

                <!-- Scroll to bottom anchor -->
                <div x-ref="chatBottomAnchor" class="h-1"></div>
            </div>

            <!-- Scroll To Bottom Floating Button -->
            <button x-show="showScrollToBottomBtn" 
                    @click="scrollToBottom()"
                    type="button" 
                    class="absolute right-6 bottom-20 w-8 h-8 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg flex items-center justify-center cursor-pointer transition-all z-20"
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
                        <span class="text-slate-600 dark:text-slate-400 italic ml-1" x-html="getReplySnippet(replyingTo)"></span>
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

            <!-- Rich Formatting Toolbar (Auto-appears on text selection or manual toggle) -->
            <div x-show="showFormatToolbar || hasTextSelection" 
                 class="px-4 py-1.5 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex items-center gap-1 z-20 flex-shrink-0 select-none transition-all"
                 x-cloak>
                <button @mousedown.prevent @click="applyFormat('bold')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs" title="Bold (Ctrl+B)">B</button>
                <button @mousedown.prevent @click="applyFormat('italic')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 italic text-xs font-serif" title="Italic (Ctrl+I)">I</button>
                <button @mousedown.prevent @click="applyFormat('underline')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 underline text-xs font-semibold" title="Underline (Ctrl+U)">U</button>
                <button @mousedown.prevent @click="applyFormat('strike')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 line-through text-xs" title="Strikethrough (Ctrl+Shift+X / Alt+Shift+5)">S</button>
                <div class="h-3.5 w-px bg-slate-300 dark:bg-slate-700 mx-1"></div>
                <button @mousedown.prevent @click="applyFormat('code')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px]" title="Code block (Ctrl+Shift+C / Ctrl+E)">&lt;/&gt;</button>
                <button @mousedown.prevent @click="applyFormat('quote')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Quote (Ctrl+Shift+>)"><i class="fa-solid fa-quote-left text-[10px]"></i></button>
                <button @mousedown.prevent @click="applyFormat('ul')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Bulleted List (Ctrl+Shift+8 / Ctrl+Shift+L)"><i class="fa-solid fa-list-ul text-[10px]"></i></button>
                <button @mousedown.prevent @click="applyFormat('ol')" type="button" class="w-6 h-6 rounded-xs hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs" title="Numbered List (Ctrl+Shift+7)"><i class="fa-solid fa-list-ol text-[10px]"></i></button>
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
                        :class="showFormatToolbar || hasTextSelection ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'"
                        title="Toggle Text Formatting Toolbar">
                    <i class="fa-solid fa-font text-xs"></i>
                </button>

                <!-- Textarea Input Box (Aligned to 36px buttons, auto-grows up to 120px) -->
                <div class="flex-1 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500/30 transition-all flex items-center min-h-[36px] px-3 py-1.5 box-border">
                    <textarea x-ref="chatInput"
                              x-model="chatInputMessage"
                              rows="1"
                              x-init="$el.style.height = '20px'; $watch('chatInputMessage', val => { if (!val || !val.trim()) { $el.style.height = '20px'; } })"
                              @input="handleInputChange($event); checkTextSelection(); $el.style.height = '20px'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px';"
                              @select="checkTextSelection()"
                              @mouseup="checkTextSelection()"
                              @keyup="checkTextSelection()"
                              @blur="setTimeout(() => { checkTextSelection(); }, 150)"
                              @keydown="handleChatTextareaKeydown($event)"
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

        </div>

        <!-- Right: Dynamic Collapsible Detail Panel (with Tabs) -->
        <div x-show="hasDetailPanel && showDetailPanel" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             class="w-80 md:w-88 border-l border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col h-full flex-shrink-0 z-20 shadow-xs overflow-hidden"
             x-cloak>
            
            <!-- Detail Panel Top Header -->
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-850/50 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-indigo-500 text-xs"></i>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Room Details</h4>
                </div>
                <button @click="showDetailPanel = false" 
                        type="button"
                        class="w-6 h-6 flex items-center justify-center rounded-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition-colors text-base cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Detail Panel Segmented Tabs -->
            <div class="px-3 pt-2.5 pb-2 bg-slate-50/40 dark:bg-slate-850/30 border-b border-slate-200 dark:border-slate-800 flex items-center gap-1 flex-shrink-0 select-none">
                <button @click="activeDetailTab = 'info'" 
                        type="button" 
                        class="flex-1 py-1.5 text-[11px] font-semibold rounded-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeDetailTab === 'info' 
                            ? 'bg-white dark:bg-slate-800 text-[#0c4da2] dark:text-blue-400 shadow-2xs border border-slate-200/80 dark:border-slate-700 font-bold' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    <i class="fa-solid fa-circle-info text-[10px]"></i>
                    <span>Info</span>
                </button>
                <button @click="activeDetailTab = 'files'" 
                        type="button" 
                        class="flex-1 py-1.5 text-[11px] font-semibold rounded-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeDetailTab === 'files' 
                            ? 'bg-white dark:bg-slate-800 text-[#0c4da2] dark:text-blue-400 shadow-2xs border border-slate-200/80 dark:border-slate-700 font-bold' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    <i class="fa-solid fa-paperclip text-[10px]"></i>
                    <span>Files</span>
                    <span class="px-1.5 py-0.2 bg-slate-100 dark:bg-slate-700 rounded-full text-[9px] font-bold" x-text="getSharedFilesList().length"></span>
                </button>
                <button @click="activeDetailTab = 'members'" 
                        type="button" 
                        class="flex-1 py-1.5 text-[11px] font-semibold rounded-md transition-all cursor-pointer flex items-center justify-center gap-1.5"
                        :class="activeDetailTab === 'members' 
                            ? 'bg-white dark:bg-slate-800 text-[#0c4da2] dark:text-blue-400 shadow-2xs border border-slate-200/80 dark:border-slate-700 font-bold' 
                            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    <i class="fa-solid fa-users text-[10px]"></i>
                    <span>Members</span>
                    <span class="px-1.5 py-0.2 bg-slate-100 dark:bg-slate-700 rounded-full text-[9px] font-bold" x-text="getUniqueParticipants().length"></span>
                </button>
            </div>

            <!-- Detail Panel Tab Contents -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs chat-scroll min-h-0">
                
                <!-- Tab 1: Info (Context & Room Summary) -->
                <div x-show="activeDetailTab === 'info'" class="space-y-4">
                    <!-- Topic / Title Card -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-md space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Discussion Room</span>
                            <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded-sm bg-blue-50 dark:bg-blue-950 text-[#0c4da2] dark:text-blue-400 border border-blue-200 dark:border-blue-800" x-text="chatType || 'General'"></span>
                        </div>
                        <h5 class="text-sm font-bold text-slate-800 dark:text-slate-100 break-words" x-text="chatTitle || 'Discussion Room'"></h5>
                        <p x-show="chatSubtitle" class="text-xs text-slate-500 dark:text-slate-400" x-text="chatSubtitle"></p>
                    </div>

                    <!-- Dynamic Work Order / Custom Context if available -->
                    <template x-if="detailPanelData || (typeof woDetail !== 'undefined' && woDetail)">
                        <div class="space-y-3">
                            <div class="border-t border-slate-200 dark:border-slate-800 pt-3">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Specifications</span>
                                <div class="grid grid-cols-2 gap-2.5 bg-slate-50/70 dark:bg-slate-800/40 p-2.5 rounded-md border border-slate-200/80 dark:border-slate-700/70 text-xs">
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-medium uppercase">Priority</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-100" x-text="(detailPanelData?.priority || woDetail?.priority) || 'Normal'"></span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] text-slate-400 font-medium uppercase">Status</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-100" x-text="(detailPanelData?.status || woDetail?.status) || 'Active'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Products / Part Items List with 1-Click #Tag button -->
                            <template x-if="(detailPanelData?.products || woDetail?.products) && (detailPanelData?.products || woDetail?.products).length > 0">
                                <div class="border-t border-slate-200 dark:border-slate-800 pt-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider" x-text="'Linked Products (' + ((detailPanelData?.products || woDetail?.products).length) + ')'"></span>
                                        <span class="text-[9px] text-slate-400">Click #Tag to insert</span>
                                    </div>
                                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                        <template x-for="(prod, pIdx) in (detailPanelData?.products || woDetail?.products)" :key="pIdx">
                                            <div class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-md flex items-center justify-between gap-2 shadow-2xs hover:border-[#0c4da2]/50 transition-colors">
                                                <div class="min-w-0 flex-1">
                                                    <span class="block font-bold text-slate-800 dark:text-slate-100 text-xs truncate font-mono" x-text="prod.customer_part_no"></span>
                                                    <span class="block text-[11px] text-slate-500 truncate" x-text="prod.customer_part_name"></span>
                                                </div>
                                                <button type="button" 
                                                        @click="chatInputMessage = (chatInputMessage ? chatInputMessage + ' ' : '') + '#' + prod.customer_part_no + ' '; $refs.chatInput?.focus();"
                                                        class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 text-indigo-600 dark:text-indigo-300 font-bold text-[10px] rounded-md border border-indigo-200 dark:border-indigo-800 transition-colors cursor-pointer flex-shrink-0"
                                                        title="Tag this part number in chat">
                                                    #Tag
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Room Statistics -->
                    <div class="border-t border-slate-200 dark:border-slate-800 pt-3">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Activity Overview</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/70 rounded-md">
                                <span class="block text-[10px] text-slate-400">Total Messages</span>
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100" x-text="chatMessages.length"></span>
                            </div>
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/70 rounded-md">
                                <span class="block text-[10px] text-slate-400">Shared Files</span>
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100" x-text="getSharedFilesList().length"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Files & Links (Shared Attachments) -->
                <div x-show="activeDetailTab === 'files'" class="space-y-2">
                    <template x-if="getSharedFilesList().length === 0">
                        <div class="py-8 text-center text-slate-400 select-none">
                            <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-50"></i>
                            <p class="text-xs">No shared files or media in this discussion.</p>
                        </div>
                    </template>

                    <div class="space-y-2">
                        <template x-for="(file, fIdx) in getSharedFilesList()" :key="fIdx">
                            <div class="p-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-md flex items-center justify-between gap-2 shadow-2xs hover:border-[#0c4da2]/50 transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0"
                                         :class="isPdfType(file.file_type, file.file_name) ? 'bg-rose-50 dark:bg-rose-950 text-rose-500' : (isImageType(file.file_type, file.file_name) ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-500' : 'bg-blue-50 dark:bg-blue-950 text-blue-500')">
                                        <i class="fa-solid text-sm" :class="isPdfType(file.file_type, file.file_name) ? 'fa-file-pdf' : (isImageType(file.file_type, file.file_name) ? 'fa-image' : 'fa-file')"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="block font-semibold text-slate-800 dark:text-slate-200 text-xs truncate" x-text="file.file_name"></span>
                                        <span class="block text-[10px] text-slate-400 truncate" x-text="(file.senderName || 'User') + ' • ' + formatBytes(file.file_size)"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <template x-if="isPdfType(file.file_type, file.file_name) || isImageType(file.file_type, file.file_name)">
                                        <button type="button" 
                                                @click="previewDoc(file.file_url, file.file_name)"
                                                class="w-7 h-7 flex items-center justify-center rounded-md text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-700 cursor-pointer"
                                                title="Preview File">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </button>
                                    </template>
                                    <a :href="file.file_url" 
                                       :download="file.file_name" 
                                       target="_blank"
                                       class="w-7 h-7 flex items-center justify-center rounded-md text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-700 cursor-pointer"
                                       title="Download File">
                                        <i class="fa-solid fa-download text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tab 3: Members (Participants List) -->
                <div x-show="activeDetailTab === 'members'" class="space-y-2">
                    <template x-if="getUniqueParticipants().length === 0">
                        <div class="py-8 text-center text-slate-400 select-none">
                            <i class="fa-solid fa-users text-3xl mb-2 opacity-50"></i>
                            <p class="text-xs">No active participants yet.</p>
                        </div>
                    </template>

                    <div class="space-y-2">
                        <template x-for="(user, uIdx) in getUniqueParticipants()" :key="uIdx">
                            <div class="p-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-md flex items-center justify-between gap-2 shadow-2xs">
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold uppercase border flex-shrink-0"
                                         :class="getUserColor(user.id).avatar"
                                         x-text="getInitials(user.name)"></div>
                                    <div class="min-w-0 flex-1">
                                        <span class="block font-bold text-slate-800 dark:text-slate-100 text-xs truncate" x-text="user.name"></span>
                                        <span class="block text-[10px] text-slate-400 truncate" x-text="user.department_code || user.email || 'Participant'"></span>
                                    </div>
                                </div>
                                <button type="button" 
                                        @click="chatInputMessage = (chatInputMessage ? chatInputMessage + ' ' : '') + '@' + user.name + ' '; $refs.chatInput?.focus();"
                                        class="px-2 py-1 bg-slate-100 hover:bg-indigo-50 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 hover:text-indigo-600 dark:text-slate-300 font-bold text-[10px] rounded-md transition-colors cursor-pointer flex-shrink-0"
                                        title="Mention in chat">
                                    @Mention
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Document / Image Modal Preview -->
    <div x-show="pdfPreviewUrl" 
         class="fixed inset-0 z-[99999] bg-black/50 flex items-center justify-center p-4"
         @click.self="pdfPreviewUrl = null"
         @keydown.escape.window="pdfPreviewUrl = null"
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
