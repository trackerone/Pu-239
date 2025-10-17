# Conversion Report: classes/Http/Handlers/Public/PeerlistHandler.php

- Legacy source: public/peerlist.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: deferred (ConfigRepository, Database, Torrent)
- Config mappings: paths.baseurl, site.ratio_free (pending manual mapping)
- Database usage: deferred (peer/torrent aggregation, helper functions for table output)
- TODOs introduced: 1 (manual extraction for peer tables and sorting helpers)
- Notes: Legacy script defines multiple helper functions and conditional sorting; marked for manual porting.
