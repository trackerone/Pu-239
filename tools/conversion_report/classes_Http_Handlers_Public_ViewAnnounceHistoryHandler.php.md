# Conversion Report: classes/Http/Handlers/Public/ViewAnnounceHistoryHandler.php

- Legacy source: public/view_announce_history.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: n/a
- Database usage: Announcement listings via Database::fetchAll with bound user id/status filters
- TODOs introduced: 0
- Notes: Preserved legacy stderr notifications and navigation scaffolding while replacing sql_query/sqlesc with Database helpers.
