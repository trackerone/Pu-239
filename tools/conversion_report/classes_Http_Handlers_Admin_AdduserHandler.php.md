# Conversion Report: classes/Http/Handlers/Admin/AdduserHandler.php

- Legacy source: admin/adduser.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Services injected: ConfigRepository, Cache, Session, Validator, User
- Config mappings: paths.baseurl → form action/breadcrumbs, signup.email_confirm → optional email notice
- Database usage: None (user creation delegated to User service)
- TODOs introduced: 0
- Notes: Rebuilt the add-user workflow with validator enforcement, password policy checks, and legacy helper integration while preserving stdhead/stdfoot rendering.
