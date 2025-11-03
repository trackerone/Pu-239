<?php

declare(strict_types=1);

namespace Pu239\Forum\Repositories;

use Pu239\Forum\Contracts\TopicRepositoryInterface;
use Pu239\Forum\Models\Topic;
use RuntimeException;

final class InMemoryTopicRepository implements TopicRepositoryInterface
{
    /** @var array<int,Topic> */
    private array $topics = [];

    /** @var array<string,int> */
    private array $slugIndex = [];

    private int $nextId = 1;

    public function paginate(int $perPage = 20, int $page = 1): array
    {
        $topics = array_values($this->topics);
        usort($topics, static function (Topic $a, Topic $b): int {
            if ($a->isPinned() !== $b->isPinned()) {
                return $a->isPinned() ? -1 : 1;
            }

            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        $offset = max(0, ($page - 1) * $perPage);

        return array_slice($topics, $offset, $perPage);
    }

    public function findBySlug(string $slug): ?Topic
    {
        $topicId = $this->slugIndex[$slug] ?? null;

        return $topicId !== null ? $this->topics[$topicId] ?? null : null;
    }

    public function save(Topic $topic): Topic
    {
        $id = $topic->getId();
        if ($id === null) {
            $id = $this->nextId++;
            $topic->setId($id);
        }

        $slug = $topic->getSlug();
        $existingId = $this->slugIndex[$slug] ?? null;
        if ($existingId !== null && $existingId !== $id) {
            throw new RuntimeException('Slug collision detected.');
        }

        $this->topics[$id] = $topic;
        $this->slugIndex[$slug] = $id;

        return $topic;
    }
}
