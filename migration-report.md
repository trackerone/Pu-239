# Migration Report

## public
- `public/tenpercent.php`: migrated from legacy mysqli/sql_query/sqlesc to `Pu239\Database` with bound parameters; switched to `bootstrap_pdo.php`; added strict typing.
- `public/contactstaff.php`: migrated from `sql_query`/`sqlesc` to `$db->run` with bound parameters; standardized bootstrap and added strict typing.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" public/contactstaff.php public/tenpercent.php
```
No matches in modified files.

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

## include
- `include/function_bemail.php`: migrated from legacy `sql_query`/`sqlesc` to `Pu239\Database` with bound parameters; added strict typing and standardized bootstrap.
- `include/function_rating.php`: migrated from legacy `sql_query`/`mysqli_num_rows` to `Pu239\Database` with bound parameters and `SELECT COUNT(*)`; added strict typing and standardized bootstrap.
- `include/stealth.php`: removed `sqlesc`/`mysqli_fetch_assoc` usage in favor of `Pu239\Database` with bound parameters; added strict typing and standardized bootstrap.
- `include/function_tmdb.php`: replaced `sql_query` with `$db->run` and bound parameters for bulk inserts; added strict typing and standardized bootstrap.
- `include/function_happyhour.php`: removed `sql_query`/`sqlesc` in favor of `$db->run` with bound parameters; added strict typing and standardized bootstrap.
- `include/database.php`, `include/DB.php`, `include/bittorrent.php`: switched to `bootstrap_pdo.php`, removed legacy scaffolding, and added strict typing.
- `include/arcade.php`: migrated from `$fluent` to `$db->fetch` and `$db->fetchAll` with bound parameters; standardized bootstrap.
- `include/cron_controller.php`: replaced `$fluent` queries with `$db->fetchAll` and bound parameters; standardized bootstrap.
- `include/function_books.php`, `include/timezone.php`: standardized bootstrap order.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" include
```
No matches.

## admin
- `admin/bannedemails.php`: switched to `bootstrap_pdo.php` and migrated from legacy `sql_query`/`sqlesc` to `$db->run`/`fetchAll` with bound parameters.
- `admin/user_hits.php`: removed `sql_query`, `mysqli_fetch_*`, and `sqlesc` in favor of `$db->fetch`/`fetchAll` with bound parameters.
- `admin/acpmanage.php`: added `bootstrap_pdo.php` and converted bulk updates from `sql_query`/`sqlesc` to `$db->run` with named placeholders.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" admin/bannedemails.php admin/user_hits.php admin/acpmanage.php
```
No matches in modified files.

## blocks
- `blocks/global/demotion.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/freeleech.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/happyhour.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/message.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/advertise.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/ajaxchat.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/comments.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/cooker.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/disclaimer.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/gift.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_movies.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_torrents.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_torrents_glide.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_torrents_scroll.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_tv.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/latest_user.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/mow.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/offers.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/poll.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/requests.php`: standardized `bootstrap_pdo.php` and added strict typing.

- `blocks/global/bugmessages.php`: migrated from `$fluent` to `$db->fetchValue` for bug count; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/crazyhour.php`: replaced `$fluent` queries with `$db->fetch`/`run`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/freeleech_contribution.php`: switched `$fluent` lookups to `$db->fetch`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/lottery.php`: converted `$fluent` usage to `$db->fetchAll` with `array_column`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/report.php`: migrated report count to `$db->fetchValue`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/staffmessages.php`: replaced `$fluent` count with `$db->fetchValue`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/global/uploadapp.php`: replaced `$fluent` count with `$db->fetchValue`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/active_24h_users.php`: migrated record and user queries to `$db->fetch`/`fetchAll` and `run`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/active_birthday_users.php`: migrated birthday lookup to `$db->fetchAll`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/active_irc_users.php`: migrated IRC user lookup to `$db->fetchAll`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/active_users.php`: migrated active user lookup to `$db->fetchAll`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/news.php`: migrated news listing to `$db->fetchAll`; standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/staff_picks.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/stats.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/top_torrents.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/torrentfreak.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/index/trivia.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/userdetails/avatar.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/userdetails/birthday.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/userdetails/browser.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `blocks/userdetails/connectable.php`: migrated `FluentPDO` lookup to `$db->fetch`; standardized bootstrap and strict typing.
- `blocks/userdetails/comments.php`: migrated from `$fluent` to `$db->fetchValue`; standardized bootstrap and strict typing.
- `blocks/userdetails/completed.php`: migrated from `$fluent` to `$db->fetchAll`; standardized bootstrap and strict typing.
- `blocks/userdetails/forumposts.php`: migrated from `$fluent` to `$db->fetchValue`; standardized bootstrap and strict typing.
- `blocks/userdetails/invitedby.php`: migrated from `$fluent` to `$db->fetchValue`/`fetchAll`; standardized bootstrap and strict typing.
- `blocks/userdetails/seedtimeratio.php`: migrated from `$fluent` to `$db->fetch`; standardized bootstrap and strict typing.
- `blocks/userdetails/showfriends.php`: migrated from `$fluent` to `$db->fetchAll`; standardized bootstrap and strict typing.
- `blocks/userdetails/showpm.php`: migrated from `$fluent` to `$db->fetch`; standardized bootstrap and strict typing.
- `blocks/userdetails/usercomments.php`: migrated from `$fluent` to `$db->fetchValue`/`fetchAll`; standardized bootstrap and strict typing.
- `blocks/userdetails/contactinfo.php`, `flush.php`, `freestuffs.php`, `gender.php`, `iphistory.php`, `irc.php`, `joined.php`, `onlinetime.php`, `report.php`, `reputation.php`, `seedbonus.php`, `shareratio.php`, `snatched_staff.php`, `torrents_block.php`, `traffic.php`, `userclass.php`, `userhits.php`, `userinfo.php`, `userstatus.php`: standardized `bootstrap_pdo.php` and added strict typing.

