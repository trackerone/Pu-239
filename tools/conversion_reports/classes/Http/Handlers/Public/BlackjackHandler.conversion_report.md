# Conversion Report: classes/Http/Handlers/Public/BlackjackHandler.php

- **Date**: 2025-10-07
- **Batch**: offset=70 size=5
- **Source legacy script**: public/blackjack.php
- **Summary**:
  - Conversion deferred due to complex, stateful blackjack gameplay engine with extensive SQL and messaging side-effects.
  - Added explicit TODO referencing the legacy script for manual extraction and auditing.
- **Todos**:
  - TODO(2025): extract legacy block from public/blackjack.php:1-420 (stateful gameplay, SQL migrations, CSRF hardening).
- **Error handling**: Existing stub wrapper still buffers output and logs thrown errors.
