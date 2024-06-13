<?php

use App\Models\BackupTaskLog;

it('scopes finished task logs', function () {

    BackupTaskLog::factory()->create([
        'finished_at' => null,
    ]);

    $finishedBackupTaskLog = BackupTaskLog::factory()->create([
        'finished_at' => now(),
    ]);

    $finishedBackupTaskLogs = BackupTaskLog::finished()->get();

    expect($finishedBackupTaskLogs->count())->toBe(1)
        ->and($finishedBackupTaskLogs->first()->id)->toBe($finishedBackupTaskLog->id);
});

it('sets finished time', function () {

    $backupTaskLog = BackupTaskLog::factory()->create([
        'finished_at' => null,
    ]);

    $backupTaskLog->setFinishedTime();

    expect($backupTaskLog->finished_at)->not->toBeNull();
});

it('sets successful time', function () {

    $backupTaskLog = BackupTaskLog::factory()->create([
        'successful_at' => null,
    ]);

    $backupTaskLog->setSuccessfulTime();

    expect($backupTaskLog->successful_at)->not->toBeNull();
});
