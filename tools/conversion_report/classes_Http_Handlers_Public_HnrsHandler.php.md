# Conversion Report: classes/Http/Handlers/Public/HnrsHandler.php

- Legacy source: public/hnrs.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: deferred (multiple Snatched/User/Cache dependencies in legacy script)
- Config mappings: deferred — heavy reliance on `hnr_config` thresholds
- Database usage: deferred — intertwined updates across snatched/users tables with bonus + cache side-effects
- TODOs introduced: 2
- Notes: Offset=210 batch=5 review highlights need for manual orchestration of seedtime fixes and ratio credit purchases before safe conversion.
