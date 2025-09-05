<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;

/**
 * Class PollVoter.
 */
class PollVoter
{
    protected $cache;
    protected $fluent;
    protected $site_config;
    protected $users_class;
    protected $polls_class;
    protected $settings;

    /**
     * PollVoter constructor.
     *
     * @param Cache    $cache
     * @param Database $fluent
     * @param User     $users_class
     * @param Poll     $polls_class
     * @param Settings $settings
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, User $users_class, Poll $polls_class, Settings $settings)
    {
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->users_class = $users_class;
        $this->polls_class = $polls_class;
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function get_count()
    {
        $search = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;

                $poll_data['user_id'] = $vote_data['user_id'];
                $poll_data['vote_date'] = $vote_data['vote_date'];
                $poll_data['time'] = TIME_NOW;
            }

            $this->cache->set('poll_data_' . $userid, $poll_data, $this->site_config['expires']['poll_data']);
        }

        return $poll_data;
    }
}
