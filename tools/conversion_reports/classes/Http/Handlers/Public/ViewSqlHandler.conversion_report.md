# Conversion Report: classes/Http/Handlers/Public/ViewSqlHandler.php

- **Date**: 2025-10-16
- **Batch**: offset=155 size=5
- **Source legacy script**: public/view_sql.php
- **Summary**:
  - Ported the Adminer iframe bootstrap directly into the handler with container-backed configuration access.
  - Preserved the legacy staff gating logic and session flash messaging for unauthorized access attempts.
- **Todos**: None.
- **Notes**: Consider migrating the inline iframe markup into a reusable template during future UI cleanups.
