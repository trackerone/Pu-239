# Conversion Report: classes/Http/Handlers/Public/ForumsHandler.php

- **Date**: 2025-10-18
- **Batch**: offset=175 size=5
- **Source legacy script**: public/forums.php
- **Summary**:
  - Conversion deferred; the legacy file orchestrates the full forum engine with chained Fluent queries, cached aggregates, and dozens of action branches.
- **Todos**:
  - TODO(2025): Extract forum routing logic and translate Fluent query chains to `Pu239\Database` with explicit prepared statements.
  - TODO(2025): Map session- and cache-dependent side effects (readpost expiry, subscription updates) into dedicated services before inlining.
- **Notes**: Handler left as buffered stub to avoid destabilising the forum subsystem until a dedicated rewrite plan is in place.
