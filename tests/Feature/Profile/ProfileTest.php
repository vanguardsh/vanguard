<?php

use App\Models\BackupDestination;
use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form');
});

test('delete page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile/remove');

    $response
        ->assertOk()
        ->assertSeeVolt('profile.delete-user-form');
});

test('connections page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/profile/connections');

    $response
        ->assertOk();
});

test('profile information can be updated', function () {
    Toaster::fake();
    $user = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('preferred_backup_destination_id', $backupDestination->id)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('America/New_York', $user->timezone);
    $this->assertSame($backupDestination->id, $user->preferred_backup_destination_id);
    $this->assertNull($user->email_verified_at);

    Toaster::assertDispatched((__('Profile details saved.')));
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $component
        ->assertHasErrors('password')
        ->assertNoRedirect();

    $this->assertNotNull($user->fresh());
});

test('the timezone must be valid', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'Invalid/Timezone')
        ->call('updateProfileInformation');

    $component
        ->assertHasErrors('timezone')
        ->assertNoRedirect();
});

test('the preferred backup destination can be nullable - not set', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user->refresh();

    $this->assertNull($user->preferred_backup_destination_id);
});

test('the preferred backup destination must exist', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('preferred_backup_destination_id', 999)
        ->call('updateProfileInformation');

    $component
        ->assertHasErrors('preferred_backup_destination_id')
        ->assertNoRedirect();

    $this->assertNull($user->refresh()->preferred_backup_destination_id);
});

test('the preferred backup destination must belong to the user', function () {

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $backupDestination = BackupDestination::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-profile-information-form')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('timezone', 'America/New_York')
        ->set('preferred_backup_destination_id', $backupDestination->id)
        ->call('updateProfileInformation');

    $component
        ->assertHasErrors('preferred_backup_destination_id')
        ->assertNoRedirect();

    $this->assertNull($user->refresh()->preferred_backup_destination_id);
});
