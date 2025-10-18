# Conversion Report: classes/Http/Handlers/Public/NeedseedHandler.php

- Legacy source: public/needseed.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')`, `$config->get('paths.images_baseurl')`
- Database usage: Two Database::fetchAll queries replacing Fluent joins for peer/torrent listings with optional category filtering
- TODOs introduced: 0
- Notes: Maintained genre cache hydration and HTML output while introducing bound parameters for duration + visibility filters.
