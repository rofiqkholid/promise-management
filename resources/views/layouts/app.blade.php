<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Promise Management')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Database Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Theme initialization script to prevent FOUC -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body x-data="{ darkMode: localStorage.getItem('theme') === 'dark', sidebarOpen: false }"
      x-init="$watch('darkMode', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); if (val) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); } if(window.updateChartThemes) window.updateChartThemes(); })"
      x-bind:class="{ 'dark': darkMode }"
      class="bg-gray-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen font-sans antialiased transition-colors duration-200">
    <div class="relative min-h-screen flex overflow-hidden">
        
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content Area Wrapper -->
        <div class="flex-1 flex flex-col md:pl-20 w-full overflow-hidden">
            
            <!-- Header -->
            @include('layouts.header')

            <!-- Main Content -->
            @yield('content')

        </div>
    </div>

    @stack('scripts')
</body>
</html>
