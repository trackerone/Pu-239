<?php

declare(strict_types=1);

namespace Pu239\Forum\Repositories;

use Pu239\Forum\Contracts\PostRepositoryInterface;
use Pu239\Forum\Models\Post;
use Pu239\Forum\Models\PostRevision;

final class InMemoryPostRepository implements PostRepositoryInterface
{
    /** @var array<int,Post> */
    private array $posts = [];

    /** @var array<int,list<PostRevision>> */
    private array $revisions = [];

    private int $nextId = 1;

    public function save(Post $post): Post
    {
        $id = $post->getId();
        if ($id === null) {
            $id = $this->nextId++;
            $post->setId($id);
        }

        $this->posts[$id] = $post;

        return $post;
    }

    public function find(int $postId): ?Post
    {
        return $this->posts[$postId] ?? null;
    }

    public function softDelete(Post $post): void
    {
        $post->softDelete();
        $this->save($post);
    }

    public function restore(Post $post): void
    {
        $post->restore();
        $this->save($post);
    }

    public function revisionsFor(int $postId): array
    {
        return $this->revisions[$postId] ?? [];
    }

    public function recordRevision(PostRevision $revision): void
    {
        $postId = $revision->getPostId();
        $this->revisions[$postId] ??= [];
        $this->revisions[$postId][] = $revision;
    }
}
