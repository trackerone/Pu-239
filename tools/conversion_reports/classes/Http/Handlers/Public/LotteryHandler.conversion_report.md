# Conversion Report: classes/Http/Handlers/Public/LotteryHandler.php

- **Date**: 2025-10-07
- **Batch**: offset=75 size=5
- **Source legacy script**: public/lottery.php
- **Summary**:
  - Conversion deferred because the legacy entry dispatches into nested `lottery/*.php` scripts and mixes direct SQL helpers (`sql_query`, `mysqli_fetch_assoc`) that require manual PDO mapping.
  - Handler retains legacy inclusion path with TODO marker for manual extraction.
- **Todos**:
  - TODO(2025): extract legacy block from public/lottery.php:1-140 (nested includes and sql_query usage).
- **Notes**: Ensure supporting lottery sub-scripts are reviewed together to avoid partial migrations.
