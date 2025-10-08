# Conversion Report: classes/Http/Handlers/Public/Ajax/OfferNotifyHandler.php

- Legacy source: public/ajax/offer_notify.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Database::run/Delete/Insert with LAST_INSERT_ID fetch preserved using bound parameters.
- TODOs introduced: 1 (csrf review on POST)
- Notes: Notification toggle returns JSON early for deletes and keeps audit helper usage consistent with legacy expectations.
