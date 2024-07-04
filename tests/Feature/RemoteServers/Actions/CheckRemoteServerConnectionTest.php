<?php

declare(strict_types=1);

use App\Actions\RemoteServer\CheckRemoteServerConnection;
use App\Events\RemoteServerConnectivityStatusChanged;
use App\Models\RemoteServer;

it('dispatches events when the script runs if the model id is passed', closure: function (): void {
    Event::fake();

    $remoteServer = RemoteServer::factory()->create([
        'connectivity_status' => RemoteServer::STATUS_OFFLINE,
    ]);

    $check = new CheckRemoteServerConnection;
    $check->byRemoteServerId($remoteServer->id);

    Event::assertDispatched(RemoteServerConnectivityStatusChanged::class, function ($e) use ($remoteServer): bool {
        return $e->remoteServer->id === $remoteServer->id;
    });
});
