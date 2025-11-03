<?php

declare(strict_types=1);

namespace Pu239\Forum\Controllers;

use Pu239\Forum\Contracts\PostRepositoryInterface;
use Pu239\Forum\Contracts\TopicRepositoryInterface;
use Pu239\Forum\Entities\ForumUser;
use Pu239\Forum\Models\Post;
use Pu239\Forum\Models\PostRevision;
use Pu239\Forum\Policies\PostPolicy;
use Pu239\Forum\Requests\StorePostRequest;
use Pu239\Forum\Requests\UpdatePostRequest;
use Pu239\Forum\Services\MarkdownService;
use Pu239\Forum\Support\AuthorizationException;
use Pu239\Forum\Support\ResourceNotFoundException;
use RuntimeException;
use function sprintf;

final class PostController
{
    public function __construct(
        private readonly TopicRepositoryInterface $topics,
        private readonly PostRepositoryInterface $posts,
        private readonly PostPolicy $postPolicy,
        private readonly StorePostRequest $storePostRequest,
        private readonly UpdatePostRequest $updatePostRequest,
        private readonly MarkdownService $markdownService,
    ) {
    }

    public function store(string $topicSlug, array $input, ForumUser $user): Post
    {
        if (! $this->postPolicy->canCreate($user)) {
            throw new AuthorizationException('You are not allowed to create posts.');
        }

        $topic = $this->topics->findBySlug($topicSlug);
        if ($topic === null) {
            throw new ResourceNotFoundException(sprintf('Topic %s not found.', $topicSlug));
        }

        if ($topic->isLocked()) {
            throw new AuthorizationException('Locked topics cannot receive new posts.');
        }

        $data = $this->storePostRequest->validate($input);
        $bodyHtml = $this->markdownService->render($data['body_md']);

        $topicId = $topic->getId();
        if ($topicId === null) {
            throw new RuntimeException('Topic has not been persisted.');
        }

        $post = new Post(null, $topicId, $user->getId(), $data['body_md'], $bodyHtml);
        $post->assignToTopic($topicId);
        $post = $this->posts->save($post);
        $topic->addPost($post);
        $this->topics->save($topic);

        return $post;
    }

    public function update(int $postId, array $input, ForumUser $user): Post
    {
        $post = $this->posts->find($postId);
        if ($post === null) {
            throw new ResourceNotFoundException(sprintf('Post %d not found.', $postId));
        }

        if (! $this->postPolicy->canUpdate($user, $post)) {
            throw new AuthorizationException('You are not allowed to edit this post.');
        }

        $data = $this->updatePostRequest->validate($input);
        $this->recordRevision($post);

        $bodyHtml = $this->markdownService->render($data['body_md']);
        $post->updateBody($data['body_md'], $bodyHtml);
        $this->posts->save($post);

        return $post;
    }

    public function destroy(int $postId, ForumUser $user): Post
    {
        $post = $this->posts->find($postId);
        if ($post === null) {
            throw new ResourceNotFoundException(sprintf('Post %d not found.', $postId));
        }

        if (! $this->postPolicy->canDelete($user, $post)) {
            throw new AuthorizationException('You are not allowed to delete this post.');
        }

        $this->posts->softDelete($post);

        return $post;
    }

    public function restore(int $postId, ForumUser $user): Post
    {
        if (! $this->postPolicy->canRestore($user)) {
            throw new AuthorizationException('You are not allowed to restore posts.');
        }

        $post = $this->posts->find($postId);
        if ($post === null) {
            throw new ResourceNotFoundException(sprintf('Post %d not found.', $postId));
        }

        $this->posts->restore($post);

        return $post;
    }

    /**
     * @return list<PostRevision>
     */
    public function revisions(int $postId): array
    {
        return $this->posts->revisionsFor($postId);
    }

    private function recordRevision(Post $post): void
    {
        $postId = $post->getId();
        if ($postId === null) {
            return;
        }

        $revision = new PostRevision($postId, $post->getBodyMarkdown(), $post->getBodyHtml());
        $this->posts->recordRevision($revision);
    }
}
