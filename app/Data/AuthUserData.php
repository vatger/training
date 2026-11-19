<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthUserData extends Data
{
    /**
     * @param  string[]  $roles
     */
    public function __construct(
        public int $id,
        public int $vatsim_id,
        public string $name,
        public ?string $first_name,
        public ?string $last_name,
        public ?string $email,
        public int $rating,
        public ?string $subdivision,
        public bool $is_staff,
        public bool $is_superuser,
        public bool $is_admin,
        public array $roles,
        public bool $is_mentor,
        public bool $is_leadership,
        public bool $is_leading_mentor,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            vatsim_id: $user->vatsim_id,
            name: $user->name,
            first_name: $user->first_name,
            last_name: $user->last_name,
            email: $user->email,
            rating: $user->rating,
            subdivision: $user->subdivision,
            is_staff: (bool) $user->is_staff,
            is_superuser: (bool) $user->is_superuser,
            is_admin: (bool) $user->is_admin,
            roles: $user->roles->pluck('name')->all(),
            is_mentor: $user->isMentor(),
            is_leadership: $user->isLeadership(),
            is_leading_mentor: $user->isLeadingMentor(),
        );
    }
}
