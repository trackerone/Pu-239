# Conversion Report: classes/Http/Handlers/Public/Ajax/ImdbLookupHandler.php

- Legacy source: public/ajax/imdb_lookup.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Services injected: Image (poster lookup)
- Config mappings: none
- Database usage: not required (legacy stub did not query DB)
- TODOs introduced: 1 (csrf on POST)
- Notes: Migrated IMDB poster lookup flow inline with handler, preserving fallback image search ordering.
