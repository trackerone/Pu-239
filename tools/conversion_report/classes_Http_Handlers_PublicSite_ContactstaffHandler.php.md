# Conversion attempt: classes/Http/Handlers/PublicSite/ContactstaffHandler.php

- Outcome: converted
- Legacy source: `public/contactstaff.php`
- Backup: `classes/Http/Handlers/PublicSite/ContactstaffHandler.php.bak`
- Notes:
  - Inlined form rendering and message submission with container-managed `Database`, `Session`, and `Cache` dependencies.
  - Preserved staff message audit logging via helper include and modernised redirects/flash messages.
- TODOs recorded in handler: `// TODO(2025): add CSRF verification`
