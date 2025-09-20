# Modernization Status Dashboard

## Summary Counts

| Metric | Value | Notes |
| --- | ---: | --- |
| `rehydrated_count` | 5 | Files successfully upgraded out of quarantine. |
| `re_quarantined_count` | 82 | Files returned to quarantine due to parse or marker blockers. |
| `pending_review_count` | 15 | Files neither rehydrated nor re-quarantined yet. |
| `quarantine_active_candidates` | 102 | Entries tracked in quarantine inventory. |
| `quarantine_with_sql` | 87 | Quarantined files still containing SQL markers. |
| `legacy_db_files_remaining` | 97 | Quarantine entries still awaiting PDO migration (includes re-quarantined + pending). |
| `frozen_modules_count` | 0 | Modules explicitly frozen from modernization. |
| `lint_logs_with_errors` | 6 | Lint reports that still flag syntax errors. |
| `lint_error_paths` | 43 | Unique file paths with parse failures across lint logs. |
| `repo_lint_issue_count` | 30 | Errors surfaced in repo-wide lint sweep. |
| `db_purge_rehydrated_v3_updates` | 5 | Recently modernized scripts in db_purge v3 report. |

## Latest Lint Outcomes

| Report | Issues | Affected paths |
| --- | ---: | --- |
| admin_lint_report.txt | 9 | admin/class_promo.php, admin/comments.php, admin/namechanger.php, admin/reports.php, admin/reputation_ad.php, admin/shit_list.php, admin/sitelog.php, admin/system_view.php, admin/warn.php |
| autofix_lint.txt | 4 | admin/class_promo.php, admin/comments.php, admin/reports.php, public/users.php |
| forums_lint_report.txt | 13 | forums/add_subscription.php, forums/delete_post.php, forums/delete_subscription.php, forums/edit_post.php, forums/member_post_history.php, forums/poll.php, forums/post_reply.php, forums/section_view.php, forums/staff_actions.php, forums/subscriptions.php, forums/view_forum.php, forums/view_my_posts.php, forums/view_topic.php |
| include_lint_recursive.txt | 1 | include/function_account_delete.php |
| public_lint_report.txt | 20 | public/blackjack.php, public/coins.php, public/comment.php, public/credits.php, public/forums.php, public/friends.php, public/gift.php, public/invite.php, public/messages.php, public/reputation.php, public/staffbox.php, public/takeedit.php, public/takeeditcp.php, public/takereseed.php, public/topten.php, public/user_unlocks.php, public/usercp.php, public/userhistory.php, public/usermood.php, public/users.php |
| repo_lint_post_bootstrap.txt | 30 | admin/class_promo.php, admin/comments.php, admin/namechanger.php, admin/reports.php, admin/reputation_ad.php, admin/shit_list.php, admin/sitelog.php, admin/system_view.php, admin/warn.php, include/function_account_delete.php, public/blackjack.php, public/coins.php, public/comment.php, public/credits.php, public/forums.php, public/friends.php, public/gift.php, public/invite.php, public/messages.php, public/reputation.php, public/staffbox.php, public/takeedit.php, public/takeeditcp.php, public/takereseed.php, public/topten.php, public/user_unlocks.php, public/usercp.php, public/userhistory.php, public/usermood.php, public/users.php |

**Unique files still failing lint:**

- admin/class_promo.php
- admin/comments.php
- admin/namechanger.php
- admin/reports.php
- admin/reputation_ad.php
- admin/shit_list.php
- admin/sitelog.php
- admin/system_view.php
- admin/warn.php
- forums/add_subscription.php
- forums/delete_post.php
- forums/delete_subscription.php
- forums/edit_post.php
- forums/member_post_history.php
- forums/poll.php
- forums/post_reply.php
- forums/section_view.php
- forums/staff_actions.php
- forums/subscriptions.php
- forums/view_forum.php
- forums/view_my_posts.php
- forums/view_topic.php
- include/function_account_delete.php
- public/blackjack.php
- public/coins.php
- public/comment.php
- public/credits.php
- public/forums.php
- public/friends.php
- public/gift.php
- public/invite.php
- public/messages.php
- public/reputation.php
- public/staffbox.php
- public/takeedit.php
- public/takeeditcp.php
- public/takereseed.php
- public/topten.php
- public/user_unlocks.php
- public/usercp.php
- public/userhistory.php
- public/usermood.php
- public/users.php

## Next Recommended Steps

- **Public batch:** Resolve syntax blockers in 20 public endpoints (public/blackjack.php, public/coins.php, public/comment.php, public/credits.php, public/forums.php…) before the next rehydrate attempt.
- **Staffpanel batch:** Address remaining admin/forums syntax fixes across 22 scripts (admin/class_promo.php, admin/comments.php, admin/namechanger.php, admin/reports.php, admin/reputation_ad.php…) to unblock PDO rewrites.
- **Follow-up rehydration:** Verify newly modernized cleanup jobs (cleanup/announcement_update.php, cleanup/cheatclean_update.php, cleanup/funds_table_update.php, cleanup/hitrun_update.php, cleanup/tvmaze_schedule_update.php) and schedule a targeted test run.
