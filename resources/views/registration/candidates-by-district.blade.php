@extends('layout')

@section('content')
<div class="w-full">
    <!-- Page Header -->
    <div class="bg-white border-b border-gray-200 px-8 py-6 sticky top-0 z-40 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">District Candidates Registration</h1>
        <p class="text-sm text-gray-600 mt-1">Import and register candidates from CSV file - missing schools will be auto-registered</p>
    </div>

    <!-- Main Content -->
    <div class="px-8 py-8">
        <div x-data="districtCandidatesManager()" @init="init()" class="space-y-6">
            
            <!-- District Selection & Import Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-12 gap-4 items-end">
                    <!-- District Selector -->
                    <div class="col-span-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-map-marker text-blue-600"></i> Select District
                        </label>
                        <div class="relative" @click.outside="districtDropdownOpen = false">
                            <button 
                                @click="districtDropdownOpen = !districtDropdownOpen"
                                class="w-full px-4 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 rounded-lg flex justify-between items-center text-sm font-medium"
                            >
                                <span x-text="selectedDistrict ? selectedDistrict.name : 'Select a district...'" class="text-gray-700"></span>
                                <i :class="districtDropdownOpen ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-xs text-gray-500"></i>
                            </button>
                            <div x-show="districtDropdownOpen" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto">
                                <input 
                                    x-model="districtSearch"
                                    type="text"
                                    placeholder="Search districts..."
                                    class="w-full px-4 py-2 border-b border-gray-200 focus:outline-none text-sm sticky top-0 bg-white"
                                >
                                <template x-for="district in filteredDistricts" :key="district.id">
                                    <div 
                                        @click="selectedDistrict = district; districtDropdownOpen = false; onDistrictChange()"
                                        :class="selectedDistrict?.id === district.id ? 'bg-blue-500 text-white' : 'hover:bg-blue-50'"
                                        class="px-4 py-3 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                                    >
                                        <div class="font-medium" x-text="district.name"></div>
                                        <div class="text-xs" :class="selectedDistrict?.id === district.id ? 'text-blue-100' : 'text-gray-500'" x-text="'Schools: ' + (district.schools_count || 0)"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="col-span-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-file-csv text-green-600"></i> Upload CSV File
                        </label>
                        <div class="flex gap-2">
                            <input 
                                type="file" 
                                id="csvFileInput"
                                @change="onFileSelected($event)"
                                accept=".csv"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :disabled="!selectedDistrict"
                            >
                            <button 
                                @click="downloadTemplate()"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 whitespace-nowrap"
                            >
                                <i class="fas fa-download"></i> Template
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            CSV must contain: school_code, candidate_id, full_name, gender, exam_type, exam_year
                        </p>
                    </div>

                    <!-- Import Button -->
                    <div class="col-span-3 flex gap-2 items-end">
                        <button 
                            @click="processImport()"
                            :disabled="!selectedFile || !selectedDistrict || isProcessing"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                        >
                            <i :class="isProcessing ? 'fas fa-spinner animate-spin' : 'fas fa-upload'"></i>
                            <span x-text="isProcessing ? 'Processing...' : 'Import Candidates'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-building text-blue-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-semibold">Registered Schools</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="registeredSchools.length"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-users text-green-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-semibold">CSV Preview</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="csvData.length"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-orange-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 uppercase font-semibold">To Register</p>
                            <p class="text-2xl font-bold text-gray-800" x-text="schoolsToRegister.length"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registered Schools List -->
            <template x-if="registeredSchools.length > 0">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-600"></i> Registered Schools in <span x-text="selectedDistrict?.name || 'Selected District'" class="text-blue-600"></span>
                    </h3>
                    <div class="grid grid-cols-2 gap-4 max-h-64 overflow-y-auto">
                        <template x-for="school in registeredSchools" :key="school.id">
                            <div class="border border-green-200 rounded-lg p-3 bg-green-50">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check text-green-600 text-lg mt-0.5"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-800 text-sm" x-text="school.code"></p>
                                        <p class="text-xs text-gray-600 truncate" x-text="school.name"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Schools To Register -->
            <template x-if="schoolsToRegister.length > 0">
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-orange-600"></i> New Schools to Auto-Register (<span x-text="schoolsToRegister.length" class="text-orange-600 font-bold"></span>)
                    </h3>
                    <div class="bg-orange-50 rounded-lg p-4 mb-4 border border-orange-200">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle text-orange-600 mr-2"></i>
                            These schools from your CSV will be automatically registered in the district when you import candidates.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 max-h-64 overflow-y-auto">
                        <template x-for="school in schoolsToRegister" :key="school.school_code">
                            <div class="border border-orange-200 rounded-lg p-3 bg-orange-50">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-plus text-orange-600 text-lg mt-0.5"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-800 text-sm" x-text="school.school_code"></p>
                                        <p class="text-xs text-gray-600 truncate" x-text="school.school_name || 'Unknown School'"></p>
                                        <p class="text-xs text-orange-600 mt-1">
                                            <span x-text="school.candidates || 0"></span> candidate(s)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- CSV Preview Table -->
            <template x-if="csvData.length > 0">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-eye text-blue-600"></i> CSV Preview (First 10 records)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 border-b-2 border-gray-300">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">School</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Candidate ID</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Full Name</th>
                                    <th class="px-3 py-2 text-center font-semibold text-gray-700">Gender</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Exam Type</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Exam Year</th>
                                    <th class="px-3 py-2 text-center font-semibold text-gray-700">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(record, idx) in csvData.slice(0, 10)" :key="idx">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-800 font-mono">
                                            <span x-text="record.school_code"></span>
                                            <template x-if="!isSchoolRegistered(record.school_code)">
                                                <i class="fas fa-exclamation-circle text-orange-600 ml-2"></i>
                                            </template>
                                        </td>
                                        <td class="px-3 py-2 text-gray-800 font-mono" x-text="record.candidate_id"></td>
                                        <td class="px-3 py-2 text-gray-800" x-text="record.full_name"></td>
                                        <td class="px-3 py-2 text-center text-gray-800" x-text="record.gender"></td>
                                        <td class="px-3 py-2 text-gray-800" x-text="record.exam_type"></td>
                                        <td class="px-3 py-2 text-gray-800" x-text="record.exam_year"></td>
                                        <td class="px-3 py-2 text-center">
                                            <template x-if="isSchoolRegistered(record.school_code)">
                                                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">
                                                    <i class="fas fa-check"></i> Registered
                                                </span>
                                            </template>
                                            <template x-if="!isSchoolRegistered(record.school_code)">
                                                <span class="inline-block px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs font-semibold">
                                                    <i class="fas fa-plus"></i> Will Register
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <template x-if="csvData.length > 10">
                            <p class="text-xs text-gray-500 mt-3">
                                Showing 10 of <span x-text="csvData.length"></span> records
                            </p>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Processing Status -->
            <template x-if="processingStatus">
                <div class="bg-white rounded-lg shadow p-6 border-l-4" :class="processingStatus.type === 'success' ? 'border-green-500' : 'border-red-500'">
                    <div class="flex items-start gap-4">
                        <div :class="processingStatus.type === 'success' ? 'text-green-600' : 'text-red-600'">
                            <i :class="processingStatus.type === 'success' ? 'fas fa-check-circle text-2xl' : 'fas fa-exclamation-circle text-2xl'"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 mb-2" x-text="processingStatus.title"></h4>
                            <p class="text-gray-600 text-sm mb-3" x-text="processingStatus.message"></p>
                            <template x-if="processingStatus.details">
                                <ul class="text-sm text-gray-700 space-y-1 ml-4">
                                    <template x-for="detail in processingStatus.details" :key="detail">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-arrow-right text-gray-500 text-xs"></i>
                                            <span x-text="detail"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>
