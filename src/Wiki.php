<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use PDOStatement;

/**
 * Class Wiki.
 */
class Wiki
{
    protected $fluent;

    /**
     * Sitelog constructor.
     *
     * @param Database $fluent
     */
    public function __construct(Database $fluent)
    {
        $this->fluent = $fluent;
    }

    /**
     * @return string
     */
    public function get_last()
    {
        try {
            return $this->fluent$sql = "SELECT * FROM 'wiki'"; $this->db->fetchAll($sql);;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param int $id
     *
     * @return mixed|string
     */
    public function get_by_id(int $id)
    {
        try {
            return $this->fluent$sql = "SELECT * FROM 'wiki'"; $this->db->fetchAll($sql);;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param int $id
     *
     * @return bool|string
     */
    public function delete(int $id)
    {
        try {
            return $this->fluent$sql = "DELETE FROM 'wiki' WHERE ..."; $this->db->perform($sql);;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
