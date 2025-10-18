# Conversion Report: classes/Http/Handlers/Public/LoginHandler.php

- **Date**: 2025-10-17
- **Batch**: offset=170 size=5
- **Source legacy script**: public/login.php
- **Summary**:
  - Moved the full login flow into the handler, wiring rate limiting, validation, and Audit logging directly against the container services.
  - Retained the IP logging / limit enforcement and rebuilt the HTML form markup without relying on the legacy include.
- **Todos**:
  - TODO(2025): add CSRF protection to the login POST workflow to align with other modernised forms.
- **Notes**: Future cleanup could centralise the return-to sanitisation logic to avoid repeated file_exists checks across endpoints.
