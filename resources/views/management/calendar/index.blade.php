@extends('layouts.app')

@section('title', 'Project & Holiday Calendar · Promise Management')

@section('content')
<style>
    /* Notion/Trello Style Calendar Overrides */
    .fc {
        font-family: inherit;
        --fc-border-color: #cbd5e1; /* Clearer slate-300 borders */
        --fc-today-bg-color: #eff6ff; /* Light blue bg */
        --fc-button-bg-color: #ffffff;
        --fc-button-border-color: #cbd5e1;
        --fc-button-text-color: #334155;
        --fc-button-hover-bg-color: #f8fafc;
        --fc-button-hover-border-color: #cbd5e1;
        --fc-button-active-bg-color: #f1f5f9;
        --fc-button-active-border-color: #cbd5e1;
    }
    .dark .fc {
        --fc-border-color: #475569; /* Clearer slate-600 borders */
        --fc-today-bg-color: rgba(30, 58, 138, 0.25);
        --fc-button-bg-color: #1e293b;
        --fc-button-border-color: #475569;
        --fc-button-text-color: #cbd5e1;
        --fc-button-hover-bg-color: #0f172a;
        --fc-button-hover-border-color: #64748b;
        --fc-button-active-bg-color: #0f172a;
    }

    /* Make today cell highlight extremely clear with a blue circle */
    .fc .fc-day-today {
        background-color: #eff6ff !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        background-color: #2563eb;
        color: #ffffff !important;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        margin: 4px;
        padding: 0 !important;
    }
    .dark .fc .fc-day-today {
        background-color: rgba(30, 58, 138, 0.25) !important;
    }
    .dark .fc .fc-day-today .fc-daygrid-day-number {
        background-color: #3b82f6;
        color: #ffffff !important;
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
        padding: 3px 6px !important;
        font-size: 10.5px !important;
        line-height: 1.25 !important;
        transition: opacity 0.15s ease, transform 0.15s ease;
    }
    .fc-event:hover {
        opacity: 0.85;
        transform: translateY(-0.5px);
    }
    
    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    
    /* FullCalendar Scroller and Scrollbar Overrides */
    .fc-scroller {
        overflow-y: auto !important;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    .fc-scroller::-webkit-scrollbar {
        width: 0px; /* Chrome/Safari */
        background: transparent;
    }
    .fc-scroller-hinh {
        overflow: hidden !important;
    }
    
    /* Microsoft Outlook Style Now Indicator */
    .fc-timegrid-now-indicator-line {
        border-color: #2563eb !important;
        border-width: 2px 0 0 !important;
    }
    .fc-timegrid-now-indicator-arrow {
        border-color: transparent transparent transparent #2563eb !important;
        border-width: 5px 0 5px 6px !important;
    }
    
    /* Outlook Style Slot Heights & Borders */
    .fc-timegrid-slot {
        height: 42px !important;
    }
    .fc .fc-timegrid-slots .fc-timegrid-slot-minor {
        border-top-style: dotted !important;
        border-top-color: #cbd5e1 !important; /* Samar dotted line */
    }
    .dark .fc .fc-timegrid-slots .fc-timegrid-slot-minor {
        border-top-color: #475569 !important;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 3px;
    }
    .dark .custom-scroll::-webkit-scrollbar-thumb {
        background: #334155;
    }
    /* Weekend highlight */
    .fc-day-sat, .fc-day-sun {
        background-color: rgba(239, 68, 68, 0.04) !important;
    }
    .dark .fc-day-sat, .dark .fc-day-sun {
        background-color: rgba(239, 68, 68, 0.10) !important;
    }
    
    /* Marquee animation for long text */
    @keyframes marqueeEffect {
        0%, 20% { transform: translateX(0); }
        50%, 70% { transform: translateX(var(--marquee-distance, 0px)); }
        90%, 100% { transform: translateX(0); }
    }
    .animate-marquee {
        display: inline-block !important;
        white-space: nowrap !important;
        text-overflow: unset !important;
        overflow: visible !important;
        width: max-content !important;
        animation: marqueeEffect 8s ease-in-out infinite;
    }
    .fc-event-main {
        overflow: hidden !important;
    }
    
    /* Make HTML5 color picker inner swatch round */
    input[type="color"]::-webkit-color-swatch-wrapper {
        padding: 0 !important;
    }
    input[type="color"]::-webkit-color-swatch {
        border: none !important;
        border-radius: 50% !important;
    }
    input[type="color"]::-moz-color-swatch {
        border: none !important;
        border-radius: 50% !important;
    }
</style>
<div class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800" x-data="calendarDashboard">
    
    {{-- ===== ACTION BAR ===== --}}
    <div class="flex items-center justify-between px-6 py-3 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex-shrink-0">
        <div>
            <h1 class="text-sm font-bold tracking-tight text-slate-800 dark:text-white flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-blue-600"></i>
                <span>Calendar &amp; Holiday Master</span>
            </h1>
            <p class="text-[10px] text-slate-405 dark:text-slate-400 mt-0.5">Schedule company events and define holidays to calculate effective working days.</p>
        </div>
        <button @click="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 px-3 h-8 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold transition-all text-xs rounded-xs shadow-none cursor-pointer">
            <i class="fa-solid fa-plus text-[10px]"></i>
            Add Event
        </button>
    </div>

    {{-- ===== TWO-COLUMN SPLIT PANEL CONTAINER (FULL PANEL) ===== --}}
    <div class="flex-1 flex min-h-0 overflow-hidden">
        
        {{-- LEFT PANEL: Legend, Mini Calendar, Upcoming Holidays --}}
        <div class="w-[30%] max-w-[360px] min-w-[280px] flex flex-col bg-slate-100/50 dark:bg-slate-900/30 border-r border-slate-300 dark:border-slate-800 overflow-hidden h-full">
            <div class="px-4 py-3 bg-slate-100/50 dark:bg-slate-900/80 border-b border-slate-300 dark:border-slate-800 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <i class="fa-solid fa-filter text-[10px]"></i> Filters &amp; Preview
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scroll">
                <!-- Legend / Filters -->
                <div class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 p-4 rounded-xs shadow-2xs space-y-3">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Calendar Colors</span>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-3.5 h-3.5 bg-[#ef4444] border border-red-650 rounded-sm flex-shrink-0"></span>
                            <span class="font-medium text-slate-700 dark:text-slate-200">Company &amp; National Holiday</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3.5 h-3.5 bg-[#3b82f6] border border-blue-650 rounded-sm flex-shrink-0"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">Company Event / Schedule</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3.5 h-3.5 bg-[#10b981] border border-green-650 rounded-sm flex-shrink-0"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">Effective Working Day Override</span>
                        </div>
                    </div>
                </div>

                <!-- Mini Calendar -->
                <div class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 p-4 rounded-xs shadow-2xs space-y-3">
                    <div class="flex justify-between items-center pb-1 border-b border-slate-100 dark:border-slate-700/50">
                        <span class="text-xs font-bold text-slate-850 dark:text-white" x-text="miniMonthName + ' ' + miniYear"></span>
                        <div class="flex gap-1">
                            <button type="button" @click="prevMiniMonth()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs px-1 cursor-pointer"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button type="button" @click="nextMiniMonth()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs px-1 cursor-pointer"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-y-2 text-center text-[10px]">
                        <span class="font-bold text-slate-400">Su</span>
                        <span class="font-bold text-slate-400">Mo</span>
                        <span class="font-bold text-slate-400">Tu</span>
                        <span class="font-bold text-slate-400">We</span>
                        <span class="font-bold text-slate-400">Th</span>
                        <span class="font-bold text-slate-400">Fr</span>
                        <span class="font-bold text-slate-400">Sa</span>
                        
                        <template x-for="d in miniDays">
                            <button type="button" 
                                    @click="d.isCurrentMonth ? selectMiniDate(d.day) : null"
                                    :class="{
                                        'text-slate-800 dark:text-slate-200 font-semibold hover:bg-slate-100 dark:hover:bg-slate-700/50': d.isCurrentMonth && !d.isToday,
                                        'text-slate-350 dark:text-slate-650': !d.isCurrentMonth,
                                        'bg-blue-600 text-white dark:bg-blue-500 dark:text-white rounded-full font-black': d.isToday
                                    }"
                                    class="h-8 w-8 flex flex-col items-center justify-center mx-auto text-[10px] cursor-pointer rounded-full transition-colors focus:outline-none relative">
                                <span x-text="d.day"></span>
                                <div class="flex gap-0.5 mt-0.5 absolute bottom-1">
                                    <template x-if="d.hasHoliday">
                                        <span class="w-1 h-1 bg-red-500 rounded-full"></span>
                                    </template>
                                    <template x-if="d.hasEvent">
                                        <span class="w-1 h-1 bg-blue-500 rounded-full"></span>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Upcoming Holiday List -->
                <div class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 p-4 rounded-xs shadow-2xs flex flex-col space-y-3">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block pb-1 border-b border-slate-100 dark:border-slate-700/50">Upcoming Holidays</span>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700/50 pr-1 max-h-[220px] overflow-y-auto custom-scroll">
                        <template x-for="holiday in upcomingHolidays" :key="holiday.id">
                            <div class="py-2.5 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="block font-bold text-xs text-slate-800 dark:text-slate-200 truncate" x-text="holiday.title"></span>
                                    <span class="block text-[10px] text-slate-400 mt-0.5" x-text="holiday.extendedProps.description || 'Official Holiday'"></span>
                                </div>
                                <span class="px-2 py-0.5 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-450 border border-rose-200/40 dark:border-rose-900/30 text-[9px] font-bold uppercase rounded-sm flex-shrink-0" x-text="formatDateStr(holiday.start)"></span>
                            </div>
                        </template>
                        <template x-if="upcomingHolidays.length === 0">
                            <p class="py-8 text-center text-xs text-slate-400 italic">No upcoming holidays.</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL: INTERACTIVE CALENDAR --}}
        <div class="flex-1 flex flex-col min-h-0 overflow-y-auto bg-white dark:bg-slate-800 p-6 space-y-4 h-full">
            <!-- Custom Header Row -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-2 border-b border-slate-100 dark:border-slate-700/50">
                <!-- Month Picker (with navigation buttons next to it) -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="navigate('prev')" class="w-8 h-8 flex items-center justify-center border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs text-slate-600 dark:text-slate-300 transition-colors cursor-pointer">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>

                    <input type="month" id="month-picker" @change="goToMonth($event.target.value)"
                            class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-sm font-bold px-3 py-1.5 rounded-xs focus:outline-none cursor-pointer text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:border-blue-500">

                    <button type="button" @click="navigate('next')" class="w-8 h-8 flex items-center justify-center border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs text-slate-600 dark:text-slate-300 transition-colors cursor-pointer">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                    <!-- Spinner loading indicator -->
                    <div x-show="isCalendarLoading" x-cloak class="flex items-center gap-1.5 text-xs text-blue-600 dark:text-blue-400 font-bold animate-pulse ml-1.5">
                        <i class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                        <span>Loading...</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                    <!-- View Switcher Tabs (Day | Week | Month) -->
                    <div class="flex bg-slate-100 dark:bg-slate-900 p-0.5 rounded-sm border border-slate-200 dark:border-slate-700">
                        <button type="button" 
                                @click="changeView('timeGridDay')" 
                                :class="currentView === 'timeGridDay' ? 'bg-white dark:bg-slate-800 shadow-xs text-slate-850 dark:text-white' : 'text-slate-500 dark:text-slate-400'" 
                                class="px-3 py-1 text-xs font-semibold rounded-xs transition-all cursor-pointer">Day</button>
                        <button type="button" 
                                @click="changeView('timeGridWeek')" 
                                :class="currentView === 'timeGridWeek' ? 'bg-white dark:bg-slate-800 shadow-xs text-slate-850 dark:text-white' : 'text-slate-500 dark:text-slate-400'" 
                                class="px-3 py-1 text-xs font-semibold rounded-xs transition-all cursor-pointer">Week</button>
                        <button type="button" 
                                @click="changeView('dayGridMonth')" 
                                :class="currentView === 'dayGridMonth' ? 'bg-white dark:bg-slate-800 shadow-xs text-slate-850 dark:text-white' : 'text-slate-500 dark:text-slate-400'" 
                                class="px-3 py-1 text-xs font-semibold rounded-xs transition-all cursor-pointer">Month</button>
                    </div>
                </div>
            </div>

            <div id="calendar" class="flex-1 min-h-[680px] h-[680px] pt-2"></div>
        </div>
    </div>

    <!-- Notion-style properties Modal -->
    <div x-show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 backdrop-blur-xs p-4"
         style="display: none;" x-cloak>
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700 w-full max-w-md shadow-xl rounded-xs overflow-hidden relative">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/10">
                <h3 class="text-xs font-black text-slate-700 dark:text-white uppercase tracking-wider" 
                    x-text="isApiHoliday ? 'Google API Holiday Detail' : (isEditMode ? 'Edit Event / Holiday' : 'Create New Event / Holiday')"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submitEventForm" class="p-5 space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Title / Event Name <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="form.title" :disabled="isApiHoliday" required placeholder="e.g. Cuti Bersama"
                           class="w-full bg-slate-50 disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-550 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Start Date <span class="text-rose-500">*</span></label>
                        <input type="date" x-model="form.start_date" :disabled="isApiHoliday" required
                               class="w-full bg-slate-50 disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-550 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">End Date</label>
                        <input type="date" x-model="form.end_date" :min="form.start_date" :disabled="isApiHoliday"
                               class="w-full bg-slate-50 disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-555 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                </div>

                <!-- Event Time Range Section (Only show if not holiday and not API holiday) -->
                <div class="grid grid-cols-2 gap-3" x-show="!form.is_holiday && !isApiHoliday" x-cloak>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Start Time</label>
                        <input type="time" x-model="form.start_time"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">End Time</label>
                        <input type="time" x-model="form.end_time" :min="form.start_time"
                               class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-sm">
                    </div>
                </div>

                <!-- Company Policy Selector for API Holidays -->
                <div x-show="isApiHoliday" class="p-3.5 bg-slate-50 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700/50 rounded-sm space-y-2">
                    <span class="block text-xs font-bold text-slate-700 dark:text-slate-200">Company Policy</span>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-850 dark:text-slate-200 cursor-pointer">
                            <input type="radio" x-model="form.is_holiday" :value="true" class="text-blue-600 focus:ring-blue-500">
                            <span>Holiday (Default)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-850 dark:text-slate-200 cursor-pointer">
                            <input type="radio" x-model="form.is_holiday" :value="false" class="text-blue-600 focus:ring-blue-500">
                            <span>Effective Working Day</span>
                        </label>
                    </div>
                </div>

                <!-- Normal Holiday Toggle (Only show if not API Holiday) -->
                <div x-show="!isApiHoliday" class="p-3.5 bg-slate-50 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-700/50 rounded-sm flex items-center justify-between">
                    <div class="space-y-0.5">
                        <span class="block text-xs font-bold text-slate-700 dark:text-slate-200">Mark as Holiday</span>
                        <span class="block text-[10px] text-slate-400">If checked, this event counts as a corporate/national holiday.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_holiday" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 dark:bg-slate-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Event Color Tag (Show if not holiday and NOT API Holiday so they can override colors only for manual events) -->
                <div class="space-y-1.5" x-show="!form.is_holiday && !isApiHoliday" x-cloak>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Event Color Tag</label>
                    <div class="flex items-center gap-2.5">
                        <template x-for="colorOption in colorOptions" :key="colorOption.value">
                            <button type="button" @click="form.color = colorOption.value"
                                    :class="form.color === colorOption.value ? 'ring-2 ring-slate-800 dark:ring-white scale-110' : ''"
                                    class="w-6 h-6 rounded-full cursor-pointer transition-all border border-transparent shadow-xs"
                                    :style="'background-color: ' + colorOption.value"
                                    :title="colorOption.name"></button>
                        </template>
                        <div class="h-4 w-px bg-slate-300 dark:bg-slate-600 mx-1"></div>
                        <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-0.5 rounded-full shadow-2xs">
                            <i class="fa-solid fa-palette text-[10px] text-slate-400" title="Custom Color Picker"></i>
                            <input type="color" x-model="form.color" class="w-5 h-5 rounded-full cursor-pointer border-none bg-transparent p-0 overflow-hidden" title="Custom Color Picker">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Description</label>
                    <textarea x-model="form.description" :disabled="isApiHoliday" rows="3" placeholder="Additional details..."
                              class="w-full bg-slate-50 disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-550 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 resize-none rounded-sm"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" x-show="isEditMode" @click="deleteEvent()"
                            class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xs transition-colors cursor-pointer mr-auto"
                            x-text="isApiHoliday ? 'Reset Kebijakan' : 'Delete Event'">
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



