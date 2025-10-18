# Conversion Report: classes/Http/Handlers/Public/UserhistoryHandler.php

- Legacy source: public/userhistory.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: deferred (ConfigRepository, Database, User)
- Config mappings: pending (paths.baseurl, forum_config.readpost_expiry)
- Database usage: deferred (multiple sql_query calls with dynamic WHERE clauses + pagination)
- TODOs introduced: 2 (manual extraction for forum/comment history + re-review offset=200)
- Notes: Handler kept as shim pending PDO pager refactor and BBCode rendering parity.
- Re-review: 2025-10-18T18:09:15Z (offset=200) — conversion deferred while planning SQL migration away from sql_query/sqlesc.
