# Conversion attempt: classes/Http/Handlers/PublicSite/TakereseedHandler.php

- Outcome: converted
- Legacy source: `public/takereseed.php`
- Backup: `classes/Http/Handlers/PublicSite/TakereseedHandler.php.bak`
- Notes:
  - Embedded reseed notification workflow using container-provided `Database`, `Message`, `Session`, and `Cache` services with bound parameters.
  - Preserved bonus deduction and audit logging to match legacy side effects.
- TODOs recorded in handler: `// TODO(2025): add CSRF verification`
