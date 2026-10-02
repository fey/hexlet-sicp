<?php

namespace App\DTO\Navigation;

use Spatie\LaravelData\Data;

class NavigationData extends Data
{
    /**
     * @param array<int, NavItemData> $main
     * @param array<int, NavItemData> $user пункты меню пользователя, у гостя — вход и регистрация
     * @param array<int, LocaleLinkData> $otherLocales
     * @param array<int, NavSectionData> $footer
     */
    public function __construct(
        public string $homeUrl,
        public string $logoUrl,
        public string $logoAlt,
        public array $main,
        public array $user,
        public LocaleLinkData $currentLocale,
        public array $otherLocales,
        public array $footer,
    ) {
    }
}
