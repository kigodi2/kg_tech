<!-- Approve Batch Modal -->
<div x-show="showApproveBatchModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center pointer-events-none" @click="showApproveBatchModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-50 pointer-events-auto" @click.stop>
        <!-- Header -->
        <div class="bg-green-50 border-b border-green-200 px-6 py-4">
            <h3 class="text-lg font-bold text-green-800">Approve Batch</h3>
            <p class="text-sm text-green-600">Confirm approval of marks batch</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 space-y-4">
            <!-- Batch Info -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-600 mb-1">Batch ID</p>
                <p class="text-sm font-mono text-gray-800" x-text="selectedBatchId || 'Loading...'"></p>
            </div>

            <!-- Feedback Input -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Feedback <span class="text-gray-400">(optional)</span>
                </label>
                <textarea 
                    x-model="approveFeedback"
                    placeholder="Add optional feedback for the submitter..."
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                    rows="3"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    <span x-text="(approveFeedback || '').length"></span>/1000 characters
                </p>
            </div>

            <!-- Warning -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                <p class="text-sm text-green-700">
                    <i class="fas fa-check-circle mr-2"></i>
                    This action will move the batch to <strong>approved</strong> status.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
            <button 
                type="button"
                @click="showApproveBatchModal = false; approveFeedback = ''"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="button"
                @click="approveBatchConfirm()"
                :disabled="isApproving"
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
                <span x-show="!isApproving">
                    <i class="fas fa-check"></i> Approve
                </span>
                <span x-show="isApproving" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i> Processing...
                </span>
            </button>
        </div>
    </div>
</div>
