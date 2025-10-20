# CoinsHandler conversion (2025-10-20)

- Status: Converted
- Notes:
  - Embedded the public/coins.php stub directly into the handler with routing guard checks.
  - The legacy workflow still raises the RuntimeException until SQL fragments are restored.
- TODOs:
  - TODO(2025): rehydrate coin rewards workflow from public/coins.php legacy stub
