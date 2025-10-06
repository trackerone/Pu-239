# Conversion Report: classes/Http/Handlers/Public/Ajax/CookerNotifyHandler.php

- Legacy source: public/ajax/cooker_notify.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Uses Database::run for delete/insert plus fetchValue for LAST_INSERT_ID().
- TODOs introduced: 1 (csrf handling for POST id/notified)
- Notes: Maintains audit helper include and returns JSON state transitions for notifications.
