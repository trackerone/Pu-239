<?php
declare(strict_types=1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;
use Psr\Container\ContainerInterface;
use PU239\Config\ConfigRepository;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

/**
 * Class Message.
 */
class Message
{
    protected $cache;
    protected $fluent;
    protected $env;
    protected $limit;
    protected $container;
    protected ConfigRepository $config;
    protected $users;

    /**
     * Message constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param ConfigRepository   $config
     * @param User               $users
     * @param ContainerInterface $c
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, ConfigRepository $config, User $users, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->config = $config;
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->users = $users;
        $this->limit = $this->env['db']['query_limit'];
    }

    /**
     *
     * @param array $values
     * @param bool  $send_email
     *
     * @throws Exception
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Delight\Auth\AuthError
     * @throws \Delight\Auth\NotLoggedInException
     * @throws \MatthiasMullie\Scrapbook\Exception\UnbegunTransaction
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     *
     * @return bool|int
     */
    public function insert(array $values, bool $send_email = true)
    {
        if (empty($values)) {
            return false;
        }
        $count = (int) ($this->limit / max(array_map('count', $values)));
        foreach (array_chunk($values, $count) as $t) {
            $sql = "INSERT INTO messages (/* columns */) VALUES (/* values */)";
$result = $this->db->perform($sql, $t);
        }

        $mailEnabled = (bool) $this->config->get('mail.smtp_enable');
        $baseUrl = (string) $this->config->get('paths.baseurl');
        foreach ($values as $user) {
            $ids[] = 'inbox_' . $user['receiver'];
            $ids[] = 'message_count_' . $user['receiver'];
            if ($send_email && $mailEnabled) {
                $emailer = $this->users->getUserFromId((int) $user['receiver']);
                if (!empty($emailer['notifs']) && preg_match('#email|pm#', $emailer['notifs'])) {
                    $message_id = $this->get_last_message((int) $user['receiver'], isset($user['sender']) ? (int) $user['sender'] : 2);
                    $message = !empty($message_id) ? "&id={$message_id}" : '';
                    $msg_body = '<h1>' . format_comment($user['subject']) . '</h1><br><br>' . format_comment($user['msg']) . "<br>
                    <a href='{$baseUrl}/messages.php?action=view_message{$message}'>View Message</a>";
                    send_mail(strip_tags($emailer['email']), 'You have received a Private Message', $msg_body, strip_tags($msg_body));
                }
            }
        }

        if (!empty($ids)) {
            $this->cache->deleteMulti($ids);
        }

        if (!empty($result)) {
            return $result;
        }

        return false;
    }

    /**
     *
     * @param int $receiver
     * @param int $sender
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_last_message(int $receiver, int $sender)
    {
        $message_id = $this->fluent->from('messages')
                                   ->select(null)
                                   ->select('id')
                                   ->where('receiver = ?', $receiver)
                                   ->where('sender = ?', $sender)
                                   ->orderBy('id DESC')
                                   ->fetch('id');

        return $message_id;
    }

    /**
     *
     * @param int $id
     * @param int $userid
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(int $id, int $userid)
    {
        $result = $this->fluent->delete('messages')
                               ->where('id = ?', $id)
                               ->execute();

        $this->cache->decrement('inbox_' . $userid);
        $this->cache->decrement('message_count_' . $userid);

        return $result;
    }

    /**
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_by_id(int $id)
    {
        $message = $this->fluent->from('messages')
                                ->where('id = ?', $id)
                                ->fetch();

        return $message;
    }

    /**
     *
     * @param array $set
     * @param int   $id
     *
     * @throws Exception
     *
     * @return bool|int|PDOStatement
     */
    public function update(array $set, int $id)
    {
        $sql = "UPDATE messages SET /* columns */ WHERE id = :id";
$result = $this->db->perform($sql, array_merge($set, ['id' => $id]));

        return $result;
    }

    /**
     * @param array $set
     * @param int   $location
     * @param int   $userid
     *
     * @throws Exception
     */
    public function update_location(array $set, int $location, int $userid)
    {
        $this->fluent->update('messages')
                     ->set($set)
                     ->where('location = ?', $location)
                     ->where('receiver = ?', $userid)
                     ->execute();
    }

    /**
     *
     * @param int  $userid
     * @param int  $location
     * @param bool $unread
     *
     * @throws Exception
     *
     * @return int
     */
    public function get_count(int $userid, int $location, bool $unread)
    {
        $pmCount = false;
        $inboxLocation = (int) $this->config->get('pm.inbox');
        $sentLocation = (int) $this->config->get('pm.sent');
        $unreadTtl = (int) $this->config->get('expires.unread');
        if ($location === $inboxLocation && $unread) {
            $pmCount = $this->cache->get('inbox_' . $userid);
        }
        if ($pmCount === false || is_null($pmCount)) {
            $pmCount = $this->fluent->from('messages')
                                    ->select(null)
                                    ->select('COUNT(id) AS count');
            if ($location === $sentLocation) {
                $pmCount = $pmCount->where('sender = ?', $userid)
                                   ->where('location = ?', $inboxLocation);
            } else {
                $pmCount = $pmCount->where('receiver = ?', $userid)
                                   ->where('location = ?', $location);
            }
            if ($unread) {
                $pmCount = $pmCount->where('unread = "yes"');
            }
            $pmCount = $pmCount->where('draft = "no"')
                               ->fetch("count");
            if ($location === $inboxLocation && $unread) {
                $this->cache->set('inbox_' . $userid, $pmCount, $unreadTtl);
            }
        }

        return is_int($pmCount) ? $pmCount : 0;
    }

    /**
     *
     * @param int $userid
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_total_count(int $userid)
    {
        $pmCount = $this->cache->get('message_count_' . $userid);
        if ($pmCount === false || is_null($pmCount)) {
            $pmCount = $this->fluent->from('messages')
                                    ->select(null)
                                    ->select('COUNT(id) AS count')
                                    ->where('receiver = ?', $userid)
                                    ->fetch("count");

            $unreadTtl = (int) $this->config->get('expires.unread');
            $this->cache->set('message_count_' . $userid, $pmCount, $unreadTtl);
        }

        return $pmCount;
    }

    /**
     *
     * @param int $dt
     *
     * @throws Exception
     *
     * @return int
     */
    public function delete_old_messages(int $dt)
    {
        $deletedLocation = (int) $this->config->get('pm.deleted');
        $inboxLocation = (int) $this->config->get('pm.inbox');
        $messages_1 = $this->fluent->from('messages')
                                   ->select(null)
                                   ->select('receiver')
                                   ->where('location = ?', $deletedLocation)
                                   ->where('added <= ?', $dt);

        $this->fluent->delete('messages')
                     ->where('location = ?', $deletedLocation)
                     ->where('added <= ?', $dt)
                     ->execute();

        $messages_2 = $this->fluent->from('messages')
                                   ->select(null)
                                   ->select('receiver')
                                   ->where('location = ?', $inboxLocation)
                                   ->where('added <= ?', $dt);

        $this->fluent->delete('messages')
                     ->where('location = ?', $inboxLocation)
                     ->where('added <= ?', $dt)
                     ->execute();

        $i = 0;
        foreach ($messages_1 as $message) {
            ++$i;
            $this->cache->delete('inbox_' . $message['receiver']);
        }
        foreach ($messages_2 as $message) {
            ++$i;
            $this->cache->delete('inbox_' . $message['receiver']);
        }

        return $i;
    }

    /**
     *
     * @param int    $userid
     * @param int    $location
     * @param int    $limit
     * @param int    $offset
     * @param string $orderby
     *
     * @throws Exception
     *
     * @return array|bool|Select
     */
    public function get_messages(int $userid, int $location, int $limit, int $offset, string $orderby)
    {
        $messages = $this->fluent->from('messages AS m');
        $sentLocation = (int) $this->config->get('pm.sent');
        $inboxLocation = (int) $this->config->get('pm.inbox');
        if ($location === $sentLocation) {
            $messages = $messages->where('sender = ?', $userid)
                                 ->where('location = ?', $inboxLocation);
        } else {
            $messages = $messages->where('receiver = ?', $userid)
                                 ->where('location = ?', $location);
        }
        $messages = $messages->select(null)
                             ->select('m.poster')
                             ->select('m.sender')
                             ->select('m.receiver')
                             ->select('m.added')
                             ->select('m.subject')
                             ->select('m.unread')
                             ->select('m.urgent')
                             ->select('m.id AS message_id')
                             ->select('f.id AS friend')
                             ->select('b.id AS blocked')
                             ->select('u.id');
        if ($location === $sentLocation) {
            $messages = $messages->leftJoin('users AS u ON m.receiver = u.id');
        } else {
            $messages = $messages->leftJoin('users AS u ON m.sender = u.id');
        }
        $messages = $messages->leftJoin('friends AS f ON m.receiver = f.userid AND m.sender = f.friendid')
                             ->leftJoin('blocks AS b ON m.receiver = b.userid AND m.sender = b.blockid')
                             ->limit($limit)
                             ->offset($offset)
                             ->orderBy($orderby)
                             ->fetchAll();

        return $messages;
    }
}
