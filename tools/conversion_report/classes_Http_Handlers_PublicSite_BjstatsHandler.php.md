# Conversion Report: classes/Http/Handlers/PublicSite/BjstatsHandler.php

- Legacy source: public/bjstats.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database
- Config mappings: allowed.play, class_names
- Database usage: Multiple SELECT leaderboards on users table for blackjack metrics with :mingames filtering
- TODOs introduced: 0
- Notes: Added typed renderer helper for repeated table markup while maintaining ratio/plus-minus formatting rules.
