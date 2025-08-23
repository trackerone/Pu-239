<?php
// admin/reputation_settings.php hotfix — replace debug calls with debug_log
if (!function_exists('debug_log')) {
    function debug_log($msg) { error_log(is_scalar($msg) ? (string)$msg : print_r($msg, true)); }
}
// Example placeholder; your real code stays, this just ensures debug calls are benign.
