<?php
require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/mysql_compat.php';


declare(strict_types = 1);

require_once INCL_DIR . 'function_trivia.php';
global $site_config;

$table = trivia_table();
$qid = $table['qid'];
$gamenum = $table['gamenum'];
$table = $table['table'];

$table = !empty($table) ? $table : stdmsg('', _('No one has attempted any questions, yet.'));

$buttons = "
    <ul class='level-center'>
        <li class='margin20'>
            <button id='button' onclick=\"get_trivia_question()\" class='button is-small'>" . _('Get Trivia Question') . "</button>
        </li>
        <li  class='margin20'>
            <a href='{$site_config['paths']['baseurl']}/trivia_results.php' target='_top' class='button is-small'>" . _('Get Trivia Results') . '</a>
        </li>
    </ul>';
$content = "
        <div class='bordered' style='display: none;'>
            <div class='alt_bordered bg-00'>
                <div id='content'></div>
            </div>
        </div>";

$trivia .= "
    <a id='trivia-hash'></a>
    <div id='trivia' class='box'>
        <div class='bordered'>
            <div class='alt_bordered bg-00'>
                <div id='trivia_display' data-qid='{$qid}' data-gamenum='{$gamenum}'>
                    <div id='trivia_content' class='has-text-centered'>{$table}{$content}</div>
                    <div id='trivia_buttons'>{$buttons}</div>
                </div>
            </div>
        </div>
    </div>";
