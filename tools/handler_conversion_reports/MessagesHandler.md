# MessagesHandler conversion (2025-10-15)

- Status: Converted
- Notes:
  - Inlined the public/messages.php guard directly into the handler entry point.
  - Maintained the stubbed exception so routing still surfaces the missing workflow cleanly.
- TODOs:
  - TODO(2025): port staff message overview from public/messages.php:10
