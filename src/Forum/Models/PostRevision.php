<?php

declare(strict_types=1);

namespace Pu239\Forum\Models;

use DateTimeImmutable;

final class PostRevision
{
    public function __construct(
        private readonly int $postId,
        private readonly string $bodyMd,
        private readonly string $bodyHtml,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getBodyMarkdown(): string
    {
        return $this->bodyMd;
    }

    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
