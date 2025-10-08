# Conversion Report: classes/Http/Handlers/Public/AchievementlistHandler.php

- Legacy source: public/achievementlist.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.images_baseurl')`
- Services: Achievementlist, Database
- TODOs introduced: 1 (`// TODO(2025): csrf` retained)
- Notes: Preserved admin-only form handling and reused Database::toArray for achievement counts.
