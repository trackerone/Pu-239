# Conversion Report: classes/Http/Handlers/Public/AchievementhistoryHandler.php

- Legacy source: public/achievementhistory.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')`, `$config->get('paths.images_baseurl')`
- Services: Usersachiev, Post, Topic, User, Achievement
- TODOs introduced: 0
- Notes: Maintained forum statistics refresh prior to rendering and reused legacy pager output for achievements table.
