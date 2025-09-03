# Batch 34 – FluentPDO Phase-out Plan

## Goal
Remove all usage of `envms/fluentpdo` from the codebase so the package can be removed.
This unblocks PHP 8.3 completely.

## Step 1 – Locate usage
- Run the workflow **FluentPDO Scan (Soft)** on PRs or manually.
- Download `fluentpdo-report.txt` artifact to see where FluentPDO is referenced.

## Step 2 – Replace incrementally
For each reference:

### Select (example)
**FluentPDO**
```php
$fpdo = new \FluentPDO($pdo);
$rows = $fpdo->from('users')->where('status', 1)->fetchAll();
