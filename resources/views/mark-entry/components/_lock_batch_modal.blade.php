<!-- Lock Batch Modal -->
<div x-show="showLockBatchModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center pointer-events-none" @click="showLockBatchModal = false">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-50 pointer-events-auto" @click.stop>
        <!-- Header -->
        <div class="bg-yellow-50 border-b border-yellow-200 px-6 py-4">
            <h3 class="text-lg font-bold text-yellow-800">Lock Batch</h3>
            <p class="text-sm text-yellow-600">Submit batch to examination authority</p>
        </div>

        <!-- Content -->
        <div class="px-6 py-4 space-y-4">
            <!-- Batch Info -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-600 mb-1">Batch ID</p>
                <p class="text-sm font-mono text-gray-800" x-text="selectedBatchId || 'Loading...'"></p>
            </div>

            <!-- Critical Warning -->
            <div class="bg-red-50 border border-red-300 rounded-lg p-4">
                <p class="text-sm font-semibold text-red-800 flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle mt-0.5"></i>
                    <span>This action is permanent and irreversible</span>
                </p>
                <ul class="text-sm text-red-700 mt-3 ml-6 space-y-1">
                    <li>✓ No further modifications allowed</li>
                    <li>✓ Batch will be locked immediately</li>
                    <li>✓ Can only be unlocked by administrator</li>
                </ul>
            </div>

            <!-- Confirmation Text -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Confirm by typing "LOCK" below
                </label>
                <input 
                    x-model="lockConfirmText"
                    type="text"
                    placeholder="Type LOCK to confirm"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 text-sm font-mono uppercase"
                />
                <p class="text-xs text-gray-500 mt-1">Case-insensitive</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
            <button 
                type="button"
                @click="showLockBatchModal = false; lockConfirmText = ''"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="button"
                @click="lockBatchConfirm()"
                :disabled="isLocking || lockConfirmText.toUpperCase() !== 'LOCK'"
                class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
            >
                <span x-show="!isLocking">
                    <i class="fas fa-lock"></i> Lock & Submit
                </span>
                <span x-show="isLocking" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i> Processing...
                </span>
            </button>
        </div>
    </div>
</div>
