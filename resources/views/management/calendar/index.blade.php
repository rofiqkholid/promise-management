@extends('layouts.app')

@section('title', 'Project & Holiday Calendar · Promise Management')

@section('content')
<style>
    /* Notion/Trello Style Calendar Overrides */
    .fc {
        font-family: inherit;
        --fc-border-color: #f1f5f9;
        --fc-today-bg-color: #f8fafc;
        --fc-button-bg-color: #ffffff;
        --fc-button-border-color: #e2e8f0;
        --fc-button-text-color: #334155;
        --fc-button-hover-bg-color: #f8fafc;
        --fc-button-hover-border-color: #cbd5e1;
        --fc-button-active-bg-color: #f1f5f9;
        --fc-button-active-border-color: #cbd5e1;
    }
    .dark .fc {
        --fc-border-color: #334155;
        --fc-today-bg-color: #1e293b;
        --fc-button-bg-color: #1e293b;
        --fc-button-border-color: #475569;
        --fc-button-text-color: #cbd5e1;
        --fc-button-hover-bg-color: #0f172a;
        --fc-button-hover-border-color: #64748b;
        --fc-button-active-bg-color: #0f172a;
    }
    
    /* Toolbar customizations */
    .fc-header-toolbar {
        margin-bottom: 1rem !important;
        padding: 4px;
    }
    .fc-toolbar-title {
        font-size: 1.1rem !important;
        font-weight: 800 !important;
        color: #1e293b;
        letter-spacing: -0.02em;
    }
    .dark .fc-toolbar-title {
        color: #f8fafc;
    }

    /* Weekday Headers */
    .fc-col-header-cell {
        padding: 8px 0 !important;
        font-size: 10px !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700 !important;
        color: #64748b;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0 !important;
    }
    .dark .fc-col-header-cell {
        color: #94a3b8;
        background-color: #0f172a;
        border-bottom-color: #334155 !important;
    }

    /* Days cells */
    .fc-daygrid-day {
        transition: background-color 0.1s ease;
    }
    .fc-daygrid-day:hover {
        background-color: #f8fafc;
    }
    .dark .fc-daygrid-day:hover {
        background-color: #1e293b/40;
    }
    
    .fc-daygrid-day-number {
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #475569;
        padding: 6px !important;
    }
    .dark .fc-daygrid-day-number {
        color: #94a3b8;
    }
    
    /* Notion style buttons */
    .fc-button {
        padding: 4px 8px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        border-radius: 4px !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        text-transform: capitalize !important;
        outline: none !important;
    }
    .fc-button-group {
        border-radius: 4px !important;
    }

    /* Event block adjustments - Smaller & sleeker text */
    .fc-event {
        border: none !important;
        margin: 1px 3px !important;
        cursor: pointer;
        padding: 1.5px 4px !important;
        font-size: 9.5px !important;
        line-height: 1.25 !important;
        transition: opacity 0.15s ease, transform 0.15s ease;
    }
    .fc-event:hover {
        opacity: 0.85;
        transform: translateY(-0.5px);
    }
    
    /* Scrollbars */
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 3px;
    }
    .dark .custom-scroll::-webkit-scrollbar-thumb {
        background: #334155;
    }
</style>

