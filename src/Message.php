<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;
use Psr\Container\ContainerInterface;

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
    protected $site_config;
    protected $users;

    /**
     * Message constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param Settings           $settings
     * @param User               $users
     * @param ContainerInterface $c
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, Settings $settings, User $users, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->site_config = $settings->get_settings();
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
            $result = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
        }

        foreach ($values as $user) {
            $ids[] = 'inbox_' . $user['receiver'];
            $ids[] = 'message_count_' . $user['receiver'];
            if ($send_email && $this->site_config['mail']['smtp_enable']) {
                $emailer = $this->users->getUserFromId((int) $user['receiver']);
                if (!empty($emailer['notifs']) && preg_match('#email|pm#', $emailer['notifs'])) {
                    $message_id = $this->get_last_message((int) $user['receiver'], isset($user['sender']) ? (int) $user['sender'] : 2);
                    $message = !empty($message_id) ? "&id={$message_id}" : '';
                    $msg_body = '<h1>' . format_comment($user['subject']) . '</h1><br><br>' . format_comment($user['msg']) . "<br>
                    <a href='{$this->site_config['paths']['baseurl']}/messages.php?action=view_message{$message}'>View Message</a>";
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
        $message_id = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

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
        $message = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

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
        $result = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

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
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
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
        if ($location === $this->site_config['pm']['inbox'] && $unread) {
            $pmCount = $this->cache->get('inbox_' . $userid);
        }
        if ($pmCount === false || is_null($pmCount)) {
            $pmCount = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        $messages_2 = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

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
        $messages = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        return $messages;
    }
}
