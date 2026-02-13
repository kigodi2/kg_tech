@php
$id = $getId();
$name = $getName();
$options = $getComponent()->getOptions();
$placeholder = $getComponent()->getPlaceholder();
$searchPlaceholder = $getComponent()->getSearchPlaceholder();
$isRequired = $isRequired();
$state = $getState();

// Convert options to format needed for Alpine
$formattedOptions = [];
foreach ($options as $value => $label) {
    $formattedOptions[] = ['value' => $value, 'label' => $label];
}
@endphp

<div
    x-data="searchableSelectComponent({
        selected: @js($state),
        options: @js($formattedOptions),
    })"
    class="space-y-2"
>
    @if ($label = $getLabel())
        <label class="block text-sm font-semibold text-gray-700">
            {{ $label }}
            @if ($isRequired)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative" @click.outside="open = false">
        <!-- Hidden input for form submission -->
        <input
            type="hidden"
            id="{{ $id }}"
            name="{{ $name }}"
            x-model="selected"
            wire:model.defer="{{ $getName() }}"
            @if ($isRequired) required @endif
        >

        <!-- Button -->
        <button
            @click="open = !open"
            type="button"
            class="w-full px-3 py-2 border border-gray-300 text-left bg-white hover:bg-gray-50 transition-colors flex justify-between items-center rounded-t"
        >
            <span
                x-text="selected ? options.find(o => o.value == selected)?.label : '{{ $placeholder }}'"
                class="text-gray-700 whitespace-nowrap"
            ></span>
            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
        </button>

        <!-- Dropdown -->
        <div
            x-show="open"
            class="absolute top-full left-0 right-0 bg-white border border-t-0 border-gray-300 z-10 rounded-b flex flex-col shadow-lg"
        >
            <!-- Search Input -->
            <input
                x-model="search"
                type="text"
                placeholder="{{ $searchPlaceholder }}"
                class="px-3 py-2 border-b border-gray-200 focus:outline-none focus:ring-0 text-sm flex-shrink-0"
            >

            <!-- Options -->
            <div class="max-h-64 overflow-y-auto">
                <template x-for="option in options.filter(o => o.label.toLowerCase().includes(search.toLowerCase()))" :key="option.value">
                    <div
                        @click="selected = option.value; open = false; $wire.dispatch('input')"
                        :class="selected == option.value ? 'bg-blue-500 text-white' : 'hover:bg-blue-500 hover:text-white'"
                        class="px-3 py-2 cursor-pointer text-sm transition-colors"
                        x-text="option.label"
                    ></div>
                </template>
            </div>
        </div>
    </div>

    @if ($help = $getHelpText())
        <p class="text-xs text-gray-500 mt-1">{{ $help }}</p>
    @endif

    @if ($errors = $getErrors())
        <div class="text-xs text-red-500 mt-1 space-y-1">
            @foreach ($errors as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
</div>

<script>
function searchableSelectComponent(config) {
    return {
        selected: config.selected,
        options: config.options,
        search: '',
        open: false,
    }
}
</script>