### Previous migrations
- `blocks/index/forum_posts.php`: migrated from legacy `sql_query`/`mysqli_fetch_assoc` to `$db->fetchAll` with bound parameters and standardized bootstrap/strict typing.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" blocks
```
No matches.

## cleanup
- `cleanup/optimizedb.php`: migrated from `sql_query`/`mysqli_fetch_assoc`/`sqlesc` to `Pu239\Database` with bound parameters; standardized bootstrap and strict typing.

- `cleanup/announcement_update.php`: migrated from legacy `sql_query` to `Pu239\Database` with bound parameters; standardized bootstrap and strict typing.
### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" cleanup/optimizedb.php cleanup/announcement_update.php
```
No matches.

****** codex/migrate-config-directory-to-aura-extendedpdo
## config
- `config/ann_config.php`: standardized bootstrap order, removed legacy `database.php` include, and added strict typing.
- `config/classes.php`, `config/config_example.php`, `config/define.php`, `config/emoticons.php`, `config/functions.php`, `config/session.php`, `config/subtitles.php`, `config/whereis.php`: standardized bootstrap and added strict typing.
- `config/definitions.php`: added missing `bootstrap_pdo.php`, imported `Pu239\\Database`, and enforced strict typing.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" config
....................
## database
- `database/sql_updates.php`: standardized `bootstrap_pdo.php`, added strict typing, and imported `Pu239\\Database`.

### Verification
```
$ rg "mysqli_|sql_query\\(|sqlesc\\(" database
******* master
```
No matches.

## plugins
- `plugins/database-hide.php`, `plugins/dump-bz2.php`, `plugins/dump-date.php`, `plugins/dump-zip.php`, `plugins/enum_types.php`, `plugins/frames.php`, `plugins/plugin.php`, `plugins/readable-dates.php`, `plugins/tables-filter.php`, `plugins/file-upload.php`, `plugins/version-noverify.php`: standardized `bootstrap_pdo.php` and added strict typing.

### Verification
```
$ rg "mysqli_|sql_query\\(|sqlesc\\(" plugins
```
No matches.

## messages
- `messages/*.php`: standardized `bootstrap_pdo.php` inclusion and added strict typing across all files.
- `messages/search.php`: replaced legacy `sqlesc` with bound parameter placeholder.
- `messages/use_draft.php`: removed `sqlesc`, `sql_query`, and `mysqli_*` calls in favor of `Pu239\Message` with bound parameters.

### Verification
```
$ rg "mysqli_|sql_query\(|sqlesc\(" messages
```
No matches.

## partials
- `partials/categories.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `partials/free_details.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `partials/genres.php`: standardized `bootstrap_pdo.php` and added strict typing.
- `partials/torrent_table.php`: standardized `bootstrap_pdo.php` and added strict typing.

### Verification
```
$ rg "mysqli_|sql_query\\(|sqlesc\\(" partials
```
No matches.

## root
- `acpmanage.php`: switched to `runtime_safe.php`/`bootstrap_pdo.php` and used `$db->fetchAll` for settings lookup.
- `bannedemails.php`: standardized bootstrap and replaced manual `PDO` usage with `$db->fetchAll`.
- `bonusmanage.php`: standardized bootstrap and replaced manual `PDO` usage with `$db->fetchAll`.

### Verification
```
$ rg "mysqli_|sql_query\\(|sqlesc\\(" acpmanage.php bannedemails.php bonusmanage.php
```
No matches in modified files.
