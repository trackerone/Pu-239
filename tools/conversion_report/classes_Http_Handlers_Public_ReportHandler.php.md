# Conversion Report: classes/Http/Handlers/Public/ReportHandler.php

- Legacy source: public/report.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: `$config->get('paths.baseurl')` for redirect + links
- Database usage: `SELECT` duplicate check and `INSERT` into `reports` using Database::fetchValue/run with bound params
- TODOs introduced: 1 (retain CSRF verification placeholder)
- Notes: Preserved cache + session side effects and error messaging while wrapping execution in try/catch.
