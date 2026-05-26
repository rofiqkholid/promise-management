@extends('layouts.app')

@section('title', 'Dashboard - Promise Management')
@section('header-title', 'Dashboard Overview')

@section('content')
            <!-- Dashboard Content -->
            <div class="flex-1 overflow-y-auto p-2 pt-17.5 space-y-2 transition-colors duration-200">

                <!-- KPI Summary Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                    <!-- KPI 1 -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 transition-colors duration-200">
                        <div class="h-12 w-12 rounded-none bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">Total Sales</span>
                            <h4 class="text-xl font-bold tracking-tight">$424,850</h4>
                            <span class="text-xs text-emerald-600 font-semibold flex items-center gap-0.5 mt-0.5">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                12.5% vs last month
                            </span>
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 transition-colors duration-200">
                        <div class="h-12 w-12 rounded-none bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">Active Tasks</span>
                            <h4 class="text-xl font-bold tracking-tight">854</h4>
                            <span class="text-xs text-emerald-600 font-semibold flex items-center gap-0.5 mt-0.5">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                8.2% vs last week
                            </span>
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 transition-colors duration-200">
                        <div class="h-12 w-12 rounded-none bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">Total Users</span>
                            <h4 class="text-xl font-bold tracking-tight">3,485</h4>
                            <span class="text-xs text-rose-600 font-semibold flex items-center gap-0.5 mt-0.5">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                1.5% decrease
                            </span>
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex items-center gap-2 transition-colors duration-200">
                        <div class="h-12 w-12 rounded-none bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider block">Server Load</span>
                            <h4 class="text-xl font-bold tracking-tight">42.8%</h4>
                            <span class="text-xs text-emerald-600 font-semibold flex items-center gap-0.5 mt-0.5">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                Stable (5 min avg)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Charts Layout Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-2">
                    <!-- Chart 1: Line Chart -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">Monthly Revenue</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Total earnings comparison</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 rounded-none">Line</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px]">
                            <canvas id="chartMonthlyRevenue"></canvas>
                        </div>
                    </div>

                    <!-- Chart 2: Doughnut Chart (Pie variation) -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">User Segments</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Subscription plans distribution</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 rounded-none">Doughnut</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px] flex items-center justify-center">
                            <canvas id="chartUserSegments"></canvas>
                        </div>
                    </div>

                    <!-- Chart 3: Vertical Bar Chart -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">Weekly Activity</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Total user actions completed</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 rounded-none">Bar</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px]">
                            <canvas id="chartWeeklyActivity"></canvas>
                        </div>
                    </div>

                    <!-- Chart 4: Pie Chart -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">Traffic Sources</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Referrals & acquisitions channels</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 rounded-none">Pie</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px] flex items-center justify-center">
                            <canvas id="chartTrafficSources"></canvas>
                        </div>
                    </div>

                    <!-- Chart 5: Spline Area Chart (Line variation) -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">System Performance</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Weekly server response time</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 rounded-none">Line Area</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px]">
                            <canvas id="chartSystemPerformance"></canvas>
                        </div>
                    </div>

                    <!-- Chart 6: Horizontal Bar Chart -->
                    <div class="bg-white dark:bg-slate-800 rounded-none p-4 border border-slate-200 dark:border-slate-700/80 flex flex-col transition-colors duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h4 class="font-bold text-slate-800 dark:text-white text-sm">Product Inventory</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Stock availability across departments</p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 bg-sky-50 dark:bg-sky-950 text-sky-600 dark:text-sky-400 rounded-none">Horizontal Bar</span>
                        </div>
                        <div class="relative flex-1 min-h-[220px]">
                            <canvas id="chartProductInventory"></canvas>
                        </div>
                    </div>
                </div>
            </div>
@endsection

