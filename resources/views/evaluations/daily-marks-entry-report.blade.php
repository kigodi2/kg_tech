@extends('layout')

@section('content')

<div class="w-full" style="font-family: 'Maiandra GD', sans-serif;" x-data="dailyMarksReportPage()" @init="init()">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <div class="flex justify-between items-center">
            <div>
                <a href="/evaluations/acsee" class="text-blue-600 hover:text-blue-800 mb-2 inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Evaluations
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Daily Marks Entry Report</h1>
                <p class="text-sm text-gray-600 mt-1">Regional Level - Subjects Performance Tracking</p>
            </div>
            <div class="flex gap-2">
                <button @click="exportDailyMarksToCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-download mr-2"></i> Export CSV
                </button>
                <button @click="printDailyMarksReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Filters (Same pattern as Schools page) -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex gap-4 items-end flex-wrap">
            <!-- Exam Year Filter -->
            <div class="flex flex-col flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Exam Year
                </label>
                <div class="relative" @click.outside="examYearOpen = false">
                    <button 
                        @click="examYearOpen = !examYearOpen"
                        class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t"
                    >
                        <span x-text="dailyMarksFilters.exam_year_id ? dailyMarksExamYears.find(y => y.id == dailyMarksFilters.exam_year_id)?.year_label : 'All Years'" class="text-gray-700 whitespace-nowrap"></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div x-show="examYearOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                        <div class="max-h-64 overflow-y-auto">
                            <div @click="dailyMarksFilters.exam_year_id = ''; examYearOpen = false;" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                All Years
                            </div>
                            <template x-for="year in dailyMarksExamYears" :key="year.id">
                                <div 
                                    @click="dailyMarksFilters.exam_year_id = year.id; examYearOpen = false;"
                                    :class="dailyMarksFilters.exam_year_id == year.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                    class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                    x-text="year.year_label"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Region Filter -->
            <div class="flex flex-col flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>Region
                </label>
                <div class="relative" @click.outside="regionOpen = false">
                    <button 
                        @click="regionOpen = !regionOpen"
                        class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t"
                    >
                        <span x-text="dailyMarksFilters.region_id ? dailyMarksRegions.find(r => r.id == dailyMarksFilters.region_id)?.name : 'All Regions'" class="text-gray-700 whitespace-nowrap"></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div x-show="regionOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                        <div class="max-h-64 overflow-y-auto">
                            <div @click="dailyMarksFilters.region_id = ''; regionOpen = false;" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                All Regions
                            </div>
                            <template x-for="region in dailyMarksRegions" :key="region.id">
                                <div 
                                    @click="dailyMarksFilters.region_id = region.id; regionOpen = false;"
                                    :class="dailyMarksFilters.region_id == region.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                    class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                    x-text="region.name"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Filter -->
            <div class="flex flex-col flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-book mr-2 text-purple-600"></i>Subject
                </label>
                <div class="relative" @click.outside="subjectOpen = false">
                    <button 
                        @click="subjectOpen = !subjectOpen"
                        class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t"
                    >
                        <span x-text="dailyMarksFilters.subject_id ? dailyMarksSubjects.find(s => s.id == dailyMarksFilters.subject_id)?.name : 'All Subjects'" class="text-gray-700 whitespace-nowrap"></span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div x-show="subjectOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                        <div class="max-h-64 overflow-y-auto">
                            <div @click="dailyMarksFilters.subject_id = ''; subjectOpen = false;" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                All Subjects
                            </div>
                            <template x-for="subject in dailyMarksSubjects" :key="subject.id">
                                <div 
                                    @click="dailyMarksFilters.subject_id = subject.id; subjectOpen = false;"
                                    :class="dailyMarksFilters.subject_id == subject.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                    class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                    x-text="subject.name"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entry Date Filter -->
            <div class="flex flex-col flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-clock mr-2 text-orange-600"></i>Entry Date
                </label>
                <input type="date" x-model="dailyMarksFilters.entry_date" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <!-- Action Buttons -->
            <button @click="loadDailyMarksReport()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm flex items-center gap-2 whitespace-nowrap font-medium">
                <i class="fas fa-search"></i> Apply
            </button>
            <button @click="resetDailyMarksFilters()" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors text-sm flex items-center gap-2 whitespace-nowrap font-medium">
                <i class="fas fa-redo"></i> Reset
            </button>

            <!-- Records Counter -->
            <div class="ml-auto text-sm text-gray-600 font-medium whitespace-nowrap">
                <span x-text="dailyMarksReportData.length + ' records'"></span>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="px-8 py-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse" style="font-size: 12px;">
                    <thead class="bg-gray-200 border border-gray-300">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold bg-orange-100">S/N</th>
                            <th class="border border-gray-300 px-4 py-2 text-left font-bold bg-orange-100">SUBJECT</th>
                            <th class="border border-gray-300 px-4 py-2 text-center font-bold bg-orange-100">EXPECTED SCRIPTS</th>
                            
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-yellow-100">MARKED DAY 1</th>
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-yellow-100">MARKED DAY 2</th>
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-yellow-100">MARKED DAY 3</th>
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-yellow-100">MARKED DAY 4</th>
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-yellow-100">MARKED DAY 5</th>
                            
                            <th colspan="2" class="border border-gray-300 px-2 py-2 text-center font-bold bg-red-100">REMAINDER</th>
                            <th class="border border-gray-300 px-4 py-2 text-center font-bold bg-green-100">REMARKS</th>
                        </tr>
                        <tr class="bg-gray-100 border border-gray-300">
                            <th colspan="3"></th>
                            <template x-for="i in 5" :key="`day${i}`">
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs font-bold">Count</th>
                                <th class="border border-gray-300 px-2 py-1 text-center text-xs font-bold">%</th>
                            </template>
                            <th class="border border-gray-300 px-2 py-1 text-center text-xs font-bold">Count</th>
                            <th class="border border-gray-300 px-2 py-1 text-center text-xs font-bold">%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in dailyMarksReportData" :key="index">
                            <tr class="border border-gray-300 hover:bg-blue-50">
                                <td class="border border-gray-300 px-4 py-2 text-center font-bold" x-text="index + 1"></td>
                                <td class="border border-gray-300 px-4 py-2 font-semibold" x-text="row.subject_name"></td>
                                <td class="border border-gray-300 px-4 py-2 text-center font-bold" x-text="row.expected_scripts"></td>
                                
                                <!-- Day 1 -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day1_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day1_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Day 2 -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day2_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day2_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Day 3 -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day3_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day3_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Day 4 -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day4_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day4_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Day 5 -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day5_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.day5_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Remainder -->
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.remainder_count"></td>
                                <td class="border border-gray-300 px-2 py-2 text-center" x-text="row.remainder_percentage.toFixed(1) + '%'"></td>
                                
                                <!-- Remarks -->
                                <td class="border border-gray-300 px-4 py-2 text-sm" x-text="row.remarks"></td>
                            </tr>
                        </template>
                        <template x-if="dailyMarksReportData.length === 0">
                            <tr>
                                <td colspan="18" class="border border-gray-300 px-4 py-6 text-center text-gray-500">
                                    No data available for the selected filters
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dailyMarksReportPage', () => ({
            dailyMarksFilters: {
                exam_year_id: '',
                region_id: '',
                subject_id: '',
                entry_date: ''
            },
            dailyMarksReportData: [],
            dailyMarksExamYears: [],
            dailyMarksRegions: [],
            dailyMarksSubjects: [],
            examYearOpen: false,
            regionOpen: false,
            subjectOpen: false,

            async init() {
                await Promise.all([
                    this.loadDailyMarksExamYears(),
                    this.loadDailyMarksRegions(),
                    this.loadDailyMarksSubjects()
                ]);
            },

            async loadDailyMarksExamYears() {
                try {
                    const response = await fetch('/api/exam-years');
                    const data = await response.json();
                    this.dailyMarksExamYears = data.data || [];
                    console.log('Loaded exam years:', this.dailyMarksExamYears.length);
                } catch (error) {
                    console.error('Failed to load exam years:', error);
                }
            },

            async loadDailyMarksRegions() {
                try {
                    const response = await fetch('/api/regions');
                    const data = await response.json();
                    this.dailyMarksRegions = data.data || data || [];
                    console.log('Loaded regions:', this.dailyMarksRegions.length);
                } catch (error) {
                    console.error('Failed to load regions:', error);
                }
            },

            async loadDailyMarksSubjects() {
                try {
                    const response = await fetch('/api/subjects');
                    const data = await response.json();
                    this.dailyMarksSubjects = data.data || data || [];
                    console.log('Loaded subjects:', this.dailyMarksSubjects.length);
                } catch (error) {
                    console.error('Failed to load subjects:', error);
                }
            },

            async loadDailyMarksReport() {
                try {
                    const params = new URLSearchParams();
                    if (this.dailyMarksFilters.exam_year_id) params.append('exam_year_id', this.dailyMarksFilters.exam_year_id);
                    if (this.dailyMarksFilters.region_id) params.append('region_id', this.dailyMarksFilters.region_id);
                    if (this.dailyMarksFilters.subject_id) params.append('subject_id', this.dailyMarksFilters.subject_id);
                    if (this.dailyMarksFilters.entry_date) params.append('entry_date', this.dailyMarksFilters.entry_date);

                    const response = await fetch(`/api/daily-marks-entry-report?${params}`);
                    const data = await response.json();
                    this.dailyMarksReportData = data;
                    console.log(`Loaded ${data.length} records`);
                } catch (error) {
                    console.error('Failed to load report:', error);
                    this.dailyMarksReportData = [];
                }
            },

            resetDailyMarksFilters() {
                // Reset filter values
                this.dailyMarksFilters = {
                    exam_year_id: '',
                    region_id: '',
                    subject_id: '',
                    entry_date: ''
                };
                
                // Close all dropdowns
                this.examYearOpen = false;
                this.regionOpen = false;
                this.subjectOpen = false;

                // Load unfiltered report
                this.loadDailyMarksReport();
            },

            exportDailyMarksToCSV() {
                if (this.dailyMarksReportData.length === 0) {
                    alert('No data to export');
                    return;
                }

                const headers = ['S/N', 'SUBJECT', 'EXPECTED SCRIPTS', 
                    'Day 1 Count', 'Day 1 %', 'Day 2 Count', 'Day 2 %', 
                    'Day 3 Count', 'Day 3 %', 'Day 4 Count', 'Day 4 %', 
                    'Day 5 Count', 'Day 5 %', 'Remainder Count', 'Remainder %', 'Remarks'];

                const rows = this.dailyMarksReportData.map((row, index) => [
                    index + 1,
                    row.subject_name,
                    row.expected_scripts,
                    row.day1_count,
                    row.day1_percentage.toFixed(1),
                    row.day2_count,
                    row.day2_percentage.toFixed(1),
                    row.day3_count,
                    row.day3_percentage.toFixed(1),
                    row.day4_count,
                    row.day4_percentage.toFixed(1),
                    row.day5_count,
                    row.day5_percentage.toFixed(1),
                    row.remainder_count,
                    row.remainder_percentage.toFixed(1),
                    row.remarks
                ]);

                const csv = [headers, ...rows].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `daily-marks-entry-report-${new Date().toISOString().split('T')[0]}.csv`;
                a.click();
                window.URL.revokeObjectURL(url);
            },

            printDailyMarksReport() {
                const printWindow = window.open('', '', 'width=1200,height=600');
                const table = document.querySelector('table');
                if (!table) {
                    alert('No table to print');
                    return;
                }
                
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Daily Marks Entry Report</title>
                        <style>
                            body { font-family: 'Maiandra GD', sans-serif; margin: 10px; }
                            h1 { text-align: center; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #333; padding: 5px; text-align: center; font-size: 11px; }
                            th { background-color: #f0f0f0; font-weight: bold; }
                            tr:nth-child(even) { background-color: #f9f9f9; }
                        </style>
                    </head>
                    <body>
                        <h1>Daily Marks Entry Report</h1>
                        <p>Report Date: ${new Date().toLocaleDateString()}</p>
                        ${table.outerHTML}
                    </body>
                    </html>
                `);
                printWindow.document.close();
                setTimeout(() => printWindow.print(), 250);
            }
        }));
    });
</script>



@endsection
