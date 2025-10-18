# Conversion Report: classes/Http/Handlers/Admin/WarnHandler.php

- Legacy source: admin/warn.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php fallback
- Services injected: none
- Config mappings: none
- Database usage: not required (legacy script was a guard placeholder)
- TODOs introduced: 1 (staff warning workflow still needs to be restored)
- Notes: Embedded the legacy access control logic directly into the handler while keeping the RuntimeException stub for missing workflow.
