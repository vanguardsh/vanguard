<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

it('redirects to GitHub when client ID and secret are set', function () {
    config()->set('services.github.client_id', 'fake-client-id');
    config()->set('services.github.client_secret', 'fake-client-secret');

    $response = $this->get(route('github.redirect'));

    $response->assertRedirect();
});

it('redirects back to login with error if GitHub login is not enabled as a guest', function () {
    config()->set('services.github.client_id', null);
    config()->set('services.github.client_secret', null);

    $response = $this->get(route('github.redirect'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    $response->assertSessionHas('loginError', 'GitHub login is not enabled.');
});

it('redirects back to connections with error if GitHub login is not enabled as a user', function () {
    Toaster::fake();
    config()->set('services.github.client_id', null);
    config()->set('services.github.client_secret', null);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('github.redirect'));

    $response->assertRedirect(route('account.connections'));
    $this->assertAuthenticatedAs($user);
    Toaster::assertDispatched(__('The GitHub connection is not available.'));
});

it('logs in existing user with GitHub ID', function () {
    $user = User::factory()->create(['github_id' => '12345']);
    $mockGithubUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGithubUser->shouldReceive('getId')->andReturn('12345');
    $mockGithubUser->shouldReceive('getEmail')->andReturn($user->email);

    Socialite::shouldReceive('driver->user')->andReturn($mockGithubUser);

    $this->get(route('github.callback'))
        ->assertRedirect(route('overview'));

    $this->assertAuthenticatedAs($user);
});

it('updates existing user with GitHub ID when found by email', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    $mockGithubUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGithubUser->shouldReceive('getId')->andReturn('12345');
    $mockGithubUser->shouldReceive('getEmail')->andReturn('user@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($mockGithubUser);

    $this->get(route('github.callback'))
        ->assertRedirect(route('overview'));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'github_id' => '12345',
    ]);
});

it('creates a new user if none exists with GitHub ID or email', function () {
    $mockGithubUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGithubUser->shouldReceive('getId')->andReturn('12345');
    $mockGithubUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $mockGithubUser->shouldReceive('getName')->andReturn('New User');

    Socialite::shouldReceive('driver->user')->andReturn($mockGithubUser);

    $this->get(route('github.callback'))
        ->assertRedirect(route('overview'));

    $user = User::where('email', 'newuser@example.com')->first();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'github_id' => '12345',
    ]);
});
