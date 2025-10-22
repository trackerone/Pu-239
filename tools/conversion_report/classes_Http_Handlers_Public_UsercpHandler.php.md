# Conversion Report: classes/Http/Handlers/Public/UsercpHandler.php

- Legacy source: public/usercp.php
- Container/bootstrap dependencies: public/index.php guard
- Services injected: None
- Config mappings: None
- Database usage: None
- TODOs introduced: 1 (user control panel still stubbed pending data restore)
- Notes: Simplified handler now mirrors the legacy stub by gating on PU239_ROUTED and raising the RuntimeException directly.
