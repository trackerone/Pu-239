<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PDOStatement;

/**
 * Class Usersachiev.
 */
class Usersachiev
{
    protected $fluent;
    protected $env;
    protected $limit;
    protected $settings;
    protected $site_config;

    /**
     * Usersachiev constructor.
     *
     * @param Database $fluent
     * @param Settings $settings
     *
     * @throws Exception
     */
    public function __construct(Database $fluent, Settings $settings)
    {
        $this->fluent = $fluent;
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->limit = $this->site_config['db']['query_limit'];
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
                // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
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
            return // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
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
            return // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
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
            return // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
