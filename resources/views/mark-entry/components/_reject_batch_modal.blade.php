<!-- Reject Batch Modal -->
<div x-show="showRejectBatchModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center pointer-events-none" @click="showRejectBatchModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-50 pointer-events-auto" @click.stop>
        <!-- Header -->
        <div class="bg-red-50 border-b border-red-200 px-6 py-4">
            <h3 class="text-lg font-bold text-red-800">Reject Batch</h3>
            <p class="text-sm text-red-600">Mark for resubmission with feedback</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 space-y-4">
            <!-- Batch Info -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-600 mb-1">Batch ID</p>
                <p class="text-sm font-mono text-gray-800" x-text="selectedBatchId || 'Loading...'"></p>
            </div>

            <!-- Rejection Reason Input -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Reason for Rejection <span class="text-red-600">*</span>
                </label>
                <textarea 
                    x-model="rejectReason"
                    placeholder="Explain why this batch is being rejected. Minimum 10 characters."
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm"
                    rows="4"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    <span x-text="(rejectReason || '').length"></span>/1000 characters
                    <template x-if="(rejectReason || '').length < 10 && (rejectReason || '').length > 0">
                        <span class="text-red-600 ml-2">(minimum 10 required)</span>
                    </template>
                </p>
            </div>

            <!-- Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    This action requires the submitter to resubmit the batch.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
            <button 
                type="button"
                @click="showRejectBatchModal = false; rejectReason = ''"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="button"
                @click="rejectBatchConfirm()"
                :disabled="isRejecting || (rejectReason || '').length < 10"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
                <span x-show="!isRejecting">
                    <i class="fas fa-times"></i> Reject
                </span>
                <span x-show="isRejecting" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i> Processing...
                </span>
            </button>
        </div>
    </div>
</div>
