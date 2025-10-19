# Conversion Report: classes/Http/Handlers/PublicSite/ContactstaffHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=260 size=5
- **Source legacy script**: public/contactstaff.php
- **Summary**:
  - Conversion postponed because the legacy form workflow coordinates captcha validation, mail queue writes, and moderation routing beyond automated extraction scope.
  - Handler continues to buffer the legacy include and calls out the manual follow-up required.
- **Todos**:
  - TODO(2025): extract legacy block from public/contactstaff.php:1-200 – migrate multi-step form handling and message delivery.
- **Error handling**: Legacy include wrapped in handler try/catch with HTTP 500 fallback on failure.
