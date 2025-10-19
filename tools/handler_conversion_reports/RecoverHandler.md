# RecoverHandler conversion (2025-10-19)

- Status: Deferred
- Notes:
  - public/recover.php orchestrates Delight/Auth password reset flows and currently contains unresolved merge markers, making automation unsafe.
  - The handler records the conversion attempt and continues buffering the legacy script output for consistency.
- TODOs:
  - TODO(2025): resolve merge conflicts and port public/recover.php:1-220 into dedicated services with CSRF protection.
