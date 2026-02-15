# Phase 3C-3: Day 1 Implementation Guide

**Date:** February 13, 2026  
**Focus:** Data Fetching & Display (Sections 1.1 - 1.7)  
**Estimated Time:** 16 hours  
**Deliverables:** 7 functional dashboard sections with real data

---

## Overview: What We're Building Today

We're wiring up 7 critical dashboard sections to display real data from our Phase 3C-2 API endpoints. By end of today, users will see:

1. ✅ **Moderation Dashboard** - All pending batches
2. ✅ **Pending Review List** - With details and action buttons
3. ✅ **Lock Status** - Batches ready for submission
4. ✅ **Submission History** - Timeline of approvals
5. ✅ **Analytics Dashboard** - Charts and statistics
6. ✅ **Audit Trail** - Change history
7. ✅ **Activity Log** - User actions

---

## Architecture Overview

```
Blade Template (mark-entry/index.blade.php)
    ↓
Alpine.js Manager (markEntryManager())
    ↓
Fetch API Calls
    ↓
Phase 3C-2 Endpoints (/api/mark-entry/*)
    ↓
Service Layer → Database
```

---

## Step 1: Enhance Alpine.js Manager

**File:** In the blade template's `<script>` section (around line 2400+)

Add these functions to the `markEntryManager()` object:

```javascript
function markEntryManager() {
    return {
        // ============ STATE ============
        loading: false,
        error: null,
        currentBatch: null,
        moderationBatches: [],
        readyBatches: [],
        submittedBatches: [],
        analyticsData: null,
        auditTrail: [],
        activityLog: [],
        
        // Pagination
        currentPage: 1,
        perPage: 20,
        totalBatches: 0,
        
        // ============ INITIALIZATION ============
        init() {
            console.log('Mark Entry Manager Initialized');
            // We'll load data on demand when sections become visible
        },
        
        // ============ UTILITY FUNCTIONS ============
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
                
                return await response.json();
            } catch (err) {
                this.error = err.message;
                console.error('API Error:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },
        
        // ============ MODERATION DASHBOARD ============
        async loadModerationDashboard(page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/moderation/pending?page=${page}&per_page=${this.perPage}`
                );
                
                this.moderationBatches = response.data;
                this.totalBatches = response.pagination.total;
                this.currentPage = page;
                
                console.log(`Loaded ${this.moderationBatches.length} pending batches`);
            } catch (err) {
                this.error = 'Failed to load moderation dashboard';
                this.showError(this.error);
            }
        },
        
        // ============ LOCK STATUS ============
        async loadLockStatus(page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/submission/ready?page=${page}&per_page=${this.perPage}`
                );
                
                this.readyBatches = response.data;
                console.log(`Loaded ${this.readyBatches.length} batches ready for locking`);
            } catch (err) {
                this.error = 'Failed to load lock status';
                this.showError(this.error);
            }
        },
        
        // ============ SUBMISSION HISTORY ============
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
                
                console.log(`Loaded submission history for batch ${batchId}`);
            } catch (err) {
                this.error = 'Failed to load submission history';
                this.showError(this.error);
            }
        },
        
        // ============ ANALYTICS ============
        async loadAnalytics() {
            try {
                this.loading = true;
                
                // Load overview
                const overview = await this.fetchApi('/api/mark-entry/analytics/overview');
                
                // Load by subject (for chart)
                const bySubject = await this.fetchApi('/api/mark-entry/analytics/by-subject');
                
                // Load error stats
                const errorStats = await this.fetchApi('/api/mark-entry/analytics/errors');
                
                this.analyticsData = {
                    overview: overview.data,
                    bySubject: bySubject.data,
                    errorStats: errorStats.data
                };
                
                console.log('Loaded analytics data');
            } catch (err) {
                this.error = 'Failed to load analytics';
                this.showError(this.error);
            }
        },
        
        // ============ AUDIT TRAIL ============
        async loadAuditTrail(batchId, page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/audit/batch/${batchId}?page=${page}&per_page=${this.perPage}`
                );
                
                this.auditTrail = response.data;
                console.log(`Loaded ${this.auditTrail.length} audit entries`);
            } catch (err) {
                this.error = 'Failed to load audit trail';
                this.showError(this.error);
            }
        },
        
        // ============ ACTIVITY LOG ============
        async loadActivityLog(userId, page = 1) {
            try {
                this.loading = true;
                const response = await this.fetchApi(
                    `/api/mark-entry/audit/user/${userId}?page=${page}&per_page=${this.perPage}`
                );
                
                this.activityLog = response.data;
                console.log(`Loaded ${this.activityLog.length} activity entries`);
            } catch (err) {
                this.error = 'Failed to load activity log';
                this.showError(this.error);
            }
        },
        
        // ============ ERROR HANDLING ============
        showError(message) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-3 rounded shadow-lg';
            toast.textContent = `⚠️ ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.remove(), 5000);
        },
        
        // ============ UTILITY: Format date ============
        formatDate(date) {
            return new Date(date).toLocaleDateString() + ' ' + 
                   new Date(date).toLocaleTimeString();
        }
    }
}
```

