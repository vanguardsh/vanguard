<?php

declare(strict_types=1);

use App\Mail\User\WelcomeMail;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

it('redirects to GitHub when client ID and secret are set', function (): void {
    config()->set('services.github.client_id', 'fake-client-id');
    config()->set('services.github.client_secret', 'fake-client-secret');

    $response = $this->get(route('github.redirect'));

    $response->assertRedirect();
});

it('redirects back to login with error if GitHub login is not enabled', function (): void {
    config()->set('services.github.client_id', null);
    config()->set('services.github.client_secret', null);

    $response = $this->get(route('github.redirect'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('loginError', 'GitHub login is not enabled.');
});

it('logs in existing user with GitHub ID', function (): void {
    $user = User::factory()->create(['github_id' => '12345']);
    $mockGithubUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGithubUser->shouldReceive('getId')->andReturn('12345');
    $mockGithubUser->shouldReceive('getEmail')->andReturn($user->email);

    Socialite::shouldReceive('driver->user')->andReturn($mockGithubUser);

    $this->get(route('github.callback'))
        ->assertRedirect(route('overview'));

    $this->assertAuthenticatedAs($user);
});

it('updates existing user with GitHub ID when found by email', function (): void {
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

it('creates a new user if none exists with GitHub ID or email', function (): void {
    Toaster::fake();
    Mail::fake();
    $mockGithubUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockGithubUser->shouldReceive('getId')->andReturn('12345');
    $mockGithubUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $mockGithubUser->shouldReceive('getName')->andReturn('New User');

    Socialite::shouldReceive('driver->user')->andReturn($mockGithubUser);

    $this->get(route('github.callback'))
        ->assertRedirect(route('overview'));

    Toaster::assertDispatched(__('Successfully logged in via GitHub!'));

    $user = User::where('email', 'newuser@example.com')->first();
    $this->assertAuthenticatedAs($user);
    Mail::assertQueued(WelcomeMail::class);
    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'github_id' => '12345',
    ]);
});
