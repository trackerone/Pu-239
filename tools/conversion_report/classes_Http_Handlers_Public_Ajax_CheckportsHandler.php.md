# Conversion Report: classes/Http/Handlers/Public/Ajax/CheckportsHandler.php

- Legacy source: public/ajax/checkports.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: none
- Database usage: Uses Database::toArray to fetch peer IP/port details for the user scope.
- TODOs introduced: 1 (csrf review for POST uid)
- Notes: Ports are probed directly with fsockopen and HTML output buffered via json_out.
