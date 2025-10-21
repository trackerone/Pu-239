# Conversion Report: classes/Http/Handlers/PublicSite/ForumsHandler.php

- Legacy source: public/forums.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: Deferred; forum controller reads numerous forum_config keys and image paths from ConfigRepository
- Database usage: Deferred; extensive Fluent-style query builder usage for forums, topics, and posts
- TODOs introduced: 1 (manual extraction follow-up)
- Notes: Stub preserved because the forums front controller contains branching actions, role checks, and UI helpers requiring manual refactor.
