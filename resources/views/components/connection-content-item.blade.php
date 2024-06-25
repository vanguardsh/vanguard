@props(['icon', 'label'])

<div class="bg-white dark:bg-gray-800 shadow-none sm:rounded-[0.70rem] border border-gray-950/5 overflow-hidden" {{ $attributes->merge() }}>
    <div class="max-w-xl p-4 sm:p-8">
        @isset($icon)
            {{ $icon }}
        @endif
        <h2 class="inline text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $label }}
        </h2>
        <div class="mt-4">
            {{ $slot }}
        </div>
    </div>
    <footer class="space-x-2 bg-gray-50/60 border-t border-gray-100 mt-6 p-4">
        {{ $actions }}
    </footer>
</div>
