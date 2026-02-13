<!-- Enhanced Audit Trail Component -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-history text-blue-600"></i>
            Complete Audit Trail
        </h3>
        <p class="text-sm text-gray-600 mt-1">All operations, modifications, and approvals logged with timestamps and user information</p>
    </div>

    <div class="divide-y divide-gray-200">
        <!-- Timeline Entry: Upload -->
        <div class="px-8 py-6 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-upload text-blue-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Marks Uploaded</p>
                    <p class="text-sm text-gray-600 mt-1">CSV file uploaded by Teacher Name</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                        <span><i class="fas fa-user mr-1"></i>Teacher Name</span>
                        <span><i class="fas fa-clock mr-1"></i>Feb 13, 2026 - 10:30 AM</span>
                        <span><i class="fas fa-file mr-1"></i>450 candidates</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Entry: Validation -->
        <div class="px-8 py-6 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-check text-green-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Validation Completed</p>
                    <p class="text-sm text-gray-600 mt-1">All records passed validation - 0 errors found</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                        <span><i class="fas fa-server mr-1"></i>Automated</span>
                        <span><i class="fas fa-clock mr-1"></i>Feb 13, 2026 - 10:32 AM</span>
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded">Valid</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Entry: Review Started -->
        <div class="px-8 py-6 hover:bg-gray-50 transition-colors">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-eye text-purple-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-800">Review Started</p>
                    <p class="text-sm text-gray-600 mt-1">HOD began moderation review of batch</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                        <span><i class="fas fa-user mr-1"></i>Dr. John Mwase (HOD)</span>
                        <span><i class="fas fa-clock mr-1"></i>Feb 13, 2026 - 11:00 AM</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded">In Progress</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Entry: Approved -->
        <div class="px-8 py-6 hover:bg-gray-50 transition-colors bg-green-50">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-thumbs-up text-green-700 text-sm font-bold"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-green-800">Batch Approved</p>
                    <p class="text-sm text-green-700 mt-1">Approved for submission to examination authority</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-green-700">
                        <span><i class="fas fa-user mr-1"></i>Dr. John Mwase (HOD)</span>
                        <span><i class="fas fa-clock mr-1"></i>Feb 13, 2026 - 14:15 PM</span>
                        <span class="px-2 py-1 bg-green-200 text-green-800 rounded font-bold">Approved</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="px-8 py-12 text-center">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-600 font-semibold">No additional history available</p>
            <p class="text-sm text-gray-500">This is the complete audit trail for this batch</p>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="px-8 py-4 bg-gray-50 border-t border-gray-200 text-xs text-gray-600">
        <i class="fas fa-lock mr-2"></i>
        This audit trail is immutable and serves as the official record for compliance and auditing purposes.
    </div>
</div>
