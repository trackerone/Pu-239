<?php
declare(strict_types=1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PDOStatement;
use PU239\Config\ConfigRepository;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

/**
 * Class Usersachiev.
 */
class Usersachiev
{
    protected $fluent;
    protected $limit;
    protected ConfigRepository $config;

    /**
     * Usersachiev constructor.
     *
     * @param Database          $fluent
     * @param ConfigRepository  $config
     *
     * @throws Exception
     */
    public function __construct(Database $fluent, ConfigRepository $config)
    {
        $this->fluent = $fluent;
        $this->config = $config;
        $this->limit = (int) $this->config->get('database.query_limit', 65536);
    }

    /**
     * @param array $values
     * @param array $update
     *
     * @return bool|string
     */
    public function insert(array $values, array $update)
    {
        try {
            $count = (int) ($this->limit / max(array_map('count', $values)));
            foreach (array_chunk($values, $count) as $t) {
                $this->fluent->insertInto('usersachiev', $t)
                             ->onDuplicateKeyUpdate($update)
                             ->execute();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * @param array $values
     *
     * @return bool|int|string
     */
    public function add(array $values)
    {
        try {
            return $this->fluent->insertInto('usersachiev')
                                ->values($values)
                                ->ignore()
                                ->execute();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param array $set
     * @param int   $userid
     *
     * @return bool|int|PDOStatement|string
     */
    public function update(array $set, int $userid)
    {
        try {
            return $sql = "UPDATE usersachiev SET /* columns */ WHERE userid = :userid";
$this->db->perform($sql, array_merge($set, ['userid' => $userid]));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param int $userid
     *
     * @return string
     */
    public function get_count(int $userid)
    {
        try {
            return $this->fluent->from('usersachiev')
                                ->select(null)
                                ->select('achpoints')
                                ->where('userid = ?', $userid)
                                ->where('achpoints >= 1')
                                ->fetch('achpoints');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param int $userid
     *
     * @return string
     */
    public function get_points(int $userid)
    {
        try {
            return $this->fluent->from('usersachiev')
                                ->select(null)
                                ->select('achpoints')
                                ->select('spentpoints')
                                ->where('userid = ?', $userid)
                                ->fetch();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
