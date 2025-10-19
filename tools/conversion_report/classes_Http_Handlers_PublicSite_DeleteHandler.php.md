# Conversion Report: classes/Http/Handlers/PublicSite/DeleteHandler.php

- Legacy source: public/delete.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php, CLASS_DIR class_user_options_2.php
- Services injected: ConfigRepository, Database, Torrent, User, Message, Session
- Config mappings: site.name → violation message, paths.baseurl → redirects, bonus.per_delete/expires.user_cache → bonus adjustments
- Database usage: Single fetch for torrent metadata plus message insert and bonus update via Database service
- TODOs introduced: 1 (POST flow still lacks CSRF verification)
- Notes: Preserved owner validation, torrent removal, bonus recalculation, PM notification, and stdhead-based messaging.
