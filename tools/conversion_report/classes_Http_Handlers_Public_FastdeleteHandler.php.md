# Conversion Report: classes/Http/Handlers/Public/FastdeleteHandler.php

- Legacy source: public/fastdelete.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Services injected: ConfigRepository, Database, Cache, Session, Torrent
- Config mappings: paths.baseurl, bonus.on, bonus.per_delete, expires.user_cache
- Database usage: SELECT torrent metadata, INSERT staff notification, UPDATE user seedbonus
- TODOs introduced: 0
- Notes: Inlined staff fast-delete flow with permission checks, torrent removal hooks, bonus rollback, and success flash message.
