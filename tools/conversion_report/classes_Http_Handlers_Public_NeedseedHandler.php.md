# Conversion Report: classes/Http/Handlers/Public/NeedseedHandler.php

- Legacy source: public/needseed.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: deferred (ConfigRepository, Database, genre helpers)
- Config mappings: baseurl, images_baseurl (pending direct mapping)
- Database usage: deferred (dual FluentPDO queries for peer/torrent listings with conditional joins)
- TODOs introduced: 1 (manual extraction covering tabbed listings and templating)
- Notes: Retained upgraded stub pending manual rewrite of dual-mode listing logic and template generation.
