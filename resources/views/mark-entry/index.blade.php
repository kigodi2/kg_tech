@extends('layout')

@section('content')
<div class="w-full flex gap-0" x-data="markEntryManager()" @init="init()">
    <!-- SIDEBAR MENU -->
    <aside class="w-64 bg-gray-900 text-gray-100 min-h-screen sticky top-[140px] overflow-y-auto">
        <div class="p-6">
            <h2 class="text-lg font-bold text-white mb-6">Mark Entry Lifecycle</h2>
            
            <!-- 1. ENTRY & VALIDATION -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-chart-bar"></i> Entry & Validation
                </h3>
                <ul class="space-y-2">
                    <li><a href="#upload" @click.prevent="smoothScroll('#upload')" class="sidebar-link text-sm hover:text-blue-400 transition cursor-pointer">📤 Upload Marks</a></li>
                    <li><a href="#csv-tab" @click.prevent="importMode = 'single'; $nextTick(() => smoothScroll('#csv-tab'))" class="sidebar-link text-sm hover:text-blue-400 transition cursor-pointer">📊 Single Subject CSV</a></li>
                    <li><a href="#school-bulk" @click.prevent="importMode = 'schoolBulk'; $nextTick(() => smoothScroll('#school-bulk'))" class="sidebar-link text-sm hover:text-blue-400 transition cursor-pointer">📦 School Bulk ZIP</a></li>
                    <li><a href="#district-bulk" @click.prevent="importMode = 'district'; $nextTick(() => smoothScroll('#district-bulk'))" class="sidebar-link text-sm hover:text-blue-400 transition cursor-pointer">📋 District Bulk ZIP</a></li>
                </ul>
            </div>

            <!-- 2. MODERATION & REVIEW -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-search"></i> Moderation & Review
                </h3>
                <ul class="space-y-2">
                    <li><a href="#moderation-dashboard" @click.prevent="smoothScroll('#moderation-dashboard')" class="sidebar-link text-sm hover:text-yellow-400 transition cursor-pointer">📋 Review Dashboard</a></li>
                    <li><a href="#pending-review" @click.prevent="smoothScroll('#pending-review')" class="sidebar-link text-sm hover:text-yellow-400 transition cursor-pointer">⏳ Pending Review</a></li>
                    <li><a href="#approve-marks" @click.prevent="smoothScroll('#approve-marks')" class="sidebar-link text-sm hover:text-yellow-400 transition cursor-pointer">✅ Approve Marks</a></li>
                    <li><a href="#reject-feedback" @click.prevent="smoothScroll('#reject-feedback')" class="sidebar-link text-sm hover:text-yellow-400 transition cursor-pointer">❌ Reject & Feedback</a></li>
                </ul>
            </div>

            <!-- 3. SUBMISSION & LOCKING -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-lock"></i> Submission & Locking
                </h3>
                <ul class="space-y-2">
                    <li><a href="#lock-status" @click.prevent="smoothScroll('#lock-status')" class="sidebar-link text-sm hover:text-green-400 transition cursor-pointer">🔒 Lock Status</a></li>
                    <li><a href="#submit-marks" @click.prevent="smoothScroll('#submit-marks')" class="sidebar-link text-sm hover:text-green-400 transition cursor-pointer">📤 Submit Marks</a></li>
                    <li><a href="#unlock-admin" @click.prevent="smoothScroll('#unlock-admin')" class="sidebar-link text-sm hover:text-green-400 transition cursor-pointer">(Admin) Unlock</a></li>
                    <li><a href="#submission-history" @click.prevent="smoothScroll('#submission-history')" class="sidebar-link text-sm hover:text-green-400 transition cursor-pointer">📜 History</a></li>
                </ul>
            </div>

            <!-- 4. REPORTS & EXPORTS -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-file-alt"></i> Reports & Exports
                </h3>
                <ul class="space-y-2">
                    <li><a href="#scoresheets" @click.prevent="smoothScroll('#scoresheets')" class="sidebar-link text-sm hover:text-purple-400 transition cursor-pointer">📄 Scoresheets (PDF)</a></li>
                    <li><a href="#csv-export" @click.prevent="smoothScroll('#csv-export')" class="sidebar-link text-sm hover:text-purple-400 transition cursor-pointer">📊 CSV Export</a></li>
                    <li><a href="#analytics" @click.prevent="smoothScroll('#analytics')" class="sidebar-link text-sm hover:text-purple-400 transition cursor-pointer">📈 Analytics</a></li>
                    <li><a href="#summary-report" @click.prevent="smoothScroll('#summary-report')" class="sidebar-link text-sm hover:text-purple-400 transition cursor-pointer">📋 Summary Report</a></li>
                </ul>
            </div>

            <!-- 5. MONITORING & AUDIT -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-clock"></i> Monitoring & Audit
                </h3>
                <ul class="space-y-2">
                    <li><a href="#lifecycle-dashboard" @click.prevent="smoothScroll('#lifecycle-dashboard')" class="sidebar-link text-sm hover:text-blue-300 transition cursor-pointer">📊 Lifecycle Dashboard</a></li>
                    <li><a href="#change-log" @click.prevent="smoothScroll('#change-log')" class="sidebar-link text-sm hover:text-blue-300 transition cursor-pointer">📝 Change Log</a></li>
                    <li><a href="#audit-trail" @click.prevent="smoothScroll('#audit-trail')" class="sidebar-link text-sm hover:text-blue-300 transition cursor-pointer">🔍 Audit Trail</a></li>
                    <li><a href="#activity-log" @click.prevent="smoothScroll('#activity-log')" class="sidebar-link text-sm hover:text-blue-300 transition cursor-pointer">👥 Activity Log</a></li>
                </ul>
            </div>

            <!-- 6. ADMINISTRATION -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fas fa-cog"></i> Administration
                </h3>
                <ul class="space-y-2">
                    <li><a href="#configuration" @click.prevent="smoothScroll('#configuration')" class="sidebar-link text-sm hover:text-indigo-400 transition cursor-pointer">⚙️ Configuration</a></li>
                    <li><a href="#permissions" @click.prevent="smoothScroll('#permissions')" class="sidebar-link text-sm hover:text-indigo-400 transition cursor-pointer">🔐 Permissions</a></li>
                    <li><a href="#batch-management" @click.prevent="smoothScroll('#batch-management')" class="sidebar-link text-sm hover:text-indigo-400 transition cursor-pointer">📦 Batch Management</a></li>
                    <li><a href="#system-logs" @click.prevent="smoothScroll('#system-logs')" class="sidebar-link text-sm hover:text-indigo-400 transition cursor-pointer">🖥️ System Logs</a></li>
                </ul>
            </div>

            <hr class="my-6 border-gray-700">
            <p class="text-xs text-gray-500 text-center">ACSEE Mark Entry v1.0</p>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col">
        <!-- Page Header -->
        <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-[140px] z-40 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-800">ACSEE Mark Entry</h1>
        </div>

        <!-- Main Content -->
        <div class="px-8 py-8 flex-1 overflow-y-auto">
            <div class="space-y-6">

            <!-- UPLOAD SECTION -->
            <div id="upload" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-lg font-bold text-gray-800 mb-4">1. Select Context</h2>
                
                <div class="grid grid-cols-12 gap-3 items-start">
                     <!-- Exam Year -->
                     <div class="col-span-1 flex flex-col h-full">
                         <label class="block text-sm font-semibold text-gray-700 mb-2">Year *</label>
                         <select 
                             x-model="examYear"
                             @change="onContextChange()"
                             class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm h-10"
                         >
                             <option value="">Select Year</option>
                             <template x-for="year in examYears" :key="year.id">
                                 <option :value="year.year_label" x-text="year.year_label"></option>
                             </template>
                         </select>
                     </div>

                    <!-- Region -->
                    <div class="col-span-1 flex flex-col h-full">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Region</label>
                        <div class="relative flex-1 flex flex-col" @click.outside="regionOpen = false">
                            <button 
                                @click="regionOpen = !regionOpen"
                                class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t text-sm h-10"
                            >
                                <span x-text="selectedRegion ? regions.find(r => r.id == selectedRegion)?.name : 'All Regions'" class="text-gray-700 whitespace-nowrap"></span>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                            <div x-show="regionOpen" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                                <input 
                                    x-model="regionSearch"
                                    type="text"
                                    placeholder="Search regions..."
                                    class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                                >
                                <div class="max-h-48 overflow-y-auto">
                                    <div @click="selectedRegion = ''; regionOpen = false; onRegionChange()" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                        All Regions
                                    </div>
                                    <template x-for="region in regions.filter(r => r.name.toLowerCase().includes(regionSearch.toLowerCase()))" :key="region.id">
                                        <div 
                                            @click="selectedRegion = region.id; regionOpen = false; onRegionChange()"
                                            :class="selectedRegion == region.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                            class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                            x-text="region.name"
                                        ></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- District -->
                    <div class="col-span-2 flex flex-col h-full">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">District</label>
                        <div class="relative flex-1 flex flex-col" @click.outside="districtOpen = false">
                            <button 
                                @click="selectedRegion && (districtOpen = !districtOpen)"
                                :disabled="!selectedRegion"
                                class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t text-sm h-10 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
                            >
                                <span x-text="selectedDistrict ? (filteredDistricts.find(d => d.id == selectedDistrict)?.name || 'Unknown District') : (selectedRegion ? 'All Districts' : 'Select Region First')" class="text-gray-700 whitespace-nowrap text-sm"></span>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                            <div x-show="districtOpen && selectedRegion" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                                <input 
                                    x-model="districtSearch"
                                    type="text"
                                    placeholder="Search districts..."
                                    class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                                >
                                <div class="max-h-48 overflow-y-auto">
                                    <div @click="selectedDistrict = ''; districtOpen = false; onDistrictChange()" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                        All Districts
                                    </div>
                                    <template x-for="district in filteredDistricts.filter(d => d.name.toLowerCase().includes(districtSearch.toLowerCase()))" :key="district.id">
                                        <div 
                                            @click="selectedDistrict = district.id; districtOpen = false; onDistrictChange()"
                                            :class="selectedDistrict == district.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                            class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                            x-text="district.name"
                                        ></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- School -->
                    <div class="col-span-3 flex flex-col h-full">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">School</label>
                        <div class="relative flex-1 flex flex-col" @click.outside="schoolOpen = false">
                            <button 
                                @click="selectedDistrict && (schoolOpen = !schoolOpen)"
                                :disabled="!selectedDistrict"
                                class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t text-sm h-10 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
                            >
                                <span x-text="selectedSchool ? (filteredSchools.find(s => s.id == selectedSchool)?.code + ' - ' + filteredSchools.find(s => s.id == selectedSchool)?.name) : (selectedDistrict ? 'All Schools' : 'Select District First')" class="text-gray-700 whitespace-nowrap text-sm"></span>
                                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                            </button>
                            <div x-show="schoolOpen && selectedDistrict" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col">
                                <input 
                                    x-model="schoolSearch"
                                    type="text"
                                    placeholder="Search schools..."
                                    class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                                >
                                <div class="max-h-48 overflow-y-auto">
                                    <div @click="selectedSchool = ''; schoolOpen = false; onContextChange()" class="px-3 py-2 hover:bg-blue-500 hover:text-white cursor-pointer text-sm transition-colors">
                                        All Schools
                                    </div>
                                    <template x-for="school in filteredSchools.filter(s => (s.code + ' ' + s.name).toLowerCase().includes(schoolSearch.toLowerCase()))" :key="school.id">
                                        <div 
                                            @click="selectedSchool = school.id; schoolOpen = false; onContextChange()"
                                            :class="selectedSchool == school.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                            class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                            x-text="school.code + ' - ' + school.name"
                                        ></div>
                                    </template>
                                    <template x-if="filteredSchools.filter(s => (s.code + ' ' + s.name).toLowerCase().includes(schoolSearch.toLowerCase())).length === 0">
                                        <div class="px-3 py-2 text-gray-500 text-sm">
                                            No schools found
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subject (Dynamically filtered by school) -->
                    <div class="col-span-4 flex flex-col h-full">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Subject *</label>
                        <div class="relative flex-1 flex flex-col" @click.outside="subjectOpen = false">
                            <button 
                                @click="selectedSchool && !subjectLoading && (subjectOpen = !subjectOpen)"
                                :disabled="!selectedSchool"
                                :class="!selectedSchool ? 'bg-gray-100 cursor-not-allowed text-gray-400' : 'hover:bg-gray-50'"
                                class="w-full px-3 py-2 border border-gray-300 text-left bg-white transition-colors flex justify-between items-center rounded-t text-sm h-10 disabled:bg-gray-100 disabled:text-gray-400"
                            >
                                <span x-show="!subjectLoading" x-text="selectedSubject ? filteredSubjects.find(s => s.id == selectedSubject)?.code : (selectedSchool ? 'Select Subject' : 'Select School First')" class="text-gray-700 whitespace-nowrap text-sm"></span>
                                <span x-show="subjectLoading" class="text-gray-500 text-sm italic flex items-center gap-2">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </span>
                                <i class="fas fa-chevron-down text-xs" :class="!selectedSchool ? 'text-gray-400' : 'text-gray-500'"></i>
                            </button>
                            
                            <!-- Helper text showing candidate info -->
                                              <div x-show="selectedSchool && !subjectLoading && filteredSubjects.length > 0" class="bg-blue-50 border-t border-blue-200 px-3 py-1.5 text-xs text-blue-700 flex items-center gap-2">
                                                  <i class="fas fa-info-circle flex-shrink-0"></i>
                                                  <span x-text="subjectFilterMessage"></span>
                                              </div>
                                             
                                             <!-- No candidates message -->
                                             <div x-show="selectedSchool && !subjectLoading && filteredSubjects.length === 0 && !yearIsLocked" class="bg-yellow-50 border-t border-yellow-200 px-3 py-1.5 text-xs text-yellow-700 flex items-center gap-2">
                                                 <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
                                                 <span x-text="subjectFilterMessage || 'No ACSEE candidates registered for the selected year.'"></span>
                                             </div>

                                             <!-- Year is locked message -->
                                             <div x-show="yearIsLocked" class="bg-red-50 border-t border-red-200 px-3 py-1.5 text-xs text-red-700 flex items-center gap-2">
                                                 <i class="fas fa-lock flex-shrink-0"></i>
                                                 <span>Year <span x-text="examYear"></span> is locked. Mark entry is disabled.</span>
                                             </div>

                            <!-- Dropdown menu -->
                            <div x-show="subjectOpen && selectedSchool && filteredSubjects.length > 0" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col shadow-lg">
                                <input 
                                    x-model="subjectSearch"
                                    type="text"
                                    placeholder="Search subjects..."
                                    class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                                >
                                <div class="max-h-48 overflow-y-auto">
                                    <template x-for="subject in filteredSubjects.filter(s => (s.code + ' ' + s.name).toLowerCase().includes(subjectSearch.toLowerCase()))" :key="subject.id">
                                        <div 
                                            @click="selectedSubject = subject.id; subjectOpen = false; onSubjectChange()"
                                            :class="selectedSubject == subject.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                            class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                            x-text="subject.code + ' - ' + subject.name"
                                        ></div>
                                    </template>
                                    <template x-if="filteredSubjects.filter(s => (s.code + ' ' + s.name).toLowerCase().includes(subjectSearch.toLowerCase())).length === 0">
                                        <div class="px-3 py-2 text-gray-500 text-sm">
                                            No subjects match your search
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <div class="col-span-1 flex items-end h-full">
                        <button type="button"
                            @click="resetContext()"
                            class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium text-sm rounded-lg transition-colors h-10"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div id="csv-tab" class="bg-white rounded-lg shadow border-b border-gray-200 scroll-mt-32">
                <div class="flex gap-8 px-6">
                    <button type="button" @click="importMode = 'single'" :class="importMode === 'single' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="py-4 font-medium transition-colors">
                        <i class="fas fa-file-csv mr-2"></i>Single Subject CSV
                    </button>
                    <button type="button" @click="importMode = 'schoolBulk'" :class="importMode === 'schoolBulk' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="py-4 font-medium transition-colors">
                        <i class="fas fa-box mr-2"></i>School Bulk ZIP
                    </button>
                    <button type="button" @click="importMode = 'district'" :class="importMode === 'district' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'" class="py-4 font-medium transition-colors">
                        <i class="fas fa-archive mr-2"></i>District Bulk ZIP
                    </button>
                </div>
            </div>

            <!-- CONTENT: Single School or District Bulk -->

            <!-- Upload Section (Single Subject) -->
            <template x-if="importMode === 'single'">
            <div id="csv-upload" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-lg font-bold text-gray-800 mb-4">2. Single Subject Mark Upload</h2>
                
                <div class="space-y-4">
                    <!-- Template Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm text-blue-900 mb-2">
                            <strong>Important Instructions for Single Subject CSV:</strong>
                        </p>
                        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                            <li><strong>Step 1:</strong> Select Year, Region, District, School, and Subject from the fields above</li>
                            <li><strong>Step 2:</strong> Download the CSV template - it will contain only eligible candidates for your selected school and subject</li>
                            <li><strong>Step 3:</strong> Fill in the candidate marks in the template (all marks must be numeric 0-100)</li>
                            <li><strong>CRITICAL:</strong> Do NOT modify the header row (index_number, sex, paper columns, etc.)</li>
                            <li><strong>CRITICAL:</strong> Do NOT add or remove candidate rows from the template</li>
                            <li><strong>CRITICAL:</strong> Do NOT change the CSV file name or structure</li>
                            <li><strong>Step 4:</strong> Upload the completed CSV file using the upload area below</li>
                        </ul>
                    </div>

                    <!-- Download Template, Print Scoresheet, Bulk Export Buttons -->
                    <div class="flex gap-2 flex-wrap">
                        <!-- Single Subject Mark Template -->
                         <button type="button"
                             @click="downloadTemplate()"
                             :disabled="!selectedSubject"
                             class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                         >
                             <i class="fas fa-download"></i> Mark Template (CSV)
                         </button>

                         <!-- Single Subject Scoresheet -->
                         <button type="button"
                             @click="printScoresheet()"
                             :disabled="!selectedSubject"
                             class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                         >
                             <i class="fas fa-file-pdf"></i> Single Scoresheet (PDF)
                         </button>

                         <!-- School Scoresheets -->
                         <button type="button"
                             @click="bulkExport()"
                             :disabled="!selectedSchool || !examYear"
                             class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                         >
                             <i class="fas fa-file-pdf"></i> School Scoresheets (ZIP)
                         </button>

                         <!-- School Mark Templates -->
                         <button type="button"
                             @click="downloadBulkCsv()"
                             :disabled="!selectedSchool || !examYear || !filteredSubjects.length"
                             :class="bulkCsvLoading ? 'opacity-75 cursor-wait' : ''"
                             class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                         >
                             <i :class="bulkCsvLoading ? 'fas fa-spinner fa-spin' : 'fas fa-download'"></i>
                             <span x-text="bulkCsvLoading ? 'Preparing...' : 'School Mark Templates (ZIP)'"></span>
                         </button>

                         <!-- District Mark Templates -->
                         <button type="button"
                             @click="downloadDistrictBulkCsv()"
                             :disabled="!selectedDistrict || selectedDistrict === '' || !examYear"
                             :class="districtBulkCsvLoading ? 'opacity-75 cursor-wait' : ''"
                             class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                             :title="!selectedDistrict || selectedDistrict === '' ? 'Select a specific district (not All Districts)' : ''"
                         >
                             <i :class="districtBulkCsvLoading ? 'fas fa-spinner fa-spin' : 'fas fa-download'"></i>
                             <span x-text="districtBulkCsvLoading ? 'Preparing...' : 'District Mark Templates (ZIP)'"></span>
                         </button>

                         <!-- District Scoresheets -->
                         <button type="button"
                             @click="downloadDistrictBulkScoresheet()"
                             :disabled="!selectedDistrict || selectedDistrict === '' || !examYear"
                             :class="districtBulkScoresheetLoading ? 'opacity-75 cursor-wait' : ''"
                             class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                             :title="!selectedDistrict || selectedDistrict === '' ? 'Select a specific district (not All Districts)' : ''"
                         >
                             <i :class="districtBulkScoresheetLoading ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'"></i>
                             <span x-text="districtBulkScoresheetLoading ? 'Preparing...' : 'District Scoresheets (ZIP)'"></span>
                         </button>

                        <span class="text-xs text-gray-500 flex items-center" x-show="!selectedSubject && !selectedSchool">
                            Select subject for single scoresheet, or school + year for bulk export
                        </span>
                    </div>

                    <!-- CSV Upload Area -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer" @click="document.getElementById('csvInput').click()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                        <p class="text-gray-700 font-medium">Click to upload or drag and drop</p>
                        <p class="text-sm text-gray-500">CSV file (max. 5MB)</p>
                        <input 
                            id="csvInput"
                            type="file"
                            accept=".csv,.txt"
                            @change="handleFileSelect"
                            class="hidden"
                        >
                    </div>

                    <!-- Selected File Info -->
                    <template x-if="selectedFile">
                        <div class="bg-gray-50 rounded-lg p-4 mt-4">
                            <p class="text-sm text-gray-700 mb-3">
                                <strong>Selected file:</strong> <span x-text="selectedFile?.name" class="text-blue-600 font-semibold"></span>
                            </p>
                            <button type="button"
                                @click="uploadFile()"
                                :disabled="uploading"
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <template x-if="!uploading">
                                    <><i class="fas fa-upload"></i> Upload Subject Marks</>
                                </template>
                                <template x-if="uploading">
                                    <><i class="fas fa-spinner fa-spin"></i> Uploading...</>
                                </template>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Import Result -->
            <div x-show="importResult" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Import Summary</h2>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <div class="text-sm text-gray-600">Total Records</div>
                            <div class="text-2xl font-bold text-blue-700" x-text="importResult?.batch?.total_records || 0"></div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="text-sm text-gray-600">Valid Records</div>
                            <div class="text-2xl font-bold text-green-700" x-text="importResult?.batch?.valid_records || 0"></div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                            <div class="text-sm text-gray-600">Errors</div>
                            <div class="text-2xl font-bold text-red-700" x-text="importResult?.batch?.error_records || 0"></div>
                        </div>
                        <div :class="importResult?.batch?.error_records === 0 ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'" class="rounded-lg p-4 border">
                            <div class="text-sm text-gray-600">Status</div>
                            <div :class="importResult?.batch?.error_records === 0 ? 'text-green-700' : 'text-yellow-700'" class="text-lg font-bold" x-text="importResult?.batch?.error_records === 0 ? 'Ready' : 'Review Errors'"></div>
                        </div>
                    </div>
                                <span :class="bulkZipPreview.is_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-3 py-1 rounded-full text-xs font-medium">
                                    <span x-text="bulkZipPreview.is_valid ? '✓ Valid' : '✗ Invalid'"></span>
                                </span>
                            </div>

                            <!-- School & Year Info -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">School</p>
                                    <p class="font-semibold text-gray-800" x-text="bulkZipPreview.school"></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Year</p>
                                    <p class="font-semibold text-gray-800" x-text="bulkZipPreview.exam_year"></p>
                                </div>
                            </div>

                            <!-- Subjects Table -->
                            <div>
                                <p class="text-gray-600 text-sm mb-2">Subjects (<span x-text="bulkZipPreview.total_files"></span>)</p>
                                <div class="max-h-48 overflow-y-auto rounded border border-gray-200">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Code</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Subject</th>
                                                <th class="px-3 py-2 text-right font-semibold text-gray-700">Candidates</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="subject in bulkZipPreview.subjects" :key="subject.subject_code">
                                                <tr class="border-t border-gray-200 hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-gray-800" x-text="subject.subject_code"></td>
                                                    <td class="px-3 py-2 text-gray-700" x-text="subject.subject_name"></td>
                                                    <td class="px-3 py-2 text-right text-gray-600" x-text="subject.candidates"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Total Candidates -->
                            <div class="bg-blue-50 rounded p-3 flex justify-between items-center">
                                <span class="text-gray-700 font-medium">Total Candidates</span>
                                <span class="text-lg font-bold text-blue-600" x-text="bulkZipPreview.total_candidates"></span>
                            </div>

                            <!-- Issues Warning -->
                            <template x-if="bulkZipPreview.issues.length > 0">
                                <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                                    <p class="text-yellow-800 font-medium text-sm mb-2">Issues Found:</p>
                                    <ul class="text-sm text-yellow-700 list-disc list-inside space-y-1">
                                        <template x-for="issue in bulkZipPreview.issues" :key="issue">
                                            <li x-text="issue"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <!-- Start Import Button -->
                            <button 
                                @click="startBulkImport()"
                                :disabled="!bulkZipPreview.is_valid || !selectedSchool || !examYear"
                                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Start Import
                            </button>
                        </div>

                        <!-- Import Progress -->
                        <div x-show="bulkImportId" class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-gray-800">Import Progress</h3>
                                <span :class="bulkImportProgress.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'" class="px-3 py-1 rounded-full text-xs font-medium" x-text="bulkImportProgress.status"></span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Progress</span>
                                    <span class="text-gray-800 font-semibold" x-text="bulkImportProgress.progress_percentage + '%'"></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-blue-600 h-3 rounded-full transition-all" :style="'width: ' + bulkImportProgress.progress_percentage + '%'"></div>
                                </div>
                            </div>

                            <!-- File Status Table -->
                            <div>
                                <p class="text-gray-600 text-sm mb-2">Files</p>
                                <div class="space-y-2">
                                    <template x-for="file in bulkImportProgress.files" :key="file.subject_code">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                            <div class="flex items-center gap-3 flex-1">
                                                <span :class="file.status === 'success' ? 'text-green-600' : file.status === 'failed' ? 'text-red-600' : 'text-blue-600'" class="font-semibold">
                                                    <i :class="file.status === 'success' ? 'fas fa-check-circle' : file.status === 'failed' ? 'fas fa-times-circle' : 'fas fa-spinner fa-spin'"></i>
                                                </span>
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-800" x-text="file.subject_code"></p>
                                                    <p class="text-xs text-gray-600" x-text="file.rows_success + ' / ' + file.rows_total + ' rows'"></p>
                                                </div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-600" x-text="file.success_rate + '%'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Summary Stats -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-green-50 rounded p-3">
                                    <p class="text-green-700 text-xs font-semibold">Successful</p>
                                    <p class="text-xl font-bold text-green-600" x-text="bulkImportProgress.summary.successful_candidates"></p>
                                </div>
                                <div class="bg-red-50 rounded p-3">
                                    <p class="text-red-700 text-xs font-semibold">Failed</p>
                                    <p class="text-xl font-bold text-red-600" x-text="bulkImportProgress.summary.failed_candidates"></p>
                                </div>
                                <div class="bg-blue-50 rounded p-3">
                                    <p class="text-blue-700 text-xs font-semibold">Total</p>
                                    <p class="text-xl font-bold text-blue-600" x-text="bulkImportProgress.summary.total_candidates"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected File Info -->
                    <div x-show="selectedFile" class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-700">
                            <strong>Selected file:</strong> <span x-text="selectedFile?.name"></span>
                        </p>
                        <button 
                            @click="uploadFile()"
                            :disabled="uploading"
                            class="mt-3 px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span x-show="!uploading"><i class="fas fa-upload"></i> Upload Subject Marks</span>
                            <span x-show="uploading"><i class="fas fa-spinner animate-spin"></i> Uploading...</span>
                        </button>
                    </div>
                </div>

            <!-- Import Result -->
            <div x-show="importResult" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Import Summary</h2>
                
                <div class="space-y-4">
                    <!-- Status -->
                    <div class="grid grid-cols-4 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                            <div class="text-sm text-gray-600">Total Records</div>
                            <div class="text-2xl font-bold text-blue-700" x-text="importResult?.batch?.total_records || 0"></div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                            <div class="text-sm text-gray-600">Valid Records</div>
                            <div class="text-2xl font-bold text-green-700" x-text="importResult?.batch?.valid_records || 0"></div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                            <div class="text-sm text-gray-600">Errors</div>
                            <div class="text-2xl font-bold text-red-700" x-text="importResult?.batch?.error_records || 0"></div>
                        </div>
                        <div class="rounded-lg p-4 border" :class="importResult?.batch?.error_records === 0 ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'">
                            <div class="text-sm text-gray-600">Status</div>
                            <div class="text-lg font-bold" :class="importResult?.batch?.error_records === 0 ? 'text-green-700' : 'text-yellow-700'" x-text="importResult?.batch?.error_records === 0 ? 'Ready' : 'Review Errors'"></div>
                        </div>
                    </div>

                    <!-- Error Report Download -->
                    <div x-show="importResult?.batch?.error_records > 0" class="flex gap-2">
                        <button type="button"
                            @click="downloadErrorReport()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-lg transition-colors flex items-center gap-2"
                        >
                            <i class="fas fa-download"></i> Download Error Report
                        </button>
                        <p class="text-sm text-gray-600 flex items-center">
                            Review and fix errors, then re-upload
                        </p>
                    </div>

                    <!-- Lock Batch Button -->
                     <div x-show="importResult?.batch?.error_records === 0" class="flex gap-2">
                         <button type="button"
                             @click="lockBatch()"
                             class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium text-sm rounded-lg transition-colors flex items-center gap-2"
                         >
                             <i class="fas fa-lock"></i> Lock Batch (No Changes Allowed)
                         </button>
                        <p class="text-sm text-gray-600 flex items-center">
                            Lock batch to prevent further modifications
                        </p>
                    </div>
                </div>
            </div>
            </template>

            <!-- School Bulk ZIP Section -->
            <div id="school-bulk" x-show="importMode === 'schoolBulk'" class="space-y-6 scroll-mt-32">
                <!-- Upload Section -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">📦 Upload School Bulk Marks ZIP</h2>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Exam Year -->
                         <div>
                             <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                             <select 
                                 x-model="schoolBulkExamYear"
                                 @change="onSchoolBulkExamYearChange()"
                                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm h-10"
                             >
                                 <option value="">Select Exam Year</option>
                                 <template x-for="year in examYears" :key="year.id">
                                     <option :value="year.year_label" x-text="year.year_label"></option>
                                 </template>
                             </select>
                         </div>

                        <!-- School -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">School *</label>
                            <div class="relative" @click.outside="schoolBulkSchoolOpen = false">
                                <button 
                                    @click="!schoolBulkExamYear ? (schoolBulkSchoolOpen = false) : (schoolBulkSchoolOpen = !schoolBulkSchoolOpen)"
                                    :disabled="!schoolBulkExamYear"
                                    class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t text-sm h-10 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
                                >
                                    <span x-text="schoolBulkId ? filteredSchoolBulkSchools.find(s => s.id == schoolBulkId)?.name : (schoolBulkExamYear ? 'Select School' : 'Select Exam Year First')" class="text-gray-700 whitespace-nowrap"></span>
                                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                </button>
                                <div x-show="schoolBulkSchoolOpen && schoolBulkExamYear" class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-50 rounded-b flex flex-col shadow-lg">
                                    <input 
                                        x-model="schoolBulkSchoolSearch"
                                        type="text"
                                        placeholder="Search schools..."
                                        class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
                                    >
                                    <div class="max-h-48 overflow-y-auto">
                                        <template x-if="filteredSchoolBulkSchools.length === 0">
                                            <div class="px-3 py-2 text-gray-500 text-sm">
                                                No schools with ACSEE candidates
                                            </div>
                                        </template>
                                        <template x-for="school in filteredSchoolBulkSchools.filter(s => (s.code + ' ' + s.name).toLowerCase().includes(schoolBulkSchoolSearch.toLowerCase()))" :key="school.id">
                                            <div 
                                                @click="schoolBulkId = school.id; schoolBulkSchoolOpen = false"
                                                :class="schoolBulkId == school.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                                                class="px-3 py-2 cursor-pointer text-sm transition-colors"
                                                x-text="school.code + ' - ' + school.name"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="mt-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ZIP File *</label>
                        <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer" @click="$refs.schoolZipInput.click()" @dragover="schoolDragOver = true" @dragleave="schoolDragOver = false" @drop="handleSchoolFileDrop($event)" :class="schoolDragOver && 'border-blue-500 bg-blue-50'">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600 font-medium">Click or drag ZIP file here</p>
                            <p class="text-gray-400 text-sm mt-1">Format: SCHOOL_CODE_YEAR.zip</p>
                            <input type="file" @change="handleSchoolFileSelect($event)" accept=".zip" x-ref="schoolZipInput" hidden>
                        </div>
                        <p x-show="selectedSchoolZipFile" class="mt-2 text-sm text-gray-600">
                            <i class="fas fa-check text-green-600 mr-1"></i>
                            <span x-text="`Selected: ${selectedSchoolZipFile?.name}`"></span>
                        </p>
                    </div>

                    <!-- Actions -->
                     <div class="mt-6 flex gap-3">
                         <button type="button" @click="previewSchoolZip()" :disabled="!selectedSchoolZipFile || !schoolBulkExamYear || !schoolBulkId" class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                             <i class="fas fa-eye"></i> Preview
                         </button>
                         <button type="button" @click="startSchoolBulkImport()" :disabled="!selectedSchoolZipFile || !schoolBulkExamYear || !schoolBulkId || !schoolBulkPreviewLoaded" class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                             <i class="fas fa-play"></i> Start Import
                         </button>
                     </div>
                </div>

                <!-- Preview Section -->
                <template x-if="schoolBulkPreviewLoaded">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Preview</h3>
                        
                        <div x-show="!schoolBulkPreview.is_valid" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-red-800 mb-2">⚠️ Validation Issues Found:</h4>
                            <ul class="space-y-1 text-red-700 text-sm">
                                <template x-for="issue in schoolBulkPreview.issues" :key="issue">
                                    <li><i class="fas fa-times-circle"></i> <span x-text="issue"></span></li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="schoolBulkPreview.is_valid" class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <p class="text-green-800"><i class="fas fa-check-circle"></i> ZIP is valid and ready to import</p>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Subjects</p>
                                <p class="text-3xl font-bold text-blue-600" x-text="schoolBulkPreview.total_files"></p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Candidates</p>
                                <p class="text-3xl font-bold text-green-600" x-text="schoolBulkPreview.total_candidates?.toLocaleString()"></p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Signed</p>
                                <p x-text="schoolBulkPreview.is_signed ? '✅ Yes' : '❌ No'" class="text-lg font-bold"></p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="font-semibold text-gray-800 mb-3">Subjects in ZIP:</h4>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <template x-for="subject in schoolBulkPreview.subjects" :key="subject.subject_code">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="font-medium text-gray-800" x-text="`${subject.subject_code} - ${subject.candidates} candidates`"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Import Progress Section -->
                <template x-if="schoolBulkImportInProgress">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">⏳ Import Progress</h3>
                        
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-sm font-semibold text-gray-700">Overall Progress</p>
                                <p class="text-sm font-bold text-blue-600" x-text="`${schoolBulkProgress.progress_percentage}%`"></p>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full" :style="`width: ${schoolBulkProgress.progress_percentage}%`"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div>
                                <p class="text-gray-600 text-sm">Total Files</p>
                                <p class="text-2xl font-bold text-gray-800" x-text="schoolBulkProgress.total_files"></p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Processed</p>
                                <p class="text-2xl font-bold text-blue-600" x-text="schoolBulkProgress.processed_files"></p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Total Candidates</p>
                                <p class="text-2xl font-bold text-green-600" x-text="schoolBulkProgress.summary?.total_candidates?.toLocaleString()"></p>
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-gray-500 text-center">
                            <i class="fas fa-sync-alt animate-spin"></i> Refreshing every 2 seconds...
                        </p>
                    </div>
                </template>

                <!-- Import Complete Section -->
                <template x-if="schoolBulkImportComplete">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">✅ Import Complete</h3>
                        
                        <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
                            <p class="font-bold text-green-800">
                                <i class="fas fa-check-circle"></i>
                                <span x-text="`Status: ${schoolBulkProgress.status.toUpperCase()}`" class="ml-2"></span>
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-green-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Total Imported</p>
                                <p class="text-3xl font-bold text-green-600" x-text="(schoolBulkProgress.summary?.successful_candidates || 0).toLocaleString()"></p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Total Candidates</p>
                                <p class="text-3xl font-bold text-blue-600" x-text="(schoolBulkProgress.summary?.total_candidates || 0).toLocaleString()"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-600 text-sm">Files Processed</p>
                                <p class="text-3xl font-bold text-gray-600" x-text="schoolBulkProgress.processed_files"></p>
                            </div>
                        </div>

                        <button type="button" @click="resetSchoolBulkImport()" class="mt-6 w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                             Import Another ZIP
                         </button>
                    </div>
                </template>
            </div>

            <!-- District Bulk Import Section -->
            <div id="district-bulk" x-show="importMode === 'district'" class="space-y-6 scroll-mt-32">
                    <!-- Upload Section -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">📦 Upload District Marks ZIP</h2>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Exam Year -->
                             <div>
                                 <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                                 <select x-model="districtExamYear" @change="onDistrictExamYearChange()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-10">
                                    <option value="">Select Exam Year</option>
                                    <template x-for="year in examYears" :key="year.id">
                                        <option :value="year.year_label" x-text="year.year_label"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- District -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">District *</label>
                                <select x-model.number="districtId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 h-10">
                                    <option value="">Select District</option>
                                    <template x-for="district in districtBulkList" :key="district.id">
                                        <option :value="district.id" x-text="`${district.code} - ${district.name}`"></option>
                                    </template>
                                </select>
                            </div>

                            <div></div>
                        </div>

                        <!-- File Upload -->
                        <div class="mt-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ZIP File *</label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer" @click="$refs.zipInput.click()" @dragover="dragOver = true" @dragleave="dragOver = false" @drop="handleZipFileDrop($event)" :class="dragOver && 'border-blue-500 bg-blue-50'">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                <p class="text-gray-600 font-medium">Click or drag ZIP file here</p>
                                <p class="text-gray-400 text-sm mt-1">Format: DISTRICT_CODE_YEAR.zip</p>
                                <input type="file" @change="handleZipFileSelect($event)" accept=".zip" x-ref="zipInput" hidden>
                            </div>
                            <p x-show="selectedZipFile" class="mt-2 text-sm text-gray-600">
                                <i class="fas fa-check text-green-600 mr-1"></i>
                                <span x-text="`Selected: ${selectedZipFile?.name}`"></span>
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex gap-3">
                            <button @click="previewZip()" :disabled="!selectedZipFile || !districtExamYear || !districtId" class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button @click="startDistrictImport()" :disabled="!selectedZipFile || !districtExamYear || !districtId || !districtPreviewLoaded" class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg transition-colors font-medium flex items-center gap-2">
                                <i class="fas fa-play"></i> Start Import
                            </button>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <template x-if="districtPreviewLoaded">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Preview</h3>
                            
                            <div x-show="!districtPreview.is_valid" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-red-800 mb-2">⚠️ Validation Issues Found:</h4>
                                <ul class="space-y-1 text-red-700 text-sm">
                                    <template x-for="issue in districtPreview.issues" :key="issue">
                                        <li><i class="fas fa-times-circle"></i> <span x-text="issue"></span></li>
                                    </template>
                                </ul>
                            </div>

                            <div x-show="districtPreview.is_valid" class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                                <p class="text-green-800"><i class="fas fa-check-circle"></i> ZIP is valid and ready to import</p>
                            </div>

                            <div class="grid grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Schools</p>
                                    <p class="text-3xl font-bold text-blue-600" x-text="districtPreview.total_schools"></p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Subjects</p>
                                    <p class="text-3xl font-bold text-purple-600" x-text="districtPreview.total_subjects"></p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Candidates</p>
                                    <p class="text-3xl font-bold text-green-600" x-text="districtPreview.total_candidates?.toLocaleString()"></p>
                                </div>
                                <div class="bg-orange-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Signed</p>
                                    <p x-text="districtPreview.is_signed ? '✅ Yes' : '❌ No'" class="text-lg font-bold"></p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="font-semibold text-gray-800 mb-3">Schools in ZIP:</h4>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    <template x-for="school in districtPreview.schools" :key="school.school_code">
                                        <div class="bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                                            <div>
                                                <p class="font-medium text-gray-800" x-text="`${school.school_code} - ${school.school_name}`"></p>
                                                <p class="text-sm text-gray-600" x-text="`${school.total_subjects} subjects, ${school.total_candidates} candidates`"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Import Progress Section -->
                    <template x-if="districtImportInProgress">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">⏳ Import Progress</h3>
                            
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <p class="text-sm font-semibold text-gray-700">Overall Progress</p>
                                    <p class="text-sm font-bold text-blue-600" x-text="`${districtProgress.progress_percentage}%`"></p>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-blue-600 h-3 rounded-full" :style="`width: ${districtProgress.progress_percentage}%`"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-4 mb-6">
                                <div>
                                    <p class="text-gray-600 text-sm">Total Schools</p>
                                    <p class="text-2xl font-bold text-gray-800" x-text="districtProgress.total_schools"></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Processed</p>
                                    <p class="text-2xl font-bold text-blue-600" x-text="districtProgress.processed_schools"></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Total Candidates</p>
                                    <p class="text-2xl font-bold text-gray-800" x-text="districtProgress.summary?.total_candidates?.toLocaleString()"></p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-sm">Imported</p>
                                    <p class="text-2xl font-bold text-green-600" x-text="districtProgress.summary?.successful_candidates?.toLocaleString()"></p>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-800 mb-3">School Status:</h4>
                                <div class="space-y-2 max-h-72 overflow-y-auto">
                                    <template x-for="school in districtProgress.schools" :key="school.school_id">
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-medium text-gray-800" x-text="`${school.school_code} - ${school.school_name}`"></p>
                                                    <p class="text-sm text-gray-600" x-text="`${school.successful_candidates}/${school.total_candidates} candidates`"></p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800" x-text="school.status.toUpperCase()"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <p class="mt-4 text-xs text-gray-500 text-center">
                                <i class="fas fa-sync-alt animate-spin"></i> Refreshing every 2 seconds...
                            </p>
                        </div>
                    </template>

                    <!-- Import Complete Section -->
                    <template x-if="districtImportComplete">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">✅ Import Complete</h3>
                            
                            <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
                                <p class="font-bold text-green-800">
                                    <i class="fas fa-check-circle"></i>
                                    <span x-text="`Status: ${districtProgress.status.toUpperCase()}`" class="ml-2"></span>
                                </p>
                            </div>

                            <div class="grid grid-cols-4 gap-4 mb-6">
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Successful Schools</p>
                                    <p class="text-3xl font-bold text-green-600" x-text="districtProgress.summary?.successful_schools || 0"></p>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Partial Schools</p>
                                    <p class="text-3xl font-bold text-yellow-600" x-text="districtProgress.summary?.partial_schools || 0"></p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Failed Schools</p>
                                    <p class="text-3xl font-bold text-red-600" x-text="districtProgress.summary?.failed_schools || 0"></p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-gray-600 text-sm">Total Imported</p>
                                    <p class="text-3xl font-bold text-blue-600" x-text="(districtProgress.summary?.successful_candidates || 0).toLocaleString()"></p>
                                </div>
                            </div>

                            <button @click="resetDistrictImport()" class="mt-6 w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                                Import Another ZIP
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ===== MODERATION & REVIEW SECTIONS ===== -->

            <!-- Review Dashboard Section -->
            <section id="moderation-dashboard" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📋 Moderation Dashboard</h2>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <div class="inline-block animate-spin">
                        <i class="fas fa-spinner text-blue-500 text-2xl"></i>
                    </div>
                    <p class="text-gray-600 mt-2">Loading pending batches...</p>
                </div>
                
                <!-- Error State -->
                <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded p-4 mb-4">
                    <p class="text-red-700">⚠️ <span x-text="error"></span></p>
                </div>
                
                <!-- Content -->
                <div x-show="!loading && !error" @click="loadModerationDashboard()">
                    <!-- Stats Bar -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="bg-yellow-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-1">Total Pending</p>
                            <p class="text-2xl font-bold text-yellow-600" x-text="totalBatches"></p>
                        </div>
                        <div class="bg-blue-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-1">Current Page</p>
                            <p class="text-2xl font-bold text-blue-600" x-text="currentPage"></p>
                        </div>
                        <div class="bg-green-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-1">Per Page</p>
                            <p class="text-2xl font-bold text-green-600" x-text="perPage"></p>
                        </div>
                        <div class="bg-purple-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-1">Showing</p>
                            <p class="text-2xl font-bold text-purple-600" x-text="moderationBatches.length"></p>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b">
                                <tr>
                                    <th class="px-4 py-2 text-left">Batch Code</th>
                                    <th class="px-4 py-2 text-left">School</th>
                                    <th class="px-4 py-2 text-left">Subject</th>
                                    <th class="px-4 py-2 text-center">Marks</th>
                                    <th class="px-4 py-2 text-center">Created</th>
                                    <th class="px-4 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="batch in moderationBatches" :key="batch.id">
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 font-mono text-xs" x-text="batch.batch_code"></td>
                                        <td class="px-4 py-2 text-xs" x-text="batch.school?.name || 'N/A'"></td>
                                        <td class="px-4 py-2 text-xs" x-text="batch.subject?.name || 'N/A'"></td>
                                        <td class="px-4 py-2 text-center text-xs" x-text="batch.total_records"></td>
                                        <td class="px-4 py-2 text-center text-xs" x-text="formatDate(batch.created_at)"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div x-show="moderationBatches.length === 0 && !loading" class="text-center py-8 text-gray-500">
                        <p>No batches awaiting moderation</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div x-show="moderationBatches.length > 0" class="flex items-center justify-between mt-4">
                        <button @click="if(currentPage > 1) loadModerationDashboard(currentPage - 1)" 
                                class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                            ← Previous
                        </button>
                        <span class="text-gray-600" x-text="`Page ${currentPage}`"></span>
                        <button @click="loadModerationDashboard(currentPage + 1)" 
                                class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                            Next →
                        </button>
                    </div>
                </div>
            </section>

            <!-- Pending Review Section -->
            <section id="pending-review" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">⏳ Pending Review</h2>
                <p class="text-sm text-gray-600 mb-4">Batches currently awaiting moderation review. Click on a batch to view details and take action.</p>
                
                <!-- Info Alert -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Same data as Review Dashboard above. Click a batch to review details and approve/reject.
                    </p>
                </div>
                
                <!-- Load Button -->
                <div x-show="!loading && moderationBatches.length === 0" class="text-center py-4">
                    <button @click="loadModerationDashboard()" 
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Load Pending Batches
                    </button>
                </div>
                
                <!-- Batch List -->
                <div x-show="!loading && moderationBatches.length > 0" class="space-y-3">
                    <template x-for="batch in moderationBatches" :key="batch.id">
                        <div class="border rounded p-4 hover:bg-gray-50 cursor-pointer transition">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-600">Batch Code</p>
                                    <p class="font-mono font-semibold text-sm" x-text="batch.batch_code"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">School</p>
                                    <p class="text-sm" x-text="batch.school?.name || 'N/A'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Subject</p>
                                    <p class="text-sm" x-text="batch.subject?.name || 'N/A'"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 mt-2 pt-2 border-t">
                                <div>
                                    <p class="text-xs text-gray-600">Total Marks</p>
                                    <p class="text-sm font-semibold" x-text="batch.total_records"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Created</p>
                                    <p class="text-xs text-gray-700" x-text="formatDate(batch.created_at)"></p>
                                </div>
                                <div class="text-right">
                                    <button class="bg-yellow-500 text-white px-3 py-1 rounded text-xs hover:bg-yellow-600">
                                        Review & Moderate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading pending batches...</p>
                </div>
            </section>

            <!-- Approve Marks Section -->
            <section id="approve-marks" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">✅ Approve Marks</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                    <p class="text-green-900 font-semibold">Approve Mark Batches</p>
                    <p class="text-sm text-green-700 mt-2">Review validated marks and approve for processing</p>
                    <p class="text-xs text-green-600 mt-4">Coming in Phase 3C - Week 2</p>
                </div>
            </section>

            <!-- Reject & Feedback Section -->
            <section id="reject-feedback" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">❌ Reject & Feedback</h3>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-900 font-semibold">Return Batches with Feedback</p>
                    <p class="text-sm text-red-700 mt-2">Reject batches and provide feedback for corrections</p>
                    <p class="text-xs text-red-600 mt-4">Coming in Phase 3C - Week 2</p>
                </div>
            </section>

            <!-- ===== SUBMISSION & LOCKING SECTIONS ===== -->

            <!-- Lock Status Section -->
            <section id="lock-status" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">🔒 Lock Status</h2>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading batches ready for submission...</p>
                </div>
                
                <!-- Error State -->
                <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded p-4 mb-4">
                    <p class="text-red-700">⚠️ <span x-text="error"></span></p>
                </div>
                
                <!-- Content -->
                <div x-show="!loading && !error" @click="loadLockStatus()">
                    <!-- Ready Count Badge -->
                    <div class="mb-6 inline-block bg-green-50 border border-green-200 rounded px-4 py-2">
                        <p class="text-sm text-gray-600">Batches Ready to Lock</p>
                        <p class="text-3xl font-bold text-green-600" x-text="readyBatches.length"></p>
                    </div>
                    
                    <!-- Table -->
                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b">
                                <tr>
                                    <th class="px-4 py-2 text-left">Batch Code</th>
                                    <th class="px-4 py-2 text-left">School</th>
                                    <th class="px-4 py-2 text-left">Subject</th>
                                    <th class="px-4 py-2 text-center">Status</th>
                                    <th class="px-4 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="batch in readyBatches" :key="batch.id">
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 font-mono text-xs" x-text="batch.batch_code"></td>
                                        <td class="px-4 py-2 text-xs" x-text="batch.school?.name"></td>
                                        <td class="px-4 py-2 text-xs" x-text="batch.subject?.name"></td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Approved</span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">
                                                Lock & Submit
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div x-show="readyBatches.length === 0" class="text-center py-8 text-gray-500 mt-4">
                        <p>No batches ready for locking</p>
                    </div>
                </div>
            </section>

            <!-- Submit Marks Section -->
            <section id="submit-marks" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">📤 Submit Marks</h3>
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 text-center">
                    <p class="text-indigo-900 font-semibold">Submit Approved Marks</p>
                    <p class="text-sm text-indigo-700 mt-2">Submit locked batches to examination authority</p>
                    <p class="text-xs text-indigo-600 mt-4">Coming in Phase 3C - Week 3</p>
                </div>
            </section>

            <!-- Unlock Admin Section -->
            <section id="unlock-admin" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">🔓 (Admin) Unlock Batches</h3>
                <p class="text-sm text-gray-600 mb-4">Select a submitted batch to unlock for resubmission (admin only)</p>
                
                <!-- Batch Selector -->
                <div class="mb-6 p-4 bg-purple-50 rounded">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Submitted Batch to Unlock</label>
                    <div class="flex gap-2">
                        <input type="number" class="border rounded px-3 py-2 flex-1" 
                               placeholder="Enter batch ID"
                               x-model.number="selectedBatchId">
                        <button @click="if(selectedBatchId) { console.log('Unlock batch:', selectedBatchId); openUnlockBatchModal(selectedBatchId); }" 
                                :disabled="!selectedBatchId"
                                class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Unlock Batch
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div x-show="!submittedBatches || submittedBatches.length === 0" class="bg-gray-50 rounded-lg p-6 text-center">
                    <p class="text-gray-600">No submitted batches available for unlock</p>
                </div>

                <!-- List of Submitted Batches -->
                <div x-show="submittedBatches && submittedBatches.length > 0" class="space-y-3">
                    <div class="text-sm text-gray-600 mb-3">
                        <strong x-text="submittedBatches.length"></strong> submitted batch(es) available:
                    </div>
                    <template x-for="batch in submittedBatches" :key="batch.id">
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        <span x-text="`Batch #${batch.id}`"></span>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span x-text="`School: ${batch.school?.name || 'Unknown'}`"></span>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <span x-text="`Subject: ${batch.subject?.code || 'Unknown'}`"></span>
                                    </p>
                                </div>
                                <button @click="openUnlockBatchModal(batch.id)"
                                        class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded transition">
                                    🔓 Unlock
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Submission History Section -->
            <section id="submission-history" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📜 Submission History</h2>
                
                <!-- Batch Selector -->
                <div class="mb-4 p-4 bg-blue-50 rounded">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch to View Submission History</label>
                    <div class="flex gap-2">
                        <input type="number" class="border rounded px-3 py-2 flex-1" 
                               placeholder="Enter batch ID" @input="selectedBatchId = $el.value">
                        <button @click="if(selectedBatchId) loadSubmissionHistory(selectedBatchId)" 
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Load History
                        </button>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading submission history...</p>
                </div>
                
                <!-- Timeline View -->
                <div x-show="!loading && currentBatch && currentBatch.history.length > 0" class="space-y-4">
                    <div class="text-sm text-gray-600 mb-4">
                        <p><strong>Batch ID:</strong> <span x-text="currentBatch.id" class="font-mono"></span></p>
                    </div>
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-green-500"></div>
                        <div class="ml-6 space-y-4">
                            <template x-for="(entry, index) in currentBatch.history" :key="index">
                                <div class="relative pb-4">
                                    <div class="absolute -left-6 top-2 w-3 h-3 rounded-full bg-blue-500 border-2 border-white"></div>
                                    <div class="bg-gray-50 rounded p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-800" x-text="entry.approval_type || 'Approval'"></p>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <span x-text="entry.approvedByUser?.name || 'System'"></span>
                                                </p>
                                            </div>
                                            <span class="text-xs px-2 py-1 rounded" :class="entry.status === 'locked' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" x-text="entry.status"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2" x-text="formatDate(entry.approved_at)"></p>
                                        <p class="text-sm text-gray-700 mt-2" x-show="entry.approval_notes" x-text="entry.approval_notes"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div x-show="!loading && (!currentBatch || currentBatch.history.length === 0)" class="text-center py-8 text-gray-500">
                    <p>No submission history found. Select a batch to view approval timeline.</p>
                </div>
            </section>

            <!-- ===== REPORTS & EXPORTS SECTIONS ===== -->

            <!-- Scoresheets Section -->
            <section id="scoresheets" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">📄 Scoresheets (PDF)</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <p class="text-blue-900 font-semibold">Generate Scoresheet PDFs</p>
                    <p class="text-sm text-blue-700 mt-2">Create and download PDF scoresheets for students</p>
                    <p class="text-xs text-blue-600 mt-4">Coming in Phase 3C - Week 3</p>
                </div>
            </section>

            <!-- CSV Export Section -->
            <section id="csv-export" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">📊 CSV Export</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                    <p class="text-green-900 font-semibold">Export Marks to CSV</p>
                    <p class="text-sm text-green-700 mt-2">Download marks data in CSV format for analysis</p>
                    <p class="text-xs text-green-600 mt-4">Coming in Phase 3C - Week 3</p>
                </div>
            </section>

            <!-- Analytics Section -->
            <section id="analytics" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-6">📈 Analytics</h2>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading analytics...</p>
                </div>
                
                <!-- Error State -->
                <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded p-4 mb-4">
                    <p class="text-red-700">⚠️ <span x-text="error"></span></p>
                </div>
                
                <!-- Content -->
                <div x-show="!loading && analyticsData" @click="loadAnalytics()">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-blue-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-2">Total Batches</p>
                            <p class="text-2xl font-bold text-blue-600" 
                               x-text="analyticsData.overview.total_batches"></p>
                        </div>
                        <div class="bg-yellow-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-2">Pending Moderation</p>
                            <p class="text-2xl font-bold text-yellow-600" 
                               x-text="analyticsData.overview.pending_moderation"></p>
                        </div>
                        <div class="bg-green-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-2">Approved</p>
                            <p class="text-2xl font-bold text-green-600" 
                               x-text="analyticsData.overview.approved_batches"></p>
                        </div>
                        <div class="bg-purple-50 rounded p-4">
                            <p class="text-xs text-gray-600 mb-2">Submitted</p>
                            <p class="text-2xl font-bold text-purple-600" 
                               x-text="analyticsData.overview.submitted_batches"></p>
                        </div>
                    </div>
                    
                    <!-- Error Statistics -->
                    <div class="bg-red-50 rounded p-4 mb-6">
                        <h3 class="font-semibold text-gray-800 mb-3">Error Statistics</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-600">Total Marks</p>
                                <p class="text-lg font-bold" 
                                   x-text="analyticsData.overview.total_marks_imported"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">With Errors</p>
                                <p class="text-lg font-bold text-red-600" 
                                   x-text="analyticsData.overview.marks_with_errors"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Error Rate</p>
                                <p class="text-lg font-bold" x-show="analyticsData.overview.total_marks_imported > 0"
                                   x-text="`${((analyticsData.overview.marks_with_errors / analyticsData.overview.total_marks_imported) * 100).toFixed(2)}%`"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- By Subject Table -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-800 mb-3">Performance by Subject</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Subject</th>
                                        <th class="px-4 py-2 text-center">Batches</th>
                                        <th class="px-4 py-2 text-center">Errors</th>
                                        <th class="px-4 py-2 text-center">Error Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in analyticsData.bySubject.slice(0, 5)" :key="item.subject">
                                        <tr class="border-b">
                                            <td class="px-4 py-2" x-text="item.subject"></td>
                                            <td class="px-4 py-2 text-center" x-text="item.total_batches"></td>
                                            <td class="px-4 py-2 text-center" x-text="item.error_records"></td>
                                            <td class="px-4 py-2 text-center">
                                                <span x-text="`${item.error_rate}%`"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Summary Report Section -->
            <section id="summary-report" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-6">📋 Summary Report</h2>
                
                <!-- Load Button -->
                <div x-show="!loading && !analyticsData" class="text-center py-4">
                    <button @click="loadAnalytics()" 
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Generate Report
                    </button>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Generating summary report...</p>
                </div>
                
                <!-- Executive Summary -->
                <div x-show="!loading && analyticsData" class="space-y-6">
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Executive Overview</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-blue-50 rounded p-3">
                                <p class="text-xs text-gray-600">Total Batches</p>
                                <p class="text-xl font-bold text-blue-600" x-text="analyticsData.overview.total_batches"></p>
                            </div>
                            <div class="bg-yellow-50 rounded p-3">
                                <p class="text-xs text-gray-600">Pending</p>
                                <p class="text-xl font-bold text-yellow-600" x-text="analyticsData.overview.pending_moderation"></p>
                            </div>
                            <div class="bg-green-50 rounded p-3">
                                <p class="text-xs text-gray-600">Approved</p>
                                <p class="text-xl font-bold text-green-600" x-text="analyticsData.overview.approved_batches"></p>
                            </div>
                            <div class="bg-purple-50 rounded p-3">
                                <p class="text-xs text-gray-600">Submitted</p>
                                <p class="text-xl font-bold text-purple-600" x-text="analyticsData.overview.submitted_batches"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Quality Metrics</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Total Marks Imported</p>
                                <p class="text-2xl font-bold text-blue-600" x-text="analyticsData.overview.total_marks_imported"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Marks with Errors</p>
                                <p class="text-2xl font-bold text-red-600" x-text="analyticsData.overview.marks_with_errors"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Error Rate</p>
                                <p class="text-2xl font-bold text-orange-600" x-show="analyticsData.overview.total_marks_imported > 0"
                                   x-text="`${((analyticsData.overview.marks_with_errors / analyticsData.overview.total_marks_imported) * 100).toFixed(2)}%`"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Subjects by Batch Volume</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Subject</th>
                                        <th class="px-4 py-2 text-center">Batches</th>
                                        <th class="px-4 py-2 text-center">Error Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in analyticsData.bySubject.slice(0, 10)" :key="item.subject">
                                        <tr class="border-b">
                                            <td class="px-4 py-2" x-text="item.subject"></td>
                                            <td class="px-4 py-2 text-center font-semibold" x-text="item.total_batches"></td>
                                            <td class="px-4 py-2 text-center">
                                                <span x-text="`${item.error_rate}%`" :class="item.error_rate > 5 ? 'text-red-600 font-semibold' : 'text-green-600'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== MONITORING & AUDIT SECTIONS ===== -->

            <!-- Lifecycle Dashboard Section -->
            <section id="lifecycle-dashboard" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-6">📊 Lifecycle Dashboard</h2>
                
                <!-- Load Button -->
                <div x-show="!loading && !analyticsData" class="text-center py-4">
                    <button @click="loadAnalytics()" 
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Load Dashboard
                    </button>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading lifecycle dashboard...</p>
                </div>
                
                <!-- Status Distribution -->
                <div x-show="!loading && analyticsData" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Batch Status Distribution</h3>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                            <div class="bg-gray-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Draft</p>
                                <p class="text-xl font-bold text-gray-600" x-text="analyticsData.overview.draft_batches || 0"></p>
                            </div>
                            <div class="bg-blue-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Validated</p>
                                <p class="text-xl font-bold text-blue-600" x-text="analyticsData.overview.validated_batches || 0"></p>
                            </div>
                            <div class="bg-yellow-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Pending</p>
                                <p class="text-xl font-bold text-yellow-600" x-text="analyticsData.overview.pending_moderation"></p>
                            </div>
                            <div class="bg-orange-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Rejected</p>
                                <p class="text-xl font-bold text-orange-600" x-text="analyticsData.overview.rejected_batches || 0"></p>
                            </div>
                            <div class="bg-green-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Approved</p>
                                <p class="text-xl font-bold text-green-600" x-text="analyticsData.overview.approved_batches"></p>
                            </div>
                            <div class="bg-purple-50 rounded p-3 text-center">
                                <p class="text-xs text-gray-600">Submitted</p>
                                <p class="text-xl font-bold text-purple-600" x-text="analyticsData.overview.submitted_batches"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 rounded p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Progress Summary</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-700">Processing Rate</span>
                                <span class="font-semibold" 
                                      x-text="`${(((analyticsData.overview.submitted_batches + analyticsData.overview.approved_batches) / analyticsData.overview.total_batches) * 100).toFixed(1)}%`"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded h-2">
                                <div class="bg-green-500 h-2 rounded" 
                                     :style="`width: ${(((analyticsData.overview.submitted_batches + analyticsData.overview.approved_batches) / analyticsData.overview.total_batches) * 100).toFixed(1)}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Change Log Section -->
            <section id="change-log" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">📝 Change Log</h2>
                
                <!-- Info -->
                <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Summary of all modifications made to marks in a batch. Same data as Audit Trail below with summary view.
                    </p>
                </div>
                
                <!-- Batch Selector -->
                <div class="mb-4 p-4 bg-blue-50 rounded">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch to View Changes</label>
                    <div class="flex gap-2">
                        <input type="number" class="border rounded px-3 py-2 flex-1" 
                               placeholder="Enter batch ID" @input="selectedBatchId = $el.value">
                        <button @click="if(selectedBatchId) loadAuditTrail(selectedBatchId)" 
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Load Changes
                        </button>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div x-show="!loading && auditTrail.length > 0" class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 rounded p-4">
                        <p class="text-xs text-gray-600">Total Changes</p>
                        <p class="text-2xl font-bold text-blue-600" x-text="auditTrail.length"></p>
                    </div>
                    <div class="bg-green-50 rounded p-4">
                        <p class="text-xs text-gray-600">Fields Modified</p>
                        <p class="text-2xl font-bold text-green-600" 
                           x-text="[...new Set(auditTrail.map(c => c.field_name))].length"></p>
                    </div>
                    <div class="bg-purple-50 rounded p-4">
                        <p class="text-xs text-gray-600">Users</p>
                        <p class="text-2xl font-bold text-purple-600" 
                           x-text="[...new Set(auditTrail.map(c => c.changed_by))].length"></p>
                    </div>
                </div>
                
                <!-- Changes Table -->
                <div x-show="!loading && auditTrail.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-4 py-2 text-left">Field</th>
                                <th class="px-4 py-2 text-center">Type</th>
                                <th class="px-4 py-2 text-center">Changed By</th>
                                <th class="px-4 py-2 text-left">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="change in auditTrail.slice(0, 20)" :key="change.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 text-xs font-semibold" x-text="change.field_name"></td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded" 
                                              x-text="change.change_type"></span>
                                    </td>
                                    <td class="px-4 py-2 text-center text-xs" x-text="change.changedByUser?.name || 'System'"></td>
                                    <td class="px-4 py-2 text-xs" x-text="formatDate(change.changed_at)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading & Empty -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading change log...</p>
                </div>
                <div x-show="!loading && auditTrail.length === 0" class="text-center py-8 text-gray-500">
                    <p>No changes found. Select a batch to view change history.</p>
                </div>
            </section>

            <!-- Audit Trail Section -->
            <section id="audit-trail" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">🔍 Audit Trail</h2>
                
                <!-- Batch Selector -->
                <div class="mb-4 p-4 bg-blue-50 rounded">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch to View Changes</label>
                    <div class="flex gap-2">
                        <input type="number" class="border rounded px-3 py-2 flex-1" 
                               placeholder="Enter batch ID" @input="selectedBatchId = $el.value">
                        <button @click="if(selectedBatchId) loadAuditTrail(selectedBatchId)" 
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Load Trail
                        </button>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading audit trail...</p>
                </div>
                
                <!-- Audit Trail Timeline -->
                <div x-show="!loading && auditTrail.length > 0" class="space-y-4">
                    <template x-for="change in auditTrail" :key="change.id">
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        <span x-text="change.field_name"></span>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2" 
                                              x-text="change.change_type"></span>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span x-text="change.changedByUser?.name || 'System'"></span>
                                        <span class="text-xs text-gray-500" x-text="`• ${formatDate(change.changed_at)}`"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-2 text-sm">
                                <p class="text-gray-600">
                                    <span class="line-through text-red-600" x-text="change.old_value"></span>
                                    <span class="ml-2 text-green-600" x-text="change.new_value"></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1" x-show="change.reason" x-text="`Reason: ${change.reason}`"></p>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Empty State -->
                <div x-show="!loading && auditTrail.length === 0" class="text-center py-8 text-gray-500">
                    <p>No audit trail entries found. Select a batch to view changes.</p>
                </div>
            </section>

            <!-- Activity Log Section -->
            <section id="activity-log" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h2 class="text-xl font-bold text-gray-800 mb-4">👥 Activity Log</h2>
                
                <!-- User Selector -->
                <div class="mb-4 p-4 bg-blue-50 rounded">
                    <label class="block text-sm font-medium text-gray-700 mb-2">View Activity For User ID</label>
                    <div class="flex gap-2">
                        <input type="number" class="border rounded px-3 py-2 flex-1" 
                               placeholder="Enter user ID" @input="selectedBatchId = $el.value">
                        <button @click="if(selectedBatchId) loadActivityLog(selectedBatchId)" 
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Load Activity
                        </button>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div x-show="loading" class="text-center py-8">
                    <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
                    <p class="text-gray-600 mt-2">Loading activity log...</p>
                </div>
                
                <!-- Activity List -->
                <div x-show="!loading && activityLog.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b">
                                <tr>
                                    <th class="px-4 py-2 text-left">Timestamp</th>
                                    <th class="px-4 py-2 text-left">Batch</th>
                                    <th class="px-4 py-2 text-left">Field</th>
                                    <th class="px-4 py-2 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="entry in activityLog" :key="entry.id">
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2 text-xs" x-text="formatDate(entry.changed_at)"></td>
                                        <td class="px-4 py-2 text-xs font-mono" x-text="entry.raw_mark_id || 'N/A'"></td>
                                        <td class="px-4 py-2 text-xs" x-text="entry.field_name"></td>
                                        <td class="px-4 py-2 text-xs">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded" x-text="entry.change_type"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div x-show="!loading && activityLog.length === 0" class="text-center py-8 text-gray-500">
                    <p>No activity found. Enter a user ID to view their actions.</p>
                </div>
            </section>

            <!-- ===== ADMINISTRATION SECTIONS ===== -->

            <!-- Configuration Section -->
            <section id="configuration" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">⚙️ Configuration</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <p class="text-blue-900 font-semibold">System Configuration</p>
                    <p class="text-sm text-blue-700 mt-2">Configure mark entry system settings</p>
                    <p class="text-xs text-blue-600 mt-4">Coming in Phase 3C - Week 4</p>
                </div>
            </section>

            <!-- Permissions Section -->
            <section id="permissions" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">🔐 Permissions</h3>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                    <p class="text-green-900 font-semibold">Role & Permission Management</p>
                    <p class="text-sm text-green-700 mt-2">Manage user roles and access permissions</p>
                    <p class="text-xs text-green-600 mt-4">Coming in Phase 3C - Week 4</p>
                </div>
            </section>

            <!-- Batch Management Section -->
            <section id="batch-management" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">📦 Batch Management</h3>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6 text-center">
                    <p class="text-purple-900 font-semibold">Batch Administration</p>
                    <p class="text-sm text-purple-700 mt-2">Manage and archive mark import batches</p>
                    <p class="text-xs text-purple-600 mt-4">Coming in Phase 3C - Week 4</p>
                </div>
            </section>

            <!-- System Logs Section -->
            <section id="system-logs" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
                <h3 class="text-xl font-bold text-gray-800 mb-4">🖥️ System Logs</h3>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <p class="text-gray-900 font-semibold">System Event Logs</p>
                    <p class="text-sm text-gray-700 mt-2">View application logs and error tracking</p>
                    <p class="text-xs text-gray-600 mt-4">Coming in Phase 3C - Week 4</p>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
