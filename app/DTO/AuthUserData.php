<?php

namespace App\DTO;

use App\Models\User;
use Spatie\LaravelData\Data;

class AuthUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isAdmin,
    ) {
    }

    public static function fromModel(User $user): self
    {
        return new self($user->id, $user->name, (bool) $user->is_admin);
    }
}
