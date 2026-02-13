<div class="space-y-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
        </div>
        <div class="flex-1 space-y-3">
            <h3 class="font-semibold text-red-800">CRITICAL: Restore Cannot Be Undone</h3>
            
            <div class="space-y-2 text-sm text-red-700">
                <p>
                    <strong>⚠️ ALL CURRENT DATA WILL BE REPLACED</strong> with data from the backup. 
                    This action cannot be undone except by restoring from another backup.
                </p>

                <p>
                    <strong>Pre-Restore Snapshot:</strong> A complete backup of your current system state 
                    will be automatically created before restore begins. However, you should understand this 
                    is your only recovery option.
                </p>

                <p>
                    <strong>Locked Exam Years:</strong> This restore will overwrite locked exam year data. 
                    You may need to re-lock the exam year afterward.
                </p>

                <p>
                    <strong>Recent Changes:</strong> Any changes made since this backup was created 
                    ({{ $record->created_at?->format('M d, Y H:i') }}) will be lost.
                </p>

                <p>
                    <strong>Audit Trail:</strong> Both the restore operation and your actions leading to it 
                    will be logged in the governance audit trail for compliance and dispute resolution.
                </p>

                <p>
                    <strong>Backup Details:</strong>
                    <br>• Type: {{ ucfirst(str_replace('_', ' ', $record->type)) }}
                    <br>• Created: {{ $record->created_at?->format('M d, Y H:i') }}
                    <br>• Size: {{ $record->getSizeFormatted() }}
                    <br>• Status: {{ $record->getStatusLabel() }}
                </p>
            </div>

            <div class="pt-2 border-t border-red-200">
                <p class="text-xs text-red-600 italic">
                    By proceeding, you acknowledge that you have properly authorized this restore operation 
                    and understand the implications.
                </p>
            </div>
        </div>
    </div>
</div>
