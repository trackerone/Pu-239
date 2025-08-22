<?php
function debug_log($v){error_log('[DEBUG] '.(is_scalar($v)?var_export($v,true):print_r($v,true)));}
function app_halt($m='Application halted'){error_log('[HALT] '.$m);throw new \RuntimeException($m);} function safe_eval($c){error_log('[BLOCKED eval]');return null;}
