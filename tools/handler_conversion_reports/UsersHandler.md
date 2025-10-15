# UsersHandler conversion (2025-10-15)

- Status: Converted
- Notes:
  - Replaced the legacy require shim with direct bootstrap and routing guards in the handler.
  - Left the runtime exception in place until the user directory flow is rebuilt.
- TODOs:
  - TODO(2025): restore user list view from public/users.php:10
