<?php

namespace App\Http\Controllers\Settings;

use App\DTO\Settings\AccountPageData;
use App\Models\User;
use App\Support\Navigation\NavigationBuilder;
use Auth;
use App\Http\Controllers\Controller;
use DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(NavigationBuilder $navigation): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $page = new AccountPageData(
            email: $user->email,
            resetPasswordUrl: route('password.request'),
            destroyUrl: route('settings.account.destroy', $user),
            menu: $navigation->settings(),
        );

        return $this->inertia($page->toArray())
            ->withViewData(['robots' => 'noindex, nofollow']);
    }

    public function destroy(): SymfonyResponse
    {
        DB::transaction(function () {
            /** @var User $user */
            $user = Auth::user();

            if ($user->delete()) {
                flash()->success(__('account.your_account_deleted'));
                Auth::logout();
            } else {
                flash()->error(__('layout.flash.error'));
            }
        });

        // home — Blade-страница: Inertia-запрос получает 409 и делает полную перезагрузку.
        return Inertia::location(route('home'));
    }
}
