# Conversion Report: classes/Http/Handlers/Public/GamesHandler.php

- **Date**: 2025-10-17
- **Batch**: offset=170 size=5
- **Source legacy script**: public/games.php
- **Summary**:
  - Inlined the blackjack availability and casino count logic using Database::fetchAll/fetchValue with bound parameters.
  - Rebuilt the game selection grid with localized config-driven URLs and colour state driven by the converted query results.
- **Todos**:
  - None.
- **Notes**: Future cleanups could extract the repeated blackjack card markup into a small helper to reduce duplication.
