# Conversion Report: classes/Http/Handlers/PublicSite/UsercpHandler.php

- Legacy source: public/usercp.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php guard
- Services injected: None
- Config mappings: None
- Database usage: None
- TODOs introduced: 1 (user control panel remains stubbed pending data restore)
- Notes: Handler now loads bootstrap once and performs the same PU239_ROUTED guard before surfacing the stub RuntimeException.