<div class="flex-1 overflow-y-auto p-6 pt-17.5 space-y-6 bg-slate-50/50 dark:bg-slate-900/60" x-data="calendarDashboard">
    
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800 p-6 border border-slate-200/60 dark:border-slate-700/50 rounded-xs shadow-2xs">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-blue-600"></i>
                <span>Calendar &amp; Holiday Master</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Schedule company events and define holidays to calculate effective working days for SPK priorities.</p>
        </div>
        <button @click="openCreateModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold transition-all text-xs uppercase tracking-wider rounded-xs shadow-xs cursor-pointer">
            <i class="fa-solid fa-plus text-[10px]"></i>
            Add Event / Holiday
        </button>
    </div>

    <!-- Main Workspace -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        
        <!-- Interactive Calendar (Left 3 Columns) -->
        <div class="xl:col-span-3 bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/50 p-6 rounded-xs shadow-2xs space-y-4">
            
            <!-- Quick Navigation Row -->
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Quick Year Jump:</span>
                <select id="year-selector" @change="goToYear($event.target.value)"
                        class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs px-2.5 py-1 rounded-sm focus:outline-none cursor-pointer text-slate-700 dark:text-slate-200">
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026" selected>2026</option>
                    <option value="2027">2027</option>
                    <option value="2028">2028</option>
                    <option value="2029">2029</option>
                    <option value="2030">2030</option>
                </select>
            </div>

            <div id="calendar"></div>
        </div>

        <!-- Meta Sidebar (Right 1 Column) -->
        <div class="xl:col-span-1 space-y-6">
            
            <!-- Legend / Information -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/50 p-5 rounded-xs shadow-2xs space-y-4">
                <h3 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50 pb-2.5 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Calendar Legend
                </h3>
                <div class="space-y-3.5 text-xs">
                    <div class="flex items-start gap-3">
                        <span class="w-3.5 h-3.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-sm flex-shrink-0 mt-0.5"></span>
                        <div class="flex-1">
                            <span class="font-bold text-slate-700 dark:text-slate-200">Company &amp; National Holiday</span>
                            <span class="block text-[10px] text-slate-400 mt-0.5">Excludes dates from SPK Due Date calculations</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-3.5 h-3.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-sm flex-shrink-0 mt-0.5"></span>
                        <div class="flex-1">
                            <span class="font-bold text-slate-700 dark:text-slate-200">Company Event / Schedule</span>
                            <span class="block text-[10px] text-slate-400 mt-0.5">Custom company plans (Normal working days)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Holiday List -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/50 p-5 rounded-xs shadow-2xs flex flex-col max-h-[520px] space-y-4">
                <h3 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50 pb-2.5 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Upcoming Holidays
                </h3>
                <div class="overflow-y-auto flex-grow divide-y divide-slate-100 dark:divide-slate-700/50 pr-1 custom-scroll">
                    <template x-for="holiday in upcomingHolidays" :key="holiday.id">
                        <div class="py-2.5 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="block font-bold text-xs text-slate-800 dark:text-slate-200 truncate" x-text="holiday.title"></span>
                                <span class="block text-[10px] text-slate-400 mt-0.5" x-text="holiday.extendedProps.description || 'Official Holiday'"></span>
                            </div>
                            <span class="px-2 py-0.5 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200/40 dark:border-rose-900/30 text-[9px] font-bold uppercase rounded-sm flex-shrink-0" x-text="formatDateStr(holiday.start)"></span>
                        </div>
                    </template>
                    <template x-if="upcomingHolidays.length === 0">
                        <p class="py-8 text-center text-xs text-slate-400 italic">No upcoming holidays scheduled.</p>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Notion-style properties Modal -->
    <div x-show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs p-4"
         style="display: none;" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 w-full max-w-md shadow-xl rounded-xs overflow-hidden relative">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/10">
                <h3 class="text-xs font-black text-slate-700 dark:text-white uppercase tracking-wider" x-text="isEditMode ? 'Edit Event / Holiday' : 'Create New Event / Holiday'"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitEventForm" class="p-5 space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Title / Event Name <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="form.title" required placeholder="e.g. Cuti Bersama"
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Start Date <span class="text-rose-500">*</span></label>
                        <input type="date" x-model="form.start_date" required
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">End Date</label>
                        <input type="date" x-model="form.end_date" :min="form.start_date"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                </div>

                <div class="p-3.5 bg-slate-50 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700/50 rounded-sm flex items-center justify-between">
                    <div class="space-y-0.5">
                        <span class="block text-xs font-bold text-slate-700 dark:text-slate-200">Mark as Holiday</span>
                        <span class="block text-[10px] text-slate-400">If checked, this event counts as a corporate/national holiday.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_holiday" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 dark:bg-slate-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="space-y-1.5" x-show="!form.is_holiday" x-cloak>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Event Color Tag</label>
                    <div class="flex gap-2.5">
                        <template x-for="colorOption in colorOptions" :key="colorOption.value">
                            <button type="button" @click="form.color = colorOption.value"
                                    :class="form.color === colorOption.value ? 'ring-2 ring-slate-800 dark:ring-white scale-110' : ''"
                                    class="w-6 h-6 rounded-full cursor-pointer transition-all border border-transparent shadow-xs"
                                    :style="'background-color: ' + colorOption.value"
                                    :title="colorOption.name"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Description</label>
                    <textarea x-model="form.description" rows="3" placeholder="Additional details..."
                              class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 resize-none rounded-sm"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" x-show="isEditMode" @click="deleteEvent()"
                            class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xs transition-colors cursor-pointer mr-auto">
                        Delete Event
                    </button>
                    <button type="button" @click="showModal = false"
                            class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xs transition-colors cursor-pointer">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('styles')
