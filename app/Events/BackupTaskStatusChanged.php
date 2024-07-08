<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BackupTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupTaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly BackupTask $backupTask,
        public ?string $status = null
    ) {
        $this->status = $status ?? $backupTask->getAttribute('status');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("backup-tasks.{$this->backupTask->getAttribute('id')}"),
        ];
    }
}
