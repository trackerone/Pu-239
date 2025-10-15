# CommentHandler conversion (2025-10-15)

- Status: Converted
- Notes:
  - Replaced the stub require wrapper with direct bootstrap and guard logic.
  - Captured the legacy exception placeholder to keep behaviour unchanged while routing errors via the handler.
- TODOs:
  - TODO(2025): implement legacy comment workflow; see public/comment.php:10
