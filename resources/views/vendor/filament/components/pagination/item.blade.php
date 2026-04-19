@props([
    'active' => false,
    'ariaLabel' => null,
    'disabled' => false,
    'icon' => null,
    'iconAlias' => null,
    'label' => null,
])

<li
    {{
        $attributes->class([
            'fi-pagination-item group/item',
            'fi-disabled' => $disabled,
            'fi-active' => $active,
        ])
    }}
>
    <button
        aria-label="{{ $ariaLabel }}"
        @disabled($disabled)
        type="button"
        @class([
            'fi-pagination-item-button group/button relative flex min-w-[2.5rem] items-center justify-center overflow-hidden rounded-xl px-3 py-2 outline-none transition duration-75',
            'hover:bg-slate-100 focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary-600 dark:hover:bg-white/5 dark:focus-visible:ring-primary-500' => ! $disabled,
            'bg-primary-600 shadow-md shadow-primary-200/80 dark:bg-primary-500' => $active,
        ])
    >
        @if (filled($icon))
            <x-filament::icon
                :alias="$iconAlias"
                :icon="$icon"
                class="fi-pagination-item-icon h-5 w-5 transition duration-75"
                @class([
                    'text-gray-400 group-hover/button:text-slate-600 dark:text-gray-500 dark:group-hover/button:text-gray-300' => ! ($disabled || $active),
                    'text-gray-300 dark:text-gray-500' => $disabled,
                    'text-white' => $active,
                ])
            />
        @endif

        @if (filled($label))
            <span
                @class([
                    'fi-pagination-item-label text-sm font-semibold',
                    'text-slate-700 dark:text-gray-200' => ! ($disabled || $active),
                    'text-gray-500 dark:text-gray-400' => $disabled,
                    'text-white' => $active,
                ])
            >
                {{ $label ?? '...' }}
            </span>
        @endif
    </button>
</li>
