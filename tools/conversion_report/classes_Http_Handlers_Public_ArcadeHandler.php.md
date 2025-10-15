# Conversion Report: classes/Http/Handlers/Public/ArcadeHandler.php

- Legacy source: public/arcade.php
- Container/bootstrap dependencies: bootstrap_web.php, include/bittorrent.php
- Config mappings: allowed.play, class_names, site.name, arcade.top_score_points, arcade.game_names, arcade.games, paths.baseurl, paths.images_baseurl
- Database usage: Database service resolved for parity (no direct queries executed)
- TODOs introduced: 0
- Notes: Handler rebuilds the arcade listing with permission gates and dynamic game roster rendering using config-driven metadata.
