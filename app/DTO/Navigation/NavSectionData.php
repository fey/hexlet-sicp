<?php

namespace App\DTO\Navigation;

use Spatie\LaravelData\Data;

class NavSectionData extends Data
{
    /**
     * @param array<int, NavItemData> $items
     */
    public function __construct(
        public ?string $title,
        public array $items,
    ) {
    }
}