@push('scripts')
<!-- FullCalendar JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('calendarDashboard', () => ({
        showModal: false,
        isEditMode: false,
        isApiHoliday: false,
        isCalendarLoading: false,
        form: { id: null, title: '', start_date: '', start_time: '', end_date: '', end_time: '', api_holiday_date: null, is_holiday: false, description: '', color: '#3b82f6' },
        
        colorOptions: [
            { name: 'Blue', value: '#3b82f6' },
            { name: 'Green', value: '#10b981' },
            { name: 'Purple', value: '#8b5cf6' },
            { name: 'Amber', value: '#f59e0b' },
            { name: 'Pink', value: '#ec4899' }
        ],

        upcomingHolidays: [],
        allEvents: [],
        calendar: null,

        // Custom view & Mini-calendar states
        currentView: 'dayGridMonth',
        miniYear: new Date().getFullYear(),
        miniMonth: new Date().getMonth(),
        miniDays: [],
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

        get miniMonthName() {
            return this.monthNames[this.miniMonth];
        },

        init() {
            this.generateMiniDays();
            this.initCalendar();
            this.loadUpcomingHolidays();
        },

        changeView(viewName) {
            this.currentView = viewName;
            if (this.calendar) {
                this.calendar.changeView(viewName);
            }
        },

        navigate(direction) {
            if (this.calendar) {
                if (direction === 'prev') this.calendar.prev();
                if (direction === 'next') this.calendar.next();
            }
        },

        generateMiniDays() {
            const firstDayIndex = new Date(this.miniYear, this.miniMonth, 1).getDay(); // 0 = Sunday
            const totalDays = new Date(this.miniYear, this.miniMonth + 1, 0).getDate();
            const prevTotalDays = new Date(this.miniYear, this.miniMonth, 0).getDate();
            
            let days = [];
            // Fill previous month overlap days
            for (let i = firstDayIndex - 1; i >= 0; i--) {
                days.push({ day: prevTotalDays - i, isCurrentMonth: false });
            }
            // Fill current month days
            for (let i = 1; i <= totalDays; i++) {
                const isToday = i === new Date().getDate() && this.miniMonth === new Date().getMonth() && this.miniYear === new Date().getFullYear();
                
                // Format date as YYYY-MM-DD
                const yyyy = this.miniYear;
                const mm = String(this.miniMonth + 1).padStart(2, '0');
                const dd = String(i).padStart(2, '0');
                const dateStr = `${yyyy}-${mm}-${dd}`;
                
                // Check if this date has events
                const dayEvents = this.allEvents.filter(e => e.start.substring(0, 10) === dateStr);
                const hasHoliday = dayEvents.some(e => e.extendedProps.is_holiday);
                const hasEvent = dayEvents.some(e => !e.extendedProps.is_holiday);

                days.push({ 
                    day: i, 
                    isCurrentMonth: true, 
                    isToday,
                    hasHoliday,
                    hasEvent
                });
            }
            // Fill next month overlap days to complete grid
            const remainingCells = 42 - days.length;
            for (let i = 1; i <= remainingCells; i++) {
                days.push({ day: i, isCurrentMonth: false });
            }
            this.miniDays = days;
        },

        prevMiniMonth() {
            if (this.miniMonth === 0) {
                this.miniMonth = 11;
                this.miniYear--;
            } else {
                this.miniMonth--;
            }
            this.generateMiniDays();
            if (this.calendar) {
                this.calendar.gotoDate(new Date(this.miniYear, this.miniMonth, 1));
            }
        },

        nextMiniMonth() {
            if (this.miniMonth === 11) {
                this.miniMonth = 0;
                this.miniYear++;
            } else {
                this.miniMonth++;
            }
            this.generateMiniDays();
            if (this.calendar) {
                this.calendar.gotoDate(new Date(this.miniYear, this.miniMonth, 1));
            }
        },

        selectMiniDate(day) {
            if (this.calendar) {
                this.calendar.gotoDate(new Date(this.miniYear, this.miniMonth, day));
            }
        },

        initCalendar() {
            const calendarEl = document.getElementById('calendar');
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: false, // Hide built-in header toolbar
                height: '100%',       // Let it expand to the container height
                nowIndicator: true,   // Display Outlook style blue time-indicator line
                slotMinTime: '07:00:00', // Start view at 7 AM
                slotMaxTime: '19:00:00', // End view at 7 PM
                slotDuration: '00:30:00', // Show 30-min slots (2 rows per hour)
                editable: false,
                selectable: true,
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                slotLabelFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },
                views: {
                    timeGridDay: {
                        dayHeaderFormat: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
                    },
                    timeGridWeek: {
                        dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }
                    }
                },
                events: '{{ route("management.calendar.events") }}',
                
                loading: (isLoading) => {
                    this.isCalendarLoading = isLoading;
                },

                select: (info) => {
                    this.openCreateModal(info.startStr, info.endStr ? this.adjustEndDate(info.endStr) : info.startStr);
                },

                eventClick: (info) => {
                    this.openEditModal(info.event);
                },

                // Sync the month picker whenever date shifts
                datesSet: (info) => {
                    let activeDate = this.calendar.getDate();
                    this.miniYear = activeDate.getFullYear();
                    this.miniMonth = activeDate.getMonth();
                    this.generateMiniDays();

                    // Format as YYYY-MM
                    let yyyy = activeDate.getFullYear();
                    let mm = String(activeDate.getMonth() + 1).padStart(2, '0');
                    let monthVal = `${yyyy}-${mm}`;
                    let el = document.getElementById('month-picker');
                    if (el) el.value = monthVal;

                    this.currentView = info.view.type;
                },

                // Custom solid style mapper
                eventDidMount: (info) => {
                    let isHoliday = info.event.extendedProps.is_holiday;
                    let bgColor = info.event.backgroundColor || (isHoliday ? '#ef4444' : '#3b82f6');
                    let textColor = '#ffffff';

                    // Apply styles
                    info.el.style.backgroundColor = bgColor;
                    info.el.style.color = textColor;
                    info.el.style.borderRadius = '3px';
                    info.el.style.border = 'none';
                    info.el.style.padding = '3px 6px';

                    let titleEl = info.el.querySelector('.fc-event-title');
                    if (titleEl) {
                        titleEl.style.color = textColor;
                        titleEl.style.fontWeight = '600';
                        titleEl.style.fontSize = '10.5px';

                        setTimeout(() => {
                            const containerWidth = titleEl.parentElement ? titleEl.parentElement.clientWidth : 0;
                            if (titleEl.scrollWidth > containerWidth && containerWidth > 0) {
                                const diff = titleEl.scrollWidth - containerWidth;
                                titleEl.style.setProperty('--marquee-distance', `-${diff + 8}px`);
                                titleEl.classList.add('animate-marquee');
                            }
                        }, 100);
                    }
                }
            });
            this.calendar.render();
        },

        goToMonth(val) {
            if (val && this.calendar) {
                let parts = val.split('-'); // [YYYY, MM]
                let year = parseInt(parts[0]);
                let month = parseInt(parts[1]) - 1; // 0-indexed month
                this.calendar.gotoDate(new Date(year, month, 1));
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
                    this.allEvents = events;
                    let todayStr = new Date().toISOString().substring(0, 10);
                    this.upcomingHolidays = events
                        .filter(e => e.extendedProps.is_holiday && e.start >= todayStr)
                        .sort((a, b) => a.start.localeCompare(b.start))
                        .slice(0, 15); // Limit to top 15
                    
                    // Trigger regeneration of mini days to display dots
                    this.generateMiniDays();
                });
        },

        openCreateModal(startDate = '', endDate = '') {
            this.isEditMode = false;
            this.isApiHoliday = false;
            let defaultDate = startDate || new Date().toISOString().substring(0, 10);
            this.form = {
                id: null,
                title: '',
                start_date: defaultDate,
                start_time: '',
                end_date: endDate || defaultDate,
                end_time: '',
                api_holiday_date: null,
                is_holiday: false,
                description: '',
                color: '#3b82f6'
            };
            this.showModal = true;
        },

        openEditModal(calendarEvent) {
            this.isEditMode = calendarEvent.extendedProps.is_db;
            this.isApiHoliday = !calendarEvent.extendedProps.is_db || !!calendarEvent.extendedProps.api_holiday_date;

            this.form = {
                id: calendarEvent.extendedProps.is_db ? calendarEvent.id.replace('db-', '') : null,
                title: calendarEvent.title,
                start_date: calendarEvent.startStr.substring(0, 10),
                start_time: calendarEvent.extendedProps.start_time || '',
                end_date: calendarEvent.extendedProps.raw_end || calendarEvent.startStr.substring(0, 10),
                end_time: calendarEvent.extendedProps.end_time || '',
                api_holiday_date: calendarEvent.extendedProps.api_holiday_date || null,
                is_holiday: calendarEvent.extendedProps.is_holiday,
                description: calendarEvent.extendedProps.description || '',
                color: calendarEvent.extendedProps.color || 
                       (calendarEvent.extendedProps.api_holiday_date 
                           ? (calendarEvent.extendedProps.is_holiday ? '#ef4444' : '#10b981')
                           : (calendarEvent.extendedProps.is_holiday ? '#ef4444' : '#3b82f6'))
            };
            this.showModal = true;
        },

        submitEventForm() {
            // Ensure is_holiday is cast to a strict boolean
            this.form.is_holiday = this.form.is_holiday === true || this.form.is_holiday === 'true';

            let url = this.isEditMode 
                ? '{{ url('management/calendar/events') }}/' + this.form.id
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
            if (!confirm('Are you sure you want to delete this event/override?')) return;

            fetch('{{ url('management/calendar/events') }}/' + this.form.id, {
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
