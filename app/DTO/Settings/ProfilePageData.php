<?php

namespace App\DTO\Settings;

use App\DTO\Navigation\NavItemData;
use Spatie\LaravelData\Data;

class ProfilePageData extends Data
{
    /**
     * @param array<int, NavItemData> $menu
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $github_name,
        public string $profileImage,
        public string $updateUrl,
        public array $menu,
    ) {
    }
}
