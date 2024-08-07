<?php

declare(strict_types=1);

use App\Livewire\Profile\APITokenManager;
use App\Models\User;
use App\Services\SanctumAbilitiesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->mockAbilitiesService = Mockery::mock(SanctumAbilitiesService::class);
    $this->mockAbilitiesService->shouldReceive('getAbilities')->andReturn([
        'General' => [
            'manage-tags' => ['name' => 'Manage Tags', 'description' => 'Allows managing of tags'],
        ],
        'Backup Destinations' => [
            'view-backup-destinations' => ['name' => 'View Backup Destinations', 'description' => 'Allows viewing backup destinations'],
        ],
        'Backup Tasks' => [
            'create-backup-tasks' => ['name' => 'Create Backup Tasks', 'description' => 'Allows creating new backup tasks'],
        ],
    ]);
    $this->app->instance(SanctumAbilitiesService::class, $this->mockAbilitiesService);
});

test('the component can be rendered', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)->assertOk();
});

test('the page can be visited by authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.api'));

    $response
        ->assertOk()
        ->assertSeeLivewire('profile.api-token-manager');
});

test('the page cannot be visited by guests', function (): void {
    $response = $this->get(route('profile.api'));

    $response->assertRedirect('login');
    $this->assertGuest();
});

test('api tokens can be created with correct abilities format', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
            'manage-tags' => false,
        ])
        ->call('createApiToken')
        ->assertHasNoErrors()
        ->assertDispatched('created');

    $this->assertCount(1, $user->fresh()->tokens);

    $token = $user->fresh()->tokens->first();
    $this->assertEquals('API Token', $token->name);
    $this->assertEquals(['view-backup-destinations', 'create-backup-tasks'], $token->abilities);
});

test('api tokens cannot be created without abilities', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('abilities', [])
        ->call('createApiToken')
        ->assertHasErrors(['abilities']);

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api tokens can be deleted', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('apiTokenIdBeingDeleted', $token->accessToken->id)
        ->call('deleteApiToken');

    $this->assertCount(0, $user->fresh()->tokens);
});

test('api token deletion confirmation works', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->call('confirmApiTokenDeletion', $token->accessToken->id)
        ->assertDispatched('open-modal', 'confirm-api-token-deletion')
        ->assertSet('apiTokenIdBeingDeleted', $token->accessToken->id);
});

test('abilities are reset after token creation', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
        ])
        ->call('createApiToken');

    $component->assertSet('abilities', array_fill_keys(array_keys($component->get('abilities')), false));
});

test('token value is displayed after creation', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('abilities', ['view-backup-destinations' => true])
        ->call('createApiToken');

    $component
        ->assertDispatched('close-modal', 'create-api-token')
        ->assertDispatched('open-modal', 'api-token-value')
        ->assertSet('plainTextToken', function ($plainTextToken): bool {
            return ! empty($plainTextToken) && is_string($plainTextToken);
        });
});

test('all available abilities are initially set to false', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class);

    $abilities = $component->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeFalse();
    }
});

test('validation error occurs when no abilities are selected', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('name', 'API Token')
        ->set('abilities', [
            'view-backup-destinations' => false,
            'create-backup-tasks' => false,
            'manage-tags' => false,
        ])
        ->call('createApiToken')
        ->assertHasErrors(['abilities']);
});

test('select all abilities works', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->call('selectAllAbilities');

    $abilities = $component->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeTrue();
    }
});

test('deselect all abilities works', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class)
        ->set('abilities', [
            'view-backup-destinations' => true,
            'create-backup-tasks' => true,
            'manage-tags' => true,
        ])
        ->call('deselectAllAbilities');

    $abilities = $component->get('abilities');
    foreach ($abilities as $ability) {
        expect($ability)->toBeFalse();
    }
});

test('view token abilities modal can be opened', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->call('viewTokenAbilities', $token->accessToken->id)
        ->assertDispatched('open-modal', 'view-token-abilities')
        ->assertSet('viewingTokenId', $token->accessToken->id);
});

test('token listing shows correct information', function (): void {
    $user = User::factory()->create();
    $user->createToken('Test Token', ['view-backup-destinations']);

    Livewire::actingAs($user)->test(APITokenManager::class)
        ->assertSee('Test Token')
        ->assertSee('Never'); // For 'Last Used' column
});

test('group expansion toggle works', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(APITokenManager::class);

    $component->call('toggleGroup', 'General')
        ->assertSet('expandedGroups.General', true)
        ->call('toggleGroup', 'General')
        ->assertSet('expandedGroups.General', false);
});
