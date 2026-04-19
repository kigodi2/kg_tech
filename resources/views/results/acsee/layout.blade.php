@extends('layout')

@section('content')
<div class="flex min-h-screen bg-gray-50">
    <!-- Side Menu Bar -->
    @include('results.acsee.components.side-menu')
    <div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-gray-900/40 lg:hidden"></div>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navigation Bar -->
        <div class="bg-white border-b border-gray-200 shadow-sm px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3 sm:items-center">
                    <button id="toggleSidebar" class="text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">@yield('page-title', $resultsModuleTitle)</h2>
                        <p class="text-sm text-gray-600">@yield('page-subtitle')</p>
                    </div>
                </div>
                
                <!-- Breadcrumbs -->
                <nav class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                    <a href="{{ route($resultsRoutePrefix . '.dashboard') }}" class="hover:text-blue-600 transition-colors">
                        <i class="fas fa-home"></i> Results
                    </a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-700">{{ $resultsModuleLabel }}</span>
                    @if(Route::currentRouteName() !== $resultsRoutePrefix . '.dashboard')
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-700 font-medium">@yield('breadcrumb-active')</span>
                    @endif
                </nav>
            </div>
        </div>
        
        <!-- Content Area with Scroll -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-4 py-6 sm:px-6 lg:px-8">
                @yield('results-content')
            </div>
        </div>
    </div>
</div>

<style>
    #sidebar {
        transition: transform 0.3s ease-in-out;
    }
    
    #sidebar.collapsed {
        transform: translateX(-100%);
    }
    
    @media (max-width: 1024px) {
        #sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            z-index: 50;
            width: min(20rem, calc(100vw - 2rem));
            background: #111827;
            box-shadow: 2px 0 20px rgba(0,0,0,0.2);
        }
        
        #sidebar.collapsed {
            transform: translateX(-100%);
        }
    }
</style>

<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const toggleSidebarButton = document.getElementById('toggleSidebar');
    const mobileBreakpoint = window.matchMedia('(max-width: 1024px)');

    function syncSidebarState() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        if (mobileBreakpoint.matches) {
            sidebar.classList.add('collapsed');
            sidebarOverlay.classList.add('hidden');
            return;
        }

        sidebar.classList.remove('collapsed');
        sidebarOverlay.classList.add('hidden');
    }

    function toggleSidebar() {
        if (!sidebar || !sidebarOverlay) {
            return;
        }

        sidebar.classList.toggle('collapsed');

        if (mobileBreakpoint.matches) {
            sidebarOverlay.classList.toggle('hidden', sidebar.classList.contains('collapsed'));
        }
    }

    toggleSidebarButton?.addEventListener('click', toggleSidebar);
    sidebarOverlay?.addEventListener('click', toggleSidebar);
    mobileBreakpoint.addEventListener?.('change', syncSidebarState);
    syncSidebarState();
</script>
@endsection
