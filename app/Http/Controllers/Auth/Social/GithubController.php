<?php

namespace App\Http\Controllers\Auth\Social;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Socialite;
use Validator;
use Exception;

class GithubController extends Controller
{
    private $socialite;
    private $user;

    public function __construct(Socialite $socialite, User $user)
    {
        $this->socialite = $socialite;
        $this->user      = $user;
    }
    /**
     * Redirect the user to the GitHub authentication page.
     */
    public function redirectToProvider()
    {
        try {
            return $this->socialite::driver('github')->scopes(['user:email'])->redirect();
        } catch (Exception $e) {
            return $this->sendFailedResponse($e->getMessage());
        }
    }

    /**
     * Obtain the user information from GitHub.
     */
    public function handleProviderCallback()
    {
        try {
            $provider = $this->getGithubProvider();
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = $provider->user();
        } catch (Exception $e) {
            flash()->error(__('auth.provider_fails'));
            return redirect()->back();
        }

        $email = $socialiteUser->getEmail();
        $name = $socialiteUser->getName();
        $name = empty($name) ? $socialiteUser->getNickname() : $name;

        $validator = $this->validator(['email' => $email, 'name' => $name]);

        if ($validator->fails()) {
            flash()->error(__('auth.provider_fails'));
            return redirect()->back();
        }

        $authUser = $this->getOrCreateUserFromSocialite($name, $email);
        $this->setGithubIntegration($authUser, $socialiteUser);

        Auth::login($authUser, true);
        flash()->success(__('auth.logged_in'));

        return redirect()->route('my');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'min:2','max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
    }

    private function getGithubProvider(): GithubProvider
    {
        return $this->socialite::driver('github');
    }

    private function getOrCreateUserFromSocialite($name, $email): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($user->trashed()) {
            $user->restore();

            return $user;
        }

        if (!$user->exists) {
            $user->name              = $name;
            $user->email_verified_at = now();
            $user->password          = Hash::make(random_bytes(10));
            $user->saveOrFail();
        }

        return $user;
    }

    private function setGithubIntegration(User $user, SocialiteUser $socialite)
    {
        $attributes = ['token' => $socialite->token];
        if ($user->githubIntegration()->exists() === false) {
            $user->githubIntegration()->create($attributes);

            return;
        }
        $user->githubIntegration->token = $socialite->token;

        $user->githubIntegration
            ->fill($attributes)
            ->save();
    }
}
