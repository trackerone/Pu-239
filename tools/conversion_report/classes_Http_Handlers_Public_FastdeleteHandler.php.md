# Conversion Report: classes/Http/Handlers/Public/FastdeleteHandler.php

- Legacy source: public/fastdelete.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: Deferred; confirmation/redirect flow references multiple config paths
- Database usage: Deferred; torrent removal, messaging, and bonus adjustments need coordinated services
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Stub maintained because the fast delete routine spans torrent cleanup, cache invalidation, and bonus recalculations.
