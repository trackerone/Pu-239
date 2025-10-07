# DataresetHandler conversion (2025-10-07)

- Status: Deferred (manual extraction required)
- Notes:
  - admin/datareset.php relies on removed $fluent queries, mixed service lookups, and unresolved message/torrent handling.
  - Handler left as stub with explicit TODO for a curated rewrite.
- TODOs:
  - TODO(2025): extract legacy block from admin/datareset.php:1-200 (requires fluent query rewrite and service wiring)
