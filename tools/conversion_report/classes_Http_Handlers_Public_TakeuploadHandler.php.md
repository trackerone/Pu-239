# Conversion Report: classes/Http/Handlers/Public/TakeuploadHandler.php

- Legacy source: public/takeupload.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php, class.bencdec.php
- Services injected: deferred (ConfigRepository, Database, Cache, Session, Torrent, Usersachiev, Roles, UploadGuard, Audit, etc.)
- Config mappings: pending (paths.baseurl, site.max_torrent_size, bonus.* toggles, youtube.pattern)
- Database usage: deferred (multiple inserts/updates across thankyou/comments/torrents/users; requires transaction safety)
- TODOs introduced: 2 (manual extraction for upload workflow; re-review offset=200)
- Notes: Handler remains as legacy shim pending reconstruction of upload validation, bonus payouts, and messaging integration.
- Re-review: 2025-10-18T18:09:15Z (offset=200) — conversion postponed awaiting PDO transaction + validation parity plan.
