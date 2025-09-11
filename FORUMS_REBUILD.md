# Forums Rebuild Guide

## Summary
27 forum module files were quarantined. All originals now reside in `forums/_quarantine/*.orig` while active endpoints are 503 stubs.

## File Inventory
| File | SHA1 | Size (bytes) | Markers |
| ---- | ---- | ------------ | ------- |
| forums/add_subscription.php | a044c5956d494397dbb964144866f73ce052ea78 | 585 | dangling_quote, missing_bootstrap, missing_strict_types |
| forums/attachment.php | d60566b4e3fecf792024741904962f33c8649372 | 2912 | likely_insert, missing_strict_types |
| forums/clear_unread_post.php | d3065cf60691c9f4d7d4c3c19267c8d378e3c4e3 | 1079 | missing_bootstrap, missing_strict_types |
| forums/delete_post.php | 04c79440a244c5a0a414b5616947b62d49d32e0f | 4635 | unclosed_brace, dangling_quote, missing_bootstrap, missing_strict_types |
| forums/delete_subscription.php | 97e0fa437aeeaa31934e86210830dc5251720d5d | 762 | unclosed_brace, missing_bootstrap, missing_strict_types |
| forums/download_attachment.php | 2de4617fa58ce928d1dc9708bcab6ab72ae6dba5 | 1068 | likely_update, missing_strict_types |
| forums/edit_post.php | e0ac92f833128fca91b6239107e0f00bd5b074be | 9188 | contains_sqlesc, unclosed_brace, likely_update, missing_bootstrap, missing_strict_types |
| forums/editor.php | 99c5c2b77594c5f8e214f44c566769a3d1a22533 | 13248 | missing_strict_types |
| forums/last_ten.php | 22790358b4c8eec11db35217567140993c3be6fe | 2636 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_strict_types |
| forums/mark_all_as_read.php | 6bd5c3b3d664102098c9782f72953c8823fbeccc | 3277 | missing_bootstrap, missing_strict_types |
| forums/member_post_history.php | 54592838fb3069ed372c56f74027854b2260a718 | 17836 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_bootstrap, missing_strict_types |
| forums/new_replies.php | 387bdce2768ca22a16b8db0991af2f46992c5812 | 9788 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_strict_types |
| forums/new_topic.php | d3a5f8c4be9d71aba65b0ebf52c626f0c5944d82 | 7653 | dangling_quote, likely_update, likely_insert, missing_bootstrap, missing_strict_types |
| forums/poll.php | 76d26e1d70e9bb24a9f3c1d38b83caebe2d49211 | 12962 | contains_mysqli, contains_sqlesc, unclosed_brace, dangling_quote, likely_insert, missing_bootstrap, missing_strict_types |
| forums/post_reply.php | d8308a7cfed06ea5633995cbcb3655088fadb991 | 3523 | contains_mysqli, contains_sqlesc, unclosed_brace, likely_select, missing_bootstrap, missing_strict_types |
| forums/quick_reply.php | d13f6c108cc618ae2ff17c6497c9800f8012d378 | 858 | missing_strict_types |
| forums/search.php | 663e15c94797b98925b45df77ff1b3967466bbf6 | 28695 | missing_strict_types |
| forums/section_view.php | 65bac16e20ad66a5b3ce544beb0168952302dcbb | 9006 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_strict_types |
| forums/staff_actions.php | f595cf5c7a1ba491b7f3ef0a0495c400414ceb8a | 10910 | contains_mysqli, contains_sqlesc, unclosed_brace, dangling_quote, likely_select, likely_update, likely_insert, missing_bootstrap, missing_strict_types |
| forums/stafflock_post.php | 146a13d35af675f22224e3458a9c052e1d5fab86 | 1107 | dangling_quote, likely_select, likely_update |
| forums/subscriptions.php | d1166103d8df47b223905f5d3bfd6906e123daa6 | 11109 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_strict_types |
| forums/undelete_post.php | 368c090ab1d930f7184552e28361d0a04da1114a | 3660 | dangling_quote, likely_update, missing_bootstrap, missing_strict_types |
| forums/view_forum.php | 5dfe1030717e4eb043baecbd5e72d3434f40feaf | 9201 | unclosed_brace, dangling_quote, likely_insert, missing_bootstrap, missing_strict_types |
| forums/view_my_posts.php | 70d7e7bc0f3f4d74ee85bdcf9347a2b5c35bcd27 | 11446 | contains_sql_query, contains_mysqli, contains_sqlesc, likely_select, missing_bootstrap, missing_strict_types |
| forums/view_post_history.php | fbb054570f39da379e9016535db703721f738b8b | 3040 | dangling_quote, missing_bootstrap, missing_strict_types |
| forums/view_topic.php | b438bf4573aef19997906f0fab95dd9c3b0034eb | 35632 | unclosed_brace, dangling_quote, likely_update, likely_insert, missing_bootstrap, missing_strict_types |
| forums/view_unread_posts.php | 852914e9e67975214c7ae5dcf3ef5dcb3ca30d0b | 12231 | missing_bootstrap, missing_strict_types |

## Suggested Rebuild Order
- forums/add_subscription.php
- forums/attachment.php
- forums/clear_unread_post.php
- forums/delete_post.php
- forums/delete_subscription.php
- forums/download_attachment.php
- forums/edit_post.php
- forums/editor.php
- forums/last_ten.php
- forums/mark_all_as_read.php
- forums/member_post_history.php
- forums/new_replies.php
- forums/new_topic.php
- forums/poll.php
- forums/post_reply.php
- forums/quick_reply.php
- forums/search.php
- forums/section_view.php
- forums/staff_actions.php
- forums/stafflock_post.php
- forums/subscriptions.php
- forums/undelete_post.php
- forums/view_forum.php
- forums/view_my_posts.php
- forums/view_post_history.php
- forums/view_topic.php
- forums/view_unread_posts.php

## PDO/Aura Patterns
- Always bind parameters; never interpolate values.
- Use transactions for dependent writes.
- Build dynamic IN() lists with named placeholders.
- Avoid `SELECT *`; specify columns explicitly.

## Restoring a File for Local Work
```
cp forums/_quarantine/<file>.orig forums/<file>
```
Make edits and test locally before committing a rebuild.

## Safety Note
All current forum endpoints intentionally return HTTP 503 until each file is rebuilt.

## Verification
The following checks were executed after stubbing:
- `rg -n "mysqli_|sql_query\(|sqlesc\(" forums/` → no matches outside `forums/_quarantine/*.orig`
- `php -l forums/*.php` → syntax OK for all stubs
- Endpoints are expected to return HTTP 503 with the maintenance message when accessed in a fully bootstrapped environment

