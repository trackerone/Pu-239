# Conversion Report: classes/Http/Handlers/PublicSite/UsersHandler.php

- Legacy source: public/users.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php guard
- Services injected: None
- Config mappings: None
- Database usage: None
- TODOs introduced: 1 (public user directory still stubbed pending SQL restore)
- Notes: Removed the buffered require wrapper; handler now guards routing and raises the legacy RuntimeException directly.
