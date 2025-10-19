# LoginHandler conversion (2025-10-18)

- Status: Converted
- Summary:
  - Inlined public/login.php flow including validation, bans, and rate limiting.
  - Ensured Auth, Session, and User services are resolved from the container.
- TODOs:
  - TODO(2025): add CSRF verification for the login form submit handling.
