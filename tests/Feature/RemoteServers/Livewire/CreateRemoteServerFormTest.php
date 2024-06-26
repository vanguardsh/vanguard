<?php

use App\Livewire\RemoteServers\CreateRemoteServerForm;
use App\Models\RemoteServer;
use App\Models\User;

test('create remote server form is rendered', function () {

    Livewire::test(CreateRemoteServerForm::class)
        ->assertStatus(200);
});

test('a user can create a remote server that we cannot connect to', function () {

    $component = Livewire::test(CreateRemoteServerForm::class)
        ->set('label', 'Test Server')
        ->set('host', '127.0.0.1')
        ->set('username', 'test')
        ->set('port', 22)
        ->call('submit');

    $component->assertSet('showingConnectionView', true);
    $component->assertSet('canConnectToRemoteServer', false);

    $this->assertDatabaseMissing('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '127.0.0.1',
        'username' => 'test',
        'port' => 22,
    ]);
});

test('a user can create a remote server that we can connect to', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(CreateRemoteServerForm::class)
        ->set('label', 'Test Server')
        ->set('host', '127.0.0.1')
        ->set('username', 'test')
        ->set('port', 22)
        ->set('databasePassword', 'password')
        ->set('testOverride', true) // This is a test override to allow the test to bypass the connection check
        ->call('submit');

    $component->assertSet('showingConnectionView', true);
    $component->assertSet('canConnectToRemoteServer', true);

    $component->assertDispatched('serverAdded');

    $remoteServer = RemoteServer::where('label', 'Test Server')->first();

    $this->assertDatabaseHas('remote_servers', [
        'label' => 'Test Server',
        'ip_address' => '127.0.0.1',
        'username' => 'test',
        'port' => 22,
        'user_id' => $user->id,
    ]);

    $this->assertTrue($remoteServer->hasDatabasePassword());
    $this->assertEquals('password', $remoteServer->getDecryptedDatabasePassword());
});

test('required fields are required', function () {

    Livewire::test(CreateRemoteServerForm::class)
        ->call('submit')
        ->assertHasErrors(['label', 'host', 'username']);
});

test('the ip address must be an ip address', function () {

    Livewire::test(CreateRemoteServerForm::class)
        ->set('host', 'not an ip address')
        ->call('submit')
        ->assertHasErrors(['host']);
});

test('ip addresses must be unique', function () {

    $remoteServer = RemoteServer::factory()->create();

    Livewire::test(CreateRemoteServerForm::class)
        ->set('host', $remoteServer->ip_address)
        ->call('submit')
        ->assertHasErrors(['host']);
});

test('port must be in valid range', function () {

    Livewire::test(CreateRemoteServerForm::class)
        ->set('port', 0)
        ->call('submit')
        ->assertHasErrors(['port']);

    Livewire::test(CreateRemoteServerForm::class)
        ->set('port', 65536)
        ->call('submit')
        ->assertHasErrors(['port']);
});
