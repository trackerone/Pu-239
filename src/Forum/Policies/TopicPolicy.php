<?php

declare(strict_types=1);

namespace Pu239\Forum\Policies;

use Pu239\Forum\Entities\ForumUser;
use Pu239\Forum\Enums\UserRole;
use Pu239\Forum\Models\Topic;

final class TopicPolicy
{
    public function canCreate(?ForumUser $user): bool
    {
        return $user !== null;
    }

    public function canUpdate(ForumUser $user, Topic $topic): bool
    {
        return $this->isOwner($user, $topic) || $this->isModerator($user);
    }

    public function canDelete(ForumUser $user, Topic $topic): bool
    {
        return $this->canUpdate($user, $topic);
    }

    public function canLock(ForumUser $user): bool
    {
        return $this->isModerator($user);
    }

    public function canPin(ForumUser $user): bool
    {
        return $this->isModerator($user);
    }

    private function isOwner(ForumUser $user, Topic $topic): bool
    {
        return $user->getId() === $topic->getUserId();
    }

    private function isModerator(ForumUser $user): bool
    {
        return $user->getRole() === UserRole::Moderator || $user->getRole() === UserRole::Admin;
    }
}
