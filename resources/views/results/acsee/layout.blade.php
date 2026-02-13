@extends('layout')

@section('content')
<div class="flex h-screen bg-gray-50">
    <!-- Side Menu Bar -->
    @include('results.acsee.components.side-menu')
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navigation Bar -->
        <div class="bg-white border-b border-gray-200 shadow-sm px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button id="toggleSidebar" class="text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">@yield('page-title', 'ACSEE Results')</h2>
                        <p class="text-sm text-gray-600">@yield('page-subtitle')</p>
                    </div>
                </div>
                
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-sm text-gray-600">
                    <a href="{{ route('results.acsee.dashboard') }}" class="hover:text-blue-600 transition-colors">
                        <i class="fas fa-home"></i> Results
                    </a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-700">ACSEE</span>
                    @if(Route::currentRouteName() !== 'results.acsee.dashboard')
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-700 font-medium">@yield('breadcrumb-active')</span>
                    @endif
                </nav>
            </div>
        </div>
        
        <!-- Content Area with Scroll -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-8 py-6">
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
            background: white;
            box-shadow: 2px 0 4px rgba(0,0,0,0.1);
        }
        
        #sidebar.collapsed {
            transform: translateX(-100%);
        }
    }
</style>

<script>
    document.getElementById('toggleSidebar')?.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
    });
</script>
@endsection
