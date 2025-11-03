<?php

declare(strict_types=1);

namespace Pu239\Forum\Policies;

use Pu239\Forum\Entities\ForumUser;
use Pu239\Forum\Enums\UserRole;
use Pu239\Forum\Models\Post;

final class PostPolicy
{
    public function canCreate(?ForumUser $user): bool
    {
        return $user !== null;
    }

    public function canUpdate(ForumUser $user, Post $post): bool
    {
        return $this->isOwner($user, $post) || $this->isModerator($user);
    }

    public function canDelete(ForumUser $user, Post $post): bool
    {
        return $this->canUpdate($user, $post);
    }

    public function canRestore(ForumUser $user): bool
    {
        return $user->getRole() === UserRole::Moderator || $user->getRole() === UserRole::Admin;
    }

    private function isOwner(ForumUser $user, Post $post): bool
    {
        return $user->getId() === $post->getUserId();
    }

    private function isModerator(ForumUser $user): bool
    {
        return $user->getRole() === UserRole::Moderator || $user->getRole() === UserRole::Admin;
    }
}
