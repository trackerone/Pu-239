# Conversion Report: classes/Http/Handlers/Public/MytorrentsHandler.php

- Legacy source: public/mytorrents.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database
- Config mappings: `site.minvotes`, `paths.baseurl`
- Database usage: `Database::fetchValue` for the torrent count and `Database::toArray` for the paginated torrent listing with dynamic ORDER BY + LIMIT/OFFSET bindings.
- TODOs introduced: 0
- Notes: Replaced FluentPDO usage with bound SQL, preserved torrenttable rendering and pager link toggles (offset=210 batch=5).
