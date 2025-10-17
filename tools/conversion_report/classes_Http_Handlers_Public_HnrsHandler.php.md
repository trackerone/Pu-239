# Conversion Report: classes/Http/Handlers/Public/HnrsHandler.php

- Legacy source: public/hnrs.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Services injected: deferred (User, Snatched, Torrent, Session, Database, Cache)
- Config mappings: pending (bonus/ration free logic)
- Database usage: deferred (multiple FluentPDO joins and transactional updates)
- TODOs introduced: 1 (manual extraction for seedtime + bonus remediation flows)
- Notes: Legacy script orchestrates complex hit-and-run remediation with class services; flagged for manual conversion.
