# Migration Report

## public
- `public/tenpercent.php`: migrated from legacy mysqli/sql_query/sqlesc to `Pu239\Database` with bound parameters; switched to `bootstrap_pdo.php`; added strict typing.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" public/tenpercent.php
```
No matches.
