<?php

namespace App\Events;

use App\Models\BackupTask;
use App\Models\BackupTaskLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreatedBackupTaskLog implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BackupTaskLog $backupTaskLog;

    public BackupTask $backupTask;

    public function __construct(BackupTaskLog $backupTaskLog)
    {
        $this->backupTaskLog = $backupTaskLog;
        $this->backupTask = $backupTaskLog->backupTask;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("new-backup-task-log.{$this->backupTask->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return ['logId' => $this->backupTaskLog->id];
    }
}
