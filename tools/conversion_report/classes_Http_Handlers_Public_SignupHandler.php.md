# Conversion Report: classes/Http/Handlers/Public/SignupHandler.php

- Legacy source: public/signup.php
- Conversion status: deferred (argon password hashing + promo workflow conflicts)
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services needed: ConfigRepository, Database, Session, Auth, User, Message, Validator
- TODOs introduced: 1
- Notes: Merge-conflicted password policy enforcement and promo/invite SQL updates need manual reconciliation before safe handler extraction.
