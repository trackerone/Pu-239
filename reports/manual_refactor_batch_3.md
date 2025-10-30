# Manual Refactor Issue Prep — Batch 3 (indices 60-79)

Source: `tools/handler_convert_report.csv`

| global_index | file | path_exists | todos | notes (trunc 80) | issue-url |
| --- | --- | --- | --- | --- | --- |
| 60 | classes/Http/Handlers/PublicSite/BugsHandler.php | yes | 1 | TODO(2025) extract legacy block from public/bugs.php:1-520 (offset=345 batch=5) | N/A (env-limited) |
| 61 | classes/Http/Handlers/PublicSite/CasinoHandler.php | yes | 1 | TODO(2025) extract legacy block from public/casino.php:1-420 (offset=345 batc... | N/A (env-limited) |
| 62 | classes/Http/Handlers/PublicSite/CatalogHandler.php | yes | 1 | TODO(2025) extract legacy block from public/catalog.php:1-420 (offset=345 bat... | N/A (env-limited) |
| 63 | classes/Http/Handlers/PublicSite/DetailsHandler.php | yes | 1 | TODO(2025) extract legacy block from public/details.php:1-1100 (offset=345 ba... | N/A (env-limited) |
| 64 | classes/Http/Handlers/PublicSite/DownloadHandler.php | yes | 1 | TODO(2025) manual extraction required public/download.php:1-201 (multi-branch... | N/A (env-limited) |
| 65 | classes/Http/Handlers/PublicSite/EditHandler.php | yes | 1 | TODO(2025) manual extraction required public/edit.php:1-270 (multi-step torre... | N/A (env-limited) |
| 66 | classes/Http/Handlers/PublicSite/FlashHandler.php | yes | 1 | TODO(2025) manual conversion required public/flash.php:1-200 (mysqli/sql_quer... | N/A (env-limited) |
| 67 | classes/Http/Handlers/PublicSite/ForumsHandler.php | yes | 1 | TODO(2025) extract legacy block from public/forums.php:1-800 (offset=345 batc... | N/A (env-limited) |
| 68 | classes/Http/Handlers/PublicSite/GiftHandler.php | yes | 1 | Legacy stub remains placeholder; TODO rehydrate public/gift.php SQL. | N/A (env-limited) |
| 69 | classes/Http/Handlers/PublicSite/HnrsHandler.php | yes | 1 | TODO(2025) manual extraction required public/hnrs.php:1-340. | N/A (env-limited) |
| 70 | classes/Http/Handlers/PublicSite/InviteHandler.php | yes | 1 | Legacy stub remains placeholder; TODO rehydrate public/invite.php SQL. | N/A (env-limited) |
| 71 | classes/Http/Handlers/PublicSite/LotteryHandler.php | yes | 1 | TODO(2025) nested includes + mysqli queries in public/lottery.php:1-200. | N/A (env-limited) |
| 72 | classes/Http/Handlers/PublicSite/MybonusHandler.php | yes | 1 | TODO(2025) manual extraction required public/mybonus.php:1-841 (karma store w... | N/A (env-limited) |
| 73 | classes/Http/Handlers/PublicSite/NewAnnouncementHandler.php | yes | 1 | TODO(2025) manual extraction required public/new_announcement.php:1-200 (sql_... | N/A (env-limited) |
| 74 | classes/Http/Handlers/PublicSite/OffersHandler.php | yes | 1 | TODO(2025) manual extraction required public/offers.php:1-420 (Offer CRUD + v... | N/A (env-limited) |
| 75 | classes/Http/Handlers/PublicSite/RecoverHandler.php | yes | 1 | TODO(2025) resolve merge markers and convert public/recover.php:1-220 (passwo... | N/A (env-limited) |
| 76 | classes/Http/Handlers/PublicSite/ReportHandler.php | yes | 1 | TODO(2025) replace Fluent placeholder + insert pipeline public/report.php:1-180. | N/A (env-limited) |
| 77 | classes/Http/Handlers/PublicSite/RequestsHandler.php | yes | 1 | TODO(2025) manual extraction required public/requests.php:1-598 (multi-action... | N/A (env-limited) |
| 78 | classes/Http/Handlers/PublicSite/TakeeditcpHandler.php | yes | 1 | TODO(2025) manual extraction required public/takeeditcp.php:1-20 (offset=295... | N/A (env-limited) |
| 79 | classes/Http/Handlers/PublicSite/TakethankyouHandler.php | yes | 1 | TODO(2025) manual extraction required public/takethankyou.php:1-200 (offset=2... | N/A (env-limited) |

**Totals**

- COUNT_IN_SUBSET: 20
- RANGE_PROCESSED: 60..79
- REMAINING_FALSE_ESTIMATE: 16

*Issue creation is blocked in this environment (no GitHub UI access); placeholder values remain until issues can be opened manually.*
