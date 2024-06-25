<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Masmerise\Toaster\Toaster;

class GitHubSocialiteController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     */
    public function redirectToProvider(): RedirectResponse|Redirect
    {
        $providerCredentialsEmpty = !can_connect_github();

        if ($providerCredentialsEmpty && Auth::guest()) {
            Log::debug('GitHub login is not enabled. Redirecting back to login.');

            return Redirect::route('login')->with('loginError', 'GitHub login is not enabled.');
        }

        if ($providerCredentialsEmpty && Auth::user()) {
            Log::debug('GitHub login is not enabled. Redirecting back to login.');

            Toaster::error(__('The GitHub connection is not available.'));
            return Redirect::route('account.connections');
        }

        /** @var GitHubProvider $githubProvider */
        $githubProvider = Socialite::driver('github');

        return $githubProvider
            ->scopes(['read:user'])
            ->redirect();
    }

    public function handleProviderCallback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            if (($user = $this->findUserByGitHubId($githubUser->getId())) instanceof \App\Models\User) {
                return $this->loginAndRedirect($user, 'Found GH ID associated with this user, logging them in.');
            }

            if (($user = $this->findUserByEmailAndUpdateGitHubId($githubUser)) instanceof \App\Models\User) {
                return $this->loginAndRedirect($user, 'Adding the user\'s GH ID to their account.');
            }

            return $this->createUserAndLogin($githubUser);

        } catch (Exception $e) {
            Log::error('GitHub OAuth login error: ' . $e->getMessage());

            return Redirect::route('login')->with('error', 'Authentication failed. There may be an error with GitHub. Please try again later.');
        }
    }

    private function findUserByGitHubId(int $githubId): ?User
    {
        return User::where('github_id', $githubId)->first();
    }

    private function findUserByEmailAndUpdateGitHubId(SocialiteUser $githubUser): ?User
    {
        $user = User::where('email', $githubUser->getEmail())->first();

        $user?->update(['github_id' => $githubUser->getId()]);

        return $user;
    }

    private function createUserAndLogin(SocialiteUser $githubUser): RedirectResponse
    {
        $user = User::create([
            'name' => $githubUser->getName(),
            'email' => $githubUser->getEmail(),
            'github_id' => $githubUser->getId(),
        ]);

        Log::debug('Creating new user with their GitHub ID and logging them in.', ['id' => $githubUser->getId()]);
        Auth::login($user);

        return Redirect::route('overview');
    }

    private function loginAndRedirect(User $user, string $message): RedirectResponse
    {
        Log::debug($message, ['id' => $user->github_id]);
        Auth::login($user);

        return Redirect::route('overview');
    }
}
