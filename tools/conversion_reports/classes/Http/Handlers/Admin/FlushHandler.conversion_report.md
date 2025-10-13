# Conversion Report: classes/Http/Handlers/Admin/FlushHandler.php

- **Date**: 2025-10-11
- **Batch**: offset=125 size=5
- **Source legacy script**: admin/flush.php
- **Summary**:
  - Inlined the ghost torrent cleanup workflow using container-backed database access and localized globals.
  - Preserved staff-only gating, audit logging, and the success messaging from the legacy script.
- **Todos**: None.
- **Notes**: Error responses remain routed through the legacy `stderr` helper for consistency.
