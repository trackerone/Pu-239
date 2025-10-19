# Conversion Report: classes/Http/Handlers/PublicSite/BotTriggersHandler.php

- **Date**: 2025-10-19
- **Batch**: offset=265 size=5
- **Source legacy script**: public/bot_triggers.php
- **Summary**:
  - Conversion blocked by the trigger/reply admin console's dependence on validators, cache coordination, and multiple domain services.
  - Recorded a TODO directing a manual port once supporting abstractions are prepared.
- **Todos**:
  - TODO(2025): extract legacy block from public/bot_triggers.php:1-420 (validation + CRUD across multiple services).
- **Error handling**: Conversion deferred; stub keeps legacy inclusion path intact.
