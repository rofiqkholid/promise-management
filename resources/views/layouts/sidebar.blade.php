@php
$activeParentId = null;
if (isset($menus)) {
    foreach ($menus as $menu) {
        if ($menu->children->isNotEmpty()) {
            foreach ($menu->children as $child) {
                if (request()->is($child->route) || request()->routeIs($child->route)) {
                    $activeParentId = $menu->id;
                    break;
                }
            }
        }
        if ($activeParentId) break;
    }
}
@endphp
<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
{{-- Mobile overlay backdrop --}}
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40"
     style="display: none;">
</div>

<!-- Sidebar -->
<aside
    x-data="{ hovering: false }"
    @mouseenter="hovering = true"
    @mouseleave="hovering = false"
    :class="sidebarOpen ? 'flex' : 'hidden md:flex'"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="-translate-x-full md:translate-x-0"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full md:translate-x-0"
    class="no-scrollbar fixed top-0 left-0 h-screen z-50 group w-72 md:w-20 md:hover:w-72 p-4 bg-white dark:bg-gray-900 flex-col flex-shrink-0 transition-all duration-300 ease-in-out overflow-y-auto overflow-x-hidden shadow-lg border-r border-gray-200 dark:border-gray-700">

    {{-- Mobile close button --}}
    <button @click="sidebarOpen = false" 
            class="md:hidden absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
        <i class="fa-solid fa-xmark text-2xl"></i>
    </button>

    <!-- Sidebar Header -->
    <div class="flex items-center ml-[3px] h-16 mb-10 flex-shrink-0">
        <img src="{{ asset('assets/image/logo-promise.png') }}" alt="Logo" class="h-11 w-11 object-contain flex-shrink-0">
        <span class="titlePromise ml-4 text-[1.5rem] font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">
            Promise
        </span>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="flex-grow" x-data="{ openMenu: {{ $activeParentId ?? 'null' }} }" x-init="$watch('sidebarOpen', value => { openMenu = value ? {{ $activeParentId ?? 'null' }} : null })">
        <ul class="space-y-1.5">
            @foreach ($menus as $menu)
                @php
                    $hasChildren = $menu->children->isNotEmpty();
                    $isActive = false;
                    if ($hasChildren) {
                        $isActive = ($activeParentId == $menu->id);
                    } else {
                        $isActive = request()->is($menu->route) || request()->routeIs($menu->route);
                    }
                    $menuUrl = $hasChildren ? '#' : (Route::has($menu->route) ? route($menu->route) : url($menu->route));
                @endphp
                <li class="mb-2">
                    @if ($hasChildren)
                        <button type="button" @click.prevent="openMenu = (openMenu === {{ $menu->id }} ? null : {{ $menu->id }})"
                            title="{{ $menu->title }}"
                            class="w-full flex items-center justify-between p-3 rounded-xs transition-colors duration-200 text-sm font-medium text-left
                                            hover:bg-blue-50 hover:text-blue-800 dark:hover:bg-gray-800 dark:hover:text-gray-200
                                            {{ $isActive ? 'bg-blue-50 text-blue-800 dark:bg-gray-800 dark:text-gray-200' : 'text-gray-600 dark:text-gray-400' }}">

                            <div class="flex items-center min-w-0 flex-1 mr-2">
                                <span class="flex items-center justify-center w-5 mr-3 flex-shrink-0 text-center">
                                    @if($menu->icon && str_contains($menu->icon, 'fa-'))
                                        <i class="{{ $menu->icon }}"></i>
                                    @else
                                        <span class="font-bold font-sans">{{ substr($menu->title, 0, 1) }}</span>
                                    @endif
                                </span>
                                <span class="truncate block w-full transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">{{ $menu->title }}</span>
                            </div>

                            <span class="flex-shrink-0 transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">
                                <i class="fa-solid fa-chevron-down h-4 w-4 transform transition-transform duration-200" :class="{'rotate-180': openMenu === {{ $menu->id }}}"></i>
                            </span>
                        </button>

                        <ul x-show="openMenu === {{ $menu->id }} && (sidebarOpen || hovering)" x-transition class="mt-1 pl-4 space-y-1 flex flex-col">
                            @foreach ($menu->children as $child)
                                @php
                                    $childActive = request()->is($child->route) || request()->routeIs($child->route);
                                    $childUrl = Route::has($child->route) ? route($child->route) : url($child->route);
                                @endphp
                                <li>
                                    <a href="{{ $childUrl }}" title="{{ $child->title }}" class="flex items-center p-3 rounded-xs transition-colors duration-200 text-xs font-medium min-w-0
                                                            hover:bg-blue-50 hover:text-blue-800 dark:hover:bg-gray-800 dark:hover:text-gray-200
                                                            {{ $childActive ? 'font-bold text-blue-700 dark:text-white' : 'text-gray-600 dark:text-gray-400' }}">
                                        <span class="flex items-center justify-center w-5 mr-3 flex-shrink-0 text-center">
                                            @if($child->icon && str_contains($child->icon, 'fa-'))
                                                <i class="{{ $child->icon }}"></i>
                                            @else
                                                <span class="font-bold font-sans">{{ substr($child->title, 0, 1) }}</span>
                                            @endif
                                        </span>
                                        <span class="truncate block flex-1 transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">{{ $child->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $menuUrl }}" title="{{ $menu->title }}" class="flex items-center p-3 rounded-xs transition-colors duration-200 text-sm font-medium min-w-0
                                        hover:bg-blue-50 hover:text-blue-800 dark:hover:bg-gray-800 dark:hover:text-gray-200
                                        {{ $isActive ? 'bg-blue-50 text-blue-800 dark:bg-gray-800 dark:text-gray-200' : 'text-gray-600 dark:text-gray-400' }}">
                            <span class="flex items-center justify-center w-5 mr-3 flex-shrink-0 text-center">
                                @if($menu->icon && str_contains($menu->icon, 'fa-'))
                                    <i class="{{ $menu->icon }}"></i>
                                @else
                                    <span class="font-bold font-sans">{{ substr($menu->title, 0, 1) }}</span>
                                @endif
                            </span>
                            <span class="truncate block flex-1 transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">{{ $menu->title }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Theme Toggle at Sidebar (from promise-drawing) -->
    <div class="flex-shrink-0 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button @click="darkMode = !darkMode" type="button" class="w-full flex items-center p-3 rounded-xs transition-colors duration-200 text-sm font-medium
            text-gray-600 dark:text-gray-400 hover:bg-blue-700 hover:text-white dark:hover:bg-gray-700">
            <div class="flex items-center justify-center w-5 flex-shrink-0">
                <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </div>
            <span class="ml-3 whitespace-nowrap transition-opacity duration-200 opacity-100 md:opacity-0 md:group-hover:opacity-100">
            </span>
        </button>
    </div>
</aside>
