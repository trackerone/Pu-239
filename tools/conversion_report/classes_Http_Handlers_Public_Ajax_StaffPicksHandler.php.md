# Conversion Report: classes/Http/Handlers/Public/Ajax/StaffPicksHandler.php

- Legacy source: public/ajax/staff_picks.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Database + Cache pulled from container; staff pick flag update remains single UPDATE with bound params.
- TODOs introduced: 1 (csrf follow-up for POST)
- Notes: Preserves audit log behavior and cache purge; translates pick toggle and TIME_NOW semantics.