---

## Step 2: Update Moderation Dashboard Section

**File:** `resources/views/mark-entry/index.blade.php`

**Find:** Section with id="moderation-dashboard" (around line 1088)

**Replace with:**

```blade
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
    <div x-show="error" class="bg-red-50 border border-red-200 rounded p-4 mb-4">
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
                <p class="text-xs text-gray-600 mb-1">Visible</p>
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
                                    View Details
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State -->
        <div x-show="moderationBatches.length === 0" class="text-center py-8 text-gray-500">
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
```

---

## Step 3: Update Lock Status Section

**File:** `resources/views/mark-entry/index.blade.php`

**Find:** Section with id="lock-status" (around line 1130)

**Replace with:**

```blade
<section id="lock-status" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
    <h2 class="text-xl font-bold text-gray-800 mb-4">🔒 Lock Status</h2>
    
    <div x-show="loading" class="text-center py-8">
        <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
        <p class="text-gray-600 mt-2">Loading batches ready for submission...</p>
    </div>
    
    <div x-show="!loading && !error" @click="loadLockStatus()">
        <!-- Ready Count Badge -->
        <div class="mb-6 inline-block bg-green-50 border border-green-200 rounded px-4 py-2">
            <p class="text-sm text-gray-600">Batches Ready to Lock</p>
            <p class="text-3xl font-bold text-green-600" x-text="readyBatches.length"></p>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
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
        
        <div x-show="readyBatches.length === 0" class="text-center py-8 text-gray-500">
            <p>No batches ready for locking</p>
        </div>
    </div>
</section>
```

---

## Step 4: Update Analytics Dashboard Section

**File:** `resources/views/mark-entry/index.blade.php`

**Find:** Section with id="analytics" (around line 1192)

**Replace with:**

```blade
<section id="analytics" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
    <h2 class="text-xl font-bold text-gray-800 mb-6">📈 Analytics</h2>
    
    <div x-show="loading" class="text-center py-8">
        <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
        <p class="text-gray-600 mt-2">Loading analytics...</p>
    </div>
    
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
```

---

## Step 5: Update Audit Trail Section

**File:** `resources/views/mark-entry/index.blade.php`

**Find:** Section with id="audit-trail" (around line 1234)

**Replace with:**

```blade
<section id="audit-trail" class="bg-white rounded-lg shadow p-6 scroll-mt-32">
    <h2 class="text-xl font-bold text-gray-800 mb-4">🔍 Audit Trail</h2>
    
    <!-- Batch Selector -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch to View Changes</label>
        <input type="number" class="border rounded px-3 py-2 w-full" 
               placeholder="Enter batch ID" @input="selectedBatchId = $el.value">
        <button @click="if(selectedBatchId) loadAuditTrail(selectedBatchId)" 
                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Load Audit Trail
        </button>
    </div>
    
    <!-- Loading State -->
    <div x-show="loading" class="text-center py-8">
        <i class="fas fa-spinner animate-spin text-blue-500 text-2xl"></i>
        <p class="text-gray-600 mt-2">Loading audit trail...</p>
    </div>
    
    <!-- Audit Trail Timeline -->
    <div x-show="!loading && auditTrail.length > 0">
        <div class="space-y-4">
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
    </div>
    
    <div x-show="!loading && auditTrail.length === 0" class="text-center py-8 text-gray-500">
        <p>No audit trail entries found. Select a batch to view changes.</p>
    </div>
</section>
```

---

## Step 6: Quick Test

1. Open browser DevTools (F12)
2. Go to Console tab
3. Navigate to Mark Entry page
4. Click on "Review Dashboard" in sidebar
5. Should see data loading spinner then batch table
6. Check console for any errors

---

## Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| Data not loading | Alpine not initialized | Make sure `@init="init()"` on wrapper div |
| 404 errors | Wrong endpoint URL | Check route list with `php artisan route:list` |
| CORS errors | Missing headers | Check Accept and X-Requested-With headers |
| Empty tables | Permission denied | Check `can:mark-entry.moderate` gate |
| Dates look weird | Timezone issue | Use `formatDate()` helper function |

---

## Next Steps After Day 1

Once sections 1.1-1.7 are complete and displaying data:

1. Add click handlers to batch rows
2. Show batch detail modals
3. Add loading indicators
4. Implement error toasts
5. Test with real data

---

**Ready to start Day 1?** 🚀

Proceed with Step 1, then follow through Steps 2-6 to build out the initial 7 sections.
