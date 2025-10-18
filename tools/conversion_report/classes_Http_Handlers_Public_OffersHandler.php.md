# Conversion Report: classes/Http/Handlers/Public/OffersHandler.php

- Inlined `public/offers.php` into the handler, wiring Config/Session/Offer/Comment/Torrent dependencies directly from the container.
- Preserved legacy redirects, validation, and Literal arithmetic updates while avoiding risky refactors.
- TODOs: none beyond existing legacy comments (e.g. future CSRF hardening remains noted inline).
