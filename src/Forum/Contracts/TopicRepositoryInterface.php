<?php

declare(strict_types=1);

namespace Pu239\Forum\Contracts;

use Pu239\Forum\Models\Topic;

interface TopicRepositoryInterface
{
    /**
     * @return list<Topic>
     */
    public function paginate(int $perPage = 20, int $page = 1): array;

    public function findBySlug(string $slug): ?Topic;

    public function save(Topic $topic): Topic;
}
