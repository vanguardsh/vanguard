<?php

namespace App\Events;

use App\Models\BackupDestination;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupDestinationConnectionCheck implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly BackupDestination $backupDestination,
        public ?string $status = null
    ) {
        $this->status = $status ?? $this->backupDestination->status;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("backup-destinations.{$this->backupDestination->id}"),
        ];
    }
}
