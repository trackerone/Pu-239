# Conversion Report: classes/Http/Handlers/Public/Ajax/MemberInputHandler.php

- Legacy source: public/ajax/member_input.php
- Container/bootstrap dependencies: bootstrap_web.php, include/helpers/audit.php, include/bittorrent.php
- Config mappings: paths.baseurl -> ConfigRepository::get('paths.baseurl')
- Database usage: Database service reused for sitelog insert; Peer/Snatched services still resolved from container.
- TODOs introduced: 1 (csrf review on POST)
- Notes: Session messaging and audit logging preserved; watched user updates retain conditional audit flows with sanitized inputs.
