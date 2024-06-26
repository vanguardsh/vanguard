<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Masmerise\Toaster\Toaster;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $timezone = '';
    public ?int $preferred_backup_destination_id = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->timezone = Auth::user()->timezone;
        $this->preferred_backup_destination_id = Auth::user()->preferred_backup_destination_id ?? null;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'timezone' => ['required', 'string', 'max:255', Rule::in(timezone_identifiers_list())],
            'preferred_backup_destination_id' => ['nullable', 'integer', Rule::exists('backup_destinations', 'id')->where('user_id', $user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Toaster::success(__('Profile details saved.'));
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('overview', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        @if (Auth::user()->canLoginWithGithub())
            <div
                class="my-4 bg-gray-100 dark:bg-gray-950/30 p-2 rounded-lg border border-gray-200 dark:border-gray-950/60 text-sm flex justify-between max-w-md">
                <div>
                    <x-icons.github class="w-6 h-6 inline mr-2 fill-current dark:text-white text-gray-900"/>
                    <span class="font-medium dark:text-white text-gray-800">
                    {{ __('You can sign in to :app with GitHub.', ['app' => config('app.name')]) }}
            </span>
                </div>
                <div>
                    @svg('heroicon-o-check', 'w-6 h-6 text-green-600 dark:text-green-400 inline mr-2')
                </div>
            </div>
        @endif
        <div class="mt-4">
            <x-input-label for="avatar" :value="__('Avatar')"/>
            <div class="flex items-center mt-2">
                <img class="w-20 h-20 rounded-full" src="{{ Auth::user()->gravatar() }}"
                     alt="{{ Auth::user()->name }}"/>
                <a href="https://gravatar.com" target="_blank"
                   class="ml-4 text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100 ease-in-out">
                    {{ __('Update your avatar on Gravatar') }}
                </a>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')"/>
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required
                          autofocus autocomplete="name"/>
            <x-input-error class="mt-2" :messages="$errors->get('name')"/>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')"/>
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required
                          autocomplete="username"/>
            <x-input-error class="mt-2" :messages="$errors->get('email')"/>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification"
                                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')"/>
            <x-select wire:model="timezone" id="timezone" name="timezone" class="mt-1 block w-full">
                @foreach (formatTimezones() as $identifier => $timezone)
                    <option value="{{ $identifier }}">{{ $timezone }}</option>
                @endforeach
            </x-select>
            <x-input-explain>
                {{ __('Your timezone is used to display dates and times to you in your local time.') }}
            </x-input-explain>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')"/>
        </div>

        @if (!Auth::user()->backupDestinations->isEmpty())
            <div>
                <x-input-label for="preferred_backup_destination_id" :value="__('Default Backup Destination')"/>
                <x-select wire:model="preferred_backup_destination_id" id="preferred_backup_destination_id"
                          name="preferred_backup_destination_id" class="mt-1 block w-full">
                    <option value="">{{ __('None') }}</option>
                    @foreach (Auth::user()->backupDestinations as $backupDestination)
                        <option value="{{ $backupDestination->id }}">{{ $backupDestination->label }} - {{ $backupDestination->type() }}</option>
                    @endforeach
                </x-select>
                <x-input-explain>
                    {{ __('The backup destination you select here will be set as the default location for storing new backup tasks.') }}
                </x-input-explain>
                <x-input-error class="mt-2" :messages="$errors->get('preferred_backup_destination_id')"/>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>
                {{ __('Save') }}
            </x-primary-button>
        </div>
    </form>
</section>
