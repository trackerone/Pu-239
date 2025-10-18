# Conversion Report: classes/Http/Handlers/Admin/NamechangerHandler.php

- Legacy source: admin/namechanger.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php fallback
- Services injected: none
- Config mappings: none
- Database usage: not required (legacy script is a placeholder)
- TODOs introduced: 1 (actual name change workflow still needs porting)
- Notes: Handler now loads the legacy guard and AuthZ requirement directly while preserving the RuntimeException placeholder.
