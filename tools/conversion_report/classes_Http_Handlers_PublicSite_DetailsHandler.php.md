# Conversion Report: classes/Http/Handlers/PublicSite/DetailsHandler.php

- Legacy source: public/details.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: Deferred; heavy use of `$config` for base URLs, assets, and feature toggles
- Database usage: Deferred; depends on Torrent, Comment, Coin, and Cache services plus direct Database statements
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Not auto-converted because the torrent detail view orchestrates numerous services, caches, and moderator actions spanning 1000+ lines.
