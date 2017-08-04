<?php
function docleanup($data)
{
    global $INSTALLER09, $queries;
    set_time_limit(1200);
    ignore_user_abort(1);
    $sql = sql_query('SHOW PROCESSLIST');
    $cnt = 0;
    while ($arr = mysqli_fetch_assoc($sql)) {
        if ($arr['db'] == $INSTALLER09['mysql_db'] and $arr['Command'] == 'Sleep' and $arr['Time'] > 60) {
            sql_query("KILL {$arr['Id']}");
            ++$cnt;
        }
    }
    if ($queries > 0) {
        write_log("Proccess Kill clean-------------------- Proccess Kill Complete using $queries queries --------------------");
    }
    if ($cnt != 0) {
        $data['clean_desc'] = "MySQLCleanup killed {$cnt} processes";
    }
    if ($data['clean_log']) {
        cleanup_log($data);
    }
}

function cleanup_log($data)
{
    $text = sqlesc($data['clean_title']);
    $added = TIME_NOW;
    $ip = sqlesc($_SERVER['REMOTE_ADDR']);
    $desc = sqlesc($data['clean_desc']);
    sql_query("INSERT INTO cleanup_log (clog_event, clog_time, clog_ip, clog_desc) VALUES ($text, $added, $ip, {$desc})") or sqlerr(__FILE__, __LINE__);
}
