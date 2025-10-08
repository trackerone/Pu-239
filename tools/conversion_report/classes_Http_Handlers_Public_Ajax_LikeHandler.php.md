# Conversion Report: classes/Http/Handlers/Public/Ajax/LikeHandler.php

- Legacy source: public/ajax/like.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: none
- Database usage: Database + Cache services injected; multiple transactional inserts/updates converted to Database::tx and Cache clears retained.
- TODOs introduced: 1 (csrf review on POST)
- Notes: Legacy like/unlike flow now lives in handler with Database transaction retries and formatted username aggregation preserved.
