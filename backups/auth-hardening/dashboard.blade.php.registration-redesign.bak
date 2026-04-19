@extends('layout')

@section('content')
<div class="w-full px-8 py-8">
    <!-- Page Header -->
    <div class="mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Registration Management Dashboard</h1>
        <p class="text-gray-600">Overview of all registration data - Regions, Districts, Schools, and Candidates</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <!-- Regions Card -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg shadow-lg p-6 text-white">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Regions</p>
                    <h3 class="text-4xl font-bold" x-text="statistics.regions">0</h3>
                </div>
                <i class="fas fa-globe text-4xl opacity-20"></i>
            </div>
            <a href="/registration/regions" class="text-blue-100 hover:text-white text-sm font-medium">Manage Regions →</a>
        </div>

        <!-- Districts Card -->
        <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg shadow-lg p-6 text-white">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Districts</p>
                    <h3 class="text-4xl font-bold" x-text="statistics.districts">0</h3>
                </div>
                <i class="fas fa-map text-4xl opacity-20"></i>
            </div>
            <a href="/registration/districts" class="text-purple-100 hover:text-white text-sm font-medium">Manage Districts →</a>
        </div>

        <!-- Schools Card -->
        <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg shadow-lg p-6 text-white">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Schools</p>
                    <h3 class="text-4xl font-bold" x-text="statistics.schools">0</h3>
                </div>
                <i class="fas fa-school text-4xl opacity-20"></i>
            </div>
            <a href="/registration/schools" class="text-green-100 hover:text-white text-sm font-medium">Manage Schools →</a>
        </div>

        <!-- Candidates Card -->
        <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-lg shadow-lg p-6 text-white">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Total Candidates</p>
                    <h3 class="text-4xl font-bold" x-text="statistics.candidates">0</h3>
                </div>
                <i class="fas fa-users text-4xl opacity-20"></i>
            </div>
            <a href="/registration/candidates" class="text-orange-100 hover:text-white text-sm font-medium">Manage Candidates →</a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div x-data="registrationDashboard()" @init="init()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Hierarchy View -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Regions Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Regions Overview</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="region in regions.slice(0, 5)" :key="region.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer" @click="selectRegion(region)">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-globe text-blue-600"></i>
                                <div>
                                    <p class="font-semibold text-gray-800" x-text="region.name"></p>
                                    <p class="text-xs text-gray-500" x-text="'Code: ' + region.code"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-800" x-text="region.districts_count + ' districts'"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="regions.length === 0">
                        <p class="text-center text-gray-500 py-4">No regions added yet</p>
                    </template>
                </div>
                <a href="/registration/regions" class="text-blue-600 hover:underline text-sm mt-4 block">View All Regions →</a>
            </div>

            <!-- Districts Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Districts</h2>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <template x-for="district in districts.slice(0, 5)" :key="district.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-map text-purple-600"></i>
                                <div>
                                    <p class="font-semibold text-gray-800" x-text="district.name"></p>
                                    <p class="text-xs text-gray-500" x-text="district.region_name"></p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600" x-text="district.schools_count + ' schools'"></p>
                        </div>
                    </template>
                    <template x-if="districts.length === 0">
                        <p class="text-center text-gray-500 py-4">No districts added yet</p>
                    </template>
                </div>
                <a href="/registration/districts" class="text-blue-600 hover:underline text-sm mt-4 block">View All Districts →</a>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div class="space-y-6">
            <!-- Candidates by Exam Type -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Candidates by Exam Type</h2>
                <div class="space-y-3">
                    <div class="flex flex-col items-center text-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">PSLE</p>
                            <p class="text-xs text-gray-500">Primary</p>
                        </div>
                        <span class="text-2xl font-bold text-gray-800" x-text="examTypeStats.PSLE || 0"></span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">CSEE</p>
                            <p class="text-xs text-gray-500">Secondary</p>
                        </div>
                        <span class="text-2xl font-bold text-gray-800" x-text="examTypeStats.CSEE || 0"></span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div>
                            <p class="text-sm font-medium text-gray-700">ACSEE</p>
                            <p class="text-xs text-gray-500">Advanced</p>
                        </div>
                        <span class="text-2xl font-bold text-gray-800" x-text="examTypeStats.ACSEE || 0"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Quick Stats</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Avg Schools per District</span>
                        <span class="font-semibold text-gray-800" x-text="(statistics.schools / statistics.districts || 0).toFixed(1)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Avg Candidates per School</span>
                        <span class="font-semibold text-gray-800" x-text="(statistics.candidates / statistics.schools || 0).toFixed(1)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Candidates Registered</span>
                        <span class="font-semibold text-gray-800" x-text="statistics.candidates"></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="/registration/regions" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-globe mr-2"></i>Add Region
                    </a>
                    <a href="/registration/schools" class="block w-full text-center bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-school mr-2"></i>Add School
                    </a>
                    <a href="/registration/candidates" class="block w-full text-center bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-users mr-2"></i>Register Candidate
                    </a>
                    <a href="/registration/candidates-by-district" class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg transition-colors text-sm font-medium">
                        <i class="fas fa-upload mr-2"></i>Import by District
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function registrationDashboard() {
    return {
        regions: [],
        districts: [],
        candidates: [],
        statistics: { regions: 0, districts: 0, schools: 0, candidates: 0 },
        examTypeStats: { PSLE: 0, CSEE: 0, ACSEE: 0 },

        async init() {
            await Promise.all([
                this.loadRegions(),
                this.loadDistricts(),
                this.loadCandidates(),
                this.calculateStats()
            ]);
        },

        async loadRegions() {
            try {
                const response = await fetch('/api/regions');
                const data = await response.json();
                this.regions = data.data || [];
            } catch (error) {
                console.error('Error loading regions:', error);
            }
        },

        async loadDistricts() {
            try {
                const response = await fetch('/api/districts');
                const data = await response.json();
                this.districts = data.data || [];
            } catch (error) {
                console.error('Error loading districts:', error);
            }
        },

        async loadCandidates() {
            try {
                const response = await fetch('/api/candidates');
                const data = await response.json();
                this.candidates = data.data || [];
            } catch (error) {
                console.error('Error loading candidates:', error);
            }
        },

        async calculateStats() {
            this.statistics.regions = this.regions.length;
            this.statistics.districts = this.districts.length;
            this.statistics.candidates = this.candidates.length;
            
            // Calculate schools count
            const schoolResponse = await fetch('/api/schools').catch(() => ({ data: [] }));
            const schoolData = await schoolResponse.json();
            this.statistics.schools = (schoolData.data || []).length;

            // Calculate exam type stats
            this.examTypeStats = {
                PSLE: this.candidates.filter(c => c.exam_type === 'PSLE').length,
                CSEE: this.candidates.filter(c => c.exam_type === 'CSEE').length,
                ACSEE: this.candidates.filter(c => c.exam_type === 'ACSEE').length,
            };

            // Update card statistics
            document.dispatchEvent(new CustomEvent('statsUpdated', { detail: this.statistics }));
        },

        selectRegion(region) {
            window.location.href = '/registration/districts?region=' + region.id;
        }
    };
}

// Update statistics in cards when data loads
document.addEventListener('statsUpdated', (e) => {
    // Cards will update via Alpine's x-text bindings
});
</script>
@endsection
