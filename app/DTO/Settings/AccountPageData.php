<?php

namespace App\DTO\Settings;

use App\DTO\Navigation\NavItemData;
use Spatie\LaravelData\Data;

class AccountPageData extends Data
{
    /**
     * @param array<int, NavItemData> $menu
     */
    public function __construct(
        public string $email,
        public string $resetPasswordUrl,
        public string $destroyUrl,
        public array $menu,
    ) {
    }
}
