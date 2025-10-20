# UsermoodHandler conversion (2025-10-20)

- Status: Converted
- Notes:
  - Inlined the public/usermood.php stub guard into the handler to keep routing consistent.
  - Left the RuntimeException in place until the moods SQL workflow is rebuilt.
- TODOs:
  - TODO(2025): rehydrate user mood workflow from public/usermood.php legacy stub
