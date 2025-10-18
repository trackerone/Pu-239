# Conversion Report: classes/Http/Handlers/PublicSite/CategoryidsHandler.php

- Legacy source: public/categoryids.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')` for browse links
- Database usage: `$db->fetchAll()` for per-category torrent counts with associative remap
- TODOs introduced: 0
- Notes: Preserved hidden-category checks and rebuilt the category table using Database service instead of Fluent queries.
