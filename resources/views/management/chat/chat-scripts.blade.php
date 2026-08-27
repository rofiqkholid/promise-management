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
    .chat-markdown pre code { background: transparent !important; padding: 0 !important; font-size: inherit !important; }
    .chat-markdown blockquote {
        border-left: 3px solid rgba(99, 102, 241, 0.6) !important;
        padding-left: 0.5rem !important;
        margin: 0.35rem 0 !important;
        font-style: italic !important;
        opacity: 0.9 !important;
    }
    .dark .chat-markdown blockquote {
        border-left-color: rgba(129, 140, 248, 0.6) !important;
    }

    .viewer-container {
        z-index: 99999 !important;
    }

    .chat-bubble-highlight .chat-bubble-out {
        animation: subtleLightHighlightOut 1.2s ease-out;
    }
    .chat-bubble-highlight .chat-bubble-in {
        animation: subtleLightHighlightIn 1.2s ease-out;
    }
    @keyframes subtleLightHighlightOut {
        0%, 35% { background-color: #38bdf8 !important; }
        100% { background-color: #007aff !important; }
    }
    @keyframes subtleLightHighlightIn {
        0%, 35% { background-color: #e0e7ff !important; }
        100% { background-color: #ffffff !important; }
    }
    .dark .chat-bubble-highlight .chat-bubble-in {
        animation: subtleLightHighlightInDark 1.2s ease-out;
    }
    @keyframes subtleLightHighlightInDark {
        0%, 35% { background-color: #334155 !important; }
        100% { background-color: #1e293b !important; }
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
        fallbackPollTimer: null,
        isReverbConnected: false,
        viewerInstance: null,
        pdfPreviewUrl: null,
        pdfPreviewName: '',
        showOnlyMediaAndLinks: false,
        selectedDateFilter: 'all', // 'all', 'today', 'yesterday', '7days', 'custom'
        customDateFilter: '',
        showScrollToBottomBtn: false,
        showFormatToolbar: false,
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
            this.stopFallbackPolling();
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

            // Check if Echo is available and functional
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                try {
                    this.echoChannel = window.Echo.private(`chat.${this.chatType}.${this.chatId}`)
                        .listen('ChatMessageSent', (e) => {
                            if (!this.chatMessages.some(m => Number(m.id) === Number(e.id))) {
                                this.chatMessages.push(e);
                                this.$nextTick(() => {
                                    this.scrollToBottom();
                                    this.initViewer();
                                });
                            }
                        })
                        .listen('ChatMessageDeleted', (e) => {
                            this.chatMessages = this.chatMessages.filter(msg => Number(msg.id) !== Number(e.chatId));
                        });

                    // Listen to Pusher/Reverb connection states
                    if (window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection) {
                        const conn = window.Echo.connector.pusher.connection;
                        conn.bind('connected', () => {
                            this.isReverbConnected = true;
                            this.stopFallbackPolling();
                        });
                        conn.bind('unavailable', () => {
                            this.isReverbConnected = false;
                            this.startFallbackPolling();
                        });
                        conn.bind('failed', () => {
                            this.isReverbConnected = false;
                            this.startFallbackPolling();
                        });
                        conn.bind('disconnected', () => {
                            this.isReverbConnected = false;
                            this.startFallbackPolling();
                        });

                        if (conn.state === 'connected') {
                            this.isReverbConnected = true;
                            this.stopFallbackPolling();
                        } else {
                            // Fallback polling active while connecting or if offline
                            this.startFallbackPolling();
                        }
                    } else {
                        this.startFallbackPolling();
                    }
                } catch (err) {
                    console.warn('Echo subscription failed, running in fallback polling mode:', err);
                    this.startFallbackPolling();
                }
            } else {
                // Echo not configured or unavailable -> fallback polling mode
                this.startFallbackPolling();
            }
        },

        startFallbackPolling() {
            if (this.fallbackPollTimer) return;
            // Poll for new messages every 5 seconds without user disruption
            this.fallbackPollTimer = setInterval(() => {
                if (this.chatId && !this.loadingChats && !this.sendingMessage) {
                    this.syncLatestMessages();
                }
            }, 5000);
        },

        stopFallbackPolling() {
            if (this.fallbackPollTimer) {
                clearInterval(this.fallbackPollTimer);
                this.fallbackPollTimer = null;
            }
        },

        syncLatestMessages() {
            if (!this.chatId) return;
            const url = `{{ url('management/chats') }}/${this.chatType}/${this.chatId}`;
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && Array.isArray(data.messages)) {
                    const currentIds = this.chatMessages.map(m => Number(m.id)).join(',');
                    const incomingIds = data.messages.map(m => Number(m.id)).join(',');
                    if (currentIds !== incomingIds) {
                        const container = this.$refs.chatContainer || document.getElementById('chat-messages-container');
                        const isNearBottom = container ? (container.scrollHeight - container.scrollTop - container.clientHeight < 150) : true;
                        this.chatMessages = data.messages;
                        this.$nextTick(() => {
                            if (isNearBottom) this.scrollToBottom();
                            this.initViewer();
                        });
                    }
                }
            })
            .catch(() => {});
        },

        leaveRoom() {
            this.stopFallbackPolling();
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

        loadChats(beforeId = null) {
            if (!this.chatId) return;
            this.loadingChats = true;

            let url = `{{ url('management/chats') }}/${this.chatType}/${this.chatId}`;
            if (beforeId) {
                url += `?before_id=${beforeId}`;
            }

            const container = this.$refs.chatContainer || document.getElementById('chat-messages-container');
            const prevScrollHeight = container ? container.scrollHeight : 0;

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.messages) {
                    if (data.messages.length < 20) {
                        this.hasMoreChats = false;
                    }
                    if (beforeId) {
                        this.chatMessages = [...data.messages, ...this.chatMessages];
                        this.$nextTick(() => {
                            if (container) {
                                container.scrollTop = container.scrollHeight - prevScrollHeight;
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
                    if (data.messages.length > 0) {
                        this.oldestChatId = data.messages[0].id;
                    }
                }
            })
            .finally(() => {
                this.loadingChats = false;
            });
        },

        handleChatScroll(e) {
            const el = e.target;
            if (el.scrollTop === 0 && this.hasMoreChats && !this.loadingChats && this.oldestChatId) {
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
            const targetEl = document.getElementById('chat-bubble-' + targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.classList.remove('chat-bubble-highlight');
                void targetEl.offsetWidth; // trigger reflow
                targetEl.classList.add('chat-bubble-highlight');
                setTimeout(() => {
                    targetEl.classList.remove('chat-bubble-highlight');
                }, 2000);
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
            return 'All Dates';
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

            // 1. Filter by Media / Links
            if (this.showOnlyMediaAndLinks) {
                list = list.filter(msg => {
                    const hasFile = !!msg.file_path;
                    const hasLink = msg.message && (msg.message.includes('http://') || msg.message.includes('https://') || msg.message.includes('www.'));
                    return hasFile || hasLink;
                });
            }

            // 2. Filter by Day / Date
            if (this.selectedDateFilter !== 'all') {
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
                
                const yest = new Date();
                yest.setDate(now.getDate() - 1);
                const yestStr = `${yest.getFullYear()}-${pad(yest.getMonth() + 1)}-${pad(yest.getDate())}`;

                if (this.selectedDateFilter === 'today') {
                    list = list.filter(m => m.created_at && m.created_at.startsWith(todayStr));
                } else if (this.selectedDateFilter === 'yesterday') {
                    list = list.filter(m => m.created_at && m.created_at.startsWith(yestStr));
                } else if (this.selectedDateFilter === '7days') {
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(now.getDate() - 7);
                    sevenDaysAgo.setHours(0, 0, 0, 0);
                    list = list.filter(m => {
                        if (!m.created_at) return false;
                        const p = m.created_at.substring(0, 10).split('-');
                        const msgDate = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
                        return msgDate >= sevenDaysAgo;
                    });
                } else if (this.selectedDateFilter === 'custom' && this.customDateFilter) {
                    list = list.filter(m => m.created_at && m.created_at.startsWith(this.customDateFilter));
                }
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

        // Formatting Toolbar
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
                case 'code':
                    replacement = `\`${selected || 'code'}\``;
                    newCursorPos = selected ? end + 2 : start + 1;
                    break;
                case 'quote':
                    replacement = `\n> ${selected || 'quote'}\n`;
                    newCursorPos = selected ? end + 4 : start + 3;
                    break;
                case 'ul':
                    replacement = `\n- ${selected || 'list item'}\n`;
                    newCursorPos = selected ? end + 4 : start + 3;
                    break;
                case 'ol':
                    replacement = `\n1. ${selected || 'list item'}\n`;
                    newCursorPos = selected ? end + 5 : start + 4;
                    break;
            }

            this.chatInputMessage = before + replacement + after;
            this.$nextTick(() => {
                textarea.focus();
                textarea.setSelectionRange(newCursorPos, newCursorPos);
            });
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
            html = html.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 rounded-xs hover:underline font-semibold text-[11px]"><i class="fa-solid fa-link text-[8.5px]"></i> $1</a>');

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
            // Bullet list
            str = str.replace(/^\s*-\s+(.*)$/gm, '<li>$1</li>');
            str = str.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
            // Numbered list
            str = str.replace(/^\s*\d+\.\s+(.*)$/gm, '<li>$1</li>');
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
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
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
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
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
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
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
