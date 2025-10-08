# Conversion Report: classes/Http/Handlers/Public/Ajax/TvmazeLookupHandler.php

- Legacy source: public/ajax/tvmaze_lookup.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: none
- Database usage: No SQL interactions; leverages Torrent service from container for poster lookup, defers to helper for remote fetch.
- TODOs introduced: 1 (csrf follow-up for POST)
- Notes: Retains regex season/episode parsing and fallback poster resolution before returning tvmaze payload.
