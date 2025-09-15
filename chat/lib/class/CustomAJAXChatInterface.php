<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../include/runtime_safe.php';
require_once __DIR__ . '/../../../include/bootstrap_pdo.php';
use Pu239\Database;

global $container;
$db = $container->get(Database::class);










/*
 * @package AJAX_Chat
 * @author Sebastian Tschan
 * @copyright (c) Sebastian Tschan
 * @license Modified MIT License
 * @link https://blueimp.net/ajax/
 */

/**
 * Class CustomAJAXChatInterface.
 */
class CustomAJAXChatInterface extends CustomAJAXChat
{
    public function initialize()
    {
        // Initialize configuration settings:
        $this->initConfig();

        // Initialize the DataBase connection:
        $this->initDataBaseConnection();
    }
}