# Conversion Report: classes/Http/Handlers/Public/UpcomingHandler.php

- Legacy source: public/upcoming.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')`, `$config->get('categories.movie')`, `$config->get('paths.images_baseurl')`
- Database usage: Delegated to `Upcoming` service for CRUD; pager uses existing helpers
- TODOs introduced: 1 (CSRF protection reminder for POST actions)
- Notes: Preserved session-driven form repopulation and staff controls; normalized self URL handling to avoid notices.
