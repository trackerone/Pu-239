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
            return $this->fluent->from('wiki')
                                ->select(null)
                                ->select('name')
                                ->orderBy('id DESC')
                                ->limit(1)
                                ->fetch('name');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function add(array $values)
    {
        try {
            return $sql = "INSERT INTO wiki (/* columns */) VALUES (/* values */)";
$this->db->perform($sql, $values);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param array $update
     * @param int   $id
     *
     * @return bool|int|PDOStatement|string
     */
    public function update(array $update, int $id)
    {
        try {
            return $sql = "UPDATE wiki SET /* columns */ WHERE id = :id";
$this->db->perform($sql, array_merge($update, ['id' => $id]));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function get_by_name(string $name)
    {
        try {
            return $this->fluent->from('wiki')
                                ->where('name LIKE ?', "%{$name}%")
                                ->orderBy('GREATEST(time, lastedit) DESC')
                                ->fetchAll(); // TODO(batch41): replace with $this->db->fetchAll("SELECT ...", [...])
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
            return $this->fluent->from('wiki')
                                ->where('id = ?', $id)
                                ->fetch(); // TODO(batch41): replace with $this->db->fetchRow("SELECT ...", [...])
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return string
     */
    public function get_latest()
    {
        try {
            return $this->fluent->from('wiki')
                                ->orderBy('GREATEST(time, lastedit) DESC')
                                ->limit(25)
                                ->fetchAll(); // TODO(batch41): replace with $this->db->fetchAll("SELECT ...", [...])
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
            return $sql = "DELETE FROM wiki WHERE id = :id";
$this->db->perform($sql, ['id' => $id]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
