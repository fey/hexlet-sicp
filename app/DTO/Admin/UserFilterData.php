<?php

namespace App\DTO\Admin;

use Spatie\LaravelData\Data;

class UserFilterData extends Data
{
    public function __construct(
        public ?string $name,
        public ?string $email,
    ) {
    }
}
