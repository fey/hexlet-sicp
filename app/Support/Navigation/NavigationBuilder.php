<?php

namespace App\Support\Navigation;

use App\DTO\Navigation\LocaleLinkData;
use App\DTO\Navigation\NavigationData;
use App\DTO\Navigation\NavItemData;
use App\DTO\Navigation\NavSectionData;
use App\Helpers\LocalizationHelper;
use App\Helpers\TemplateHelper;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelLocalization;

/**
 * Состав шапки и футера. Пока его рендерит только Inertia-шелл;
 * _nav.blade.php и _footer.blade.php переводятся на этот класс в #1980.
 */
class NavigationBuilder
{
    /** Маршруты, уже переехавшие на Inertia: на них ведёт <Link>, на остальные — <a>. */
    private const array INERTIA_ROUTES = [
        'settings.profile.index',
    ];

    public function __construct(private Request $request)
    {
    }

    public function build(): NavigationData
    {
        $locale = app()->getLocale();
        /** @var User|null $user */
        $user = $this->request->user();

        return new NavigationData(
            homeUrl: LaravelLocalization::getLocalizedURL($locale, route('home')),
            logoUrl: asset("images/logo-{$locale}.svg"),
            logoAlt: __('layout.nav.logo_alt'),
            main: $this->main($locale, $user),
            user: $user === null ? $this->guest() : $this->userMenu($user),
            currentLocale: $this->localeLink($locale),
            otherLocales: array_values(array_map(
                fn(string $code) => $this->localeLink($code),
                array_keys(LocalizationHelper::getOtherLocales($locale, LaravelLocalization::getSupportedLocales())),
            )),
            footer: $this->footer($locale),
        );
    }

    /**
     * @return array<int, NavItemData>
     */
    public function settings(): array
    {
        return [
            $this->route('account.profile', 'settings.profile.index'),
            $this->route('account.account', 'settings.account.index'),
        ];
    }

    /**
     * @return array<int, NavItemData>
     */
    private function main(string $locale, ?User $user): array
    {
        $items = [
            $this->route('layout.nav.chapters', 'chapters.index'),
            $this->route('layout.nav.exercises', 'exercises.index'),
            // TODO: translate guide to english
            $locale === 'ru'
                ? $this->link('layout.nav.sicp_read', 'https://guides.hexlet.io/how-to-learn-sicp/')
                : $this->link('layout.nav.sicp_read', route('pages.show', ['page' => 'how-to-learn-sicp'])),
            $this->route('layout.nav.rating', 'top.index'),
            $this->link('layout.nav.sicp_book', TemplateHelper::getBookLink($locale)),
        ];

        if ($user?->can('accessAdmin', User::class)) {
            $items[] = new NavItemData(
                label: __('admin.title'),
                href: route('admin.users.index'),
                icon: 'shield-lock',
                children: [
                    $this->route('admin.users.title', 'admin.users.index', icon: 'users'),
                    $this->route('admin.comments.title', 'admin.comments.index', icon: 'messages'),
                    $this->route('admin.solutions.title', 'admin.solutions.index', icon: 'code'),
                    $this->route('admin.export.title', 'admin.export.index', icon: 'download'),
                ],
            );
        }

        return $items;
    }

    /**
     * @return array<int, NavItemData>
     */
    private function guest(): array
    {
        $items = [];

        if (app()->environment('local')) {
            $items[] = new NavItemData(label: 'Dev-login', href: route('auth.dev-login'), method: 'post');
        }

        $items[] = $this->route('layout.nav.login', 'login');
        $items[] = $this->route('layout.nav.register', 'register');

        return $items;
    }

    /**
     * @return array<int, NavItemData>
     */
    private function userMenu(User $user): array
    {
        return [
            new NavItemData(label: $user->name, href: route('users.show', $user)),
            $this->route('account.settings', 'settings.account.index'),
            $this->route('layout.nav.my_progress', 'my.show'),
            new NavItemData(label: __('layout.nav.logout'), href: route('logout'), method: 'post'),
        ];
    }

    /**
     * @return array<int, NavSectionData>
     */
    private function footer(string $locale): array
    {
        $projects = [
            $this->link('layout.footer.os_projects.cv', 'https://github.com/Hexlet/hexlet-cv'),
            $this->link('layout.footer.os_projects.editor', 'https://github.com/hexlet-rus/runit'),
        ];

        if ($locale === 'ru') {
            $projects[] = $this->link('layout.footer.os_projects.career', 'https://career.hexlet.io/');
        }

        return [
            new NavSectionData(null, [
                $this->link('layout.footer.about', route('pages.show', ['page' => 'about'])),
                $this->link('layout.footer.source_code', 'https://github.com/Hexlet/hexlet-sicp'),
                $this->link('layout.footer.volunteers_in_tg', 'https://t.me/hexletcommunity/12'),
            ]),
            new NavSectionData(__('layout.footer.help'), [
                $this->link('layout.footer.free', 'https://ru.hexlet.io/courses_free'),
                $this->link('layout.footer.recommended_books', 'https://ru.hexlet.io/pages/recommended-books'),
            ]),
            new NavSectionData(__('layout.footer.other_os_projects'), $projects),
            new NavSectionData(__('layout.footer.additionally'), [
                $this->link('layout.footer.os_projects.hexlet', 'https://ru.hexlet.io/'),
                $this->link('layout.footer.os_projects.code_basics', 'https://ru.code-basics.com/'),
                $this->link('layout.footer.os_projects.codebattle', 'https://codebattle.hexlet.io/'),
            ]),
        ];
    }

    private function route(string $labelKey, string $routeName, ?string $icon = null): NavItemData
    {
        return new NavItemData(
            label: __($labelKey),
            href: route($routeName),
            active: $this->request->routeIs($routeName),
            inertia: in_array($routeName, self::INERTIA_ROUTES, true),
            icon: $icon,
        );
    }

    private function link(string $labelKey, string $href): NavItemData
    {
        return new NavItemData(label: __($labelKey), href: $href);
    }

    private function localeLink(string $code): LocaleLinkData
    {
        return new LocaleLinkData(
            code: $code,
            label: LocalizationHelper::getNativeLanguageName($code),
            flagUrl: LocalizationHelper::getPathToLocaleFlag($code),
            href: LaravelLocalization::getLocalizedURL($code, null, [], true),
        );
    }
}
