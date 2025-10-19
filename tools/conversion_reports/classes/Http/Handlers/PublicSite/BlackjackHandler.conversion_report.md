# Conversion Report: classes/Http/Handlers/PublicSite/BlackjackHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=265 size=5
- **Source legacy script**: public/blackjack.php
- **Summary**:
  - Review exposed a sprawling blackjack engine with dynamic SQL, messaging side-effects, and randomization coupled to global helpers.
  - Left the handler untouched aside from documenting the manual migration requirement.
- **Todos**:
  - TODO(2025): extract legacy block from public/blackjack.php:1-500 (gameplay engine spans DB + messaging interactions).
- **Error handling**: Conversion deferred; existing stub continues to include the legacy script safely.
