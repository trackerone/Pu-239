Batch 1 Manual Refactor Issue Preparation

| global_index | file | path_exists | todos | notes (trunc 80) | issue-url |
| --- | --- | --- | --- | --- | --- |
| 20 | classes/Http/Handlers/Admin/SiteSettingsHandler.php | yes | 1 | TODO(2025) legacy script complex admin/site_settings.php:1-400 | N/A (env-limited) |
| 21 | classes/Http/Handlers/Admin/SnatchedTorrentsHandler.php | yes | 1 | TODO(2025) heavy legacy admin/snatched_torrents.php:1-220 | N/A (env-limited) |
| 22 | classes/Http/Handlers/Admin/UpgradeDatabaseHandler.php | yes | 1 | TODO(2025) extract admin/upgrade_database.php legacy workflow (offset=335 bat... | N/A (env-limited) |
| 23 | classes/Http/Handlers/HomeHandler.php | yes | 1 | TODO(2025) manual extraction required public/index.legacy.php:1-400 | N/A (env-limited) |
| 24 | classes/Http/Handlers/Public/Ajax/TorrentsLookupHandler.php | yes | 1 | TODO added for manual extraction of complex torrents_lookup flow | N/A (env-limited) |
| 25 | classes/Http/Handlers/Public/AnnouncementHandler.php | yes | 1 | TODO(2025) dynamic announcement workflow requires manual Database migration | N/A (env-limited) |
| 26 | classes/Http/Handlers/Public/BitbucketHandler.php | yes | 1 | TODO(2025) legacy script complex public/bitbucket.php:1-400 | N/A (env-limited) |
| 27 | classes/Http/Handlers/Public/BlackjackHandler.php | yes | 1 | TODO(2025) legacy script complex public/blackjack.php:1-420 | N/A (env-limited) |
| 28 | classes/Http/Handlers/Public/CatalogHandler.php | yes | 1 | TODO(2025) manual extraction required public/catalog.php:1-400 (offset=215 ba... | N/A (env-limited) |
| 29 | classes/Http/Handlers/Public/ForumsHandler.php | yes | 1 | TODO(2025) manual extraction required public/forums.php:1-400 | N/A (env-limited) |
| 30 | classes/Http/Handlers/Public/HnrsHandler.php | yes | 2 | TODO(2025) Snatched/User bonus adjustments span public/hnrs.php:70-210; needs... | N/A (env-limited) |
| 31 | classes/Http/Handlers/Public/LotteryHandler.php | yes | 1 | TODO(2025) legacy script dispatch and sql_query usage require manual extraction | N/A (env-limited) |
| 32 | classes/Http/Handlers/Public/MoviesHandler.php | yes | 1 | TODO(2025) multi-source cache workflow requires curated port | N/A (env-limited) |
| 33 | classes/Http/Handlers/Public/MybonusHandler.php | yes | 1 | TODO(2025) complex bonus catalogue with fluent chains | N/A (env-limited) |
| 34 | classes/Http/Handlers/Public/RssHandler.php | yes | 1 | TODO added for rss feed builder and validation | N/A (env-limited) |
| 35 | classes/Http/Handlers/Public/RulesHandler.php | yes | 1 | TODO(2025) manual extraction required public/rules.php localisation blocks | N/A (env-limited) |
| 36 | classes/Http/Handlers/Public/SetclassHandler.php | yes | 1 | TODO(2025) merge conflict markers require manual extraction public/setclass.p... | N/A (env-limited) |
| 37 | classes/Http/Handlers/Public/SharemarksHandler.php | yes | 2 | TODO(2025) sharetable rendering + Fluent joins pending; re-reviewed offset=180 | N/A (env-limited) |
| 38 | classes/Http/Handlers/Public/SignupHandler.php | yes | 1 | TODO(2025) public/signup.php lines 35-150 contain merge markers + argon polic... | N/A (env-limited) |
| 39 | classes/Http/Handlers/Public/StaffboxHandler.php | yes | 1 | TODO(2025) legacy content missing per tools/rehydrate_v3_manifest.csv | N/A (env-limited) |

Totals:
COUNT_FALSE_TOTAL: 96
COUNT_IN_SUBSET: 20
RANGE_PROCESSED: 20..39
REMAINING_FALSE_ESTIMATE: 56
