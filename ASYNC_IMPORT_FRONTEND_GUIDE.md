# Async Bulk Import - Frontend Integration Guide

## Quick Start

For large candidate imports (500+ candidates), use the new async endpoint instead of the traditional sync workflow.

## API Endpoint

```
POST /api/candidates/import/async
```

## JavaScript Example (Complete)

```javascript
// Add this to your import modal or form
async function handleAsyncBulkImport(csvFile, examYear = '2026', examType = 'ACSEE') {
    try {
        // Create form data
        const formData = new FormData();
        formData.append('file', csvFile);
        formData.append('exam_year', examYear);
        formData.append('exam_type', examType);
        formData.append('mode', 'skip'); // or 'replace'

        // Show loading state
        showLoadingMessage('Uploading file...');

        // Send to async endpoint
        const response = await fetch('/api/candidates/import/async', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await response.json();

        if (response.status === 202) {
            // Import accepted - processing in background
            showSuccessMessage('Import queued for processing!');
            console.log('Import ID:', data.import_id);
            console.log('File Path:', data.file_path);
            
            // Optional: Show tracking info
            displayImportStatus(data.import_id);
            
            return {
                success: true,
                importId: data.import_id,
                filePath: data.file_path,
                message: data.message
            };
        } else {
            // Error occurred
            showErrorMessage(data.message || 'Import failed');
            return {
                success: false,
                error: data.message,
                details: data.errors
            };
        }

    } catch (error) {
        console.error('Upload error:', error);
        showErrorMessage('Error uploading file: ' + error.message);
        return {
            success: false,
            error: error.message
        };
    }
}

function showLoadingMessage(message) {
    // Show to user
    console.log('Loading:', message);
}

function showSuccessMessage(message) {
    console.log('Success:', message);
    // Update UI
}

function showErrorMessage(message) {
    console.log('Error:', message);
    // Update UI
}

function displayImportStatus(importId) {
    console.log('Tracking import:', importId);
    // Could display: "Your import is being processed..."
}
```

## Alpine.js Integration (for candidates.blade.php)

```javascript
// Add to your candidatesManager() function
async function importAsyncBulkFile(file) {
    try {
        this.importProcessing = true;
        this.importProcessingMessage = 'Uploading bulk import file...';

        const formData = new FormData();
        formData.append('file', file);
        formData.append('exam_year', this.importExamYear || '2026');
        formData.append('exam_type', this.importExamType || 'ACSEE');
        formData.append('mode', 'skip');

        const response = await fetch('/api/candidates/import/async', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();

        if (response.status === 202) {
            // Success - job dispatched
            this.showMessage('✅ Import queued! Processing in background...', 'success');
            this.showImportModal = false;
            this.importFile = null;
            
            // Optional: Log tracking info
            console.log('Import dispatched:', {
                importId: data.import_id,
                filePath: data.file_path,
                message: data.message
            });
            
        } else {
            this.showMessage('❌ Import failed: ' + (data.message || 'Unknown error'), 'error');
        }

    } catch (error) {
        console.error('Async import error:', error);
        this.showMessage('Error: ' + error.message, 'error');
    } finally {
        this.importProcessing = false;
        this.importProcessingMessage = '';
    }
}
```

## Complete Modal Integration

If you want to add an "Async Bulk Import" button to the import modal:

```blade
<!-- In your import modal -->
<div x-show="showImportModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-3xl w-full">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Import Candidates</h2>
            <button @click="showImportModal = false" class="text-gray-500 hover:text-gray-700 text-2xl">
                &times;
            </button>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            
            <!-- Toggle between Sync and Async -->
            <div class="flex gap-4">
                <button 
                    @click="importMode = 'sync'"
                    :class="importMode === 'sync' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    Standard Import (< 500)
                </button>
                <button 
                    @click="importMode = 'async'"
                    :class="importMode === 'async' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors"
                >
                    Bulk Import (500+)
                </button>
            </div>

            <!-- Async import section -->
            <div x-show="importMode === 'async'" class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-gray-700">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        For large imports, file is processed in background. You'll get immediate confirmation.
                    </p>
                </div>

                <div 
                    @drop.prevent="handleAsyncDrop($event)"
                    @dragover.prevent="asyncDragActive = true"
                    @dragleave.prevent="asyncDragActive = false"
                    :class="asyncDragActive ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'"
                    class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
                >
                    <input 
                        type="file"
                        id="async-import-file"
                        @change="handleAsyncFileSelect($event)"
                        accept=".csv,.txt"
                        class="hidden"
                    >
                    <label for="async-import-file" class="cursor-pointer block">
                        <i class="fas fa-cloud-upload-alt text-4xl text-blue-600 mb-2"></i>
                        <p class="text-lg font-semibold text-gray-700">Drop CSV file here or click</p>
                        <p class="text-sm text-gray-500 mt-1">Accepts .csv and .txt files (up to 50MB)</p>
                    </label>
                </div>

                <div x-show="asyncImportFile" class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="text-sm text-gray-700">
                        <strong>Selected:</strong> <span x-text="asyncImportFile ? asyncImportFile.name : ''"></span>
                        <span class="text-gray-500" x-text="asyncImportFile ? '(' + (asyncImportFile.size / 1024 / 1024).toFixed(1) + ' MB)' : ''"></span>
                    </p>
                </div>

                <button 
                    @click="startAsyncImport()"
                    :disabled="!asyncImportFile || asyncImporting"
                    class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                >
                    <i :class="asyncImporting ? 'fas fa-spinner animate-spin' : 'fas fa-upload'"></i>
                    <span x-text="asyncImporting ? 'Processing...' : 'Start Bulk Import'"></span>
                </button>
            </div>

            <!-- Standard sync section (existing) -->
            <div x-show="importMode === 'sync'">
                <!-- Your existing sync import UI -->
            </div>
        </div>
    </div>
</div>
```

