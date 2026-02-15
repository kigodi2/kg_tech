<!-- Toast Notification Component -->
<div x-show="toastMessage" class="fixed top-24 right-4 z-50 max-w-sm">
    <div 
        :class="{
            'bg-green-50 border-l-4 border-green-500 text-green-700': toastType === 'success',
            'bg-red-50 border-l-4 border-red-500 text-red-700': toastType === 'error',
            'bg-blue-50 border-l-4 border-blue-500 text-blue-700': toastType === 'info',
            'bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700': toastType === 'warning'
        }"
        class="rounded-lg shadow-lg p-4 flex items-start gap-3"
    >
        <!-- Icon -->
        <div class="flex-shrink-0 pt-0.5">
            <i 
                :class="{
                    'fas fa-check-circle': toastType === 'success',
                    'fas fa-exclamation-circle': toastType === 'error',
                    'fas fa-info-circle': toastType === 'info',
                    'fas fa-exclamation-triangle': toastType === 'warning'
                }"
            ></i>
        </div>

        <!-- Message -->
        <div class="flex-1">
            <p class="text-sm font-medium" x-text="toastMessage"></p>
            <template x-if="toastDetails">
                <p class="text-xs opacity-80 mt-1" x-text="toastDetails"></p>
            </template>
        </div>

        <!-- Close Button -->
        <button 
            @click="closeToast()"
            class="flex-shrink-0 text-lg hover:opacity-70 transition-opacity"
        >
            &times;
        </button>
    </div>
</div>
