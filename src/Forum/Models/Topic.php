<?php

declare(strict_types=1);

namespace Pu239\Forum\Models;

use DateTimeImmutable;
use Pu239\Forum\Entities\ForumUser;
use RuntimeException;

final class Topic
{
    /** @var list<Post> */
    private array $posts = [];

    public function __construct(
        private ?int $id,
        private string $slug,
        private string $title,
        private readonly int $userId,
        private bool $isLocked = false,
        private bool $isPinned = false,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new RuntimeException('Topic identifier is immutable once set.');
        }

        $this->id = $id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function rename(string $title, string $slug): void
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->touch();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function lock(ForumUser $actor): void
    {
        $this->assertModerator($actor);
        $this->isLocked = true;
        $this->touch();
    }

    public function unlock(ForumUser $actor): void
    {
        $this->assertModerator($actor);
        $this->isLocked = false;
        $this->touch();
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function pin(ForumUser $actor): void
    {
        $this->assertModerator($actor);
        $this->isPinned = true;
        $this->touch();
    }

    public function unpin(ForumUser $actor): void
    {
        $this->assertModerator($actor);
        $this->isPinned = false;
        $this->touch();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addPost(Post $post): void
    {
        if ($this->isLocked) {
            throw new RuntimeException('Locked topics cannot receive new posts.');
        }

        if ($post->getTopicId() !== $this->id && $this->id !== null) {
            throw new RuntimeException('Post topic mismatch.');
        }

        $this->posts[] = $post;
        $this->touch();
    }

    /**
     * @return list<Post>
     */
    public function getPosts(): array
    {
        return $this->posts;
    }

    private function assertModerator(ForumUser $actor): void
    {
        if (! $actor->isModerator()) {
            throw new RuntimeException('Only moderators can perform this action.');
        }
    }
}
