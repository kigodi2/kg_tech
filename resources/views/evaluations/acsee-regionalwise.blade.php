@extends('layout')

@section('content')
<div class="w-full" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800" style="font-family: 'Maiandra GD', sans-serif;">ACSEE Evaluations</h1>
    </div>

    <div class="flex h-full">
        <div class="bg-gray-800 border-r border-gray-700 shadow-sm overflow-y-auto transition-all duration-200"
             style="height: calc(100vh - 140px); font-family: 'Maiandra GD', sans-serif;"
             x-data="evaluationsSidebar()"
             x-init="init()"
             :class="collapsed ? 'w-16' : 'w-64'">
            <nav class="space-y-2" :class="collapsed ? 'p-2' : 'p-6'">
                <button @click="toggleSidebar()"
                        class="w-full text-left px-3 py-2 text-gray-300 hover:bg-gray-700 rounded-lg transition-colors flex items-center justify-between">
                    <i class="fas fa-bars w-5"></i>
                    <span x-show="!collapsed" class="text-xs">Collapse</span>
                </button>
                <div>
                    <a href="/evaluations/acsee/zonalwise" @click="collapseSidebar()" class="w-full text-left px-4 py-2 font-semibold text-white hover:bg-gray-700 rounded-lg transition-colors flex items-center justify-between block">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-globe text-blue-500 w-5"></i>
                            <span x-show="!collapsed">ZONALWISE</span>
                        </span>
                        <i x-show="!collapsed" class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
                <div>
                    <a href="/evaluations/acsee/regionalwise" @click="collapseSidebar()" class="w-full text-left px-4 py-2 font-semibold text-white bg-gray-700 rounded-lg transition-colors flex items-center justify-between block">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-map text-green-500 w-5"></i>
                            <span x-show="!collapsed">REGIONALWISE</span>
                        </span>
                        <i x-show="!collapsed" class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto bg-gray-100 px-8 py-8">
            <div class="mb-8">
                <h1 class="text-5xl font-bold text-gray-900" style="font-family: 'Maiandra GD', sans-serif;">REGIONALWISE EVALUATIONS</h1>
                <p class="mt-2 text-gray-600 text-2xl" style="font-family: 'Maiandra GD', sans-serif;">Choose an evaluation type to view detailed data</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional General Evaluation</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Councilwise Evaluation</div>
                <div class="bg-slate-900 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Schoolwise Evaluation</div>
                <div class="bg-slate-500 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Districtwise Evaluation</div>

                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Best Ten (10) Councils</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Least Ten (10) Councils</div>
                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Best Ten (10) Schools</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Least Ten (10) Schools</div>

                <div class="bg-slate-900 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Best Ten (10) Girls</div>
                <div class="bg-slate-500 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Least Ten (10) Girls</div>
                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Best Ten (10) Boys</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Least Ten (10) Boys</div>

                <div class="bg-slate-900 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Overall Ten (10) Best Students</div>
                <div class="bg-slate-500 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Overall Ten (10) Least Students</div>
                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Government Schools</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Non-Government Schools</div>

                <div class="bg-slate-900 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Ownership Result Evaluation</div>
                <div class="bg-slate-500 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Subjectwise Result Evaluation</div>
                <div class="bg-red-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Mark Entry Status Report</div>
                <div class="bg-emerald-600 text-white px-3 py-3 text-lg font-bold uppercase shadow">Regional Subject Summary Evaluation</div>
            </div>
        </div>
    </div>
</div>
<script>
    function evaluationsSidebar() {
        return {
            collapsed: false,
            init() {
                this.collapsed = localStorage.getItem('acsee_evaluations_sidebar_collapsed') === '1';
            },
            toggleSidebar() {
                this.collapsed = !this.collapsed;
                localStorage.setItem('acsee_evaluations_sidebar_collapsed', this.collapsed ? '1' : '0');
            },
            collapseSidebar() {
                this.collapsed = true;
                localStorage.setItem('acsee_evaluations_sidebar_collapsed', '1');
            }
        };
    }
</script>
@endsection
