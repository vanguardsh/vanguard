<div
    class="pg:pb-0 scroll-hidden flex h-24 overflow-x-scroll px-8 pb-4 lg:block lg:h-auto lg:justify-normal lg:space-y-3.5 lg:overflow-x-visible lg:px-0">
    <x-sidebar-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
        @svg('heroicon-o-user', 'h-5 w-5 mr-2 inline')
        {{ __('Profile') }}
    </x-sidebar-nav-link>
    <x-sidebar-nav-link :href="route('tags.index')" :active="request()->routeIs('tags.index')" wire:navigate>
        @svg('heroicon-o-tag', 'h-5 w-5 mr-2 inline')
        {{ __('Tags') }}
    </x-sidebar-nav-link>
    <x-sidebar-nav-link :href="route('account.connections')" :active="request()->routeIs('account.connections')" wire:navigate>
        @svg('heroicon-o-key', 'h-5 w-5 mr-2 inline')
        {{ __('Connections') }}
    </x-sidebar-nav-link>
    <x-sidebar-nav-link :href="route('account.remove-account')" :active="request()->routeIs('account.remove-account')" wire:navigate>
        @svg('heroicon-o-trash', 'h-5 w-5 mr-2 inline')
        {{ __('Remove Account') }}
    </x-sidebar-nav-link>
</div>