function markEntryManager() {
     return {
         importMode: 'single', // 'single' or 'district'
         currentYear: new Date().getFullYear(),
         examYear: new Date().getFullYear(),
         yearIsLocked: false,
         yearStatus: null, // 'active', 'locked', 'draft'
         selectedRegion: '',
         selectedDistrict: '',
         selectedSchool: '',
         selectedSubject: '',
         regions: [],
         districts: [],
         schools: [],
         subjects: [],
         examYears: [],
         filteredSubjects: [],
         subjectLoading: false,
         subjectFilterMessage: '',
         candidateCount: 0,
         selectedFile: null,
         uploading: false,
         importResult: null,
        subjectInfo: '',
        bulkCsvLoading: false,
        districtBulkCsvLoading: false,
        districtBulkScoresheetLoading: false,
        // Bulk import states
        importMode: 'single',
        bulkZipPreview: null,
        bulkImportId: null,
        bulkImportProgress: null,
        // Filter dropdown states
        regionOpen: false,
        districtOpen: false,
        schoolOpen: false,
        subjectOpen: false,
        // Filter search states
        regionSearch: '',
        districtSearch: '',
        schoolSearch: '',
        subjectSearch: '',

        // Computed - Filtered Districts
        get filteredDistricts() {
            if (!this.selectedRegion) return this.districts;
            return this.districts.filter(d => d.region_id == this.selectedRegion);
        },
        
        // Computed - Filtered Schools
         get filteredSchools() {
             if (!this.selectedDistrict) return this.schools;
             return this.schools.filter(s => s.district_id == this.selectedDistrict);
         },

         // ========== LOCALSTORAGE PERSISTENCE ==========
         saveContext() {
             const context = {
                 examYear: this.examYear,
                 selectedRegion: this.selectedRegion,
                 selectedDistrict: this.selectedDistrict,
                 selectedSchool: this.selectedSchool,
                 selectedSubject: this.selectedSubject,
                 timestamp: Date.now()
             };
             try {
                 localStorage.setItem('irms_mark_entry_context', JSON.stringify(context));
             } catch (e) {
                 console.warn('Failed to save context to localStorage:', e);
             }
         },

         restoreContext() {
             try {
                 const stored = localStorage.getItem('irms_mark_entry_context');
                 if (stored) {
                     const context = JSON.parse(stored);
                     this.examYear = context.examYear || new Date().getFullYear();
                     this.selectedRegion = context.selectedRegion || '';
                     this.selectedDistrict = context.selectedDistrict || '';
                     this.selectedSchool = context.selectedSchool || '';
                     this.selectedSubject = context.selectedSubject || '';
                     console.log('✓ Context restored from localStorage');
                 }
             } catch (e) {
                 console.warn('Failed to restore context from localStorage:', e);
             }
         },

         clearStoredContext() {
             try {
                 localStorage.removeItem('irms_mark_entry_context');
             } catch (e) {
                 console.warn('Failed to clear localStorage:', e);
             }
         },

         async init() {
              // Restore context from localStorage if available
              this.restoreContext();
              
              await this.loadRegions();
              // Don't load districts/schools upfront - will load on user selection
              // This significantly improves initial page load time
              await this.loadSubjects();
              await this.loadExamYears();
              
              // Only set default exam year if not restored from localStorage
              if (!this.examYear || this.examYear === new Date().getFullYear()) {
                  await this.setDefaultExamYear();
              }
              
              // Load districts and schools if context was restored
              if (this.selectedRegion) {
                  await this.loadDistricts();
              }
              if (this.selectedDistrict) {
                  await this.loadSchools();
              }
              if (this.selectedSchool) {
                  await this.loadFilteredSubjects();
              }
              
              // Set up watchers to auto-save context on changes
              this.$watch('examYear', () => this.saveContext());
              this.$watch('selectedRegion', () => this.saveContext());
              this.$watch('selectedDistrict', () => this.saveContext());
              this.$watch('selectedSchool', () => this.saveContext());
              this.$watch('selectedSubject', () => this.saveContext());
              },

              smoothScroll(selector) {
              const element = document.querySelector(selector);
              if (element) {
                  element.scrollIntoView({
                      behavior: 'smooth',
                      block: 'start'
                  });
              }
          },

        async loadRegions() {
            try {
                const response = await fetch('/api/mark-entry/acsee/regions');
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                this.regions = data.data || [];
            } catch (error) {
                console.error('Error loading regions:', error);
                this.showMessage('Error loading regions: ' + error.message, 'error');
            }
        },

        async loadDistricts() {
             if (!this.selectedRegion) {
                  this.districts = [];
                  return;
             }
             try {
                  const response = await fetch(
                      `/api/mark-entry/acsee/districts?region_id=${this.selectedRegion}`
                  );
                  if (!response.ok) {
                      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                  }
                  const data = await response.json();
                  this.districts = data.data || [];
             } catch (error) {
                  console.error('Error loading districts:', error);
                  this.showMessage('Error loading districts: ' + error.message, 'error');
             }
         },

        async loadSchools() {
             if (!this.selectedDistrict) {
                  this.schools = [];
                  return;
             }
             try {
                  const response = await fetch(
                      `/api/mark-entry/acsee/schools?district_id=${this.selectedDistrict}`
                  );
                  if (!response.ok) {
                      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                  }
                  const data = await response.json();
                  this.schools = data.data || [];
             } catch (error) {
                  console.error('Error loading schools:', error);
                  this.showMessage('Error loading schools: ' + error.message, 'error');
             }
         },

        async onRegionChange() {
             this.regionSearch = '';
             this.selectedDistrict = '';
             this.districtSearch = '';
             this.selectedSchool = '';
             this.schoolSearch = '';
             // Load districts for the selected region
             await this.loadDistricts();
         },

        async onDistrictChange() {
             this.districtSearch = '';
             this.selectedSchool = '';
             this.schoolSearch = '';
             // Load schools for the selected district
             await this.loadSchools();
         },

        async loadSubjects() {
             try {
                  const response = await fetch('/api/mark-entry/acsee/subjects');
                  if (!response.ok) {
                      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                  }
                  const data = await response.json();
                  this.subjects = data.data || [];
             } catch (error) {
                  console.error('Error loading subjects:', error);
                  this.showMessage('Error loading subjects: ' + error.message, 'error');
             }
         },

         async loadExamYears() {
             try {
                  const response = await fetch('/api/exam-years/with-acsee');
                  if (!response.ok) {
                      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                  }
                  const data = await response.json();
                  this.examYears = data.years || [];
                  console.log('✅ Exam years with ACSEE loaded:', this.examYears);
             } catch (error) {
                  console.error('❌ Error loading exam years:', error);
                  this.showMessage('Error loading exam years: ' + error.message, 'error');
             }
         },

         async setDefaultExamYear() {
             try {
                  const response = await fetch('/api/exam-years/active');
                  if (!response.ok) {
                      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                  }
                  const data = await response.json();
                  if (data.active_year) {
                      // Set default exam year from active year
                      this.examYear = data.active_year.year_label;
                      console.log('✓ Default exam year set to:', data.active_year.year_label);
                 }
             } catch (error) {
                 console.error('Error loading active exam year:', error);
                 this.showMessage('Error loading active exam year: ' + error.message, 'error');
             }
         },

         async loadFilteredSubjects() {
            // Load subjects specific to the selected school and exam year
            if (!this.selectedSchool || !this.examYear) {
                this.filteredSubjects = [];
                return;
            }

            this.subjectLoading = true;
            this.selectedSubject = ''; // Reset subject selection
            this.subjectSearch = '';

            try {
                const params = new URLSearchParams({
                    school_id: this.selectedSchool,
                    exam_year: this.examYear,
                });

                const response = await fetch(`/api/mark-entry/acsee/subjects-by-school?${params}`);
                
                // Try to parse JSON only if response headers indicate JSON
                let data = {};
                try {
                    data = await response.json();
                } catch (parseError) {
                    console.error('Invalid JSON response:', parseError);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    throw parseError;
                }

                // Handle success (200 OK)
                if (response.ok && data.success) {
                    this.filteredSubjects = data.data || [];
                    this.candidateCount = data.candidate_count || 0;
                    this.subjectFilterMessage = data.message || '';
                    this.yearIsLocked = false;
                }
                // Handle validation errors (422 Unprocessable Entity)
                else if (response.status === 422) {
                    this.filteredSubjects = [];
                    this.candidateCount = 0;
                    this.subjectFilterMessage = data.message || 'Cannot load subjects for this year.';
                    
                    // Check if year is locked
                    if (data.code === 'YEAR_LOCKED') {
                        this.yearIsLocked = true;
                    } else {
                        this.yearIsLocked = false;
                    }
                    
                    console.warn('Mark entry validation error:', data);
                }
                // Handle other errors
                else if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                else {
                    console.error('Error loading filtered subjects:', data);
                    this.filteredSubjects = [];
                    this.subjectFilterMessage = 'Error loading subjects. Please try again.';
                    this.yearIsLocked = false;
                }
            } catch (error) {
                console.error('Error fetching filtered subjects:', error);
                this.filteredSubjects = [];
                this.subjectFilterMessage = 'Error loading subjects: ' + error.message;
                this.yearIsLocked = false;
                this.showMessage('Failed to load subjects for this school: ' + error.message, 'error');
            } finally {
                this.subjectLoading = false;
            }
        },

        onSubjectChange() {
            this.subjectSearch = '';
            const subject = this.filteredSubjects.find(s => s.id == this.selectedSubject);
            if (subject) {
                let info = `Papers: ${subject.written_papers}`;
                if (subject.has_practical) info += ', Practical: Yes';
                if (subject.has_project) info += ', Project: Yes';
                this.subjectInfo = info;
            }
        },

        onContextChange() {
            // Context changed, reset search fields
            this.schoolSearch = '';
            // Load filtered subjects for this school/year
            this.loadFilteredSubjects();
        },

        resetContext() {
            this.examYear = new Date().getFullYear();
            this.selectedRegion = '';
            this.selectedDistrict = '';
            this.selectedSchool = '';
            this.selectedSubject = '';
            this.filteredSubjects = [];
            this.subjectFilterMessage = '';
            this.candidateCount = 0;
            this.selectedFile = null;
            this.importResult = null;
            this.clearStoredContext();
        },

        downloadTemplate() {
            if (!this.selectedSubject) {
                this.showMessage('Please select a subject', 'error');
                return;
            }

            if (!this.examYear || !this.selectedSchool) {
                this.showMessage('Please select exam year and school', 'error');
                return;
            }

            const params = new URLSearchParams({
                exam_year: this.examYear,
                school_id: this.selectedSchool,
                subject_id: this.selectedSubject,
            });

            window.location.href = `/mark-entry/acsee/download-template?${params}`;
        },

        handleFileSelect(event) {
            this.selectedFile = event.target.files[0];
        },

        uploadFile() {
             if (!this.selectedFile) {
                 this.showMessage('Please select a file', 'error');
                 return;
             }

             if (!this.examYear || !this.selectedSchool || !this.selectedSubject) {
                 this.showMessage('Please select all required fields (year, school, subject)', 'error');
                 return;
             }

             this.uploading = true;

             // Use XMLHttpRequest instead of fetch to avoid extension interference
             const xhr = new XMLHttpRequest();
             const formData = new FormData();
             formData.append('exam_year', this.examYear);
             formData.append('school_id', this.selectedSchool);
             formData.append('subject_id', this.selectedSubject);
             formData.append('file', this.selectedFile);
             formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

             xhr.addEventListener('load', () => {
                 try {
                     if (xhr.status === 200 || xhr.status === 201) {
                         const data = JSON.parse(xhr.responseText);
                         if (data.success) {
                             this.importResult = data;
                             this.showMessage(`${data.message || 'Import successful'}`, 'success');
                         } else {
                             this.showMessage(data.message || 'Upload failed', 'error');
                         }
                     } else {
                         let errorMsg = `Server error (${xhr.status})`;
                         try {
                             const data = JSON.parse(xhr.responseText);
                             errorMsg = data.message || errorMsg;
                         } catch (e) {}
                         this.showMessage(errorMsg, 'error');
                     }
                 } catch (e) {
                     this.showMessage('Error processing response', 'error');
                 } finally {
                     this.uploading = false;
                 }
             });

             xhr.addEventListener('error', () => {
                 this.showMessage('Network error. Please check your connection and try again.', 'error');
                 this.uploading = false;
             });

             xhr.addEventListener('abort', () => {
                 this.showMessage('Upload cancelled', 'error');
                 this.uploading = false;
             });

             xhr.open('POST', '/mark-entry/acsee/upload');
             xhr.send(formData);
         },

        async downloadErrorReport() {
            if (!this.importResult?.batch?.id) {
                this.showMessage('No batch to report', 'error');
                return;
            }

            window.location.href = `/mark-entry/acsee/batch/${this.importResult.batch.id}/error-report`;
        },

        async lockBatch() {
             if (!this.importResult?.batch?.id) {
                 this.showMessage('No batch to lock', 'error');
                 return;
             }

             try {
                 const response = await fetch(`/mark-entry/acsee/batch/${this.importResult.batch.id}/lock`, {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                     },
                 });

                 // Handle non-OK HTTP responses
                 if (!response.ok) {
                     throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                 }

                 let data = {};
                 try {
                     data = await response.json();
                 } catch (parseError) {
                     throw new Error('Invalid JSON response from server');
                 }

                 if (data.success) {
                     this.showMessage(data.message, 'success');
                     this.importResult.batch.status = 'locked';
                 } else {
                     this.showMessage(data.message || 'Lock failed', 'error');
                 }
             } catch (error) {
                 console.error('Lock error:', error);
                 this.showMessage('Error locking batch: ' + error.message, 'error');
             }
         },

         printScoresheet() {
             if (!this.selectedSubject || !this.examYear || !this.selectedSchool) {
                     this.showMessage('Please select exam year, school, and subject', 'error');
                     return;
                 }

                 const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }

                 const params = new URLSearchParams({
                     exam_year_id: examYearId,
                     school_id: this.selectedSchool,
                     subject_id: this.selectedSubject,
                 });

                 window.open(`/mark-entry/acsee/scoresheet/print?${params}`, '_blank');
             this.showMessage('Opening scoresheet PDF...', 'success');
         },

         bulkExport() {
             if (!this.examYear || !this.selectedSchool) {
                 this.showMessage('Please select exam year and school', 'error');
                 return;
             }

             const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
             if (!examYearId) {
                 this.showMessage('Invalid exam year selected', 'error');
                 return;
             }

             const params = new URLSearchParams({
                 exam_year_id: examYearId,
                 school_id: this.selectedSchool,
             });

             window.location.href = `/mark-entry/acsee/scoresheet/bulk-export?${params}`;
             this.showMessage('Preparing bulk export...', 'success');
         },

         async downloadBulkCsv() {
             if (!this.selectedSchool || !this.examYear) {
                 this.showMessage('Please select exam year and school', 'error');
                 return;
             }

             if (!this.filteredSubjects.length) {
                 this.showMessage('No subjects available for download', 'error');
                 return;
             }

             this.bulkCsvLoading = true;

             try {
                 // Convert year_label to exam_year_id
                 const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }

                 const params = new URLSearchParams({
                     exam_year_id: examYearId,
                     school_id: this.selectedSchool,
                 });

                 const response = await fetch(`/mark-entry/acsee/bulk-csv-download?${params}`);

                 if (!response.ok) {
                     let errorMessage = 'Failed to download bulk CSV';
                     try {
                         const data = await response.json();
                         if (response.status === 422 && data.errors) {
                             // Validation errors
                             const errorList = Object.values(data.errors).flat().join(', ');
                             errorMessage = `Validation error: ${errorList}`;
                         } else {
                             errorMessage = data.message || errorMessage;
                         }
                     } catch (parseError) {
                         // Server returned HTML error page instead of JSON
                         errorMessage = `Server error (${response.status}): ${response.statusText}. Please check your selections and try again.`;
                     }
                     this.showMessage(errorMessage, 'error');
                     return;
                 }

                 // Trigger download
                 const blob = await response.blob();
                 const url = window.URL.createObjectURL(blob);
                 const link = document.createElement('a');
                 link.href = url;

                 // Get school name from schools array
                 const selectedSchoolObj = this.schools.find(s => s.id == this.selectedSchool);
                 const schoolName = selectedSchoolObj ? selectedSchoolObj.name.replace(/\s+/g, '_') : 'SCHOOL';
                 
                 link.download = `${schoolName}_ACSEE_${this.examYear}_MarkTemplate.zip`;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 window.URL.revokeObjectURL(url);

                 this.showMessage('Bulk CSV download complete', 'success');
                 } catch (error) {
                 console.error('Download error:', error);
                 this.showMessage('Error downloading bulk CSV: ' + error.message, 'error');
                 } finally {
                 this.bulkCsvLoading = false;
                 }
                 },

                 async downloadDistrictBulkCsv() {
                 if (!this.selectedDistrict || !this.examYear) {
                 this.showMessage('Please select district and exam year', 'error');
                 return;
                 }

                 this.districtBulkCsvLoading = true;

                 try {
                 // Convert year_label to exam_year_id
                 const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }

                 const params = new URLSearchParams({
                     exam_year_id: parseInt(examYearId),
                     district_id: parseInt(this.selectedDistrict),
                 });

                 const response = await fetch(`/mark-entry/acsee/district-bulk-csv-download?${params}`);

                 if (!response.ok) {
                     let errorMessage = 'Failed to download district mark templates';
                     try {
                         const data = await response.json();
                         if (response.status === 422 && data.errors) {
                             const errorList = Object.values(data.errors).flat().join(', ');
                             errorMessage = `Validation error: ${errorList}`;
                         } else {
                             errorMessage = data.message || errorMessage;
                         }
                     } catch (parseError) {
                         errorMessage = `Server error (${response.status}): ${response.statusText}. Please check your selections and try again.`;
                     }
                     this.showMessage(errorMessage, 'error');
                     return;
                 }

                 // Trigger download
                 const blob = await response.blob();
                 const url = window.URL.createObjectURL(blob);
                 const link = document.createElement('a');
                 link.href = url;
                 
                 const selectedDistrictObj = this.filteredDistricts.find(d => d.id == this.selectedDistrict);
                 const districtName = selectedDistrictObj ? selectedDistrictObj.name.replace(/\s+/g, '_') : 'DISTRICT';
                 
                 link.download = `${districtName}_ACSEE_${this.examYear}_MarkTemplate.zip`;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 window.URL.revokeObjectURL(url);

                 this.showMessage('District mark templates download complete', 'success');
                 } catch (error) {
                 console.error('Download error:', error);
                 this.showMessage('Error downloading district mark templates: ' + error.message, 'error');
                 } finally {
                 this.districtBulkCsvLoading = false;
                 }
                 },

                 async downloadDistrictBulkScoresheet() {
                     if (!this.selectedDistrict || !this.examYear) {
                         console.error('Validation failed', { selectedDistrict: this.selectedDistrict, examYear: this.examYear });
                         this.showMessage('Please select district and exam year', 'error');
                         return;
                     }

                     this.districtBulkScoresheetLoading = true;

                     try {
                         // Convert year_label to exam_year_id
                         const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
                         if (!examYearId) {
                             this.showMessage('Invalid exam year selected', 'error');
                             return;
                         }

                         const params = new URLSearchParams({
                             exam_year_id: parseInt(examYearId),
                             district_id: parseInt(this.selectedDistrict),
                         });
                         
                         console.log('Downloading district scoresheets with params:', {
                             exam_year_id: parseInt(examYearId),
                             district_id: parseInt(this.selectedDistrict),
                         });

                 const response = await fetch(`/mark-entry/acsee/district-bulk-scoresheet-download?${params}`);

                 if (!response.ok) {
                     let errorMessage = 'Failed to download district scoresheets';
                     try {
                         const data = await response.json();
                         if (response.status === 422 && data.errors) {
                             const errorList = Object.values(data.errors).flat().join(', ');
                             errorMessage = `Validation error: ${errorList}`;
                         } else {
                             errorMessage = data.message || errorMessage;
                         }
                     } catch (parseError) {
                         errorMessage = `Server error (${response.status}): ${response.statusText}. Please check your selections and try again.`;
                     }
                     this.showMessage(errorMessage, 'error');
                     return;
                 }

                 // Trigger download
                 const blob = await response.blob();
                 const url = window.URL.createObjectURL(blob);
                 const link = document.createElement('a');
                 link.href = url;
                 
                 const selectedDistrictObj = this.districts.find(d => d.id == this.selectedDistrict);
                 const districtName = selectedDistrictObj ? selectedDistrictObj.name.replace(/\s+/g, '_') : 'DISTRICT';
                 
                 link.download = `${districtName}_ACSEE_${this.examYear}_Scoresheets.zip`;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 window.URL.revokeObjectURL(url);

                 this.showMessage('District scoresheets download complete', 'success');
                 } catch (error) {
                 console.error('Download error:', error);
                 this.showMessage('Error downloading district scoresheets: ' + error.message, 'error');
                 } finally {
                 this.districtBulkScoresheetLoading = false;
                 }
                 },

                 async handleZipSelect(event) {
                 const file = event.target.files[0];
                 if (!file) return;

                 this.bulkZipPreview = null;
                 const formData = new FormData();
                 formData.append('zip_file', file);
                 
                 const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                 try {
                 const response = await fetch('/api/bulk-import/preview', {
                     method: 'POST',
                     body: formData,
                     headers: {
                         'X-CSRF-TOKEN': csrfToken
                     }
                 });

                 if (!response.ok) {
                     const errorData = await response.json();
                     this.showMessage(errorData.message || errorData.errors?.join(', ') || 'Upload failed with status ' + response.status, 'error');
                     return;
                 }

                 const data = await response.json();

                 if (data.success) {
                     this.bulkZipPreview = data.preview;
                 } else {
                     this.showMessage(data.errors ? data.errors.join(', ') : 'Invalid ZIP file', 'error');
                     this.bulkZipPreview = null;
                 }
                 } catch (error) {
                 console.error('Preview error:', error);
                 this.showMessage('Error previewing ZIP: ' + error.message, 'error');
                 }
                 },

         async startBulkImport() {
             if (!this.bulkZipPreview || !this.selectedSchool || !this.examYear) {
                 this.showMessage('Please select school, year, and upload ZIP', 'error');
                 return;
             }

             try {
                 const examYearId = this.examYears.find(y => y.year_label == this.examYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }

                 const response = await fetch('/api/bulk-import/start', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                     },
                     body: JSON.stringify({
                         school_id: this.selectedSchool,
                         exam_year_id: examYearId,
                     }),
                 });

                 const data = await response.json();

                 if (data.success) {
                     this.bulkImportId = data.bulk_import_id;
                     this.showMessage('Import started', 'success');
                     this.trackImportProgress();
                 } else {
                     this.showMessage(data.message || 'Failed to start import', 'error');
                 }
             } catch (error) {
                 console.error('Import error:', error);
                 this.showMessage('Error starting import: ' + error.message, 'error');
             }
         },

         async trackImportProgress() {
             if (!this.bulkImportId) return;

             const pollProgress = async () => {
                 try {
                     const response = await fetch(`/api/bulk-import/${this.bulkImportId}/progress`);
                     const data = await response.json();

                     if (data.success) {
                         this.bulkImportProgress = data.progress;

                         if (data.progress.status === 'completed' || data.progress.status === 'failed') {
                             this.showMessage(
                                 `Import ${data.progress.status}: ${data.progress.summary.successful_candidates} successful, ${data.progress.summary.failed_candidates} failed`,
                                 data.progress.status === 'completed' ? 'success' : 'error'
                             );
                         } else {
                             // Poll again in 1 second
                             setTimeout(pollProgress, 1000);
                         }
                     }
                 } catch (error) {
                     console.error('Progress tracking error:', error);
                 }
             };

             await pollProgress();
         },

         // School Bulk Import Methods
         schoolBulkExamYear: '',
         schoolBulkYearSearch: '',
         schoolBulkYearOpen: false,
         schoolBulkId: '',
         schoolBulkSchoolSearch: '',
         schoolBulkSchoolOpen: false,
         schoolBulkList: [],
         selectedSchoolZipFile: null,
         schoolDragOver: false,
         
         schoolBulkPreviewLoaded: false,
         schoolBulkPreview: null,
         
         schoolBulkImportInProgress: false,
         schoolBulkImportComplete: false,
         schoolBulkImportId: null,
         schoolBulkProgress: {
             progress_percentage: 0,
             status: 'pending',
             total_files: 0,
             processed_files: 0,
             summary: {
                 total_candidates: 0,
                 successful_candidates: 0,
             }
         },
         schoolBulkProgressInterval: null,
         
         // Computed - Filtered Schools for School Bulk Import
         get filteredSchoolBulkSchools() {
             if (!this.schoolBulkExamYear) return [];
             
             // Return schools with ACSEE candidates for the selected exam year
             return this.schoolBulkList || [];
         },
         
         async onSchoolBulkExamYearChange() {
             // Reset school selection when exam year changes
             this.schoolBulkId = '';
             this.schoolBulkSchoolSearch = '';
             this.schoolBulkSchoolOpen = false;
             
             // Load schools for selected exam year
             if (this.schoolBulkExamYear) {
                 await this.loadSchoolBulkList();
             } else {
                 this.schoolBulkList = [];
             }
         },
         
         async loadSchoolBulkList() {
             try {
                 const response = await fetch(
                     `/api/mark-entry/acsee/schools-by-year?exam_year=${this.schoolBulkExamYear}`
                 );
                 const data = await response.json();
                 this.schoolBulkList = data.data || [];
                 console.log('✓ Schools loaded for exam year', this.schoolBulkExamYear, ':', this.schoolBulkList.length);
             } catch (error) {
                 console.error('Error loading schools:', error);
                 this.schoolBulkList = [];
             }
         },
         
         handleSchoolFileSelect(event) {
             this.selectedSchoolZipFile = event.target.files[0];
         },
         
         handleSchoolFileDrop(event) {
             event.preventDefault();
             this.schoolDragOver = false;
             this.selectedSchoolZipFile = event.dataTransfer.files[0];
         },
         
         async previewSchoolZip() {
             const formData = new FormData();
             formData.append('zip_file', this.selectedSchoolZipFile);
             formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
             
             try {
                 const response = await fetch('/api/bulk-import/preview', {
                     method: 'POST',
                     body: formData
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.schoolBulkPreview = data.preview;
                     this.schoolBulkPreviewLoaded = true;
                 } else {
                     this.showMessage('Validation failed: ' + JSON.stringify(data.errors || data.message), 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         async startSchoolBulkImport() {
             try {
                 // Convert year_label to exam_year_id
                 const examYearId = this.examYears.find(y => y.year_label == this.schoolBulkExamYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }
                 
                 const response = await fetch('/api/bulk-import/start', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                     },
                     body: JSON.stringify({
                         school_id: this.schoolBulkId,
                         exam_year_id: examYearId
                     })
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.schoolBulkImportId = data.bulk_import_id;
                     this.schoolBulkImportInProgress = true;
                     this.monitorSchoolBulkProgress();
                 } else {
                     this.showMessage('Error: ' + data.message, 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         monitorSchoolBulkProgress() {
             this.schoolBulkProgressInterval = setInterval(() => {
                 fetch(`/api/bulk-import/${this.schoolBulkImportId}/progress`)
                     .then(r => r.json())
                     .then(data => {
                         if (data.success) {
                             this.schoolBulkProgress = data.progress;
                             
                             if (['completed', 'partial', 'failed'].includes(this.schoolBulkProgress.status)) {
                                 clearInterval(this.schoolBulkProgressInterval);
                                 this.schoolBulkImportInProgress = false;
                                 this.schoolBulkImportComplete = true;
                             }
                         }
                     })
                     .catch(err => console.error('Error fetching progress:', err));
             }, 2000);
         },
         
         resetSchoolBulkImport() {
             this.selectedSchoolZipFile = null;
             this.schoolBulkPreviewLoaded = false;
             this.schoolBulkPreview = null;
             this.schoolBulkImportInProgress = false;
             this.schoolBulkImportComplete = false;
             this.schoolBulkImportId = null;
             this.schoolBulkProgress = null;
             if (this.schoolBulkProgressInterval) clearInterval(this.schoolBulkProgressInterval);
         },

         // District Bulk Import Methods
         districtExamYear: '',
         districtId: '',
         districtBulkList: [],
         selectedZipFile: null,
         dragOver: false,
         
         districtPreviewLoaded: false,
         districtPreview: null,
         
         districtImportInProgress: false,
         districtImportComplete: false,
         districtBulkImportId: null,
         districtProgress: {
             progress_percentage: 0,
             status: 'pending',
             total_schools: 0,
             processed_schools: 0,
             schools: [],
             summary: {
                 total_candidates: 0,
                 successful_candidates: 0,
             }
         },
         districtProgressInterval: null,
         
         handleZipFileSelect(event) {
             this.selectedZipFile = event.target.files[0];
         },
         
         handleZipFileDrop(event) {
             event.preventDefault();
             this.dragOver = false;
             this.selectedZipFile = event.dataTransfer.files[0];
         },
         
         async previewZip() {
             const formData = new FormData();
             formData.append('zip_file', this.selectedZipFile);
             formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
             
             try {
                 const response = await fetch('/api/bulk-import/preview', {
                     method: 'POST',
                     body: formData
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.districtPreview = data.preview;
                     this.districtPreviewLoaded = true;
                 } else {
                     this.showMessage('Validation failed: ' + JSON.stringify(data.errors || data.message), 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         async startDistrictImport() {
             try {
                 // Convert year_label to exam_year_id
                 const examYearId = this.examYears.find(y => y.year_label == this.districtExamYear)?.id;
                 if (!examYearId) {
                     this.showMessage('Invalid exam year selected', 'error');
                     return;
                 }
                 
                 const response = await fetch('/api/bulk-import/district/start', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                     },
                     body: JSON.stringify({
                         district_id: this.districtId,
                         exam_year_id: examYearId
                     })
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.districtBulkImportId = data.bulk_import_id;
                     this.districtImportInProgress = true;
                     this.monitorDistrictProgress();
                 } else {
                     this.showMessage('Error: ' + data.message, 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         async onDistrictExamYearChange() {
             // Reset district selection when exam year changes
             this.districtId = '';
             
             // Load districts for selected exam year
             if (this.districtExamYear) {
                 await this.loadDistrictBulkList();
             } else {
                 this.districtBulkList = [];
             }
         },
         
         async loadDistrictBulkList() {
             try {
                 const response = await fetch(
                     `/api/mark-entry/acsee/districts-by-year?exam_year=${this.districtExamYear}`
                 );
                 const data = await response.json();
                 this.districtBulkList = data.data || [];
                 console.log('✓ Districts loaded for exam year', this.districtExamYear, ':', this.districtBulkList.length);
             } catch (error) {
                 console.error('Error loading districts:', error);
                 this.districtBulkList = [];
             }
         },
         
         monitorDistrictProgress() {
             this.districtProgressInterval = setInterval(() => {
                 fetch(`/api/bulk-import/${this.districtBulkImportId}/progress`)
                     .then(r => r.json())
                     .then(data => {
                         if (data.success) {
                             this.districtProgress = data.progress;
                             
                             if (['completed', 'partial', 'failed'].includes(this.districtProgress.status)) {
                                 clearInterval(this.districtProgressInterval);
                                 this.districtImportInProgress = false;
                                 this.districtImportComplete = true;
                             }
                         }
                     })
                     .catch(err => console.error('Error fetching progress:', err));
             }, 2000);
         },
         
         async retryDistrictSchool(schoolId) {
             if (!confirm('Retry this school?')) return;
             
             try {
                 const response = await fetch(`/api/bulk-import/${this.districtBulkImportId}/retry-school`, {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify({ school_id: schoolId })
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.showMessage('Retry started. Check progress below.', 'success');
                     this.districtImportInProgress = true;
                     this.districtImportComplete = false;
                     this.monitorDistrictProgress();
                 } else {
                     this.showMessage('Error: ' + data.message, 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         async retryDistrictAll() {
             if (!confirm('Retry all failed schools?')) return;
             
             try {
                 const response = await fetch(`/api/bulk-import/${this.districtBulkImportId}/retry-all`, {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' }
                 });
                 const data = await response.json();
                 
                 if (data.success) {
                     this.showMessage('Retry started for ' + data.schools_retried + ' schools.', 'success');
                     this.districtImportInProgress = true;
                     this.districtImportComplete = false;
                     this.monitorDistrictProgress();
                 } else {
                     this.showMessage('Error: ' + data.message, 'error');
                 }
             } catch (err) {
                 this.showMessage('Error: ' + err.message, 'error');
             }
         },
         
         resetDistrictImport() {
             this.selectedZipFile = null;
             this.districtPreviewLoaded = false;
             this.districtPreview = null;
             this.districtImportInProgress = false;
             this.districtImportComplete = false;
             this.districtBulkImportId = null;
             this.districtProgress = null;
             if (this.districtProgressInterval) clearInterval(this.districtProgressInterval);
         },

         showMessage(message, type) {
                 const alertDiv = document.createElement('div');
                 const bgClass = type === 'success' ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800';
                 const icon = type === 'success' ? '✓' : '✕';
                 
                 alertDiv.className = `fixed top-40 right-4 left-4 sm:left-auto sm:w-96 ${bgClass} p-5 rounded-lg border-2 z-50 shadow-xl`;
                alertDiv.innerHTML = `<div class="flex items-start gap-3">
                    <span class="text-2xl font-bold">${icon}</span>
                    <div class="flex-1">
                        <p class="font-bold text-lg">${type === 'success' ? 'Success' : 'Error'}</p>
                        <p class="text-sm mt-1 whitespace-pre-wrap">${message}</p>
                    </div>
                    <button class="text-lg leading-none opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>`;
                
                document.body.appendChild(alertDiv);
                
                // Auto-remove after 6 seconds, but keep longer for errors
                const timeout = type === 'success' ? 5000 : 8000;
                setTimeout(() => {
                    if (alertDiv.parentElement) {
                        alertDiv.remove();
                    }
                }, timeout);
            },

            // ===================== PHASE 3C-3: DATA FETCHING =====================
            
            // State for Phase 3C-3
            loading: false,
            error: null,
            currentBatch: {
                id: null,
                history: [],
                school: {},
                subject: {},
                examType: {}
            },
            moderationBatches: [],
            readyBatches: [],
            submittedBatches: [],
            analyticsData: {
                overview: {
                    submitted_batches: 0,
                    approved_batches: 0,
                    total_batches: 0
                },
                bySubject: [],
                errorStats: {}
            },
            auditTrail: [],
            activityLog: [],
            selectedBatchId: null,
            currentPage: 1,
            perPage: 20,
            totalBatches: 0,
            error: null,
            loading: false,
            
            // Initialize Phase 3C-3
            init3C3() {
            console.log('Phase 3C-3 Data Fetching Initialized');
            },
            
            // Utility: Fetch from API
            async fetchApi(endpoint, options = {}) {
            try {
                this.loading = true;
                this.error = null;
                
                const response = await fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...options.headers
                    },
                    ...options
                });
                
                if (!response.ok) {
                    throw new Error(`API Error: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                this.loading = false;
                return data;
            } catch (err) {
                this.error = err.message;
                this.loading = false;
                console.error('API Error:', err);
                this.showMessage(`⚠️ ${err.message}`, 'error');
                throw err;
            }
            },
            
            // Format date utility
            formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString() + ' ' + 
                   new Date(date).toLocaleTimeString();
            },
            
            // =========== MODERATION DASHBOARD ===========
            async loadModerationDashboard(page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/moderation/pending?page=${page}&per_page=${this.perPage}`
                );
                
                this.moderationBatches = response.data;
                this.totalBatches = response.pagination.total;
                this.currentPage = page;
                
                console.log(`✓ Loaded ${this.moderationBatches.length} pending batches`);
            } catch (err) {
                this.error = 'Failed to load moderation dashboard';
            }
            },
            
            // =========== LOCK STATUS ===========
            async loadLockStatus(page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/submission/ready?page=${page}&per_page=${this.perPage}`
                );
                
                this.readyBatches = response.data;
                console.log(`✓ Loaded ${this.readyBatches.length} batches ready for locking`);
            } catch (err) {
                this.error = 'Failed to load lock status';
            }
            },
            
            // =========== SUBMISSION HISTORY ===========
            async loadSubmissionHistory(batchId) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/submission/batch/${batchId}/history`
                );
                
                this.currentBatch = {
                    id: batchId,
                    history: response.data
                };
                
                console.log(`✓ Loaded submission history for batch ${batchId}`);
            } catch (err) {
                this.error = 'Failed to load submission history';
            }
            },
            
            // =========== ANALYTICS ===========
            async loadAnalytics() {
            try {
                this.loading = true;
                
                // Load all analytics in parallel
                const [overview, bySubject, errorStats] = await Promise.all([
                    this.fetchApi('/api/mark-entry/analytics/overview'),
                    this.fetchApi('/api/mark-entry/analytics/by-subject'),
                    this.fetchApi('/api/mark-entry/analytics/errors')
                ]);
                
                this.analyticsData = {
                    overview: overview.data,
                    bySubject: bySubject.data,
                    errorStats: errorStats.data
                };
                
                console.log('✓ Loaded analytics data');
            } catch (err) {
                this.error = 'Failed to load analytics';
            }
            },
            
            // =========== AUDIT TRAIL ===========
            async loadAuditTrail(batchId, page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/audit/batch/${batchId}?page=${page}&per_page=${this.perPage}`
                );
                
                this.auditTrail = response.data;
                console.log(`✓ Loaded ${this.auditTrail.length} audit entries`);
            } catch (err) {
                this.error = 'Failed to load audit trail';
            }
            },
            
            // =========== ACTIVITY LOG ===========
            async loadActivityLog(userId, page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/audit/user/${userId}?page=${page}&per_page=${this.perPage}`
                );
                
                this.activityLog = response.data;
                console.log(`✓ Loaded ${this.activityLog.length} activity entries`);
            } catch (err) {
                this.error = 'Failed to load activity log';
            }
            },

            // =========== MODERATION WORKFLOW ===========
            // State variables for moderation
            showApproveBatchModal: false,
            showRejectBatchModal: false,
            selectedBatchId: null,
            approveFeedback: '',
            rejectReason: '',
            isApproving: false,
            isRejecting: false,
            
            // Open approve modal
            openApproveBatchModal(batchId) {
                this.selectedBatchId = batchId;
                this.approveFeedback = '';
                this.showApproveBatchModal = true;
            },
            
            // Confirm and submit approval
            async approveBatchConfirm() {
                if (!this.selectedBatchId) return;
                this.isApproving = true;
                
                try {
                    // Add 30-second timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    
                    const response = await fetch(`/api/mark-entry/moderation/batch/${this.selectedBatchId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            feedback: this.approveFeedback || null
                        }),
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage('Batch approved successfully', 'success');
                        this.showApproveBatchModal = false;
                        this.approveFeedback = '';
                        // Refresh the moderation dashboard
                        await this.loadPendingReview();
                    } else {
                        this.showMessage(data.message || 'Failed to approve batch', 'error');
                    }
                } catch (error) {
                    console.error('Error approving batch:', error);
                    if (error.name === 'AbortError') {
                        this.showMessage('Request timeout. Server may be busy. Please try again.', 'error');
                    } else {
                        this.showMessage('Error: ' + error.message, 'error');
                    }
                } finally {
                    this.isApproving = false;
                }
            },
            
            // Open reject modal
            openRejectBatchModal(batchId) {
                this.selectedBatchId = batchId;
                this.rejectReason = '';
                this.showRejectBatchModal = true;
            },
            
            // Confirm and submit rejection
            async rejectBatchConfirm() {
                if (!this.selectedBatchId || (this.rejectReason || '').length < 10) return;
                this.isRejecting = true;
                
                try {
                    // Add 30-second timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    
                    const response = await fetch(`/api/mark-entry/moderation/batch/${this.selectedBatchId}/reject`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            reason: this.rejectReason
                        }),
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage('Batch rejected successfully. Submitter will be notified.', 'success');
                        this.showRejectBatchModal = false;
                        this.rejectReason = '';
                        // Refresh the moderation dashboard
                        await this.loadPendingReview();
                    } else {
                        this.showMessage(data.message || 'Failed to reject batch', 'error');
                    }
                } catch (error) {
                    console.error('Error rejecting batch:', error);
                    if (error.name === 'AbortError') {
                        this.showMessage('Request timeout. Server may be busy. Please try again.', 'error');
                    } else {
                        this.showMessage('Error: ' + error.message, 'error');
                    }
                } finally {
                    this.isRejecting = false;
                }
            },

            // =========== SUBMISSION WORKFLOW ===========
            // State variables for submission
            showLockBatchModal: false,
            showUnlockBatchModal: false,
            lockConfirmText: '',
            unlockReason: '',
            isLocking: false,
            isUnlocking: false,
            
            // Open lock modal
            openLockBatchModal(batchId) {
                this.selectedBatchId = batchId;
                this.lockConfirmText = '';
                this.showLockBatchModal = true;
            },
            
            // Confirm and submit lock
            async lockBatchConfirm() {
                if (!this.selectedBatchId || this.lockConfirmText.toUpperCase() !== 'LOCK') return;
                this.isLocking = true;
                
                try {
                    // Add 30-second timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 30000);
                    
                    const response = await fetch(`/api/mark-entry/submission/lock/${this.selectedBatchId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage('Batch locked and submitted successfully', 'success');
                        this.showLockBatchModal = false;
                        this.lockConfirmText = '';
                        // Refresh the submission dashboard
                        await this.loadLockStatus();
                    } else {
                        this.showMessage(data.message || 'Failed to lock batch', 'error');
                    }
                } catch (error) {
                    console.error('Error locking batch:', error);
                    if (error.name === 'AbortError') {
                        this.showMessage('Request timeout. Server may be busy. Please try again.', 'error');
                    } else {
                        this.showMessage('Error: ' + error.message, 'error');
                    }
                } finally {
                    this.isLocking = false;
                }
            },
            
            // Open unlock modal (admin only)
            openUnlockBatchModal(batchId) {
                this.selectedBatchId = batchId;
                this.unlockReason = '';
                this.showUnlockBatchModal = true;
            },
            
            // Close unlock modal
            closeUnlockModal() {
                this.showUnlockBatchModal = false;
                this.unlockReason = '';
                this.selectedBatchId = null;
                this.isUnlocking = false;
            },
            
            // Confirm and submit unlock
            async unlockBatchConfirm() {
                if (!this.selectedBatchId || (this.unlockReason || '').length < 10) {
                    this.showMessage('Please enter at least 10 characters for the unlock reason.', 'error');
                    return;
                }
                
                this.isUnlocking = true;
                let success = false;
                
                try {
                    const batchId = parseInt(this.selectedBatchId);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    
                    // Create abort controller with 10 second timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000);
                    
                    const response = await fetch(`/api/mark-entry/submission/unlock/${batchId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            reason: this.unlockReason
                        }),
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.message || `HTTP ${response.status}`);
                    }
                    
                    const data = await response.json();
                    success = true;
                    this.showMessage('Batch unlocked successfully!', 'success');
                    
                } catch (error) {
                    console.error('Unlock error:', error);
                    if (error.name === 'AbortError') {
                        this.showMessage('Request timeout - server took too long to respond', 'error');
                    } else {
                        this.showMessage(`Failed to unlock batch: ${error.message}`, 'error');
                    }
                } finally {
                    // ALWAYS reset state - THIS IS GUARANTEED TO RUN
                    this.isUnlocking = false;
                    this.showUnlockBatchModal = false;
                    this.unlockReason = '';
                    this.selectedBatchId = null;
                    
                    // Refresh if success
                    if (success && this.loadSubmittedBatches) {
                        setTimeout(() => this.loadSubmittedBatches(), 500);
                    }
                }
            },

            // =========== TOAST NOTIFICATION SYSTEM ===========
            toastMessage: '',
            toastType: 'info', // 'success', 'error', 'info', 'warning'
            toastDetails: '',
            toastTimeout: null,
            
            showMessage(message, type = 'info', details = '') {
                this.toastMessage = message;
                this.toastType = type;
                this.toastDetails = details;
                
                // Auto-hide after 5 seconds
                clearTimeout(this.toastTimeout);
                this.toastTimeout = setTimeout(() => {
                    this.closeToast();
                }, 5000);
            },
            
            closeToast() {
                this.toastMessage = '';
                this.toastType = 'info';
                this.toastDetails = '';
                clearTimeout(this.toastTimeout);
            },

            };
            }
</script>

        <!-- MODALS AND NOTIFICATIONS -->
         {{-- @include('mark-entry.components._approve_batch_modal') --}} <!-- TODO: Implement approve modal later -->
         {{-- @include('mark-entry.components._reject_batch_modal') --}} <!-- TODO: Implement reject modal later -->
         {{-- @include('mark-entry.components._lock_batch_modal') --}} <!-- TODO: Implement lock modal later -->
         {{-- @include('mark-entry.components._unlock_batch_modal') --}} <!-- TODO: Implement unlock modal later -->
         @include('mark-entry.components._toast_notification')
        </div>
        </div>
        </div>
        </div>
        @endsection
