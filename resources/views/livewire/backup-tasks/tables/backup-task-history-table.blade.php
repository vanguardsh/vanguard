<div>
    @if (count($backupTaskLogs) !== 0)
        <div>
            <x-table.table-wrapper
                title="{{ __('Previously Executed Backup Tasks') }}"
                description="{{ __('View your log of previously executed backup tasks.') }}">
                <x-slot name="icon">
                    <x-heroicon-o-archive-box class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </x-slot>
                <x-table.table-header>
                    <div class="col-span-2">{{ __('Label') }}</div>
                    <div class="col-span-2">{{ __('Backup Type') }}</div>
                    <div class="col-span-2">{{ __('Backup Destination') }}</div>
                    <div class="col-span-2">{{ __('Result') }}</div>
                    <div class="col-span-2">{{ __('Date') }}</div>
                    <div class="col-span-2">{{ __('Actions') }}</div>
                </x-table.table-header>
                <x-table.table-body>
                    @foreach ($backupTaskLogs as $backupTaskLog)
                        <x-table.table-row>
                            <div class="col-span-12 sm:col-span-2 flex flex-col sm:flex-row sm:items-center">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $backupTaskLog->backupTask->label }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 sm:hidden">
                                    {{ $backupTaskLog->created_at ? $backupTaskLog->created_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('d M Y H:i') : __('Never') }}
                                </p>
                            </div>

                            <div class="col-span-12 sm:col-span-2 mt-2 sm:mt-0">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $backupTaskLog->backupTask->type === 'files' ? 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-800 dark:text-cyan-100' }}">
            @svg($backupTaskLog->backupTask->type === 'files' ? 'heroicon-o-document-duplicate' : 'heroicon-o-circle-stack', 'h-4 w-4 mr-1')
            {{ ucfirst($backupTaskLog->backupTask->type) }}
        </span>
                            </div>

                            <div class="col-span-12 sm:col-span-2 mt-2 sm:mt-0">
                                <p class="text-gray-600 dark:text-gray-300">{{ $backupTaskLog->backupTask->backupDestination->label }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">({{ $backupTaskLog->backupTask->backupDestination->type() }})</p>
                            </div>

                            <div class="col-span-12 sm:col-span-2 mt-2 sm:mt-0">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ (bool) $backupTaskLog->successful_at ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' }}">
            @svg((bool) $backupTaskLog->successful_at ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle', 'h-4 w-4 mr-1')
            {{ (bool) $backupTaskLog->successful_at ? __('Finished') : __('Failed') }}
        </span>
                            </div>

                            <div class="col-span-12 sm:col-span-2 mt-2 sm:mt-0 hidden sm:block">
                                <p class="text-gray-600 dark:text-gray-300">
                                    {{ $backupTaskLog->created_at ? $backupTaskLog->created_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('d M Y H:i') : __('Never') }}
                                </p>
                            </div>

                            <div class="col-span-12 sm:col-span-2 mt-4 sm:mt-0 flex justify-start sm:justify-center space-x-2">
                                <x-secondary-button
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-historic-log-{{ $backupTaskLog->id }}')"
                                    iconOnly
                                    title="{{ __('Click to view this log') }}"
                                >
                                    @svg('heroicon-o-document-text', 'h-4 w-4')
                                    <span class="sr-only">{{ __('View Log') }}</span>
                                </x-secondary-button>
                                <x-secondary-button
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'backup-task-remove-historic-log-{{ $backupTaskLog->id }}')"
                                    iconOnly
                                    title="{{ __('Remove this log') }}"
                                >
                                    @svg('heroicon-o-trash', 'h-4 w-4')
                                    <span class="sr-only">{{ __('Remove Log') }}</span>
                                </x-secondary-button>
                            </div>

                            @livewire('backup-tasks.buttons.delete-backup-task-log-button', ['backupTaskLog' => $backupTaskLog])
                            <x-modal name="backup-task-historic-log-{{ $backupTaskLog->id }}">
                                <x-slot name="title">
                                    {{ __('Viewing log for finished task: ":label".', ['label' => $backupTaskLog->backupTask->label]) }}
                                </x-slot>
                                <x-slot name="description">
                                    {{ __('This log was generated :date.', ['date' => $backupTaskLog->created_at->timezone(Auth::user()->timezone ?? config('app.timezone'))->format('l, d F Y H:i')]) }}
                                </x-slot>
                                <x-slot name="icon">
                                    heroicon-o-document-text
                                </x-slot>
                                <div class="text-center">
                                    <div class="mb-5">
                                        <x-textarea id="logOutput" readonly
                                                    class="pre text-sm text-gray-800 bg-gray-50 font-mono" rows="16" wrap>
                                            {{ $backupTaskLog->output }}
                                        </x-textarea>
                                    </div>
                                    <div class="mt-6">
                                        <x-secondary-button x-on:click="$dispatch('close')" centered>
                                            {{ __('Close') }}
                                        </x-secondary-button>
                                    </div>
                                </div>
                            </x-modal>
                        </x-table.table-row>
                    @endforeach
                </x-table.table-body>
            </x-table.table-wrapper>

            <div class="mt-4 flex justify-end">
                {{ $backupTaskLogs->links() }}
            </div>
        </div>
    @endif
</div>
