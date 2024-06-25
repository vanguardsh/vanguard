<x-account-wrapper pageTitle="{{ __('Connections') }}">
        <x-connection-content-item label="{{ __('GitHub') }}">
            <x-slot name="icon">
                <x-icons.github class="w-8 h-8 inline mr-2 fill-current dark:text-white text-gray-900"/>
            </x-slot>
            @if(!can_connect_github())
                <div class="py-2 px-4 bg-red-50 text-red-600 border-l-4 border-red-600 font-normal my-6">
                    @svg('heroicon-o-exclamation-triangle', 'h-5 w-5 inline mr-2')
                    <span>
                    {{ __('GitHub connection is not configured.') }}
                </span>
                </div>
            @endif

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Connecting your GitHub account with :app will allow you to login with GitHub.', ['app' => config('app.name')]) }}
            </p>

            <x-slot name="actions">
                @if(can_connect_github())
                <a href="{{ route('github.redirect') }}">
                    <x-primary-button>
                        {{ __('Link GitHub') }}
                    </x-primary-button>
                </a>
                    @else
                        <x-primary-button disabled class="cursor-not-allowed bg-opacity-50">
                            {{ __('Link GitHub') }}
                        </x-primary-button>
                @endif
            </x-slot>
        </x-connection-content-item>
</x-account-wrapper>
