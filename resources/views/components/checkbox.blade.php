@props(['name', 'value' => null, 'label' => null])

<div class="form-check">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 dark:focus:ring-primary-600 dark:focus:ring-offset-gray-800" id="{{ $name }}" {{ $attributes }}>
    @if ($label)
        <label class="form-check-label" for="{{ $name }}">
            {{ $label }}
        </label>
    @endif
</div>
