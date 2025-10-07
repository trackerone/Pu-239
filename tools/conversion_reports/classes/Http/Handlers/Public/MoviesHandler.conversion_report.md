# Conversion Report: classes/Http/Handlers/Public/MoviesHandler.php

- **Date**: 2025-10-07
- **Batch**: offset=75 size=5
- **Source legacy script**: public/movies.php
- **Summary**:
  - Conversion deferred; the legacy script orchestrates multiple cached data feeds (TMDB, TVMaze, IMDB) with large helper blocks requiring careful extraction.
  - Handler keeps legacy require path and records TODO for manual follow-up.
- **Todos**:
  - TODO(2025): extract legacy block from public/movies.php:1-360 (cache hydration, helper generation, and proxy usage).
- **Notes**: Coordinate with caching subsystem maintainers before attempting a full port.
