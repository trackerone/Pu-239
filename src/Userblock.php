<?php
declare(strict_types=1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PU239\Config\ConfigRepository;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

/**
 * Class Userblock.
 */
class Userblock
{
    protected $fluent;
    protected $cache;
    protected ConfigRepository $config;

    /**
     * Userblock constructor.
     *
     * @param Cache             $cache
     * @param Database          $fluent
     * @param ConfigRepository  $config
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, ConfigRepository $config)
    {
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * @param int $userid
     *
     * @return bool|mixed|string
     */
    public function get(int $userid)
    {
        try {
            $blocks = $this->cache->get('userblocks_' . $userid);
            if ($blocks === false || is_null($blocks)) {
                while (!$blocks) {
                    $blocks = $this->fluent->from('user_blocks')
                                           ->select(null)
                                           ->select('index_page')
                                           ->select('global_stdhead')
                                           ->select('userdetails_page')
                                           ->where('userid = ?', $userid)
                                           ->fetch();
                    if (!$blocks) {
                        $this->add(['userid' => $userid]);
                    }
                }

                $this->cache->set(
                    'userblocks_' . $userid,
                    $blocks,
                    (int) $this->config->get('expires.user_blocks', 0),
                );
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $blocks;
    }

    /**
     * @param array $values
     *
     * @return bool|int|string
     */
    public function add(array $values)
    {
        try {
            return $this->fluent->insertInto('user_blocks')
                                ->values($values)
                                ->ignore()
                                ->execute();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
