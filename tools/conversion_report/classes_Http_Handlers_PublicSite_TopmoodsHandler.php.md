# Conversion Report: classes/Http/Handlers/PublicSite/TopmoodsHandler.php

- Legacy source: public/topmoods.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, Cache
- Config mappings: paths.baseurl, paths.images_baseurl
- Database usage: SELECT moods.*, users.mood, COUNT(users.mood) AS moodcount FROM users LEFT JOIN moods ... ORDER BY moodcount DESC
- TODOs introduced: 0
- Notes: Preserved cache hydration for mood leaderboard and inline guidance text for launching mood picker popup.