## Alpine.js Methods for Async Import

```javascript
// Add these methods to your candidatesManager() component

// Data properties
importMode: 'sync', // 'sync' or 'async'
asyncImportFile: null,
asyncDragActive: false,
asyncImporting: false,

// Methods
handleAsyncFileSelect(event) {
    const files = event.target.files;
    if (files.length > 0) {
        this.asyncImportFile = files[0];
    }
},

handleAsyncDrop(event) {
    this.asyncDragActive = false;
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        this.asyncImportFile = files[0];
    }
},

async startAsyncImport() {
    if (!this.asyncImportFile) {
        this.showMessage('Please select a file', 'error');
        return;
    }

    this.asyncImporting = true;
    
    try {
        const formData = new FormData();
        formData.append('file', this.asyncImportFile);
        formData.append('exam_year', this.importExamYear || '2026');
        formData.append('exam_type', this.importExamType || 'ACSEE');
        formData.append('mode', 'skip');

        const response = await fetch('/api/candidates/import/async', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();

        if (response.status === 202) {
            this.showMessage('✅ ' + data.message, 'success');
            this.showImportModal = false;
            this.asyncImportFile = null;
            
            // Could add UI to show import tracking
            console.log('Import dispatched with ID:', data.import_id);
            
        } else {
            this.showMessage('Error: ' + (data.message || 'Import failed'), 'error');
        }

    } catch (error) {
        console.error('Import error:', error);
        this.showMessage('Error: ' + error.message, 'error');
    } finally {
        this.asyncImporting = false;
    }
}
```

## User Experience Flow

### When to Use Sync Import
- ✅ Small batches (< 500 candidates)
- ✅ Want immediate feedback
- ✅ Need to verify before commit

### When to Use Async Import
- ✅ Large batches (500+ candidates)
- ✅ Don't want to wait
- ✅ Server performance critical

## Response Handling

### Success (202 Accepted)
```json
{
  "success": true,
  "message": "Import job dispatched. Processing in background...",
  "file_path": "imports/XYZ.csv",
  "import_id": "import_abc123"
}
```

**Action**: Show success message, close modal, refresh candidates list

### Error (400/422)
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": {
    "file": ["The file must be a csv file."]
  }
}
```

**Action**: Show error message, keep modal open, allow retry

## Monitoring Import Progress

The job logs progress as it processes. Check:

```bash
# In production
tail -f storage/logs/laravel.log | grep "bulk import"

# Or in real-time
php artisan queue:work --verbose
```

Sample output:
```
[2026-02-15 12:00:00] Starting candidate bulk import
[2026-02-15 12:00:15] Bulk import summary
  - total_rows: 4276
  - imported: 4177
  - skipped: 99
[2026-02-15 12:00:20] Candidate bulk import completed successfully
```

## Testing Checklist

- [ ] Upload 100 candidates → Gets 202 response
- [ ] Upload 1000 candidates → Gets 202 response
- [ ] Check logs for processing status
- [ ] Verify candidates were imported
- [ ] Check temp files are cleaned up
- [ ] Test with invalid CSV → Shows error

## Troubleshooting

### Getting 400 error
→ Check file format (must be CSV)
→ Check file size (max 50MB)
→ Check required columns

### Getting 422 error
→ File validation failed
→ Check error details in response
→ Download template and use that format

### No progress indication
→ Job is processing in background
→ Check logs: `tail -f storage/logs/laravel.log`
→ Could add real-time progress UI (future enhancement)

---

**Done!** Async bulk import is ready for production use. 🚀
