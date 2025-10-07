# Conversion Report: classes/Http/Handlers/Public/MybonusHandler.php

- **Date**: 2025-10-07
- **Batch**: offset=75 size=5
- **Source legacy script**: public/mybonus.php
- **Summary**:
  - Conversion deferred due to extensive `$fluent` query chains, bonus catalogue branching, and cross-service interactions (Auth, Bonuslog, Message).
  - Handler preserves legacy bootstrap and adds TODO marker for manual porting.
- **Todos**:
  - TODO(2025): extract legacy block from public/mybonus.php:1-320 (bonus purchase flows and dependent service updates).
- **Notes**: Plan migration alongside karma bonus feature rewrite to avoid regressions.
