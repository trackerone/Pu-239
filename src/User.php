<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

namespace Pu239;

use Delight\Auth\AttemptCancelledException;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use Delight\Auth\DuplicateUsernameException;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use DI\DependencyException;
use DI\NotFoundException;
use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Select;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use PDOStatement;
use Psr\Container\ContainerInterface;
use Spatie\Image\Exceptions\InvalidManipulation;
use function urlencode;

/**
 * Class User.
 */
class User
{
    protected $fluent;
    protected $cache;
    protected $site_config;
    protected $session;
    protected $auth;
    protected $flash;
    protected $achieve;
    protected $container;
    protected $settings;
    protected $userblock;

    /**
     * User constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param Auth               $auth
     * @param Session            $session
     * @param Settings           $settings
     * @param Usersachiev        $achieve
     * @param Userblock          $userblock
     * @param ContainerInterface $c
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, Auth $auth, Session $session, Settings $settings, Usersachiev $achieve, Userblock $userblock, ContainerInterface $c)
    {
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->cache = $cache;
        $this->fluent = $fluent;
        $this->auth = $auth;
        $this->session = $session;
        $this->achieve = $achieve;
        $this->container = $c;
        $this->userblock = $userblock;
    }

    /**
     *
     * @param string $username
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function getUserIdFromName(string $username)
    {
        $user = $this->cache->get('userid_from_' . strtolower($username));
        if ($user === false || is_null($user)) {
            $user = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;
            $this->cache->set('search_users_' . $username, $users, 86400);
        }

        return $users;
    }

    /**
     *
     * @param string $item
     * @param int    $userid
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_item(string $item, int $userid)
    {
        $user = $this->getUserFromId($userid);

        return $user[$item];
    }

    /**
     *
     * @param int  $userid
     * @param bool $fresh
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function getUserFromId(int $userid, bool $fresh = false)
    {
        $user = $this->cache->get('user_' . $userid);
        if ($fresh || $user === false || is_null($user)) {
            $user = $this->fluent$sql = "SELECT * FROM 'users AS u'"; $this->db->fetchAll($sql);;

        return $users;
    }

    /**
     *
     * @param string $bot
     * @param string $torrent_pass
     * @param string $auth
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_bot_id(string $bot, string $torrent_pass, string $auth)
    {
        $userid = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;

        return $ids;
    }

    /**
     * @param $torrent_pass
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function get_user_from_torrent_pass(string $torrent_pass)
    {
        if (strlen($torrent_pass) != 64) {
            return false;
        }
        $userid = $this->cache->get('torrent_pass_' . $torrent_pass);
        if ($userid === false || is_null($userid)) {
            $userid = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;

        return $users;
    }

    /**
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function get_latest_user()
    {
        require_once CLASS_DIR . 'class_user_options.php';
        $userid = $this->cache->get('latestuser_');
        if ($userid === false || is_null($userid)) {
            $userid = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;
        $group1 = !empty($group1) ? $group1 : [];
        $group2 = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;
        $group2 = !empty($group2) ? $group2 : [];
        $group3 = $this->fluent$sql = "SELECT * FROM 'users'"; $this->db->fetchAll($sql);;

        $group3 = !empty($group3) ? $group3 : [];

        return array_merge($group1, $group2, $group3);
    }

    /**
     * @param array $users
     *
     * @throws Exception
     */
    public function delete_users(array $users)
    {
        foreach ($users as $user) {
            $this->fluent$sql = "DELETE FROM 'users' WHERE ..."; $this->db->perform($sql);;

            $this->delete_user_cache($user);
        }
    }

    /**
     * @param array $users
     *
     * @throws Exception
     */
    public function delete_user_cache(array $users)
    {
        foreach ($users as $userid) {
            if (!empty($userid)) {
                $user = $this->getUserFromId($userid);
                $username = !empty($user) ? $user['username'] : '';
                $this->cache->deleteMulti([
                    'get_all_boxes_' . $userid,
                    'inbox_' . $userid,
                    'insertJumpTo' . $userid,
                    'is_staffs',
                    'peers_' . $userid,
                    'poll_votes_' . $userid,
                    'port_data_' . $userid,
                    'shitlist_' . $userid,
                    'user' . $userid,
                    'user_' . $userid,
                    'useravatar_' . $userid,
                    'userclasses_' . $username,
                    'user_friends_' . $userid,
                    'userhnrs_' . $userid,
                    'users_names_' . $username,
                    'user_rep_' . $userid,
                    'user_snatches_data_' . $userid,
                    'userstatus_' . $userid,
                ]);
                unset($user);
            }
        }
    }

    /**
     * @param string $email
     *
     * @return mixed|string
     */
    public function get_count_by_email(string $email)
    {
        try {
            return $this->fluent->from('users')
                                ->select(null)
                                ->select('COUNT(id) AS count')
                                ->where('email = ?', $email)
                                ->fetch('count');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $username
     *
     * @return mixed|string
     */
    public function get_count_by_username(string $username)
    {
        try {
            return $this->fluent->from('users')
                                ->select(null)
                                ->select('COUNT(id) AS count')
                                ->where('username = ?', $username)
                                ->fetch('count');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $where
     * @param string $by
     *
     * @return mixed|string
     */
    public function get_count(string $where, string $by)
    {
        $allowed_columns = [
            'invitedby',
        ];
        if (!in_array($where, $allowed_columns)) {
            return false;
        }
        try {
            return $this->fluent->from('users')
                                ->select(null)
                                ->select('COUNT(id) AS count')
                                ->where('status = 0')
                                ->where($where . ' = ?', $by)
                                ->fetch('count');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
