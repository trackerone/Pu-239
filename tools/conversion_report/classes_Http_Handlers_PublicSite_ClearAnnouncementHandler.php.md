# Conversion Report: classes/Http/Handlers/PublicSite/ClearAnnouncementHandler.php

- Legacy source: public/clear_announcement.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')` for redirect and `$config->get('expires.user_cache')` for cache TTL
- Database usage: `$db->run()` to reset announcement tracking fields; cache row updated via `Cache` service
- TODOs introduced: 0
- Notes: Maintained audit logging and cache invalidation while enforcing container error handling wrapper.
