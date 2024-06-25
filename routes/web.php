<?php

use App\Http\Controllers\BackupDestinations\EditController as BackupDestinationEditController;
use App\Http\Controllers\BackupTasks\EditController as BackupTaskEditController;
use App\Http\Controllers\RemoteServers\EditController as RemoteServerEditController;
use App\Http\Controllers\Tags\EditController as TagEditControllerAlias;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/overview');

Route::get('overview', [App\Http\Controllers\OverviewController::class, '__invoke'])
    ->middleware(['auth'])
    ->name('overview');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('profile/remove', 'account.remove-account')
    ->middleware(['auth'])
    ->name('account.remove-account');

Route::view('profile/connections', 'account.connections')
    ->middleware(['auth'])
    ->name('account.connections');

Route::view('frequently-asked-questions', 'frequently-asked-questions')
    ->middleware(['auth'])
    ->name('frequently-asked-questions');

Route::middleware(['auth'])
    ->prefix('remote-servers')
    ->group(function () {

        Route::view('/', 'remote-servers.index')
            ->name('remote-servers.index');

        Route::view('create', 'remote-servers.create')
            ->name('remote-servers.create');

        Route::get('edit/{remoteServer}', [RemoteServerEditController::class, '__invoke'])
            ->name('remote-servers.edit')
            ->middleware('can:update,remoteServer');
    });

Route::middleware(['auth'])
    ->prefix('backup-destinations')
    ->group(function () {

        Route::view('/', 'backup-destinations.index')
            ->name('backup-destinations.index');

        Route::view('create', 'backup-destinations.create')
            ->name('backup-destinations.create');

        Route::get('edit/{backupDestination}', [BackupDestinationEditController::class, '__invoke'])
            ->name('backup-destinations.edit')
            ->middleware('can:update,backupDestination');

    });

Route::middleware(['auth'])
    ->prefix('backup-tasks')
    ->group(function () {

        Route::view('/', 'backup-tasks.index')
            ->name('backup-tasks.index');

        Route::view('create', 'backup-tasks.create')
            ->name('backup-tasks.create');

        Route::get('edit/{backupTask}', [BackupTaskEditController::class, '__invoke'])
            ->name('backup-tasks.edit')
            ->middleware('can:update,backupTask');
    });

Route::middleware(['auth'])
    ->prefix('tags')
    ->group(function () {

        Route::view('/', 'tags.index')
            ->name('tags.index');

        Route::view('create', 'tags.create')
            ->name('tags.create');

        Route::get('edit/{tag}', [TagEditControllerAlias::class, '__invoke'])
            ->name('tags.edit')
            ->middleware('can:update,tag');

    });

require __DIR__ . '/auth.php';
