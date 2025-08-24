<?php
/**
 * Static Guard v2 — configurable via env:
 *   GUARD_MODE: 'fail' (default) or 'warn'
 *   GUARD_EXCLUDE: comma-separated substrings to skip (e.g. "vendor,tools/static_guard.php")
 *   GUARD_FAIL_ON: comma-separated category names to fail on (lowercase)
 *   GUARD_MAX_ERRORS: integer threshold to allow (optional)
 */
$root = __DIR__ . '/../';

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

$env_exclude = getenv('GUARD_EXCLUDE') ?: '';
$exclude = array_filter(array_map('trim', explode(',', $env_exclude)));

$env_fail_on = getenv('GUARD_FAIL_ON') ?: '';
$fail_on = array_filter(array_map('trim', explode(',', strtolower($env_fail_on))));

$mode = strtolower(getenv('GUARD_MODE') ?: 'fail');
$maxErrors = getenv('GUARD_MAX_ERRORS');
$maxErrors = ($maxErrors !== false && $maxErrors !== '') ? intval($maxErrors) : null;

$patterns = [
    'merge_conflict_marker' => '/^(<<<<<<<|=======|>>>>>>>)/m',
    'short_open_tag'        => '/<\?(?!php|=)/',
    'eval_usage'            => '/\beval\s*\(/i',
    'terminate_calls'       => '/\b(die|exit)\s*\(/i',
    'debug_calls'           => '/\b(var_dump|print_r|dd)\s*\(/i',
    'deprecated_mysql'      => '/\bmysql_(query|connect|pconnect|select_db|fetch_(assoc|array|row)|num_rows|real_escape_string|insert_id|error|errno)\b/i',
];

$issues = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!preg_match('/\.php$/i', $path)) continue;

    $rel = substr($path, strlen($root));

    // Exclusions
    $skip = false;
    foreach ($exclude as $x) {
        if ($x !== '' && strpos($rel, $x) !== false) { $skip = true; break; }
    }
    if ($skip) continue;

    $content = @file_get_contents($path);
    if ($content === false) continue;

    foreach ($patterns as $name => $regex) {
        if (preg_match($regex, $content)) {
            $issues[] = [$name, $rel];
        }
    }
}

$lines = [];
if (!empty($issues)) {
    $lines[] = "Static guard found issues:";
    foreach ($issues as $e) {
        $lines[] = "- {$e[0]} : {$e[1]}";
    }
} else {
    $lines[] = "Static guard: OK";
}
$reportPath = __DIR__ . '/guard_report.txt';
@file_put_contents($reportPath, implode(PHP_EOL, $lines) . PHP_EOL);

$shouldFail = false;
if (!empty($issues)) {
    if ($mode === 'warn') {
        $shouldFail = false;
    } else {
        if (!empty($fail_on)) {
            foreach ($issues as $e) {
                if (in_array(strtolower($e[0]), $fail_on, true)) {
                    $shouldFail = true; break;
                }
            }
        } else {
            $shouldFail = true;
        }
        if ($maxErrors !== null && count($issues) <= $maxErrors) {
            $shouldFail = false;
        }
    }
}

echo implode(PHP_EOL, $lines) . PHP_EOL;
exit($shouldFail ? 1 : 0);
