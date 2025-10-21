# Conversion Report: classes/Http/Handlers/PublicSite/CasinoHandler.php

- Legacy source: public/casino.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: Deferred; script consumes multiple site settings (limits, ratio rules, auto-shout toggles)
- Database usage: Deferred; casino logic coordinates Casino, CasinoBets, User, and Cache services plus direct Database writes
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Stub left in place because the casino engine blends randomisation, bonus payouts, messaging, and audit logging that need careful manual migration.
