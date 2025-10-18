# Conversion attempt: classes/Http/Handlers/PublicSite/AjaxchatHandler.php

- Outcome: converted
- Legacy source: `public/ajaxchat.php`
- Backup: `classes/Http/Handlers/PublicSite/AjaxchatHandler.php.preconvert.bak`
- Notes:
  - Lifted bootstrap logic into handler scope with container-backed database handle and retained legacy AJAX Chat includes.
  - Maintains `check_user_status()` guard and instantiates `CustomAJAXChat` to trigger legacy side-effects.
- TODOs recorded in handler: _none_
