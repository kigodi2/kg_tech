<!-- Side Menu Bar -->
<div id="sidebar" class="w-80 bg-gray-900 text-white flex flex-col shadow-lg">
    
    <!-- Header -->
    <div class="px-6 py-6 border-b border-gray-700">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-chart-line text-blue-400 text-2xl"></i>
            <div>
                <h1 class="text-lg font-bold">ACSEE Results</h1>
                <p class="text-xs text-gray-400">Results Management System</p>
            </div>
        </div>
    </div>
    
    <!-- Scrollable Menu Items -->
    <div class="flex-1 overflow-y-auto">
        <nav class="px-4 py-6 space-y-1">
            
            <!-- HIERARCHY GRID NAVIGATION -->
            <div class="mb-6">
                <a href="{{ route('hierarchy.regions') }}"
                   class="menu-item group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-green-100 transition-colors">
                    <i class="fas fa-th text-green-500 group-hover:text-green-600 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium text-green-600">Hierarchy Grid</p>
                        <p class="text-xs text-gray-500">Region → District → School</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
            
            <!-- SECTION A: CONFIGURATION -->
            <div class="mb-6">
                
                <!-- Grading System -->
                <a href="{{ route('results.acsee.grading.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.grading.*') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.grading.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-sliders-h text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Grading System</p>
                        <p class="text-xs text-gray-500">Grade boundaries & GPA</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
            
            <!-- SECTION B: RESULT PROCESSING -->
            <div class="mb-6">
                
                <!-- Result Processing -->
                <a href="{{ route('results.acsee.processing.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.processing.*') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.processing.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-cog text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Result Processing</p>
                        <p class="text-xs text-gray-500">Grade & compute scores</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
            
            <!-- SECTION C: RESULTS MANAGEMENT -->
            <div class="mb-6">
                
                <!-- Results -->
                <a href="{{ route('results.acsee.results.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.results.*') && !request()->routeIs('results.acsee.results.candidate') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.results.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-list-check text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Results</p>
                        <p class="text-xs text-gray-500">View & publish results</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
                
                <!-- Result Linking -->
                <a href="{{ route('results.acsee.linking.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.linking.*') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.linking.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-link text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Result Linking</p>
                        <p class="text-xs text-gray-500">Pre-processing validation</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
            
            <!-- SECTION D: OUTPUT & COMMUNICATION -->
            <div class="mb-6">
                
                <!-- Reports -->
                <a href="{{ route('results.acsee.reports.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.reports.*') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.reports.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-file-alt text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Reports</p>
                        <p class="text-xs text-gray-500">Performance analysis</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
            
            <!-- SECTION E: GOVERNANCE & AUDIT -->
            <div class="mb-6">
                
                <!-- Audit & Logs -->
                <a href="{{ route('results.acsee.audit.index') }}"
                   class="menu-item {{ request()->routeIs('results.acsee.audit.*') ? 'active' : '' }} group px-4 py-3 rounded-lg flex items-center gap-3 hover:bg-blue-100 transition-colors {{ request()->routeIs('results.acsee.audit.*') ? 'bg-blue-600' : '' }}">
                    <i class="fas fa-history text-gray-400 group-hover:text-blue-400 w-5"></i>
                    <div class="flex-1">
                        <p class="font-medium">Audit & Logs</p>
                        <p class="text-xs text-gray-500">Processing history</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Footer -->
    <div class="px-6 py-4 border-t border-gray-700 text-xs text-gray-400">
        <div class="flex items-center justify-between">
            <span>Exam Year: {{ session('exam_year', '2026') }}</span>
            <span class="px-2 py-1 bg-gray-800 rounded text-gray-300">
                {{ Auth::user()?->role ?? 'User' }}
            </span>
        </div>
    </div>
</div>

<style>
    .menu-item {
        position: relative;
    }
    
    .menu-item.active {
        background-color: #1e40af;
        border-left: 4px solid #3b82f6;
    }
    
    .menu-item.active i:first-child {
        color: #60a5fa;
    }
</style>
