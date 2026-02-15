<!-- Unlock Batch Modal (Admin Only) -->
<div x-show="showUnlockBatchModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center" @click="if (isUnlocking === false) showUnlockBatchModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-50" @click.stop style="pointer-events: auto;">
        <!-- Header -->
        <div class="bg-purple-50 border-b border-purple-200 px-6 py-4">
            <h3 class="text-lg font-bold text-purple-800">Unlock Batch</h3>
            <p class="text-sm text-purple-600">Admin action - Allow resubmission</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 space-y-4">
            <!-- Batch Info -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-600 mb-1">Batch ID</p>
                <p class="text-sm font-mono text-gray-800" x-text="selectedBatchId || 'Loading...'"></p>
            </div>

            <!-- Admin Warning -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                <p class="text-sm text-purple-700">
                    <i class="fas fa-shield-alt mr-2"></i>
                    This is a restricted administrative action.
                </p>
            </div>

            <!-- Unlock Reason Input -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Reason for Unlock <span class="text-red-600">*</span>
                </label>
                <textarea 
                    x-model="unlockReason"
                    placeholder="Document why this batch needs to be unlocked. Minimum 10 characters."
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                    rows="4"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    <span x-text="(unlockReason || '').length"></span>/1000 characters
                    <template x-if="(unlockReason || '').length < 10 && (unlockReason || '').length > 0">
                        <span class="text-red-600 ml-2">(minimum 10 required)</span>
                    </template>
                </p>
            </div>

            <!-- Audit Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    This action will be fully logged in the audit trail.
                </p>
            </div>
        </div>

        <!-- Footer -->
         <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
             <button 
                 type="button"
                 @click="showUnlockBatchModal = false; unlockReason = ''; selectedBatchId = null; isUnlocking = false"
                 class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
             >
                 Cancel
             </button>
             <button 
                 type="button"
                 @click="unlockBatchConfirm()"
                 :disabled="isUnlocking || (unlockReason || '').length < 10"
                 class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
             >
                 <span x-show="!isUnlocking">
                     <i class="fas fa-unlock"></i> Unlock Batch
                 </span>
                 <span x-show="isUnlocking" class="flex items-center gap-2">
                     <i class="fas fa-spinner fa-spin"></i> Processing...
                 </span>
             </button>
         </div>
    </div>
</div>