@push('scripts')
    <!-- ChartJS Configurations -->
    <script>
        (function() {
            function initCharts() {
                if (typeof window.Chart === 'undefined') {
                    setTimeout(initCharts, 50);
                    return;
                }

            const getThemeColors = () => {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    gridColor: isDark ? '#334155' : '#e2e8f0',
                    textColor: isDark ? '#94a3b8' : '#64748b'
                };
            };

            let colors = getThemeColors();

            // Global Chart config defaults
            Chart.defaults.color = colors.textColor;
            Chart.defaults.font.family = "'Outfit', sans-serif";

            const charts = [];

            // 1. Monthly Revenue Chart (Line)
            const ctx1 = document.getElementById('chartMonthlyRevenue').getContext('2d');
            charts.push(new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [12000, 19000, 15000, 25000, 22000, 30000, 45000, 38000, 52000, 58000, 64000, 75000],
                        borderColor: '#3b82f6',
                        borderWidth: 3,
                        pointBackgroundColor: '#3b82f6',
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: colors.gridColor },
                            ticks: { 
                                color: colors.textColor,
                                callback: value => '$' + value.toLocaleString() 
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: colors.textColor }
                        }
                    }
                }
            }));

            // 2. User Segments (Doughnut)
            const ctx2 = document.getElementById('chartUserSegments').getContext('2d');
            charts.push(new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Enterprise', 'Professional', 'Standard', 'Free'],
                    datasets: [{
                        data: [35, 25, 22, 18],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#64748b'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { 
                                boxWidth: 12, 
                                padding: 15,
                                color: colors.textColor
                            }
                        }
                    },
                    cutout: '65%'
                }
            }));

            // 3. Weekly Activity (Bar)
            const ctx3 = document.getElementById('chartWeeklyActivity').getContext('2d');
            charts.push(new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Tasks Done',
                        data: [65, 84, 72, 90, 85, 40, 30],
                        backgroundColor: '#10b981',
                        borderRadius: 0,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: colors.gridColor },
                            ticks: { color: colors.textColor }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: colors.textColor }
                        }
                    }
                }
            }));

            // 4. Traffic Sources (Pie)
            const ctx4 = document.getElementById('chartTrafficSources').getContext('2d');
            charts.push(new Chart(ctx4, {
                type: 'pie',
                data: {
                    labels: ['Search', 'Direct', 'Referral', 'Social', 'Email'],
                    datasets: [{
                        data: [42, 28, 15, 10, 5],
                        backgroundColor: ['#1d4ed8', '#38bdf8', '#60a5fa', '#f43f5e', '#fb7185'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { 
                                boxWidth: 12, 
                                padding: 15,
                                color: colors.textColor
                            }
                        }
                    }
                }
            }));

            // 5. System Performance (Line Area)
            const ctx5 = document.getElementById('chartSystemPerformance').getContext('2d');
            charts.push(new Chart(ctx5, {
                type: 'line',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    datasets: [{
                        label: 'Avg Response Time (ms)',
                        data: [120, 110, 95, 115, 85, 78],
                        borderColor: '#0284c7',
                        borderWidth: 2,
                        backgroundColor: 'rgba(2, 132, 199, 0.15)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: colors.gridColor },
                            ticks: { color: colors.textColor }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: colors.textColor }
                        }
                    }
                }
            }));

            // 6. Product Inventory (Horizontal Bar)
            const ctx6 = document.getElementById('chartProductInventory').getContext('2d');
            charts.push(new Chart(ctx6, {
                type: 'bar',
                data: {
                    labels: ['Machinery', 'Electronics', 'Raw Metals', 'Safety Gear', 'Office Supplies'],
                    datasets: [{
                        label: 'In Stock (units)',
                        data: [250, 480, 190, 650, 310],
                        backgroundColor: '#3b82f6',
                        borderRadius: 0,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { color: colors.gridColor },
                            ticks: { color: colors.textColor }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: colors.textColor }
                        }
                    }
                }
            }));


            window.updateChartThemes = function() {
                const currentColors = getThemeColors();
                Chart.defaults.color = currentColors.textColor;

                charts.forEach(chart => {
                    if (chart.options.scales) {
                        if (chart.options.scales.x) {
                            if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = currentColors.gridColor;
                            if (chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = currentColors.textColor;
                        }
                        if (chart.options.scales.y) {
                            if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = currentColors.gridColor;
                            if (chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = currentColors.textColor;
                        }
                    }
                    if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                        chart.options.plugins.legend.labels.color = currentColors.textColor;
                    }
                    chart.update();
                });
            };
        }

        initCharts();
    })();
    </script>
@endpush
