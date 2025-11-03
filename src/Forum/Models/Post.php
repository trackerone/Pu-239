<?php

declare(strict_types=1);

namespace Pu239\Forum\Models;

use DateTimeImmutable;

final class Post
{
    private ?DateTimeImmutable $editedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        private ?int $id,
        private ?int $topicId,
        private readonly int $userId,
        private string $bodyMd,
        private string $bodyHtml,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
        ?DateTimeImmutable $editedAt = null,
        ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->editedAt = $editedAt;
        $this->deletedAt = $deletedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new \RuntimeException('Post identifier is immutable once set.');
        }

        $this->id = $id;
    }

    public function getTopicId(): ?int
    {
        return $this->topicId;
    }

    public function assignToTopic(int $topicId): void
    {
        if ($this->topicId !== null && $this->topicId !== $topicId) {
            throw new \RuntimeException('Cannot reassign a post to a different topic.');
        }

        $this->topicId = $topicId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBodyMarkdown(): string
    {
        return $this->bodyMd;
    }

    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function updateBody(string $bodyMd, string $bodyHtml): void
    {
        $this->bodyMd = $bodyMd;
        $this->bodyHtml = $bodyHtml;
        $this->editedAt = new DateTimeImmutable();
        $this->touch();
    }

    public function softDelete(): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = new DateTimeImmutable();
        $this->touch();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->touch();
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getEditedAt(): ?DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
