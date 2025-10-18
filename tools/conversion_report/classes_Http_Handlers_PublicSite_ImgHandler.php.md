# Conversion Report: classes/Http/Handlers/PublicSite/ImgHandler.php

- Legacy source: public/img.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: Database
- Config mappings: None
- Database usage: None (legacy script only retrieved Database instance)
- TODOs introduced: 0
- Notes: Reimplemented image proxy with header caching logic, replicating conditional 304 responses and fallback poster resolution.
