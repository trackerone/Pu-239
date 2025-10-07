# Conversion Report: classes/Http/Handlers/Public/Ajax/CheckportHandler.php

- **Date**: 2025-10-06
- **Batch**: offset=65 size=5
- **Source legacy script**: public/ajax/checkport.php
- **Summary**:
  - Moved the single-port connectivity probe into the handler while preserving the fsockopen-based check and JSON response.
  - Retained user gating via `check_user_status()`.
- **Todos**:
  - TODO(2025): csrf.
- **Error handling**: Wrapped execution in try/catch with HTTP 500 fallback as per handler guidelines.
