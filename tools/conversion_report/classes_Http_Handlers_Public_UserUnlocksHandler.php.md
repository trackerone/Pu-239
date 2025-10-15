# Conversion Report: classes/Http/Handlers/Public/UserUnlocksHandler.php

- Legacy source: public/user_unlocks.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php, class_user_options_2.php
- Config mappings: expires.user_cache
- Database usage: Database::run for bitmask updates and Database::fetch for refreshed perms; cache row updated for user profile
- TODOs introduced: 1 (add CSRF protection to POST unlock form)
- Notes: Handler encapsulates unlock toggles, audit logging, and cache refresh with stricter ID validation and staff gating.
