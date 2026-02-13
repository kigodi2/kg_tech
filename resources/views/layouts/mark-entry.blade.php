<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ACSEE Mark Entry') - IRMS</title>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-item-active {
            @apply bg-blue-600 text-white border-l-4 border-blue-700;
        }
    </style>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform" :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }" @click.away="sidebarOpen = true" x-cloak>
            <div class="flex flex-col h-full">
                <!-- Logo & Header -->
                <div class="px-6 py-4 border-b border-gray-700">
                    <h1 class="text-xl font-bold">📊 ACSEE Marks</h1>
                    <p class="text-xs text-gray-400 mt-1">Lifecycle Management</p>
                </div>

                <!-- Navigation Groups -->
                <nav class="flex-1 overflow-y-auto py-6 space-y-8">
                    
                    <!-- GROUP 1: ENTRY & VALIDATION -->
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">📤 Entry & Validation</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('mark-entry.acsee.entry-validation.index') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors"
                                   :class="request()->routeIs('mark-entry.acsee.entry-validation.*') ? 'sidebar-item-active' : ''">
                                    <i class="fas fa-upload w-4"></i>
                                    <span>Upload Marks</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- GROUP 2: MODERATION & REVIEW -->
                    @can('mark-entry.moderate')
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">🔍 Moderation</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('mark-entry.acsee.moderation.dashboard') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors"
                                   :class="request()->routeIs('mark-entry.acsee.moderation.*') ? 'sidebar-item-active' : ''">
                                    <i class="fas fa-eye w-4"></i>
                                    <span>Review Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endcan

                    <!-- GROUP 3: SUBMISSION & LOCKING -->
                    @can('mark-entry.lock')
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">🔒 Submission</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('mark-entry.acsee.submission.dashboard') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors"
                                   :class="request()->routeIs('mark-entry.acsee.submission.*') ? 'sidebar-item-active' : ''">
                                    <i class="fas fa-lock w-4"></i>
                                    <span>Lock & Submit</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endcan

                    <!-- GROUP 4: REPORTS & EXPORTS -->
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">📑 Reports</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="#" class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors">
                                    <i class="fas fa-file-pdf w-4"></i>
                                    <span>Scoresheets</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors">
                                    <i class="fas fa-table w-4"></i>
                                    <span>CSV Exports</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- GROUP 5: MONITORING & AUDIT -->
                    @can('mark-entry.audit')
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">🕐 Monitoring</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('mark-entry.acsee.monitoring.dashboard') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors"
                                   :class="request()->routeIs('mark-entry.acsee.monitoring.*') ? 'sidebar-item-active' : ''">
                                    <i class="fas fa-chart-line w-4"></i>
                                    <span>Lifecycle Status</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mark-entry.acsee.monitoring.audit-trail') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors">
                                    <i class="fas fa-history w-4"></i>
                                    <span>Audit Trail</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endcan

                    <!-- GROUP 6: ADMINISTRATION -->
                    @can('mark-entry.admin')
                    <div>
                        <p class="px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">⚙️ Admin</p>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('mark-entry.acsee.admin.configuration') }}" 
                                   class="px-6 py-2 flex items-center gap-3 text-sm hover:bg-gray-800 transition-colors"
                                   :class="request()->routeIs('mark-entry.acsee.admin.*') ? 'sidebar-item-active' : ''">
                                    <i class="fas fa-cog w-4"></i>
                                    <span>Configuration</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endcan
                </nav>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-700">
                    <p class="text-xs text-gray-400">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ auth()->user()->role->name ?? 'Role' }}</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden" :class="{ 'ml-64': sidebarOpen }">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-bars text-gray-600"></i>
                </button>
                
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name ?? 'User' }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-red-600 transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
