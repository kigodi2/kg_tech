<!-- District Bulk Import Section -->
<div class="space-y-6">

    <!-- Upload Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">📦 Upload District Marks ZIP</h2>
        
        <div class="grid grid-cols-3 gap-4">
            <!-- Exam Year -->
             <div>
                 <label class="block text-sm font-semibold text-gray-700 mb-2">Exam Year *</label>
                 <select x-model="districtExamYear" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                     <option value="">Select Exam Year</option>
                     <template x-for="year in examYears" :key="year.id">
                         <option :value="year.year_label" x-text="year.year_label"></option>
                     </template>
                 </select>
             </div>

            <!-- District -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">District *</label>
                <select x-model.number="districtId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select District</option>
                    <template x-for="district in districts" :key="district.id">
                        <option :value="district.id" x-text="`${district.code} - ${district.name}`"></option>
                    </template>
                </select>
            </div>

            <!-- Spacing -->
            <div></div>
        </div>

        <!-- File Upload -->
        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">ZIP File *</label>
            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer" @click="$refs.zipInput.click()" @dragover="dragOver = true" @dragleave="dragOver = false" @drop="handleFileDrop($event)" :class="dragOver && 'border-blue-500 bg-blue-50'">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 font-medium">Click or drag ZIP file here</p>
                <p class="text-gray-400 text-sm mt-1">Format: DISTRICT_CODE_YEAR.zip</p>
                <input type="file" @change="handleFileSelect($event)" accept=".zip" x-ref="zipInput" hidden>
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
            
            <!-- Validation Status -->
            <div x-show="!districtPreview.is_valid" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-red-800 mb-2">⚠️ Validation Issues Found:</h4>
                <ul class="space-y-1 text-red-700 text-sm">
                    <template x-for="issue in districtPreview.issues" :key="issue">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle flex-shrink-0 mt-1"></i>
                            <span x-text="issue"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <div x-show="districtPreview.is_valid" class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                <p class="text-green-800 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <strong>ZIP is valid and ready to import</strong>
                </p>
            </div>

            <!-- Preview Data -->
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
                    <p x-text="districtPreview.is_signed ? '✅ Yes' : '❌ No'" class="text-lg font-bold" :class="districtPreview.is_signed ? 'text-green-600' : 'text-gray-600'"></p>
                </div>
            </div>

            <!-- Schools List -->
            <div class="mt-6">
                <h4 class="font-semibold text-gray-800 mb-3">Schools in ZIP:</h4>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="school in districtPreview.schools" :key="school.school_code">
                        <div class="bg-gray-50 rounded-lg p-3 flex justify-between items-center">
                            <div>
                                <p class="font-medium text-gray-800" x-text="`${school.school_code} - ${school.school_name}`"></p>
                                <p class="text-sm text-gray-600" x-text="`${school.total_subjects} subjects, ${school.total_candidates} candidates`"></p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-mono" x-text="`${school.subjects.length} CSV files`"></div>
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
            
            <!-- Overall Progress -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <p class="text-sm font-semibold text-gray-700">Overall Progress</p>
                    <p class="text-sm font-bold text-blue-600" x-text="`${districtProgress.progress_percentage}%`"></p>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all" :style="`width: ${districtProgress.progress_percentage}%`"></div>
                </div>
            </div>

            <!-- Import Status -->
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
                    <p class="text-2xl font-bold text-gray-800" x-text="districtProgress.summary.total_candidates?.toLocaleString()"></p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Imported</p>
                    <p class="text-2xl font-bold text-green-600" x-text="districtProgress.summary.successful_candidates?.toLocaleString()"></p>
                </div>
            </div>

            <!-- Per-School Status -->
            <div>
                <h4 class="font-semibold text-gray-800 mb-3">School Status:</h4>
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    <template x-for="school in districtProgress.schools" :key="school.school_id">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-800" x-text="`${school.school_code} - ${school.school_name}`"></p>
                                    <p class="text-sm text-gray-600" x-text="`${school.successful_candidates}/${school.total_candidates} candidates (${school.processed_subjects}/${school.total_subjects} subjects)`"></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold" :class="school.status === 'success' ? 'bg-green-100 text-green-800' : school.status === 'processing' ? 'bg-blue-100 text-blue-800' : school.status === 'partial' ? 'bg-yellow-100 text-yellow-800' : school.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'" x-text="school.status.toUpperCase()"></span>
                            </div>
                            <div x-show="school.status === 'processing'" class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${(school.processed_subjects / school.total_subjects) * 100}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Auto-refresh notice -->
            <p class="mt-4 text-xs text-gray-500 text-center">
                <i class="fas fa-sync-alt animate-spin"></i> Refreshing every 2 seconds...
            </p>
        </div>
    </template>

    <!-- Import Complete Section -->
    <template x-if="districtImportComplete">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">✅ Import Complete</h3>
            
            <!-- Status Badge -->
            <div class="mb-6 p-4 rounded-lg" :class="districtProgress.status === 'completed' ? 'bg-green-50 border border-green-200' : districtProgress.status === 'partial' ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200'">
                <p class="font-bold" :class="districtProgress.status === 'completed' ? 'text-green-800' : districtProgress.status === 'partial' ? 'text-yellow-800' : 'text-red-800'">
                    <i class="fas" :class="districtProgress.status === 'completed' ? 'fa-check-circle' : districtProgress.status === 'partial' ? 'fa-exclamation-circle' : 'fa-times-circle'"></i>
                    <span x-text="`Status: ${districtProgress.status.toUpperCase()}`" class="ml-2"></span>
                </p>
                <p class="text-sm mt-2" x-text="districtProgress.status === 'completed' ? 'All schools imported successfully!' : districtProgress.status === 'partial' ? 'Some schools failed. See details below.' : 'Import failed. Please check errors.'"></p>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                    <p class="text-gray-600 text-sm">Successful Schools</p>
                    <p class="text-3xl font-bold text-green-600" x-text="districtProgress.summary?.successful_schools || 0"></p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-100">
                    <p class="text-gray-600 text-sm">Partial Schools</p>
                    <p class="text-3xl font-bold text-yellow-600" x-text="districtProgress.summary?.partial_schools || 0"></p>
                </div>
                <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                    <p class="text-gray-600 text-sm">Failed Schools</p>
                    <p class="text-3xl font-bold text-red-600" x-text="districtProgress.summary?.failed_schools || 0"></p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <p class="text-gray-600 text-sm">Total Imported</p>
                    <p class="text-3xl font-bold text-blue-600" x-text="(districtProgress.summary?.successful_candidates || 0).toLocaleString()"></p>
                </div>
            </div>

            <!-- Failed Schools (if any) -->
            <template x-if="districtProgress.summary?.failed_schools > 0 || districtProgress.summary?.partial_schools > 0">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-red-800 mb-3">Issues Found:</h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="school in districtProgress.schools.filter(s => s.status === 'failed' || s.status === 'partial')" :key="school.school_id">
                            <div class="bg-white rounded p-3">
                                <p class="font-medium text-gray-800" x-text="`${school.school_code} - ${school.school_name}`"></p>
                                <p class="text-sm text-red-700 mt-1" x-text="school.error_summary || 'Unknown error'"></p>
                                <button @click="retryDistrictSchool(school.school_id)" class="mt-2 text-sm bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded transition-colors">
                                    Retry This School
                                </button>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Retry All Button -->
                    <button @click="retryDistrictAll()" class="mt-4 w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors font-medium flex items-center justify-center gap-2">
                        <i class="fas fa-redo"></i> Retry All Failed Schools
                    </button>
                </div>
            </template>

            <!-- Success Message -->
            <template x-if="districtProgress.status === 'completed'">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-green-800 font-medium">
                        ✅ All <span x-text="districtProgress.summary.total_candidates"></span> candidates' marks have been imported successfully!
                    </p>
                    <p class="text-green-700 text-sm mt-2">You can now view the marks in the mark entry interface or dashboard.</p>
                </div>
            </template>

            <!-- Reset Button -->
            <button @click="resetDistrictImport()" class="mt-6 w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors font-medium">
                Import Another ZIP
            </button>
        </div>
    </template>

</div>
