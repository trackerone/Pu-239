# Conversion Report: classes/Http/Handlers/Public/StaffboxHandler.php

- Legacy source: public/staffbox.php (rehydrate candidate: _quarantine/rebroken/public/staffbox.php)
- Container/bootstrap dependencies: delegated to legacy entry
- Services injected: None
- Config mappings: None
- Database usage: None (legacy script still has $db->run('); fragments)
- TODOs introduced: 1 — TODO(2025) extract legacy block after SQL markers are resolved
- Notes: Restored buffered legacy include instead of inline RuntimeException to keep staffbox routing operational pending rehydrate.
