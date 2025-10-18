# Conversion Report: classes/Http/Handlers/Public/SearchHandler.php

- Legacy source: public/search.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, User, Session
- Config mappings: paths.baseurl
- Database usage: SELECT id, name, hex(info_hash) FROM torrents WHERE name LIKE :search
- TODOs introduced: 0
- Notes: Ported bot-authenticated JSON search, status gating, and redirect fallback for non-bot access.
