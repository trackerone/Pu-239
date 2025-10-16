# Conversion Report: classes/Http/Handlers/Public/FlashHandler.php

- **Date**: 2025-10-16
- **Batch**: offset=150 size=5
- **Source legacy script**: public/flash.php
- **Summary**:
  - Migrated the arcade Flash scoreboard logic to use the shared Database wrapper with bound parameters for all ranking queries.
  - Preserved the original layout and routing checks while normalizing request handling and sanitization.
- **Todos**:
  - TODO(2025): verify the legacy member-level selection (formerly `$row['level']`) still reflects the intended scoreboard row.
- **Notes**: Consider caching leaderboard results or moving repeated COUNT queries into batched calculations.
