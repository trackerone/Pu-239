<?php

declare(strict_types=1);

namespace Pu239\Forum\Entities;

use Pu239\Forum\Enums\UserRole;

final class ForumUser
{
    public function __construct(
        private readonly int $id,
        private readonly UserRole $role,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator || $this->role === UserRole::Admin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
