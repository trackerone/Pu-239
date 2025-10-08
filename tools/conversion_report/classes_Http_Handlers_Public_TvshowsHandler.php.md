# Conversion Report: classes/Http/Handlers/Public/TvshowsHandler.php

- Legacy source: public/tvshows.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')`, `$config->get('paths.images_baseurl')`, `$config->get('categories.tv')`
- Database usage: Relies on legacy `$fluent` query builder for torrents and cast lookups (left intact for safety)
- TODOs introduced: 2 (rating filter query param verification)
- Notes: Embedded search/pager rendering and poster lookup via Image service; retained cache usage for cast listings.
