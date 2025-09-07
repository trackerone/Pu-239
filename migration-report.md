# Migration Report

## public
- `public/tenpercent.php`: migrated from legacy mysqli/sql_query/sqlesc to `Pu239\Database` with bound parameters; switched to `bootstrap_pdo.php`; added strict typing.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" public/tenpercent.php
```
No matches.

## bin
- `bin/validate_images.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/optimize_resize_images.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/usersfix.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/uglify.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/clear_cache.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/mysql_drop_fks.php`: switched to `bootstrap_pdo.php`, migrated to `Pu239\Database` with bound parameters, and added strict typing.
- `bin/jobby.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/rename_image_hashes.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/remove_torrents.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/set_perms.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/resize_multi_threads.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.
- `bin/remove_altered_images.php`: switched to `bootstrap_pdo.php`, added strict typing, and removed legacy bootstrap scaffolding.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" bin
```
No matches.
