<?php

use App\Models\BackupTask;
use App\Models\User;

test('the page can be rendered by by the owner of the remote server', function () {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('backup-tasks.edit', $backupTask));

    $response->assertOk();
    $response->assertViewIs('backup-tasks.edit');
    $response->assertViewHas('backupTask', $backupTask);

    $this->assertAuthenticatedAs($user);
    $this->assertEquals($user->id, $backupTask->user_id);
});

test('the page is not rendered by unauthorized users', function () {

    $user = User::factory()->create();

    $backupTask = BackupTask::factory()->create();

    $response = $this->actingAs($user)->get(route('backup-tasks.edit', $backupTask));

    $response->assertForbidden();

    $this->assertAuthenticatedAs($user);

    $this->assertNotEquals($user->id, $backupTask->user_id);
});

test('the page is not rendered by guests', function () {

    $backupTask = BackupTask::factory()->create();

    $response = $this->get(route('backup-tasks.edit', $backupTask));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});
