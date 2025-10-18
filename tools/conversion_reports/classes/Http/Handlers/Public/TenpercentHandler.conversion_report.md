# Conversion Report: classes/Http/Handlers/Public/TenpercentHandler.php

- **Date**: 2025-10-17
- **Batch**: offset=170 size=5
- **Source legacy script**: public/tenpercent.php
- **Summary**:
  - Migrated the ten percent bonus workflow into the handler using Database::run with bound parameters and cache updates for user/torrent state.
  - Preserved the legacy messaging + session feedback loop and rebuilt the statistics table output inline.
- **Todos**:
  - TODO(2025): add CSRF verification to the POST handler before allowing credit adjustments.
  - TODO(2025): confirm the hard-coded bonus debit (10.0) still matches current business rules.
- **Notes**: Consider extracting the repeated ratio calculations into a helper shared with other bonus features in a follow-up.
