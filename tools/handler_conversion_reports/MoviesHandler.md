# MoviesHandler conversion (2025-10-19)

- Status: Converted
- Summary:
  - Inlined public/movies.php routing guard, cache lookups, and list rendering inside the handler.
  - Localized ConfigRepository/Cache access via the container and preserved poster rendering helper via a scoped closure.
- TODOs:
  - TODO(2025): review TVMaze cache decoding for potential streaming API fallback when bz2 payloads are unavailable.
