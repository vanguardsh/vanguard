<?php

declare(strict_types=1);

use App\Console\Commands\ExecuteScheduledBackupTasksCommand;
use App\Jobs\RunFileBackupTaskJob;
use App\Models\BackupTask;

use function Pest\Laravel\freezeTime;

it('queues tasks that are eligible to be ran', function (): void {
    Queue::fake();

    $taskOne = BackupTask::factory()->create([
        'status' => 'ready',
        'frequency' => 'weekly',
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => null,
    ]);

    $taskTwo = BackupTask::factory()->create([
        'status' => 'ready',
        'frequency' => 'weekly',
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => now()->subWeek(),
    ]);

    $taskThree = BackupTask::factory()->create([
        'status' => 'ready',
        'frequency' => 'daily',
        'time_to_run_at' => now()->format('H:i'),
    ]);

    $taskFour = BackupTask::factory()->create([
        'status' => 'ready',
        'frequency' => null,
        'time_to_run_at' => null,
        'custom_cron_expression' => '* * * * *',
    ]);

    $this->artisan(ExecuteScheduledBackupTasksCommand::class)
        ->expectsOutputToContain('Running scheduled backup tasks...')
        ->run();

    Queue::assertPushed(RunFileBackupTaskJob::class, fn ($job): bool => $job->backupTaskId === $taskOne->id);

    Queue::assertPushed(RunFileBackupTaskJob::class, fn ($job): bool => $job->backupTaskId === $taskTwo->id);

    Queue::assertPushed(RunFileBackupTaskJob::class, fn ($job): bool => $job->backupTaskId === $taskThree->id);

    Queue::assertPushed(RunFileBackupTaskJob::class, fn ($job): bool => $job->backupTaskId === $taskFour->id);
});

it('updates the weekly run at time for weekly jobs', function (): void {
    Queue::fake();

    freezeTime(fn () => now()->startOfWeek()->addHours(12));

    $task = BackupTask::factory()->create([
        'status' => 'ready',
        'frequency' => 'weekly',
        'time_to_run_at' => now()->format('H:i'),
        'last_scheduled_weekly_run_at' => now()->subWeek(),
    ]);

    $this->artisan(ExecuteScheduledBackupTasksCommand::class)
        ->expectsOutputToContain('Running scheduled backup tasks...')
        ->run();

    $task->refresh();

    $this->assertEquals(now()->format('Y-m-d H:i'), $task->last_scheduled_weekly_run_at->format('Y-m-d H:i'));
});

it('does not queue tasks that are not eligible to be ran', function (): void {
    Queue::fake();

    BackupTask::factory()->create(['status' => 'running']);

    $this->artisan(ExecuteScheduledBackupTasksCommand::class)
        ->expectsOutputToContain('Running scheduled backup tasks...')
        ->run();

    queue::assertNotPushed(RunFileBackupTaskJob::class);
});

it('does not queue tasks that are ineligible to be ran', function (): void {
    Queue::fake();

    freezeTime(fn (): string => now()->format('12:00'));

    BackupTask::factory()->create([
        'status' => 'ready',
        'time_to_run_at' => now()->format('11:00'),
        'frequency' => 'daily',
    ]);

    BackupTask::factory()->create([
        'status' => 'ready',
        'time_to_run_at' => now()->format('11:00'),
        'last_scheduled_weekly_run_at' => now()->days(3),
        'frequency' => 'weekly',
    ]);

    BackupTask::factory()->create([
        'status' => 'ready',
        'time_to_run_at' => now()->format('11:00'),
        'last_scheduled_weekly_run_at' => null,
        'frequency' => 'weekly',
    ]);

    BackupTask::factory()->paused()->create([
        'status' => 'ready',
        'time_to_run_at' => now()->format('11:00'),
        'frequency' => 'daily',
    ]);

    $this->artisan(ExecuteScheduledBackupTasksCommand::class)
        ->expectsOutputToContain('Running scheduled backup tasks...')
        ->run();

    $this->assertEquals('11:00', BackupTask::first()->time_to_run_at);

    Queue::assertNotPushed(RunFileBackupTaskJob::class);
});
