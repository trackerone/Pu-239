<?php

declare(strict_types=1);

namespace Pu239\Forum\Controllers;

use Pu239\Forum\Contracts\PostRepositoryInterface;
use Pu239\Forum\Contracts\TopicRepositoryInterface;
use Pu239\Forum\Entities\ForumUser;
use Pu239\Forum\Models\Post;
use Pu239\Forum\Models\Topic;
use Pu239\Forum\Policies\TopicPolicy;
use Pu239\Forum\Requests\StoreTopicRequest;
use Pu239\Forum\Services\MarkdownService;
use Pu239\Forum\Services\TopicSlugService;
use Pu239\Forum\Support\AuthorizationException;
use Pu239\Forum\Support\ResourceNotFoundException;
use RuntimeException;
use function sprintf;

final class TopicController
{
    public function __construct(
        private readonly TopicRepositoryInterface $topics,
        private readonly PostRepositoryInterface $posts,
        private readonly TopicPolicy $topicPolicy,
        private readonly StoreTopicRequest $storeTopicRequest,
        private readonly MarkdownService $markdownService,
        private readonly TopicSlugService $slugService,
    ) {
    }

    /**
     * @return list<Topic>
     */
    public function index(int $perPage = 20, int $page = 1): array
    {
        return $this->topics->paginate($perPage, $page);
    }

    public function show(string $slug): Topic
    {
        $topic = $this->topics->findBySlug($slug);
        if ($topic === null) {
            throw new ResourceNotFoundException(sprintf('Topic %s not found.', $slug));
        }

        return $topic;
    }

    public function store(array $input, ForumUser $user): Topic
    {
        if (! $this->topicPolicy->canCreate($user)) {
            throw new AuthorizationException('You are not allowed to create topics.');
        }

        $data = $this->storeTopicRequest->validate($input);
        $slug = $this->slugService->generate($data['title'], fn (string $candidate): bool => $this->topics->findBySlug($candidate) !== null);

        $topic = new Topic(null, $slug, $data['title'], $user->getId());
        $topic = $this->topics->save($topic);
        $topicId = $topic->getId();
        if ($topicId === null) {
            throw new RuntimeException('Topic ID missing after persistence.');
        }

        $bodyHtml = $this->markdownService->render($data['body_md']);
        $post = new Post(null, $topicId, $user->getId(), $data['body_md'], $bodyHtml);
        $post->assignToTopic($topicId);
        $post = $this->posts->save($post);
        $topic->addPost($post);
        $this->topics->save($topic);

        return $topic;
    }

    public function lock(string $slug, ForumUser $actor): Topic
    {
        $topic = $this->show($slug);
        if (! $this->topicPolicy->canLock($actor)) {
            throw new AuthorizationException('You are not allowed to lock topics.');
        }

        $topic->lock($actor);
        $this->topics->save($topic);

        return $topic;
    }

    public function unlock(string $slug, ForumUser $actor): Topic
    {
        $topic = $this->show($slug);
        if (! $this->topicPolicy->canLock($actor)) {
            throw new AuthorizationException('You are not allowed to unlock topics.');
        }

        $topic->unlock($actor);
        $this->topics->save($topic);

        return $topic;
    }

    public function pin(string $slug, ForumUser $actor): Topic
    {
        $topic = $this->show($slug);
        if (! $this->topicPolicy->canPin($actor)) {
            throw new AuthorizationException('You are not allowed to pin topics.');
        }

        $topic->pin($actor);
        $this->topics->save($topic);

        return $topic;
    }

    public function unpin(string $slug, ForumUser $actor): Topic
    {
        $topic = $this->show($slug);
        if (! $this->topicPolicy->canPin($actor)) {
            throw new AuthorizationException('You are not allowed to unpin topics.');
        }

        $topic->unpin($actor);
        $this->topics->save($topic);

        return $topic;
    }
}
