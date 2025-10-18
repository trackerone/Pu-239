# Conversion Report: classes/Http/Handlers/Public/SharemarksHandler.php

- Legacy source: public/sharemarks.php
- Conversion status: deferred (complex sharetable helper extraction)
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services needed: ConfigRepository, Database, Cache, User
- TODOs introduced: 2
- Notes: Legacy implementation relies on nested sharetable() function, AJAX bookmark toggles, and multiple FluentPDO joins; requires curated rewrite.
