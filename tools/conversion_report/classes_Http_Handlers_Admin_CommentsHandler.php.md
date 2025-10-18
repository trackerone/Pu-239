# Conversion Report: classes/Http/Handlers/Admin/CommentsHandler.php

- Legacy source: admin/comments.php
- Container/bootstrap dependencies: bootstrap_web.php, public/index.php fallback
- Services injected: none
- Config mappings: none
- Database usage: not required (legacy script only enforced routing/roles)
- TODOs introduced: 1 (comment moderation workflow still missing from legacy stub)
- Notes: Inlined the legacy guard so the handler now bootstraps and applies AuthZ before raising the existing RuntimeException placeholder.
