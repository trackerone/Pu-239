# Conversion Report: classes/Http/Handlers/Public/TagsHandler.php

- **Date**: 2025-10-10
- **Batch**: offset=120 size=5
- **Source legacy script**: public/tags.php
- **Summary**:
  - Inlined the BBcode reference page generation directly into the handler with localized variables.
  - Introduced a private `insertTag` helper mirroring the legacy function while keeping formatting helpers intact.
  - Preserved the existing CSRF follow-up reminder for POST requests.
- **Todos**:
  - TODO(2025): add CSRF verification for POST submissions (carried over from legacy script).
- **Notes**: None.
