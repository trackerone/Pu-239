# MessagesHandler conversion (2025-10-20)

- Status: Converted
- Notes:
  - Wrapped the public/messages.php stub logic inside the handler with routing and bootstrap guards.
  - Preserved the RuntimeException signalling until the staff mailbox SQL can be rebuilt.
- TODOs:
  - TODO(2025): port staff message overview from public/messages.php when the data layer returns
