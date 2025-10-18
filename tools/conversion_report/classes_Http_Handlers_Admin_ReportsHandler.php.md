# Conversion Report: classes/Http/Handlers/Admin/ReportsHandler.php

- Legacy source: admin/reports.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php fallback
- Services injected: none
- Config mappings: none
- Database usage: not required (legacy script contained no queries)
- TODOs introduced: 1 (report center UI and SQL still pending restoration)
- Notes: Embedded the legacy routing guard so the handler enforces AuthZ directly before surfacing the RuntimeException stub.
