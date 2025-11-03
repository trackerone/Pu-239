<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Pu239\Forum\Controllers\PostController;
use Pu239\Forum\Controllers\TopicController;
use Pu239\Forum\Entities\ForumUser;
use Pu239\Forum\Enums\UserRole;
use Pu239\Forum\Policies\PostPolicy;
use Pu239\Forum\Policies\TopicPolicy;
use Pu239\Forum\Repositories\InMemoryPostRepository;
use Pu239\Forum\Repositories\InMemoryTopicRepository;
use Pu239\Forum\Requests\StorePostRequest;
use Pu239\Forum\Requests\StoreTopicRequest;
use Pu239\Forum\Requests\UpdatePostRequest;
use Pu239\Forum\Services\MarkdownService;
use Pu239\Forum\Services\TopicSlugService;
use Pu239\Forum\Support\AuthorizationException;

final class ForumCoreTest extends TestCase
{
    public function test_forum_flow(): void
    {
        $topicRepository = new InMemoryTopicRepository();
        $postRepository = new InMemoryPostRepository();
        $markdownService = new MarkdownService();
        $slugService = new TopicSlugService();
        $topicPolicy = new TopicPolicy();
        $postPolicy = new PostPolicy();
        $storeTopicRequest = new StoreTopicRequest();
        $storePostRequest = new StorePostRequest();
        $updatePostRequest = new UpdatePostRequest();

        $topicController = new TopicController(
            $topicRepository,
            $postRepository,
            $topicPolicy,
            $storeTopicRequest,
            $markdownService,
            $slugService,
        );

        $postController = new PostController(
            $topicRepository,
            $postRepository,
            $postPolicy,
            $storePostRequest,
            $updatePostRequest,
            $markdownService,
        );

        $author = new ForumUser(1, UserRole::User);
        $moderator = new ForumUser(2, UserRole::Moderator);
        $topic = $topicController->store([
            'title' => 'Hello World',
            'body_md' => "# Heading\n\nThis is a [link](javascript:alert('x'))",
        ], $author);

        self::assertSame('hello-world', $topic->getSlug());
        self::assertCount(1, $topic->getPosts());
        $initialPost = $topic->getPosts()[0];
        self::assertStringContainsString('Heading', $initialPost->getBodyHtml());
        self::assertStringNotContainsString('javascript:', $initialPost->getBodyHtml());

        $duplicate = $topicController->store([
            'title' => 'Hello World',
            'body_md' => 'Another body',
        ], $author);
        self::assertSame('hello-world-2', $duplicate->getSlug());

        $reply = $postController->store('hello-world', ['body_md' => 'Reply body'], $author);
        self::assertSame('Reply body', $reply->getBodyMarkdown());

        $postController->update($reply->getId() ?? 0, ['body_md' => 'Updated reply'], $author);
        self::assertSame('Updated reply', $reply->getBodyMarkdown());
        self::assertNotNull($reply->getEditedAt());
        self::assertCount(1, $postController->revisions($reply->getId() ?? 0));

        $postController->destroy($reply->getId() ?? 0, $author);
        self::assertTrue($reply->isDeleted());

        $postController->restore($reply->getId() ?? 0, $moderator);
        self::assertFalse($reply->isDeleted());

        $topicController->pin('hello-world', $moderator);
        self::assertTrue($topic->isPinned());

        $topicController->lock('hello-world', $moderator);
        self::assertTrue($topic->isLocked());

        $this->expectException(AuthorizationException::class);
        $postController->store('hello-world', ['body_md' => 'Will fail'], $author);
    }

    public function test_update_requires_authorization(): void
    {
        $topicRepository = new InMemoryTopicRepository();
        $postRepository = new InMemoryPostRepository();
        $markdownService = new MarkdownService();

        $topicController = new TopicController(
            $topicRepository,
            $postRepository,
            new TopicPolicy(),
            new StoreTopicRequest(),
            $markdownService,
            new TopicSlugService(),
        );

        $postController = new PostController(
            $topicRepository,
            $postRepository,
            new PostPolicy(),
            new StorePostRequest(),
            new UpdatePostRequest(),
            $markdownService,
        );

        $author = new ForumUser(1, UserRole::User);
        $otherUser = new ForumUser(2, UserRole::User);

        $topic = $topicController->store([
            'title' => 'AuthZ topic',
            'body_md' => 'Initial body',
        ], $author);

        $post = $postController->store($topic->getSlug(), ['body_md' => 'Author body'], $author);

        $this->expectException(AuthorizationException::class);
        $postController->update($post->getId() ?? 0, ['body_md' => 'Attempted edit'], $otherUser);
    }
}