</div>

<script>
function districtCandidatesManager() {
    return {
        // State
        selectedDistrict: null,
        districtDropdownOpen: false,
        districtSearch: '',
        selectedFile: null,
        csvData: [],
        registeredSchools: [],
        schoolsToRegister: [],
        isProcessing: false,
        processingStatus: null,
        districts: [],

        // Initialization
        async init() {
            await this.loadDistricts();
        },

        // Load districts
        async loadDistricts() {
            try {
                const response = await fetch('/api/districts', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                this.districts = data.data || data;
            } catch (error) {
                console.error('Error loading districts:', error);
                this.showAlert('Error loading districts', 'error');
            }
        },

        // Computed properties
        get filteredDistricts() {
            return this.districts.filter(d => 
                d.name.toLowerCase().includes(this.districtSearch.toLowerCase())
            );
        },

        // When district changes
        async onDistrictChange() {
            if (!this.selectedDistrict) {
                this.registeredSchools = [];
                this.csvData = [];
                this.schoolsToRegister = [];
                return;
            }

            // Load registered schools for this district
            await this.loadRegisteredSchools();
            
            // Re-analyze CSV if present
            if (this.csvData.length > 0) {
                this.analyzeCSV();
            }
        },

        // Load registered schools
        async loadRegisteredSchools() {
            if (!this.selectedDistrict) return;

            try {
                const response = await fetch(`/api/districts/${this.selectedDistrict.id}/schools`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                this.registeredSchools = data.data || data;
            } catch (error) {
                console.error('Error loading schools:', error);
                this.registeredSchools = [];
            }
        },

        // File selected
        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.selectedFile = file;
            this.parseCSV(file);
        },

        // Parse CSV
        parseCSV(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const lines = text.split('\n');
                const headers = lines[0].split(',').map(h => h.trim().toLowerCase());

                this.csvData = [];
                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;

                    const values = lines[i].split(',').map(v => v.trim());
                    const record = {};

                    headers.forEach((header, idx) => {
                        record[header] = values[idx] || '';
                    });

                    if (record.school_code || record.candidate_id) {
                        this.csvData.push(record);
                    }
                }

                this.analyzeCSV();
            };
            reader.readAsText(file);
        },

        // Analyze CSV
        analyzeCSV() {
            this.schoolsToRegister = [];
            const schoolCodes = {};

            this.csvData.forEach(record => {
                const schoolCode = record.school_code?.trim();
                if (!schoolCode) return;

                if (!this.isSchoolRegistered(schoolCode)) {
                    if (!schoolCodes[schoolCode]) {
                        schoolCodes[schoolCode] = {
                            school_code: schoolCode,
                            school_name: record.school_name || '',
                            candidates: 0
                        };
                    }
                    schoolCodes[schoolCode].candidates++;
                }
            });

            this.schoolsToRegister = Object.values(schoolCodes);
        },

        // Check if school is registered
        isSchoolRegistered(schoolCode) {
            return this.registeredSchools.some(s => s.code === schoolCode || s.code?.trim() === schoolCode);
        },

        // Download template
        downloadTemplate() {
            const headers = ['school_code', 'candidate_id', 'full_name', 'gender', 'exam_type', 'exam_year'];
            const sampleData = [
                ['S0861', 'S0861-0001', 'ABBY JACKSON MARUA', 'M', 'ACSEE', '2026'],
                ['S0861', 'S0861-0002', 'ABDUL RAZAQ HAMZA MWINYIJUMA', 'M', 'ACSEE', '2026'],
                ['S0862', 'S0862-0001', 'JOHN PAUL SHEM', 'M', 'ACSEE', '2026'],
            ];

            const csv = [
                headers.join(','),
                ...sampleData.map(row => row.map(v => `"${v}"`).join(','))
            ].join('\n');

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `district_candidates_template_${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showAlert('Template downloaded successfully', 'success');
        },

        // Process import
        async processImport() {
            if (!this.selectedFile || !this.selectedDistrict) {
                this.showAlert('Please select a district and CSV file', 'error');
                return;
            }

            this.isProcessing = true;
            this.processingStatus = null;

            try {
                const formData = new FormData();
                formData.append('file', this.selectedFile);
                formData.append('district_id', this.selectedDistrict.id);

                const response = await fetch('/api/registration/import-by-district', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    this.processingStatus = {
                        type: 'success',
                        title: 'Import Successful',
                        message: 'Candidates and schools have been registered successfully',
                        details: [
                            `${data.schools_registered || 0} new schools registered`,
                            `${data.candidates_imported || 0} candidates imported`,
                            `${data.schools_skipped || 0} schools already registered`,
                            `${data.candidates_skipped || 0} candidates skipped (duplicates)`
                        ]
                    };

                    // Reset form
                    setTimeout(() => {
                        this.selectedFile = null;
                        document.getElementById('csvFileInput').value = '';
                        this.csvData = [];
                        this.onDistrictChange();
                    }, 2000);
                } else {
                    this.processingStatus = {
                        type: 'error',
                        title: 'Import Failed',
                        message: data.message || 'An error occurred during import',
                        details: data.errors || []
                    };
                }
            } catch (error) {
                console.error('Import error:', error);
                this.processingStatus = {
                    type: 'error',
                    title: 'Import Error',
                    message: error.message || 'An unexpected error occurred',
                    details: []
                };
            } finally {
                this.isProcessing = false;
            }
        },

        // Show alert
        showAlert(message, type) {
            const alertDiv = document.createElement('div');
            const bgClass = type === 'success' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-red-100 text-red-700 border-red-300';
            
            alertDiv.className = `fixed top-24 right-8 ${bgClass} p-4 rounded-lg border max-w-sm z-50 shadow-lg`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);

            setTimeout(() => alertDiv.remove(), 4000);
        }
    };
}
</script>

@endsection
