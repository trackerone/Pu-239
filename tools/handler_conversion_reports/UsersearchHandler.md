# UsersearchHandler conversion (2025-10-09)

- Status: Converted
- Notes:
  - Migrated `public/ajax/usersearch.php` into the handler with repository lookup via the service container.
  - Preserved JSON response contract for successful and invalid requests.
- TODOs:
  - TODO(2025): add CSRF validation for AJAX keyword search.
