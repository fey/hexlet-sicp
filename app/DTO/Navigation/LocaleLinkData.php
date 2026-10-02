<?php

namespace App\DTO\Navigation;

use Spatie\LaravelData\Data;

class LocaleLinkData extends Data
{
    public function __construct(
        public string $code,
        public string $label,
        public string $flagUrl,
        public string $href,
    ) {
    }
}
