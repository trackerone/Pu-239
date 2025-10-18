# Conversion Report: classes/Http/Handlers/Public/PeerlistHandler.php

- Legacy source: public/peerlist.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, Torrent
- Config mappings: `paths.baseurl`, `site.ratio_free`
- Database usage: `Database::toArray` for peer aggregation joining torrents/users with `INET6_NTOA` conversion and bound torrent id.
- TODOs introduced: 0
- Notes: Ported helper sorting into private comparators and rebuilt the peer tables while respecting anonymity rules (offset=210 batch=5).
