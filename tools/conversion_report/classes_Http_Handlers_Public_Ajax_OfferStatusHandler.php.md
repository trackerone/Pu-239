# Conversion Report: classes/Http/Handlers/Public/Ajax/OfferStatusHandler.php

- Legacy source: public/ajax/offer_status.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Database::run for status update; audit_log call retained for moderation tracking.
- TODOs introduced: 1 (csrf review on POST)
- Notes: Staff-only guard and status cycling logic migrated directly with JSON responses triggered for invalid submissions.
