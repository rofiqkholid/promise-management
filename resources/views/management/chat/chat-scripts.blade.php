<x-sweetalert />

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.css">
<style>
    /* Force chat messages container to scroll smoothly and reliably */
    #chat-messages-container {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        flex: 1 1 0% !important;
        min-height: 0 !important;
        touch-action: pan-y !important;
        overscroll-behavior-y: contain !important;
        -webkit-overflow-scrolling: touch !important;
    }

    /* Sleek custom scrollbar */
    .chat-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .chat-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .chat-scroll::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }
    .dark .chat-scroll::-webkit-scrollbar-thumb {
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

    .chat-bubble-out {
        background-color: #007aff !important;
        color: #ffffff !important;
        border-radius: 1.15rem 0.25rem 1.15rem 1.15rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }
    .chat-bubble-in {
        background-color: #ffffff !important;
        color: #1e293b !important;
        border-radius: 0.25rem 1.15rem 1.15rem 1.15rem !important;
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
    }
    .dark .chat-bubble-in {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border-color: rgba(51, 65, 85, 0.85) !important;
    }

    /* Markdown content inside bubbles styling with high specificity */
    .chat-markdown {
        word-break: break-word;
    }
    .chat-markdown p { margin-bottom: 0.35rem !important; line-height: 1.5 !important; }
    .chat-markdown p:last-child { margin-bottom: 0 !important; }
    .chat-markdown strong, .chat-markdown b { font-weight: 700 !important; }
    .chat-markdown em, .chat-markdown i { font-style: italic !important; }
    .chat-markdown del, .chat-markdown s, .chat-markdown strike { text-decoration: line-through !important; }
    .chat-markdown ul { list-style-type: disc !important; padding-left: 1.25rem !important; margin: 0.35rem 0 !important; }
    .chat-markdown ol { list-style-type: decimal !important; padding-left: 1.25rem !important; margin: 0.35rem 0 !important; }
    .chat-markdown li { display: list-item !important; margin-bottom: 0.2rem !important; }
    
    /* Natural, seamless link styling in chat bubbles */
    .chat-bubble-out .chat-markdown a {
        color: #ffffff !important;
        text-decoration: underline !important;
        text-underline-offset: 3px !important;
        text-decoration-thickness: 1.5px !important;
        font-weight: 600 !important;
        word-break: break-all !important;
        transition: opacity 0.15s ease !important;
    }
    .chat-bubble-out .chat-markdown a:hover {
        opacity: 0.85 !important;
    }
    .chat-bubble-in .chat-markdown a {
        color: #2563eb !important;
        text-decoration: underline !important;
        text-underline-offset: 3px !important;
        text-decoration-thickness: 1.5px !important;
        font-weight: 600 !important;
        word-break: break-all !important;
        transition: color 0.15s ease !important;
    }
    .chat-bubble-in .chat-markdown a:hover {
        color: #1d4ed8 !important;
    }
    .dark .chat-bubble-in .chat-markdown a {
        color: #60a5fa !important;
    }
    .dark .chat-bubble-in .chat-markdown a:hover {
        color: #93c5fd !important;
    }
    
    .chat-markdown ol {
        list-style-type: decimal !important;
        list-style-position: outside !important;
        margin: 0.35rem 0 0.35rem 1.35rem !important;
        padding: 0 !important;
    }
    .chat-markdown ul {
        list-style-type: disc !important;
        list-style-position: outside !important;
        margin: 0.35rem 0 0.35rem 1.35rem !important;
        padding: 0 !important;
    }
    .chat-markdown li {
        margin: 0.15rem 0 !important;
        line-height: 1.45 !important;
        display: list-item !important;
    }
    .chat-bubble-out .chat-markdown ol,
    .chat-bubble-out .chat-markdown ul {
        color: #ffffff !important;
    }
    
    .chat-markdown code { 
        background-color: rgba(0, 0, 0, 0.08) !important; 
        padding: 1px 4px !important; 
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; 
        font-size: 85% !important; 
        border-radius: 3px !important; 
    }
    .dark .chat-markdown code { background-color: rgba(255, 255, 255, 0.15) !important; }
    .chat-markdown pre { 
        background-color: rgba(0, 0, 0, 0.06) !important; 
        padding: 6px 10px !important; 
        border-radius: 4px !important; 
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; 
        font-size: 85% !important; 
        overflow-x: auto !important; 
        margin: 0.35rem 0 !important; 
    }
    .dark .chat-markdown pre { background-color: rgba(255, 255, 255, 0.1) !important; }
    /* Quotes (High-Contrast & Beautifully Highlighted) */
    .chat-markdown blockquote {
        border-left: 3.5px solid #4f46e5 !important;
        background-color: rgba(99, 102, 241, 0.08) !important;
        padding: 4px 10px !important;
        margin: 0.35rem 0 !important;
        border-radius: 0 6px 6px 0 !important;
        font-style: italic !important;
    }
    .dark .chat-markdown blockquote {
        border-left-color: #818cf8 !important;
        background-color: rgba(99, 102, 241, 0.2) !important;
    }
    .chat-bubble-out .chat-markdown blockquote {
        border-left: 3.5px solid #ffffff !important;
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: #ffffff !important;
    }

    .viewer-container {
        z-index: 99999 !important;
    }

    .chat-bubble-highlight {
        position: relative !important;
        z-index: 30 !important;
    }
    
    /* Highlight animation for incoming (white/slate) bubbles */
    .chat-bubble-in.chat-bubble-highlight {
        animation: chatBubbleInHighlight 2.5s cubic-bezier(0.25, 1, 0.5, 1) !important;
    }
    @keyframes chatBubbleInHighlight {
        0% {
            background-color: #ffffff;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            transform: scale(1);
        }
        15%, 45% {
            background-color: #fef08a !important; /* Rich amber-yellow flash */
            box-shadow: 0 0 0 3px #f59e0b, 0 10px 25px -5px rgba(245, 158, 11, 0.5) !important;
            transform: scale(1.025);
        }
        70% {
            background-color: #fef9c3 !important;
            box-shadow: 0 0 0 2px #fbbf24, 0 4px 12px rgba(245, 158, 11, 0.25) !important;
            transform: scale(1.01);
        }
        100% {
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transform: scale(1);
        }
    }
    .dark .chat-bubble-in.chat-bubble-highlight {
        animation: chatBubbleInDarkHighlight 2.5s cubic-bezier(0.25, 1, 0.5, 1) !important;
    }
    @keyframes chatBubbleInDarkHighlight {
        0% {
            background-color: #1e293b;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            transform: scale(1);
        }
        15%, 45% {
            background-color: #78350f !important;
            box-shadow: 0 0 0 3px #fbbf24, 0 10px 25px -5px rgba(251, 191, 36, 0.6) !important;
            transform: scale(1.025);
        }
        70% {
            background-color: #451a03 !important;
            box-shadow: 0 0 0 2px #f59e0b, 0 4px 12px rgba(245, 158, 11, 0.3) !important;
            transform: scale(1.01);
        }
        100% {
            background-color: #1e293b;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transform: scale(1);
        }
    }

    /* Highlight animation for outgoing (blue) bubbles */
    .chat-bubble-out.chat-bubble-highlight {
        animation: chatBubbleOutHighlight 2.5s cubic-bezier(0.25, 1, 0.5, 1) !important;
    }
    @keyframes chatBubbleOutHighlight {
        0% {
            box-shadow: 0 0 0 0 rgba(251, 191, 36, 0);
            transform: scale(1);
        }
        15%, 45% {
            box-shadow: 0 0 0 4px #fbbf24, 0 10px 30px -5px rgba(251, 191, 36, 0.7) !important;
            filter: brightness(1.25) contrast(1.1);
            transform: scale(1.025);
        }
        70% {
            box-shadow: 0 0 0 2px #fde047, 0 4px 15px rgba(251, 191, 36, 0.4) !important;
            filter: brightness(1.1);
            transform: scale(1.01);
        }
        100% {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            filter: brightness(1);
            transform: scale(1);
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
window.chatRoomEngine = function(defaultType = 'work_order', defaultId = null) {
    return {
        chatType: defaultType,
        chatId: defaultId,
        chatTitle: '',
        chatSubtitle: '',
        chatMessages: [],
        chatInputMessage: '',
        chatAttachments: [],
        replyingTo: null,
        
        // Mentions & Tags
        mentionUsers: [],
        mentionItems: [],
        showMentionDropdown: false,
        mentionMode: null, // '@' or '#'
        mentionQuery: '',
        mentionCursorPos: 0,
        selectedMentionIndex: 0,

        // State & UI
        isDragging: false,
        loadingChats: false,
        sendingMessage: false,
        hasMoreChats: true,
        oldestChatId: null,
        echoChannel: null,
        isPusherConnected: false,
        viewerInstance: null,
        pdfPreviewUrl: null,
        pdfPreviewName: '',
        showOnlyMediaAndLinks: false,
        selectedDateFilter: 'all', // 'all', 'today', 'yesterday', '7days', 'custom'
        customDateFilter: '',
        showScrollToBottomBtn: false,
        showFormatToolbar: false,
        searchQuery: '',
        showSearchBar: false,
        searchResults: [],
        currentSearchIndex: -1,
        searchDebounceTimer: null,
        currentUserId: {{ Auth::check() ? Auth::user()->id : 0 }},

        // User Avatar Colors
        userColorPalette: [
            { avatar: 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800', name: 'text-emerald-600 dark:text-emerald-400' },
            { avatar: 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800', name: 'text-indigo-600 dark:text-indigo-400' },
            { avatar: 'bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800', name: 'text-violet-600 dark:text-violet-400' },
            { avatar: 'bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800', name: 'text-sky-600 dark:text-sky-400' },
            { avatar: 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800', name: 'text-amber-600 dark:text-amber-400' },
            { avatar: 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800', name: 'text-rose-600 dark:text-rose-400' },
            { avatar: 'bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800', name: 'text-teal-600 dark:text-teal-400' },
        ],

        getUserColor(userId) {
            const index = Math.abs(Number(userId || 0)) % this.userColorPalette.length;
            return this.userColorPalette[index];
        },

        initRoom(type, id, title = '', subtitle = '') {
            if (this.echoChannel && typeof window.Echo !== 'undefined') {
                try {
                    window.Echo.leave(`chat.${this.chatType}.${this.chatId}`);
                } catch (e) {}
                this.echoChannel = null;
            }

            this.chatType = type;
            this.chatId = id;
            this.chatTitle = title;
            this.chatSubtitle = subtitle;
            this.chatMessages = [];
            this.chatInputMessage = '';
            this.chatAttachments = [];
            this.replyingTo = null;
            this.hasMoreChats = true;
            this.oldestChatId = null;

            if (!id) return;

            this.loadMentionables();
            this.loadChats();
            this.listenEcho();
        },

        listenEcho() {
            if (!this.chatId) return;

            // Check if Echo is available and configured
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                try {
                    const handleMessageIncoming = (e) => {
                        if (!e || !e.id) return;
                        if (!this.chatMessages.some(m => Number(m.id) === Number(e.id))) {
                            this.chatMessages.push(e);
                            this.$nextTick(() => {
                                this.scrollToBottom();
                                this.initViewer();
                            });
                        }
                    };

                    const handleMessageDeleted = (e) => {
                        if (!e || !e.chatId) return;
                        this.chatMessages = this.chatMessages.filter(msg => Number(msg.id) !== Number(e.chatId));
                    };

                    this.echoChannel = window.Echo.private(`chat.${this.chatType}.${this.chatId}`)
                        .listen('.ChatMessageSent', handleMessageIncoming)
                        .listen('ChatMessageSent', handleMessageIncoming)
                        .listen('.ChatMessageDeleted', handleMessageDeleted)
                        .listen('ChatMessageDeleted', handleMessageDeleted);

                    // Track Pusher connection states gracefully without polling
                    if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                        const conn = window.Echo.connector.pusher.connection;
                        conn.bind('connected', () => {
                            this.isPusherConnected = true;
                        });
                        conn.bind('disconnected', () => {
                            this.isPusherConnected = false;
                        });
                        conn.bind('unavailable', () => {
                            this.isPusherConnected = false;
                        });
                        conn.bind('failed', () => {
                            this.isPusherConnected = false;
                        });
                        conn.bind('error', (err) => {
                            this.isPusherConnected = false;
                            // Silently ignore quota / connection errors so the app runs uninterrupted
                            console.debug('Pusher connection notice (operating in local direct mode):', err);
                        });

                        this.isPusherConnected = (conn.state === 'connected');
                    }
                } catch (err) {
                    console.debug('Echo setup notice (operating in standard direct mode):', err);
                }
            }
        },

        leaveRoom() {
            if (this.echoChannel && typeof window.Echo !== 'undefined') {
                try {
                    window.Echo.leave(`chat.${this.chatType}.${this.chatId}`);
                } catch (e) {}
                this.echoChannel = null;
            }
            if (this.viewerInstance) {
                this.viewerInstance.destroy();
                this.viewerInstance = null;
            }
            this.chatId = null;
            this.chatMessages = [];
        },

        loadMentionables() {
            if (!this.chatId) return;
            fetch(`{{ url('management/chats/mentionables') }}/${this.chatType}/${this.chatId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.mentionUsers = data.users || [];
                    this.mentionItems = data.items || [];
                }
            })
            .catch(e => console.error(e));
        },

        loadChats(beforeId = null, targetId = null, callback = null) {
            if (!this.chatId) return;
            this.loadingChats = true;

            let url = `{{ url('management/chats') }}/${this.chatType}/${this.chatId}`;
            const params = new URLSearchParams();
            if (beforeId) params.append('before_id', beforeId);
            if (targetId) params.append('target_id', targetId);
            params.append('limit', '20');
            url += '?' + params.toString();

            const container = this.$refs.chatContainer || document.getElementById('chat-messages-container');
            const prevScrollHeight = container ? container.scrollHeight : 0;

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && Array.isArray(data.messages)) {
                    if (data.has_more !== undefined) {
                        this.hasMoreChats = data.has_more;
                    } else if (data.messages.length < 20) {
                        this.hasMoreChats = false;
                    }

                    if (beforeId || targetId) {
                        // Merge and deduplicate by id
                        const existingMap = new Map(this.chatMessages.map(m => [m.id, m]));
                        data.messages.forEach(m => existingMap.set(m.id, m));
                        const merged = Array.from(existingMap.values()).sort((a, b) => a.id - b.id);
                        this.chatMessages = merged;

                        this.$nextTick(() => {
                            if (container && !targetId) {
                                container.scrollTop = container.scrollHeight - prevScrollHeight;
                            }
                            this.initViewer();
                            if (typeof callback === 'function') callback();
                        });
                    } else {
                        this.chatMessages = data.messages;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.initViewer();
                            // Multi-stage scroll to guarantee viewport lands at newest message after images/DOM render
                            setTimeout(() => { this.scrollToBottom(); }, 60);
                            setTimeout(() => { this.scrollToBottom(); }, 200);
                            setTimeout(() => { this.scrollToBottom(); }, 500);
                            if (typeof callback === 'function') callback();
                        });
                    }

                    if (this.chatMessages.length > 0) {
                        this.oldestChatId = this.chatMessages[0].id;
                    }
                }
            })
            .catch(e => console.error('Error loading chats:', e))
            .finally(() => {
                this.loadingChats = false;
            });
        },

        onSearchInput() {
            if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
            const query = (this.searchQuery || '').trim();
            if (!query) {
                this.searchResults = [];
                this.currentSearchIndex = -1;
                return;
            }

            // Immediately scan local chatMessages in memory
            const q = query.toLowerCase();
            const localMatches = this.chatMessages.filter(m => {
                const matchMsg = m.message && m.message.toLowerCase().includes(q);
                const matchUser = m.user_name && m.user_name.toLowerCase().includes(q);
                const matchFile = (m.file_name && m.file_name.toLowerCase().includes(q)) ||
                                  (Array.isArray(m.attachments) && m.attachments.some(a => (a.name || a.file_name || '').toLowerCase().includes(q)));
                return matchMsg || matchUser || matchFile;
            }).map(m => m.id);

            this.searchResults = localMatches;
            if (this.searchResults.length > 0) {
                // Focus on the newest matched message
                this.currentSearchIndex = this.searchResults.length - 1;
                this.jumpToMessage(this.searchResults[this.currentSearchIndex]);
            } else {
                this.currentSearchIndex = -1;
            }

            // Debounced server search across full history if query is 2+ chars
            if (query.length >= 2) {
                this.searchDebounceTimer = setTimeout(() => {
                    this.searchServer(query);
                }, 350);
            }
        },

        searchPrev() {
            if (this.searchResults.length === 0 || this.currentSearchIndex <= 0) return;
            this.currentSearchIndex--;
            this.jumpToMessage(this.searchResults[this.currentSearchIndex]);
        },

        searchNext() {
            if (this.searchResults.length === 0 || this.currentSearchIndex >= this.searchResults.length - 1) return;
            this.currentSearchIndex++;
            this.jumpToMessage(this.searchResults[this.currentSearchIndex]);
        },

        clearSearch() {
            this.searchQuery = '';
            this.searchResults = [];
            this.currentSearchIndex = -1;
            if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
        },

        searchServer(query) {
            if (!this.chatId || !query) return;
            const url = `{{ url('management/chats') }}/${this.chatType}/${this.chatId}?q=${encodeURIComponent(query)}`;
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                    const serverIds = data.messages.map(m => m.id);
                    const mergedSet = new Set([...this.searchResults, ...serverIds]);
                    this.searchResults = Array.from(mergedSet).sort((a, b) => a - b);

                    // If currently no selection, select latest match
                    if (this.currentSearchIndex === -1 && this.searchResults.length > 0) {
                        this.currentSearchIndex = this.searchResults.length - 1;
                        this.jumpToMessage(this.searchResults[this.currentSearchIndex]);
                    }
                }
            })
            .catch(() => {});
        },

        handleChatScroll(e) {
            const el = e.target;
            if (el.scrollTop <= 40 && this.hasMoreChats && !this.loadingChats && this.oldestChatId) {
                this.loadChats(this.oldestChatId);
            }
            this.showScrollToBottomBtn = (el.scrollHeight - el.scrollTop - el.clientHeight) > 180;
        },

        scrollToBottom() {
            const container = this.$refs.chatContainer || document.getElementById('chat-messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        jumpToMessage(targetId) {
            if (!targetId) return;
            const targetRow = document.getElementById('chat-bubble-' + targetId);
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const bubbleBox = targetRow.querySelector('.chat-bubble-in, .chat-bubble-out') || targetRow;
                bubbleBox.classList.remove('chat-bubble-highlight');
                void bubbleBox.offsetWidth; // trigger reflow
                bubbleBox.classList.add('chat-bubble-highlight');
                setTimeout(() => {
                    bubbleBox.classList.remove('chat-bubble-highlight');
                }, 2400);
            } else {
                // Target message is not in currently loaded window -> load history up to targetId!
                this.loadChats(this.oldestChatId, targetId, () => {
                    setTimeout(() => {
                        const newTargetRow = document.getElementById('chat-bubble-' + targetId);
                        if (newTargetRow) {
                            newTargetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            const bubbleBox = newTargetRow.querySelector('.chat-bubble-in, .chat-bubble-out') || newTargetRow;
                            bubbleBox.classList.remove('chat-bubble-highlight');
                            void bubbleBox.offsetWidth;
                            bubbleBox.classList.add('chat-bubble-highlight');
                            setTimeout(() => {
                                bubbleBox.classList.remove('chat-bubble-highlight');
                            }, 2400);
                        }
                    }, 150);
                });
            }
        },

        jumpToDate(mode, customDate = null) {
            if (!this.chatId) return;
            this.selectedDateFilter = mode;
            if (customDate) this.customDateFilter = customDate;

            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

            let targetDateStr = '';
            let dateLabel = '';

            if (mode === 'today') {
                targetDateStr = todayStr;
                dateLabel = 'Today';
            } else if (mode === 'yesterday') {
                const yest = new Date();
                yest.setDate(now.getDate() - 1);
                targetDateStr = `${yest.getFullYear()}-${pad(yest.getMonth() + 1)}-${pad(yest.getDate())}`;
                dateLabel = 'Yesterday';
            } else if (mode === '7days') {
                const sevenDaysAgo = new Date();
                sevenDaysAgo.setDate(now.getDate() - 7);
                targetDateStr = `${sevenDaysAgo.getFullYear()}-${pad(sevenDaysAgo.getMonth() + 1)}-${pad(sevenDaysAgo.getDate())}`;
                dateLabel = 'Last 7 Days';
            } else if (mode === 'custom' && customDate) {
                targetDateStr = customDate;
                dateLabel = customDate;
            }

            if (!targetDateStr) return;

            // Search in currently loaded messages (find earliest message matching or >= targetDateStr)
            const matchMsg = this.chatMessages.find(m => {
                if (!m.created_at) return false;
                const mDate = m.created_at.substring(0, 10);
                return mDate >= targetDateStr;
            });

            if (matchMsg) {
                this.jumpToMessage(matchMsg.id);
            } else {
                // If not in currently loaded batch, fetch from server and jump directly
                fetch(`{{ url('management/chats') }}/${this.chatType}/${this.chatId}?target_date=${targetDateStr}&limit=30`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                        const existingIds = new Set(this.chatMessages.map(m => Number(m.id)));
                        const newMsgs = data.messages.filter(m => !existingIds.has(Number(m.id)));
                        this.chatMessages = [...newMsgs, ...this.chatMessages].sort((a, b) => Number(a.id) - Number(b.id));
                        this.oldestChatId = this.chatMessages.length > 0 ? this.chatMessages[0].id : null;
                        this.hasMoreChats = data.has_more !== undefined ? data.has_more : this.hasMoreChats;
                        
                        this.$nextTick(() => {
                            const firstMatch = this.chatMessages.find(m => m.created_at && m.created_at.substring(0, 10) >= targetDateStr);
                            if (firstMatch) {
                                this.jumpToMessage(firstMatch.id);
                            }
                        });
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(`No messages found on or after ${dateLabel}`, 'info');
                        }
                    }
                })
                .catch(() => {});
            }
        },

        getDateFilterLabel() {
            if (this.selectedDateFilter === 'today') return 'Today';
            if (this.selectedDateFilter === 'yesterday') return 'Yesterday';
            if (this.selectedDateFilter === '7days') return 'Last 7 Days';
            if (this.selectedDateFilter === 'custom' && this.customDateFilter) {
                const parts = this.customDateFilter.split('-');
                if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
                return this.customDateFilter;
            }
            return 'Jump to Date';
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const normalized = dateStr.replace(/-/g, '/');
            const d = new Date(normalized);
            if (isNaN(d.getTime())) return dateStr;
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${hours}:${minutes}`;
        },

        getDateDivider(dateStr) {
            if (!dateStr) return '';
            const normalized = dateStr.substring(0, 10);
            const parts = normalized.split('-');
            if (parts.length !== 3) return normalized;

            const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            if (isNaN(date.getTime())) return normalized;

            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);

            const isSameDay = (d1, d2) => d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate();

            if (isSameDay(date, today)) return 'Today';
            if (isSameDay(date, yesterday)) return 'Yesterday';

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]}`;
        },

        shouldShowDateDivider(messages, index) {
            if (!messages || messages.length === 0 || index === 0) return true;
            const current = messages[index];
            const prev = messages[index - 1];
            if (!current || !prev || !current.created_at || !prev.created_at) return false;
            
            const curDate = current.created_at.substring(0, 10);
            const prevDate = prev.created_at.substring(0, 10);
            return curDate !== prevDate;
        },

        shouldShowMessageFooter(messages, index) {
            if (!messages || index >= messages.length - 1) return true;
            const current = messages[index];
            const next = messages[index + 1];
            if (!current || !next) return true;

            // If next message has a date divider after it, show footer
            if (this.shouldShowDateDivider(messages, index + 1)) return true;
            
            // If next message is from a different user, show footer
            if (Number(current.user_id) !== Number(next.user_id)) return true;
            
            // If next message is sent in a different minute, show footer
            const curTime = current.created_at ? current.created_at.substring(0, 16) : '';
            const nextTime = next.created_at ? next.created_at.substring(0, 16) : '';
            return curTime !== nextTime;
        },

        shouldShowAvatar(messages, index) {
            if (!messages || index === 0) return true;
            const current = messages[index];
            const prev = messages[index - 1];
            if (!current || !prev) return true;

            // If current message has a date divider above it, show avatar
            if (this.shouldShowDateDivider(messages, index)) return true;

            // If previous message is from a different user, show avatar
            if (Number(current.user_id) !== Number(prev.user_id)) return true;

            // If time (minute) changed from previous message, show avatar
            const curTime = current.created_at ? current.created_at.substring(0, 16) : '';
            const prevTime = prev.created_at ? prev.created_at.substring(0, 16) : '';
            if (curTime !== prevTime) return true;

            return false;
        },

        getFilteredMessages() {
            let list = this.chatMessages;

            // Filter by Media / Links toggle only
            if (this.showOnlyMediaAndLinks) {
                list = list.filter(msg => {
                    const hasFile = !!msg.file_path;
                    const hasLink = msg.message && (msg.message.includes('http://') || msg.message.includes('https://') || msg.message.includes('www.'));
                    return hasFile || hasLink;
                });
            }

            return list;
        },

        // Reply / Quoting
        setReply(msg) {
            this.replyingTo = {
                id: msg.id,
                user_name: msg.user_name || 'User',
                message: msg.message ? (msg.message.length > 80 ? msg.message.substring(0, 80) + '...' : msg.message) : '',
                file_name: msg.file_name,
                file_type: msg.file_type
            };
            this.$nextTick(() => {
                const input = this.$refs.chatInput;
                if (input) input.focus();
            });
        },

        cancelReply() {
            this.replyingTo = null;
        },

        // Mentions (@user and #item)
        handleInputChange(e) {
            const val = e.target.value;
            const pos = e.target.selectionStart;
            this.mentionCursorPos = pos;

            const textBeforeCursor = val.substring(0, pos);
            const atMatch = textBeforeCursor.match(/@([a-zA-Z0-9_\s]*)$/);
            const hashMatch = textBeforeCursor.match(/#([a-zA-Z0-9_\-\s]*)$/);

            if (atMatch) {
                this.mentionMode = '@';
                this.mentionQuery = atMatch[1].toLowerCase().trim();
                this.showMentionDropdown = true;
                this.selectedMentionIndex = 0;
            } else if (hashMatch) {
                this.mentionMode = '#';
                this.mentionQuery = hashMatch[1].toLowerCase().trim();
                this.showMentionDropdown = true;
                this.selectedMentionIndex = 0;
            } else {
                this.showMentionDropdown = false;
                this.mentionMode = null;
            }
        },

        getFilteredMentions() {
            if (this.mentionMode === '@') {
                return this.mentionUsers.filter(u => 
                    u.name.toLowerCase().includes(this.mentionQuery) || 
                    (u.email && u.email.toLowerCase().includes(this.mentionQuery))
                ).slice(0, 6);
            } else if (this.mentionMode === '#') {
                return this.mentionItems.filter(i => 
                    (i.code && i.code.toLowerCase().includes(this.mentionQuery)) || 
                    (i.name && i.name.toLowerCase().includes(this.mentionQuery))
                ).slice(0, 6);
            }
            return [];
        },

        selectMention(item) {
            const input = this.$refs.chatInput;
            if (!input) return;

            const val = this.chatInputMessage;
            const textBefore = val.substring(0, this.mentionCursorPos);
            const textAfter = val.substring(this.mentionCursorPos);

            let replacePattern = this.mentionMode === '@' ? /@([a-zA-Z0-9_\s]*)$/ : /#([a-zA-Z0-9_\-\s]*)$/;
            let insertedTag = this.mentionMode === '@' ? `@${item.name} ` : `#${item.code || item.name} `;

            const newTextBefore = textBefore.replace(replacePattern, insertedTag);
            this.chatInputMessage = newTextBefore + textAfter;
            this.showMentionDropdown = false;
            this.mentionMode = null;

            this.$nextTick(() => {
                input.focus();
                const newPos = newTextBefore.length;
                input.setSelectionRange(newPos, newPos);
            });
        },

        // Rich Formatting & Block Styling
        applyFormat(type) {
            const textarea = this.$refs.chatInput;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = this.chatInputMessage.substring(start, end);
            let before = this.chatInputMessage.substring(0, start);
            let after = this.chatInputMessage.substring(end);
            let replacement = '';
            let newCursorPos = start;

            switch(type) {
                case 'bold':
                    replacement = `**${selected || 'bold text'}**`;
                    newCursorPos = selected ? end + 4 : start + 2;
                    break;
                case 'italic':
                    replacement = `*${selected || 'italic text'}*`;
                    newCursorPos = selected ? end + 2 : start + 1;
                    break;
                case 'strike':
                    replacement = `~~${selected || 'strikethrough'}~~`;
                    newCursorPos = selected ? end + 4 : start + 2;
                    break;
                case 'underline':
                    replacement = `<u>${selected || 'underlined text'}</u>`;
                    newCursorPos = selected ? end + 7 : start + 3;
                    break;
                case 'code':
                    if (selected.includes('\n')) {
                        replacement = `\`\`\`\n${selected || 'code block'}\n\`\`\``;
                        newCursorPos = selected ? end + 8 : start + 4;
                    } else {
                        replacement = `\`${selected || 'code'}\``;
                        newCursorPos = selected ? end + 2 : start + 1;
                    }
                    break;
                case 'quote':
                    if (selected) {
                        const quoted = selected.split('\n').map(l => `> ${l}`).join('\n');
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}${quoted}\n`;
                        newCursorPos = start + replacement.length;
                    } else {
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}> quote text\n`;
                        newCursorPos = start + replacement.length;
                    }
                    break;
                case 'ul':
                    if (selected) {
                        const listItems = selected.split('\n').map(l => `- ${l.replace(/^[-*•\d\.]+\s*/, '')}`).join('\n');
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}${listItems}\n`;
                        newCursorPos = start + replacement.length;
                    } else {
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}- List item\n`;
                        newCursorPos = start + replacement.length;
                    }
                    break;
                case 'ol':
                    if (selected) {
                        let count = 1;
                        const listItems = selected.split('\n').map(l => `${count++}. ${l.replace(/^[-*•\d\.]+\s*/, '')}`).join('\n');
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}${listItems}\n`;
                        newCursorPos = start + replacement.length;
                    } else {
                        replacement = `${before.endsWith('\n') || !before ? '' : '\n'}1. List item\n`;
                        newCursorPos = start + replacement.length;
                    }
                    break;
            }

            this.chatInputMessage = before + replacement + after;
            this.$nextTick(() => {
                textarea.focus();
                textarea.setSelectionRange(newCursorPos, newCursorPos);
                textarea.style.height = '20px';
                textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
            });
        },

        // Textarea Keyboard Shortcuts (Microsoft Teams, Word & Slack standards) & Smart List Continuation
        handleChatTextareaKeydown(event) {
            const textarea = this.$refs.chatInput;
            if (!textarea) return;

            const isCtrl = event.ctrlKey || event.metaKey;
            const isAlt = event.altKey;
            const isShift = event.shiftKey;
            const key = event.key ? event.key.toLowerCase() : '';

            // 1. Standard Microsoft / Slack / Teams Formatting Shortcuts
            if (isCtrl) {
                // Ctrl+B: Bold (MS Word / Teams / Slack)
                if (key === 'b') {
                    event.preventDefault();
                    this.applyFormat('bold');
                    return;
                }
                // Ctrl+I: Italic (MS Word / Teams / Slack)
                if (key === 'i') {
                    event.preventDefault();
                    this.applyFormat('italic');
                    return;
                }
                // Ctrl+U: Underline (MS Word / Teams)
                if (key === 'u' && !isShift) {
                    event.preventDefault();
                    this.applyFormat('underline');
                    return;
                }
                // Strikethrough: Ctrl+Shift+X (Teams/Slack), Ctrl+Shift+S (Discord), Alt+Shift+5 (Word)
                if ((isShift && (key === 'x' || key === 's')) || (isAlt && isShift && key === '5')) {
                    event.preventDefault();
                    this.applyFormat('strike');
                    return;
                }
                // Code / Code block: Ctrl+Shift+C (Teams/Slack), Ctrl+E, Ctrl+`
                if ((isShift && key === 'c') || key === 'e' || key === '`') {
                    event.preventDefault();
                    this.applyFormat('code');
                    return;
                }
                // Blockquote: Ctrl+Shift+9 (Slack), Ctrl+Shift+. / > (Teams/Notion), Ctrl+Shift+Q
                if (isShift && (key === '9' || key === '.' || key === '>' || key === 'q')) {
                    event.preventDefault();
                    this.applyFormat('quote');
                    return;
                }
                // Bullet List: Ctrl+Shift+8 (MS Word/Teams/Slack), Ctrl+Shift+L (Wordpad/Evernote), Ctrl+Shift+U
                if (isShift && (key === '8' || key === 'l' || key === 'u')) {
                    event.preventDefault();
                    this.applyFormat('ul');
                    return;
                }
                // Numbered List: Ctrl+Shift+7 (MS Word/Teams/Slack), Ctrl+Shift+O
                if (isShift && (key === '7' || key === 'o')) {
                    event.preventDefault();
                    this.applyFormat('ol');
                    return;
                }
            }

            // 2. Smart Backspace on Empty List Item (Delete entire prefix at once)
            if (event.key === 'Backspace') {
                const val = this.chatInputMessage || '';
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;

                if (start === end) {
                    const linesBefore = val.substring(0, start).split('\n');
                    const lineBeforeCursor = linesBefore[linesBefore.length - 1];
                    const lineAfterCursor = val.substring(start).split('\n')[0];

                    // Check if current line is just a list prefix with no text: e.g. "1. ", "2. ", "- ", "* ", "> "
                    const emptyListMatch = lineBeforeCursor.match(/^(\s*)(\d+\.|[-*•>]|\d+\))\s*$/);
                    if (emptyListMatch && lineAfterCursor.trim() === '') {
                        event.preventDefault();
                        const lineStartPos = start - lineBeforeCursor.length;
                        const before = val.substring(0, lineStartPos);
                        const after = val.substring(start + lineAfterCursor.length);

                        // If preceded by a newline, remove the line and merge up
                        let newText = '';
                        let newCursor = lineStartPos;
                        if (before.endsWith('\n')) {
                            newText = before.slice(0, -1) + after;
                            newCursor = lineStartPos - 1;
                        } else {
                            newText = before + after;
                            newCursor = lineStartPos;
                        }

                        this.chatInputMessage = newText;
                        this.$nextTick(() => {
                            textarea.setSelectionRange(newCursor, newCursor);
                            textarea.style.height = '20px';
                            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                        });
                        return;
                    }
                }
            }

            // 3. Smart List Continuation & Enter Handling
            if (event.key === 'Enter') {
                const val = this.chatInputMessage || '';
                const start = textarea.selectionStart;
                const lineBeforeCursor = val.substring(0, start).split('\n').pop();

                // Numbered list pattern: "1. item" or " 2. item"
                const numMatch = lineBeforeCursor.match(/^(\s*)(\d+)\.\s*(.*)$/);
                // Bullet list pattern: "- item" or "* item" or "• item"
                const bulletMatch = lineBeforeCursor.match(/^(\s*)([-*•])\s*(.*)$/);

                if (numMatch) {
                    const indent = numMatch[1];
                    const num = parseInt(numMatch[2], 10);
                    const itemContent = numMatch[3].trim();

                    if (!itemContent) {
                        // Empty numbered list line -> Exit list (remove prefix and stay as clean line)
                        event.preventDefault();
                        const lineStartPos = start - lineBeforeCursor.length;
                        const before = val.substring(0, lineStartPos);
                        const after = val.substring(start);
                        this.chatInputMessage = before + after;
                        const newPos = lineStartPos;
                        this.$nextTick(() => {
                            textarea.setSelectionRange(newPos, newPos);
                            textarea.style.height = '20px';
                            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                        });
                        return;
                    }

                    // Has content -> Auto increment to next list number: e.g. "2. "
                    event.preventDefault();
                    const nextNumStr = `\n${indent}${num + 1}. `;
                    const before = val.substring(0, start);
                    const after = val.substring(start);
                    this.chatInputMessage = before + nextNumStr + after;
                    const newPos = start + nextNumStr.length;
                    this.$nextTick(() => {
                        textarea.setSelectionRange(newPos, newPos);
                        textarea.style.height = '20px';
                        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                    });
                    return;
                }

                if (bulletMatch) {
                    const indent = bulletMatch[1];
                    const bullet = bulletMatch[2];
                    const itemContent = bulletMatch[3].trim();

                    if (!itemContent) {
                        // Empty bullet list line -> Exit list (remove prefix and stay as clean line)
                        event.preventDefault();
                        const lineStartPos = start - lineBeforeCursor.length;
                        const before = val.substring(0, lineStartPos);
                        const after = val.substring(start);
                        this.chatInputMessage = before + after;
                        const newPos = lineStartPos;
                        this.$nextTick(() => {
                            textarea.setSelectionRange(newPos, newPos);
                            textarea.style.height = '20px';
                            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                        });
                        return;
                    }

                    // Has content -> Auto continue bullet list: e.g. "- "
                    event.preventDefault();
                    const nextBulletStr = `\n${indent}${bullet} `;
                    const before = val.substring(0, start);
                    const after = val.substring(start);
                    this.chatInputMessage = before + nextBulletStr + after;
                    const newPos = start + nextBulletStr.length;
                    this.$nextTick(() => {
                        textarea.setSelectionRange(newPos, newPos);
                        textarea.style.height = '20px';
                        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
                    });
                    return;
                }

                // Normal message line (send on Enter without Shift)
                if (!event.shiftKey) {
                    event.preventDefault();
                    this.sendMessage();
                    textarea.style.height = '20px';
                }
            }
        },

        // Format Quoted / Reply Snippet with Clean Inline Styles
        getReplySnippet(reply) {
            if (!reply) return 'Quoted Message';
            let text = reply.message || (reply.file_name ? '📎 ' + reply.file_name : 'Quoted Message');

            // Strip blockquotes (>), list markers, code blocks, and collapse multiple whitespace/newlines
            text = text
                .replace(/^>\s?/gm, '')
                .replace(/^[\t ]*[-*•\d\.]+\s*/gm, '')
                .replace(/```[\s\S]*?```/g, ' [Code] ')
                .replace(/\n+/g, ' ')
                .trim();

            // Escape HTML characters
            let str = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Render inline styles: bold, italic, strike, underline, code
            str = str.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            str = str.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            str = str.replace(/~~([^~]+)~~/g, '<del>$1</del>');
            str = str.replace(/&lt;u&gt;(.*?)&lt;\/u&gt;/gi, '<u>$1</u>');
            str = str.replace(/`([^`]+)`/g, '<code class="bg-black/15 dark:bg-white/15 px-1 py-0.5 rounded-xs font-mono text-[9.5px]">$1</code>');

            // Highlight @mentions & #items
            str = str.replace(/@([a-zA-Z0-9_\s]{2,30})(?=[,\.\s<\n]|$)/g, '<span class="font-bold opacity-90">@$1</span>');
            str = str.replace(/#([a-zA-Z0-9_\-]{2,30})/g, '<span class="font-bold font-mono opacity-90">#$1</span>');

            return str;
        },

        // Parse Markdown & Mentions
        renderFormattedMessage(text) {
            if (!text) return '';
            let html = '';

            if (typeof marked !== 'undefined' && typeof marked.parse === 'function') {
                try {
                    html = marked.parse(text, { breaks: true, gfm: true });
                } catch(e) {
                    html = this.fallbackMarkdown(text);
                }
            } else {
                html = this.fallbackMarkdown(text);
            }

            // Highlight @Mentions
            html = html.replace(/@([a-zA-Z0-9_\s]{2,30})(?=[,\.\s<\n]|$)/g, '<span class="font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-1 py-0.5 rounded-xs">@$1</span>');
            
            // Highlight #Items
            html = html.replace(/#([a-zA-Z0-9_\-]{2,30})/g, '<span class="font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1 py-0.5 rounded-xs font-mono">#$1</span>');

            // Autolink URL if not already in an <a> tag
            const urlRegex = /(?<!href=")(https?:\/\/[^\s<]+)/g;
            html = html.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer" class="hover:underline cursor-pointer">$1</a>');

            return html;
        },

        fallbackMarkdown(text) {
            let str = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            
            // Code block
            str = str.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            // Inline code
            str = str.replace(/`([^`]+)`/g, '<code>$1</code>');
            // Bold
            str = str.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            // Italic
            str = str.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            // Strikethrough
            str = str.replace(/~~([^~]+)~~/g, '<del>$1</del>');
            // Blockquote
            str = str.replace(/^>\s?(.*)$/gm, '<blockquote>$1</blockquote>');
            str = str.replace(/<\/blockquote>\n<blockquote>/g, '<br>');

            // Bullet list
            str = str.replace(/^[\t ]*[-*•]\s+(.+)$/gm, '<li class="chat-li-ul">$1</li>');
            str = str.replace(/(<li class="chat-li-ul">[\s\S]*?<\/li>)(?!(?:\s*<li class="chat-li-ul">))/g, function(match) {
                return '<ul>' + match.replace(/class="chat-li-ul"/g, '') + '</ul>';
            });

            // Numbered list
            str = str.replace(/^[\t ]*(\d+)\.\s+(.+)$/gm, '<li class="chat-li-ol">$2</li>');
            str = str.replace(/(<li class="chat-li-ol">[\s\S]*?<\/li>)(?!(?:\s*<li class="chat-li-ol">))/g, function(match) {
                return '<ol>' + match.replace(/class="chat-li-ol"/g, '') + '</ol>';
            });

            // Newlines
            str = str.replace(/\n/g, '<br>');
            return str;
        },

        // Attachments
        handleFileSelect(e) {
            const files = Array.from(e.target.files);
            this.addFiles(files);
            e.target.value = '';
        },

        handleDrop(e) {
            this.isDragging = false;
            if (e.dataTransfer && e.dataTransfer.files) {
                const files = Array.from(e.dataTransfer.files);
                this.addFiles(files);
            }
        },

        addFiles(files) {
            files.forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    if (typeof showToast === 'function') showToast(`File ${file.name} exceeds 10MB limit.`, 'error');
                    return;
                }
                const alreadyExists = this.chatAttachments.some(a => a.name === file.name && a.size === file.size);
                if (!alreadyExists) {
                    const isImg = file.type.startsWith('image/') || file.name.toLowerCase().endsWith('.png') || file.name.toLowerCase().endsWith('.jpg') || file.name.toLowerCase().endsWith('.jpeg') || file.name.toLowerCase().endsWith('.webp');
                    const isPdf = file.type.includes('pdf') || file.name.toLowerCase().endsWith('.pdf');
                    const previewUrl = (isImg || isPdf) ? URL.createObjectURL(file) : null;
                    this.chatAttachments.push({ file, name: file.name, size: file.size, type: file.type, previewUrl });
                }
            });
        },

        removeAttachment(index) {
            const att = this.chatAttachments[index];
            if (att && att.previewUrl) URL.revokeObjectURL(att.previewUrl);
            this.chatAttachments.splice(index, 1);
        },

        isImageType(fileType, fileName = '') {
            return (fileType && (fileType.startsWith('image/') || fileType.includes('png') || fileType.includes('jpeg') || fileType.includes('jpg') || fileType.includes('webp') || fileType.includes('gif'))) ||
                   (fileName && (fileName.toLowerCase().endsWith('.png') || fileName.toLowerCase().endsWith('.jpg') || fileName.toLowerCase().endsWith('.jpeg') || fileName.toLowerCase().endsWith('.webp') || fileName.toLowerCase().endsWith('.gif')));
        },

        isPdfType(fileType, fileName = '') {
            return (fileType && fileType.includes('pdf')) || (fileName && fileName.toLowerCase().endsWith('.pdf'));
        },

        isDocType(fileType, fileName = '') {
            return (fileType && (fileType.includes('word') || fileType.includes('officedocument') || fileType.includes('msword'))) || 
                   (fileName && (fileName.toLowerCase().endsWith('.doc') || fileName.toLowerCase().endsWith('.docx') || fileName.toLowerCase().endsWith('.xlsx') || fileName.toLowerCase().endsWith('.xls')));
        },

        getImageAttachments(msg) {
            if (msg.attachments && msg.attachments.length > 0) {
                return msg.attachments
                    .map((a, idx) => ({ ...a, index: a.index !== undefined ? a.index : idx }))
                    .filter(a => this.isImageType(a.file_type, a.file_name));
            }
            if (msg.file_path && this.isImageType(msg.file_type, msg.file_name)) {
                return [{
                    index: 0,
                    file_name: msg.file_name,
                    file_type: msg.file_type,
                    file_size: msg.file_size,
                    file_url: msg.file_url,
                    download_url: msg.download_url
                }];
            }
            return [];
        },

        getDocAttachments(msg) {
            if (msg.attachments && msg.attachments.length > 0) {
                return msg.attachments
                    .map((a, idx) => ({ ...a, index: a.index !== undefined ? a.index : idx }))
                    .filter(a => !this.isImageType(a.file_type, a.file_name));
            }
            if (msg.file_path && !this.isImageType(msg.file_type, msg.file_name)) {
                return [{
                    index: 0,
                    file_name: msg.file_name,
                    file_type: msg.file_type,
                    file_size: msg.file_size,
                    file_url: msg.file_url,
                    download_url: msg.download_url
                }];
            }
            return [];
        },

        getImageGridClass(count) {
            if (count === 1) return 'grid-cols-1 max-w-[280px]';
            if (count === 2) return 'grid-cols-2 max-w-[340px]';
            return 'grid-cols-3 max-w-[420px]'; // Maksimum 3 gambar lebarnya
        },

        previewDoc(url, name) {
            if (!url) return;
            if (this.isPdfType('', name)) {
                this.pdfPreviewName = name || 'PDF Preview';
                this.pdfPreviewUrl = url;
            } else if (this.isImageType('', name)) {
                this.pdfPreviewName = name || 'Image Preview';
                this.pdfPreviewUrl = url;
            }
        },

        initViewer() {
            if (typeof Viewer === 'undefined') return;
            const container = this.$refs.chatContainer || document.getElementById('chat-messages-container');
            if (!container) return;

            this.$nextTick(() => {
                if (this.viewerInstance) {
                    this.viewerInstance.destroy();
                    this.viewerInstance = null;
                }
                this.viewerInstance = new Viewer(container, {
                    navbar: false,
                    toolbar: true,
                    title: true,
                    url: 'data-original',
                    filter(img) {
                        return img.classList.contains('chat-image-thumb');
                    }
                });
            });
        },

        getApiHeaders() {
            const headers = {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            };
            if (typeof window.Echo !== 'undefined' && window.Echo && typeof window.Echo.socketId === 'function') {
                const sid = window.Echo.socketId();
                if (sid) headers['X-Socket-ID'] = sid;
            }
            return headers;
        },

        // Send Message
        async sendMessage() {
            if (this.sendingMessage) return;
            const messageText = (this.chatInputMessage || '').trim();
            const hasFiles = this.chatAttachments.length > 0;

            if (!messageText && !hasFiles) return;

            this.sendingMessage = true;

            // Snapshot and clear input immediately to prevent double submissions
            const filesToSend = [...this.chatAttachments];
            const replySnapshot = this.replyingTo;
            this.chatInputMessage = '';
            this.chatAttachments = [];
            this.replyingTo = null;

            if (this.$refs.chatInput) {
                this.$refs.chatInput.style.height = '20px';
            }

            const formData = new FormData();
            if (messageText) formData.append('message', messageText);
            if (replySnapshot) formData.append('reply_to_id', replySnapshot.id);

            if (filesToSend.length === 1) {
                formData.append('file', filesToSend[0].file);
            } else if (filesToSend.length > 1) {
                filesToSend.forEach(att => {
                    formData.append('files[]', att.file);
                });
            }

            try {
                const res = await fetch(`{{ url('management/chats') }}/${this.chatType}/${this.chatId}`, {
                    method: 'POST',
                    headers: this.getApiHeaders(),
                    body: formData
                }).then(r => r.json());

                if (res.success && res.message_data) {
                    if (!this.chatMessages.some(m => Number(m.id) === Number(res.message_data.id))) {
                        this.chatMessages.push(res.message_data);
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.initViewer();
                        });
                    }
                } else {
                    if (typeof showToast === 'function') showToast(res.message || 'Failed to send message.', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof showToast === 'function') showToast('Failed to send message.', 'error');
            } finally {
                this.sendingMessage = false;
                this.$nextTick(() => {
                    if (this.$refs.chatInput) this.$refs.chatInput.focus();
                });
            }
        },

        deleteMessage(msgId) {
            const doDelete = () => {
                fetch(`{{ url('management/chats') }}/${msgId}`, {
                    method: 'DELETE',
                    headers: this.getApiHeaders()
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        this.chatMessages = this.chatMessages.filter(m => Number(m.id) !== Number(msgId));
                        if (typeof showToast === 'function') {
                            showToast('Message deleted successfully.', 'success');
                        }
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(res.message || 'Failed to delete message.', 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof showToast === 'function') {
                        showToast('Failed to delete message.', 'error');
                    }
                });
            };

            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: 'Delete Message?',
                    text: 'Are you sure you want to delete this message? This action cannot be undone.',
                    icon: 'warning',
                    confirmButtonText: 'Yes, Delete',
                    confirmButtonColor: '#e11d48',
                    cancelButtonText: 'Cancel',
                    onConfirm: doDelete
                });
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Message?',
                    text: 'Are you sure you want to delete this message?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Are you sure you want to delete this message?')) doDelete();
            }
        },

        deleteAttachment(msgId, attachmentIndex, filePath, fHash) {
            const doDelete = () => {
                let url = `{{ url('management/chats') }}/${msgId}/attachment/${attachmentIndex}`;
                const params = new URLSearchParams();
                if (filePath) params.append('file_path', filePath);
                if (fHash) params.append('f', fHash);
                const queryStr = params.toString();
                if (queryStr) {
                    url += (url.includes('?') ? '&' : '?') + queryStr;
                }

                fetch(url, {
                    method: 'DELETE',
                    headers: this.getApiHeaders()
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        if (res.deleted_message) {
                            this.chatMessages = this.chatMessages.filter(m => Number(m.id) !== Number(msgId));
                        } else if (res.message_data) {
                            const idx = this.chatMessages.findIndex(m => Number(m.id) === Number(msgId));
                            if (idx !== -1) {
                                this.chatMessages.splice(idx, 1, res.message_data);
                                this.chatMessages = [...this.chatMessages];
                            }
                        }
                        if (typeof showToast === 'function') {
                            showToast('Attachment deleted successfully.', 'success');
                        }
                        this.$nextTick(() => {
                            this.initViewer();
                        });
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(res.message || 'Failed to delete attachment.', 'error');
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (typeof showToast === 'function') {
                        showToast('Error deleting attachment.', 'error');
                    }
                });
            };

            if (typeof window.confirmDialog === 'function') {
                window.confirmDialog({
                    title: 'Delete Attachment?',
                    text: 'Are you sure you want to remove this attached file?',
                    icon: 'warning',
                    confirmButtonText: 'Yes, Delete',
                    confirmButtonColor: '#e11d48',
                    cancelButtonText: 'Cancel',
                    onConfirm: doDelete
                });
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Attachment?',
                    text: 'Are you sure you want to remove this attached file?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Are you sure you want to delete this attached file?')) doDelete();
            }
        }
    };
};
</script>
@endpush
