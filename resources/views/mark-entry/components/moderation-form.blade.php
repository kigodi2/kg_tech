<!-- Moderation Form Component -->
<div x-data="moderationForm()" class="bg-white rounded-lg shadow p-8">
    <h3 class="text-xl font-bold text-gray-800 mb-6">Moderation Decision Form</h3>

    <!-- Approve Section -->
    <div class="mb-8 pb-8 border-b border-gray-200">
        <h4 class="text-lg font-semibold text-green-700 mb-4 flex items-center gap-2">
            <i class="fas fa-thumbs-up"></i>
            Approve Batch
        </h4>
        <p class="text-gray-600 text-sm mb-4">
            Approve this batch after satisfactory review. It will be ready for submission to the examination authority.
        </p>
        <button @click="submitApproval()" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
            <i class="fas fa-check mr-2"></i>
            Approve This Batch
        </button>
    </div>

    <!-- Reject Section -->
    <div x-data="{ showRejectForm: false }">
        <h4 class="text-lg font-semibold text-red-700 mb-4 flex items-center gap-2">
            <i class="fas fa-times-circle"></i>
            Request Corrections
        </h4>
        <p class="text-gray-600 text-sm mb-4">
            Return this batch to the school if corrections are needed.
        </p>

        <button @click="showRejectForm = !showRejectForm" class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
            <i class="fas fa-reply mr-2"></i>
            Return for Corrections
        </button>

        <!-- Rejection Form -->
        <div x-show="showRejectForm" x-transition class="mt-6 p-6 bg-red-50 border border-red-200 rounded-lg">
            <label class="block text-sm font-bold text-gray-800 mb-3">Reason for Rejection *</label>
            <textarea x-model="rejectionReason" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" rows="5" placeholder="Specify what needs to be corrected:&#10;- Data quality issues&#10;- Validation errors&#10;- Format problems&#10;- Missing information"></textarea>

            <div class="mt-4 flex gap-3">
                <button @click="submitRejection()" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors">
                    Return Batch
                </button>
                <button @click="showRejectForm = false" class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded-lg transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function moderationForm() {
    return {
        rejectionReason: '',

        submitApproval() {
            if (confirm('Are you sure you want to approve this batch? This cannot be undone immediately.')) {
                // Submit approval
                console.log('Approving batch');
            }
        },

        submitRejection() {
            if (!this.rejectionReason.trim()) {
                alert('Please provide a reason for returning the batch');
                return;
            }

            if (confirm('Are you sure you want to return this batch for corrections?')) {
                console.log('Rejecting batch with reason:', this.rejectionReason);
            }
        }
    }
}
</script>
