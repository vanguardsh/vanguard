<div>
    @section('title', __('Create Notification Stream'))
    <x-slot name="header">
        {{ __('Create Notification Stream') }}
    </x-slot>
    <x-notification-stream-form :form="$form" submitLabel="{{ __('Save') }}" />
</div>
