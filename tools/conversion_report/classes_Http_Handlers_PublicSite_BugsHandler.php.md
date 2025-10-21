# Conversion Report: classes/Http/Handlers/PublicSite/BugsHandler.php

- Legacy source: public/bugs.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: Deferred; legacy code reads site name, base URL, and staff lists from ConfigRepository
- Database usage: Deferred; bug tracker performs multi-table updates and message inserts via Database + cache coordination
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Stub retained because the bug workflow mixes Fluent remnants, messaging notifications, and role-restricted actions that require manual review.
