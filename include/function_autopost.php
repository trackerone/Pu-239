<?php
declare(strict_types=1);

$db = $container->get(Database::class);

require_once __DIR__ . '/runtime_safe.php';

use Delight\Auth\AuthError;
use Delight\Auth\NotLoggedInException;
use DI\DependencyException;
use DI\NotFoundException;

use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Message;
use Spatie\Image\Exceptions\InvalidManipulation;

/**
 * @param string $subject
 * @param string $body
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws AuthError
 * @throws NotLoggedInException
 * @throws \PDOException
 * @throws UnbegunTransaction
 * @throws \PHPMailer\PHPMailer\Exception
 * @throws InvalidManipulation
 *
 * @return array|null
 */
function auto_post($subject = 'Error - Subject Missing', $body = 'Error - No Body')
{
    global $container, $site_config, $CURUSER;

    // $fluent removed — use $this->db (ExtendedPdo)
    if (user_exists($site_config['chatbot']['id'])) {
        $topicid = $fluent->from('topics')
                          ->select(null)
                          ->select('id')
                          ->where('forum_id = ?', $site_config['staff_forums'][0])
                          ->where('topic_name = ?', $subject)
                          ->fetch('id');
        if (!$topicid) {
            $values = [
                'user_id' => $site_config['chatbot']['id'],
                'forum_id' => $site_config['staff_forums'][0],
                'topic_name' => $subject,
            ];
            $sql = "INSERT INTO topics (/* columns */) VALUES (/* values */)";
$topicid = $db->perform($sql, $values);

            $set = [
                'topic_count' => new Literal('topic_count + 1'),
            ];
            $sql = "UPDATE forums SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $site_config['staff_forums'][0]]));
        }

        $values = [
            'topic_id' => $topicid,
            'user_id' => $site_config['chatbot']['id'],
            'added' => TIME_NOW,
            'body' => $body,
        ];
        $sql = "INSERT INTO posts (/* columns */) VALUES (/* values */)";
$postid = $db->perform($sql, $values);

        $set = [
            'last_post' => $postid,
        ];
        $sql = "UPDATE topics SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $topicid]));

        $set = [
            'post_count' => new Literal('post_count + 1'),
        ];
        $sql = "UPDATE forums SET /* columns */ WHERE id = :id";
$db->perform($sql, array_merge($set, ['id' => $site_config['staff_forums'][0]]));

        $cache = $container->get(Cache::class);
        $cache->delete('last_posts_' . $CURUSER['class']);
        $cache->delete('forum_posts_' . $CURUSER['id']);

        unset($values);
        $values[] = [
            'receiver' => $site_config['site']['owner'],
            'added' => TIME_NOW,
            'subject' => $subject,
            'msg' => $body,
        ];
        $messages_class = $container->get(Message::class);
        $messages_class->insert($values);

        return [
            'topicid' => $topicid,
            'postid' => $postid,
        ];
    }

    return null;
}
