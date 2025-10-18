# Conversion Report: classes/Http/Handlers/Public/SnatchesHandler.php

- Legacy source: public/snatches.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, Torrent, Session
- Config mappings: paths.baseurl, site.ratio_free
- Database usage: COUNT snatched rows with joins; SELECT snatch history with limit/offset binding
- TODOs introduced: 0
- Notes: Recreated torrent snatch history table with ratio-free handling, pagination, and anonymity safeguards.
