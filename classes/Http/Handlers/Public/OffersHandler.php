<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5

namespace PU239\Http\Handlers\Public;

use Envms\FluentPDO\Literal;
use Pu239\Comment;
use Pu239\Config\ConfigRepository;
use Pu239\Image;
use Pu239\Offer;
use Pu239\Session;
use Pu239\Torrent;
use Rakit\Validation\Validator;

final class OffersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T18:54:37Z via handler-convert offset=215 size=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');
            $movieCategories = $config->arr('categories.movie');
            $siteName = (string) $config->get('site.name');

            $user = check_user_status();

            $stdhead = [];
            $stdfoot = [
                'js' => [
                    has_access($user['class'], UC_STAFF, '') ? get_file_name('offer_js') : '',
                ],
            ];

            /** @var Image $images */
            $images = $container->get(Image::class);
            /** @var Offer $offers */
            $offers = $container->get(Offer::class);
            /** @var Comment $comments */
            $comments = $container->get(Comment::class);
            /** @var Torrent $torrent */
            $torrent = $container->get(Torrent::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            $hasAccess = has_access($user['class'], UC_USER, '');

            $actions = [
                'view_all',
                'add_offer',
                'edit_offer',
                'delete_offer',
                'view_offer',
                'delete_comment',
                'edit',
                'edit_comment',
                'add_comment',
                'post_comment',
            ];

            $dt = TIME_NOW;
            $session->set('post_offer_data', $_POST);
            $data = $_GET;
            $viewAll = $add = $edit = $delete = $view = $editComment = $addComment = $postComment = false;
            $editForm = '';
            $postData = [];
            $id = 0;

            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'delete_comment':
                        $commentId = isset($data['cid']) ? (int) $data['cid'] : 0;
                        $torrentId = isset($data['tid']) ? (int) $data['tid'] : 0;
                        $comment = $comments->get_comment_by_id($commentId);
                        if (!empty($comment) && (has_access($user['class'], UC_STAFF, 'forum_mod') || $user['id'] === $comment['user'])) {
                            if ($comments->delete($commentId)) {
                                $offers->update([
                                    'comments' => new Literal('comments - 1'),
                                ], $torrentId);
                                audit_log(
                                    $user['id'] ?? null,
                                    'torrent.moderate',
                                    [
                                        'id' => $commentId,
                                        'op' => 'offer.comment.delete',
                                    ],
                                );
                                $session->set('is-success', _('Comment Deleted'));
                            } else {
                                $session->set('is-warning', _('Comment Not Deleted'));
                            }
                        } else {
                            $session->set('is-danger', _('You do not have access to delete this comment'));
                        }
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_offer&id=' . $torrentId);
                        app_halt('Exit called');
                    case 'edit':
                        $editComment = true;
                        $commentId = isset($data['cid']) ? (int) $data['cid'] : 0;
                        $comment = $comments->get_comment_by_id($commentId);
                        $offer = $offers->get($comment['offer'], false);
                        $editForm = "
                <h2 class='has-text-centered'>" . _('Editing a comment for') . ': ' . format_comment($offer['name']) . "</h2>
                <form class='form-inline table-wrapper' method='post' action='{$baseUrl}/offers.php?action=edit_comment' accept-charset='utf-8'>
                    <input type='hidden' name='id' value='{$comment['offer']}'>
                    <input type='hidden' name='cid' value='{$comment['id']}'>
                    <div class='columns is-marginless is-paddingless'>
                        <div class='column is-one-quarter has-text-left'>" . _('Comment') . "</div>
                        <div class='column'>" . BBcode($comment['text']) . "</div>
                    </div>
                    <div class='has-text-centered padding20'>
                        <input type='submit' value='" . _('Update') . "' class='button is-small'>
                    </div>
                </form>";
                        break;
                    case 'edit_comment':
                        $editComment = true;
                        break;
                    case 'post_comment':
                        $postComment = true;
                        $id = isset($data['id']) ? (int) $data['id'] : 0;
                        break;
                    case 'add_comment':
                        $addComment = true;
                        $id = isset($data['id']) ? (int) $data['id'] : 0;
                        $offer = $offers->get($id, false);
                        $editForm = "
                <h2 class='has-text-centered'>" . _('Add Comment to') . ': ' . format_comment($offer['name']) . "</h2>
                <form class='form-inline table-wrapper' method='post' action='{$baseUrl}/offers.php?action=post_comment' accept-charset='utf-8'>
                    <input type='hidden' name='id' value='{$id}'>
                    <div class='columns is-marginless is-paddingless'>
                        <div class='column is-one-quarter has-text-left'>" . _('Comment') . "</div>
                        <div class='column'>" . BBcode() . "</div>
                    </div>
                    <div class='has-text-centered padding20'>
                        <input type='submit' value='" . _('Add Comment') . "' class='button is-small'>
                    </div>
                </form>";
                        break;
                    case 'view_offer':
                        $view = true;
                        $id = isset($data['id']) ? (int) $data['id'] : 0;
                        $postData = $offers->get($id, has_access($user['class'], UC_STAFF, ''));
                        break;
                    case 'view_all':
                        $viewAll = true;
                        break;
                    case 'add_offer':
                        $add = true;
                        $postData = $session->get('post_offer_data');
                        break;
                    case 'edit_offer':
                        $edit = true;
                        $id = isset($data['id']) ? (int) $data['id'] : 0;
                        $postData = $offers->get($id, false);
                        break;
                    case 'delete_offer':
                        $delete = true;
                        $id = isset($data['id']) ? (int) $data['id'] : 0;
                        break;
                }
            }

            if ($add || $edit || $editComment || $addComment) {
                $stdhead = [
                    'css' => [
                        get_file_name('sceditor_css'),
                    ],
                ];
                $stdfoot = [
                    'js' => [
                        get_file_name('imdb_js'),
                        get_file_name('dragndrop_js'),
                        get_file_name('sceditor_js'),
                    ],
                ];
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                /** @var Validator $validator */
                $validator = $container->get(Validator::class);
                if ($postComment) {
                    $validation = $validator->validate($_POST, [
                        'id' => 'required|numeric',
                        'body' => '',
                    ]);
                    if ($validation->fails()) {
                        $errors = $validation->errors();
                        stderr(_('Error'), $errors->firstOfAll()['name']);
                        app_halt('Exit called');
                    }
                    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                    $values = [
                        'text' => htmlsafechars($_POST['body'] ?? ''),
                        'offer' => $id,
                        'user' => $user['id'],
                        'added' => $dt,
                    ];
                    if ($comments->add($values)) {
                        $offers->update([
                            'comments' => new Literal('comments + 1'),
                        ], $id);
                        $session->set('is-success', _('Comment Added'));
                    } else {
                        $session->set('is-warning', _('Comment Not Added'));
                    }
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_offer&id=' . $id);
                    app_halt('Exit called');
                } elseif ($editComment) {
                    $validation = $validator->validate($_POST, [
                        'id' => 'required|numeric',
                        'cid' => 'required|numeric',
                        'body' => '',
                    ]);
                    if ($validation->fails()) {
                        $errors = $validation->errors();
                        stderr(_('Error'), $errors->firstOfAll()['name']);
                        app_halt('Exit called');
                    }
                    $commentId = isset($_POST['cid']) ? (int) $_POST['cid'] : 0;
                    $comment = $comments->get_comment_by_id($commentId);
                    $values = [
                        'text' => htmlsafechars($_POST['body'] ?? ''),
                    ];
                    if (!empty($comment) && (has_access($user['class'], UC_STAFF, 'forum_mod') || $user['id'] === $comment['user'])) {
                        if ($comments->update($values, $commentId)) {
                            $session->set('is-success', _('Comment Updated'));
                        } else {
                            $session->set('is-warning', _('Comment Not Updated'));
                        }
                    } else {
                        $session->set('is-danger', _('You do not have access to update this comment'));
                    }
                    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?action=view_offer&id=' . $id);
                    app_halt('Exit called');
                } else {
                    $validation = $validator->validate($_POST, [
                        'type' => 'required|numeric',
                        'name' => 'required|regex:/[A-Za-z0-9\:_\-\s]/',
                        'poster' => 'required|url:http,https',
                        'url' => 'required|url:http,https',
                        'id' => 'numeric',
                        'body' => '',
                    ]);
                    if ($validation->fails()) {
                        $errors = $validation->errors();
                        stderr(_('Error'), $errors->firstOfAll()['name']);
                        app_halt('Exit called');
                    }
                    $values = [
                        'category' => (int) ($_POST['type'] ?? 0),
                        'name' => htmlsafechars($_POST['name'] ?? ''),
                        'poster' => htmlsafechars($_POST['poster'] ?? ''),
                        'url' => htmlsafechars($_POST['url'] ?? ''),
                        'added' => $dt,
                        'userid' => $user['id'],
                        'description' => htmlsafechars($_POST['body'] ?? ''),
                    ];
                    if ($add) {
                        if ($offers->insert($values)) {
                            $session->unset('post_offer_data');
                            $session->set('is-success', _fe('Offer: {0} Added', format_comment($_POST['name'] ?? '')));
                            header('Location: ' . $_SERVER['PHP_SELF']);
                            app_halt('Exit called');
                        }
                    } elseif ($edit) {
                        $values['updated'] = $dt;
                        unset($values['added']);
                        if ($offers->update($values, (int) ($_POST['id'] ?? 0))) {
                            $session->set('is-success', _fe('Offer: {0} Updated', format_comment($_POST['name'] ?? '')));
                            header('Location: ' . $_SERVER['PHP_SELF']);
                            app_halt('Exit called');
                        }
                    }
                }
            }

            $htmlOut = $addNew = $update = '';
            $form = "
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Category') . "</div>
                    <div class='column'>
                        " . category_dropdown($movieCategories) . "
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Offer') . "</div>
                    <div class='column'>
                        <input type='text' class='w-100' name='name' autocomplete='on' value='" . (!empty($postData['name']) ? htmlsafechars($postData['name']) : '') . "' required>
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Poster') . "</div>
                    <div class='column'>
                        <input type='url' id='image_url' placeholder='" . _('External Image URL') . "' class='w-100' onchange=\"return grab_url(event)\" value='" . (!empty($postData['poster']) ? htmlsafechars($postData['poster']) : '') . "'>
                        <input type='url' id='poster' maxlength='255' name='poster' class='w-100 is-hidden' " . (!empty($postData['poster']) ? "value='" . htmlsafechars($postData['poster']) . "'" : '') . ">
                        <div class='poster_container has-text-centered'></div>
                        <div id='droppable' class='droppable bg-03 top20'>
                            <span id='comment'>" . _('Drop images or click here to select images.') . "</span>
                            <div id='loader' class='is-hidden'>
                                <img src='{$imagesBaseUrl}/forums/updating.svg' alt='Loading...'>
                            </div>
                        </div>
                        <div class='output-wrapper output'></div>
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('IMDb Link') . "</div>
                    <div class='column'>
                        <input type='url' class='w-100' id='url' name='url' autocomplete='on' value='" . (!empty($postData['url']) ? htmlsafechars($postData['url']) : '') . "' required>
                        <div id='imdb_outer'></div>
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Description') . "</div>
                    <div class='column'>" . BBcode(!empty($postData['description']) ? htmlsafechars($postData['description']) : '') . '</div>
                </div>';

            if ($hasAccess) {
                if ($add) {
                    $addNew = "
            <h2 class='has-text-centered'>" . _('Add Offer') . "</h2>
            <form class='form-inline table-wrapper' method='post' action='{$_SERVER['PHP_SELF']}?action=add_offer' enctype='multipart/form-data' accept-charset='utf-8'>$form
                <div class='has-text-centered'>
                    <input type='submit' value='Add' class='button is-small'>
                </div>
            </form>";
                    $addNew = main_div($addNew, 'has-text-centered w-75 min-350', 'padding20');
                } elseif ($edit && is_valid_id($id)) {
                    $update = "
            <h2 class='has-text-centered'>" . _('Edit Offer') . "</h2>
            <form class='form-inline table-wrapper' method='post' action='{$_SERVER['PHP_SELF']}?action=edit_offer' enctype='multipart/form-data' accept-charset='utf-8'>$form
                <div class='has-text-centered padding20'>
                    <input type='hidden' name='id' value='{$id}'>
                    <input type='submit' value='" . _('Update') . "' class='button is-small'>
                </div>
            </form>";
                    $update = main_div($update, 'has-text-centered w-75 min-350', 'padding20');
                } elseif ($delete && is_valid_id($id)) {
                    if ($offers->delete($id, $user['class'] >= UC_STAFF, $user['id']) === 1) {
                        audit_log(
                            $user['id'] ?? null,
                            'torrent.moderate',
                            [
                                'id' => $id,
                                'op' => 'offer.delete',
                            ],
                        );
                        $session->set('is-success', _('Offer Deleted'));
                    } else {
                        $session->set('is-warning', _('Offer was NOT Deleted'));
                    }
                }
            }

            $viewOffer = $hasVotes = '';
            if ($view && is_valid_id($id)) {
                preg_match('/(tt[\d]{7,8})/i', $postData['url'] ?? '', $match);
                if (!empty($match[1])) {
                    $imdbId = $match[1];
                    $imdbInfo = get_imdb_info($match[1], true, false, null, $postData['poster'] ?? '');
                    if (isset($imdbInfo[0])) {
                        $imdbInfo = "
                <div class='columns has-text-left bg-03 top20 round10'>
                    <div class='column is-one-quarter'>" . _('IMDb Info') . "</div>
                    <div class='column'>{$imdbInfo[0]}</div>
                </div>";
                    }
                }
                if (isset($postData['vote_yes']) || isset($postData['vote_no'])) {
                    $hasVotes = "
                <div class='columns has-text-left bg-03 top20 round10'>
                    <div class='column is-one-quarter'>" . _('User Votes') . "</div>
                    <div class='column is-1 tooltipper' title='{$postData['vote_yes']} " . _('Users voting for this Offer.') . "'><i class='icon-thumbs-up icon has-text-success is-marginless' aria-hidden='true'></i>{$postData['vote_yes']}</div>
                    <div class='column is-1 tooltipper' title='{$postData['vote_no']} " . _('Users voting against this Offer.') . "'><i class='icon-thumbs-down icon has-text-danger is-marginless' aria-hidden='true'></i>{$postData['vote_no']}</div>
                </div>";
                }
                $viewOffer .= "
                <div class='columns has-text-left bg-03 round10'>
                    <div class='column is-one-quarter'>" . _('Category') . "</div>
                    <div class='column'>{$postData['fullcat']}</div>
                </div>
                <div class='columns bg-03 top20 round10'>
                    <div class='column is-one-quarter has-text-left'>" . _('Description') . "</div>
                    <div class='column'>" . (!empty($postData['description']) ? format_comment($postData['description']) : '') . "</div>
                </div>{$imdbInfo}{$hasVotes}
                <div class='columns bg-03 top20 round10'>
                    <div class='has-text-centered padding20'>
                        <a class='button is-small' href='{$baseUrl}/offers.php?action=add_comment&amp;id={$id}'>Add a comment</a>
                    </div>
                </div>";
                $commentList = $comments->get_comment_by_column('offer', $id);
                $viewOffer .= commenttable($commentList, 'offer');
                $viewOffer = main_div($viewOffer, 'has-text-left', 'padding20');
            }

            $htmlOut .= "
    <ul class='level-center bg-06 padding10'>
        <li><a href='{$_SERVER['PHP_SELF']}?action=add_offer'>" . _('Add Offer') . '</a></li>' . ($viewAll ? "
        <li><a href='{$_SERVER['PHP_SELF']}'>" . _('View Incomplete Offers') . '</a></li>' : "
        <li><a href='{$_SERVER['PHP_SELF']}?action=view_all'>" . _('View All Offers') . '</a></li>') . "
    </ul>
    <h1 class='has-text-centered'>{$siteName}'s " . _('Offers') . '</h1>';

            if ($editForm !== '') {
                $htmlOut .= $editForm;
            } elseif ($addNew !== '') {
                $htmlOut .= $addNew;
            } elseif ($viewOffer !== '') {
                $htmlOut .= $viewOffer;
            } elseif ($update !== '') {
                $htmlOut .= $update;
            } else {
                $count = $offers->get_count(($data['action'] ?? '') === 'view_all', (bool) $user['hidden']);
                $perPage = 25;
                $pager = pager($perPage, (int) $count, $_SERVER['PHP_SELF'] . '?');
                $menuTop = $count > $perPage ? $pager['pagertop'] : '';
                $menuBottom = $count > $perPage ? $pager['pagerbottom'] : '';
                $offerRows = $offers->get_all($pager['pdo']['limit'], $pager['pdo']['offset'], 'added', true, $viewAll, (bool) $user['hidden']);
                $heading = "
                    <tr>
                        <th class='has-text-centered'>" . _('Category') . "</th>
                        <th class='has-text-centered min-250'>" . _('Offer') . "</th>
                        <th class='has-text-centered'>" . _('Offered By') . "</th>
                        <th class='has-text-centered'><i class='icon-commenting-o icon' aria-hidden='true'></i></th>
                        <th class='has-text-centered'>" . _('Status') . "</th>
                        <th class='has-text-centered'><i class='icon-user-plus icon' aria-hidden='true'></i></th>" . ($hasAccess ? "
                        <th class='has-text-centered'><i class='icon-tools icon' aria-hidden='true'></i></th>" : '') . '
                    </tr>';
                $body = '';
                if (!empty($offerRows)) {
                    foreach ($offerRows as $offerRow) {
                        $hasFullAccess = $user['id'] === $offerRow['userid'] || (has_access($user['class'], UC_STAFF, '') && $hasAccess);
                        $catIcon = !empty($offerRow['image']) ? "<img src='{$imagesBaseUrl}caticons/" . get_category_icons() . '/' . format_comment($offerRow['image']) . "' class='tooltipper' alt='" . format_comment($offerRow['cat']) . "' title='" . format_comment($offerRow['cat']) . "' height='20px' width='auto'>" : format_comment($offerRow['cat']);
                        $poster = !empty($offerRow['poster']) ? "<div class='has-text-centered'><img src='" . url_proxy($offerRow['poster'], true, 250) . "' alt='image' class='img-polaroid'></div>" : '';
                        $background = $imdbId = '';
                        preg_match('#(tt\d{7,8})#', $offerRow['url'], $match);
                        if (!empty($match[1])) {
                            $imdbId = $match[1];
                            $background = $images->find_images($imdbId, $type = 'background');
                            $background = !empty($background) ? "style='background-image: url({$background});'" : '';
                            $posterUrl = !empty($offerRow['poster']) ? $offerRow['poster'] : $images->find_images($imdbId, $type = 'poster');
                            $poster = empty($posterUrl) ? "<img src='{$imagesBaseUrl}noposter.png' alt='" . _('Poster') . "' class='tooltip-poster'>" : "<img src='" . url_proxy($posterUrl, true, 250) . "' alt='" . _('Poster') . "' class='tooltip-poster'>";
                        }
                        $chef = format_username($offerRow['userid']);
                        $plot = $torrent->get_plot($imdbId);
                        if (!empty($plot)) {
                            $stripped = strip_tags($plot);
                            $plot = strlen($stripped) > 500 ? substr($plot, 0, 500) . '...' : $stripped;
                            $plot = "
                                                        <div class='column padding5 is-4'>
                                                            <span class='size_4 has-text-primary has-text-weight-bold'>" . _('Plot') . ":</span>
                                                        </div>
                                                        <div class='column padding5 is-8'>
                                                            <span class='size_4'>{$plot}</span>
                                                        </div>";
                        } else {
                            $plot = '';
                        }
                        $hover = upcoming_hover($baseUrl . '/offers.php?action=view_offer&amp;id=' . $offerRow['id'], 'upcoming_' . $offerRow['id'], $offerRow['name'], $background, $poster, get_date($offerRow['added'], 'MYSQL'), get_date($offerRow['added'], 'MYSQL'), $chef, $plot);
                        $body .= "
                    <tr>
                        <td class='has-text-centered'>{$catIcon}</td>
                        <td>$hover</td>
                        <td class='has-text-centered'>{$chef}</td>
                        <td class='has-text-centered'><span class='tooltipper' title='" . _('Comments') . "'>" . number_format($offerRow['comments']) . "</span></td>
                        <td class='has-text-centered'>
                            <div data-id='{$offerRow['id']}' data-status='{$offerRow['status']}' class='offer_status tooltipper' title='" . ($offerRow['status'] === 'pending' ? _('This offer is still pending.') : ($offerRow['status'] === 'approved' ? _('This offer has been approved.') : _('This offer has been denied.'))) . "'>
                                <span id='status_{$offerRow['id']}'>" . ($offerRow['status'] === 'approved' ? "<i class='icon-thumbs-up icon has-text-success is-marginless' aria-hidden='true'></i>" : ($offerRow['status'] === 'denied' ? "<i class='icon-thumbs-down icon has-text-danger is-marginless' aria-hidden='true'></i>" : "<i class='icon-thumbs-down icon is-marginless' aria-hidden='true'></i>")) . "</span>
                            </div>
                        </td>
                        <td class='has-text-centered w-10'>
                            <div class='level-center'>
                                <div data-id='{$offerRow['id']}' data-voted='{$offerRow['voted']}' class='offer_vote tooltipper' title='" . ($offerRow['voted'] === 'yes' ? _('You support this request.') : ($offerRow['voted'] === 'no' ? _('You oppose this request.') : _('You have not voted for or against this request.'))) . "'>
                                    <span id='vote_{$offerRow['id']}'>" . ($offerRow['voted'] === 'yes' ? "<i class='icon-thumbs-up icon has-text-success is-marginless' aria-hidden='true'></i>" : ($offerRow['voted'] === 'no' ? "<i class='icon-thumbs-down icon has-text-danger is-marginless' aria-hidden='true'></i>" : "<i class='icon-thumbs-up icon is-marginless' aria-hidden='true'></i>")) . "</span>
                                </div>
                                <div data-id='{$offerRow['id']}' data-notified='{$offerRow['notify']}' class='offer_notify tooltipper' title='" . ($offerRow['notify'] === 1 ? _('You will be notified when this has been uploaded.') : _('You will NOT be notified when this has been uploaded.')) . "'>
                                    <span id='notify_{$offerRow['id']}'>" . ($offerRow['notify'] === 1 ? "<i class='icon-mail icon has-text-success is-marginless' aria-hidden='true'></i>" : "<i class='icon-envelope-open-o icon has-text-info is-marginless' aria-hidden='true'></i>") . '</span>
                                </div>
                            </div>
                        </td>' . ($hasAccess ? "
                        <td class='has-text-centered'>" . ($hasFullAccess ? "
                            <a href='{$_SERVER['PHP_SELF']}?action=edit_offer&amp;id={$offerRow['id']}' class='tooltipper' title='" . _('Edit Offer') . "'><i class='icon-edit icon has-text-info' aria-hidden='true'></i></a>
                            <a href='{$_SERVER['PHP_SELF']}?action=delete_offer&amp;id={$offerRow['id']}' class='tooltipper' title='" . _('Delete Offer') . "'><i class='icon-trash-empty icon has-text-danger' aria-hidden='true'></i></a>" : '') . '
                        </td>' : '') . '
                    </tr>';
                    }
                } else {
                    $cols = $hasAccess ? 7 : 6;
                    $body = "
                    <tr>
                        <td colspan='{$cols}' class='has-text-centered'>" . _('No Offers') . '</td>
                    </tr>';
                }
                $htmlOut .= $menuTop . main_table($body, $heading) . $menuBottom;
            }

            $title = _('Offers');
            $breadcrumbs = [
                "<a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, $stdhead, 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
