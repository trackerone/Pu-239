<?php

declare(strict_types=1);

namespace Pu239\Forum\Contracts;

use Pu239\Forum\Models\Post;
use Pu239\Forum\Models\PostRevision;

interface PostRepositoryInterface
{
    public function save(Post $post): Post;

    public function find(int $postId): ?Post;

    public function softDelete(Post $post): void;

    public function restore(Post $post): void;

    /**
     * @return list<PostRevision>
     */
    public function revisionsFor(int $postId): array;

    public function recordRevision(PostRevision $revision): void;
}
