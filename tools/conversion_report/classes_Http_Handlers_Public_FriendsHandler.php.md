# Conversion Report: classes/Http/Handlers/Public/FriendsHandler.php

- Legacy source: public/friends.php (rehydrate candidate: _quarantine/rebroken/public/friends.php)
- Container/bootstrap dependencies: delegated to legacy entry
- Services injected: None
- Config mappings: None
- Database usage: None (legacy file still contains $db->run('); placeholders)
- TODOs introduced: 1 — TODO(2025) extract legacy block once rehydrate markers are resolved
- Notes: Reinstated buffered require stub instead of RuntimeException so routing mirrors legacy behaviour while awaiting clean SQL.