<!-- FullCalendar CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<!-- FullCalendar JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('calendarDashboard', () => ({
        showModal: false,
        isEditMode: false,
        form: { id: null, title: '', start_date: '', end_date: '', is_holiday: false, description: '', color: '#3b82f6' },
        
        colorOptions: [
            { name: 'Blue', value: '#3b82f6' },
            { name: 'Green', value: '#10b981' },
            { name: 'Purple', value: '#8b5cf6' },
            { name: 'Amber', value: '#f59e0b' },
            { name: 'Pink', value: '#ec4899' }
        ],

        upcomingHolidays: [],
        calendar: null,

        init() {
            this.initCalendar();
            this.loadUpcomingHolidays();
        },

        initCalendar() {
            const calendarEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: false,
                selectable: true,
                events: '{{ route("management.calendar.events") }}',
                
                select: (info) => {
                    this.openCreateModal(info.startStr, info.endStr ? this.adjustEndDate(info.endStr) : info.startStr);
                },

                eventClick: (info) => {
                    this.openEditModal(info.event);
                },

                // Sync the year-selector dropdown whenever dates set changes (prev/next clicks)
                datesSet: (info) => {
                    let currentYear = info.view.currentStart.getFullYear();
                    let el = document.getElementById('year-selector');
                    if (el) el.value = currentYear;
                },

                // Custom Pastel style mapper to match Notion aesthetics
                eventDidMount: (info) => {
                    let isHoliday = info.event.extendedProps.is_holiday;
                    let customColor = info.event.extendedProps.color;
                    
                    // Default light colors
                    let bgColor = isHoliday ? '#ffe2dd' : '#e3f2fd';
                    let textColor = isHoliday ? '#bf1a1a' : '#0d47a1';
                    
                    // Default dark colors
                    let darkBgColor = isHoliday ? '#5f2621' : '#1e293b';
                    let darkTextColor = isHoliday ? '#ffaba4' : '#64b5f6';

                    if (customColor && !isHoliday) {
                        const colorMap = {
                            '#3b82f6': { bg: '#e3f2fd', text: '#0d47a1', darkBg: '#1e293b', darkText: '#64b5f6' }, // Blue
                            '#10b981': { bg: '#e8f5e9', text: '#1b5e20', darkBg: '#064e3b', darkText: '#81c784' }, // Green
                            '#8b5cf6': { bg: '#f3e5f5', text: '#4a148c', darkBg: '#3b0764', darkText: '#d8b4fe' }, // Purple
                            '#f59e0b': { bg: '#fff3e0', text: '#e65100', darkBg: '#451a03', darkText: '#fde047' }, // Amber
                            '#ec4899': { bg: '#fce4ec', text: '#880e4f', darkBg: '#500724', darkText: '#fda4af' }  // Pink
                        };
                        let match = colorMap[customColor];
                        if (match) {
                            bgColor = match.bg;
                            textColor = match.text;
                            darkBgColor = match.darkBg;
                            darkTextColor = match.darkText;
                        }
                    }

                    // Apply styles
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    info.el.style.backgroundColor = isDarkMode ? darkBgColor : bgColor;
                    info.el.style.color = isDarkMode ? darkTextColor : textColor;
                    info.el.style.borderRadius = '3px';
                    info.el.style.border = 'none';

                    let titleEl = info.el.querySelector('.fc-event-title');
                    if (titleEl) {
                        titleEl.style.color = isDarkMode ? darkTextColor : textColor;
                        titleEl.style.fontWeight = '600';
                        titleEl.style.fontSize = '9.5px';
                    }
                }
            });
            this.calendar.render();
        },

        goToYear(year) {
            if (this.calendar) {
                let currentDate = this.calendar.getDate();
                let month = String(currentDate.getMonth() + 1).padStart(2, '0');
                this.calendar.gotoDate(`${year}-${month}-01`);
            }
        },

        adjustEndDate(endDateStr) {
            let date = new Date(endDateStr);
            date.setDate(date.getDate() - 1);
            return date.toISOString().substring(0, 10);
        },

        loadUpcomingHolidays() {
            fetch('{{ route("management.calendar.events") }}')
                .then(r => r.json())
                .then(events => {
                    let todayStr = new Date().toISOString().substring(0, 10);
                    this.upcomingHolidays = events
                        .filter(e => e.extendedProps.is_holiday && e.start >= todayStr)
                        .sort((a, b) => a.start.localeCompare(b.start))
                        .slice(0, 15); // Limit to top 15
                });
        },

        openCreateModal(startDate = '', endDate = '') {
            this.isEditMode = false;
            let defaultDate = startDate || new Date().toISOString().substring(0, 10);
            this.form = {
                id: null,
                title: '',
                start_date: defaultDate,
                end_date: endDate || defaultDate,
                is_holiday: false,
                description: '',
                color: '#3b82f6'
            };
            this.showModal = true;
        },

        openEditModal(calendarEvent) {
            if (calendarEvent.extendedProps.is_db === false) {
                // If it's a dynamic API national holiday, block editing
                alert('National holidays from the API cannot be edited or deleted manually.');
                return;
            }

            this.isEditMode = true;
            this.form = {
                id: calendarEvent.id.replace('db-', ''),
                title: calendarEvent.title,
                start_date: calendarEvent.startStr,
                end_date: calendarEvent.extendedProps.raw_end,
                is_holiday: calendarEvent.extendedProps.is_holiday,
                description: calendarEvent.extendedProps.description || '',
                color: calendarEvent.extendedProps.color || '#3b82f6'
            };
            this.showModal = true;
        },

        submitEventForm() {
            let url = this.isEditMode 
                ? `/management/calendar/events/${this.form.id}`
                : '{{ route("management.calendar.store") }}';
            let method = this.isEditMode ? 'PATCH' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showModal = false;
                    this.calendar.refetchEvents();
                    this.loadUpcomingHolidays();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        },

        deleteEvent() {
            if (!confirm('Are you sure you want to delete this event?')) return;

            fetch(`/management/calendar/events/${this.form.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.showModal = false;
                    this.calendar.refetchEvents();
                    this.loadUpcomingHolidays();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error(err));
        },

        formatDateStr(dateStr) {
            if (!dateStr) return '';
            let date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            let day = String(date.getDate()).padStart(2, '0');
            let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let month = months[date.getMonth()];
            return `${day} ${month}`;
        }
    }));
});
</script>
@endpush
