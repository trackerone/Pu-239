# Lottery Module Rebuild Guide

## 1) Summary
Three PHP files in `lottery/` exhibited parse errors and were quarantined. Temporary stubs now return HTTP 503 while the module is rebuilt.

## 2) Quarantine Report
| File | SHA1 | Size | Markers |
| --- | --- | --- | --- |
| lottery/config.php | beed9dd872f51c75a77b9d014208b2e7f675e798 | 6536 | likely_update, missing_bootstrap, missing_strict_types, unclosed_brace, dangling_quote, parse_error |
| lottery/tickets.php | 10a3b4d2de9ea425fce811f9d7bcbea1b0ac1908 | 6826 | contains_sql_query, contains_mysqli, likely_select, likely_update, missing_bootstrap, missing_strict_types, unclosed_brace, parse_error |
| lottery/viewtickets.php | 75915023dd7959ed7e2f8867bc30fcefb2ee05a7 | 2320 | contains_sql_query, contains_mysqli, likely_select, missing_strict_types, parse_error |

## 3) Suggested Rebuild Order
- config.php
- tickets.php
- viewtickets.php

## 4) Implementation Notes for Later
- Use `Pu239\Database` with bound params only (no `sql_query`, `sqlesc`, `mysqli_`).
- Transactions for dependent writes (insert+update, counters).
- No `SELECT *`; explicit column lists.
- `LIMIT`/`OFFSET` → cast to int and inline.
- `IN (...)` → dynamic placeholders (`:id0`, `:id1`, ...).
- Safe `ORDER BY` → whitelist field & direction.

## 5) Restoring a File for Local Work
```
cp lottery/_quarantine/<file>.orig lottery/<file>
```

## 6) Safety Note
503 stubs are intentional until each file is properly rebuilt.

## DONE-CHECKS
- `rg -n "mysqli_|sql_query\(|sqlesc\(" lottery/` → matches only inside `lottery/_quarantine/*.orig`
- `php -l lottery/*.php` → all pass
- `curl -i http://127.0.0.1:8000/lottery/tickets.php` → HTTP 503 with plaintext maintenance banner
