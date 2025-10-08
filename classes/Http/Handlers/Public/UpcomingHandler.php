<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=90-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Session;
use Pu239\Upcoming;
use Rakit\Validation\Validator;

final class UpcomingHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=90-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Upcoming $cooker */
            $cooker = $container->get(Upcoming::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $user = check_user_status();

            $stdfoot = [
                'js' => [
                    get_file_name('imdb_js'),
                    get_file_name('dragndrop_js'),
                ],
            ];
            $hasAccess = has_access($user['class'], UC_USER, 'internal') || has_access($user['class'], UC_STAFF, '');
            $session->set('post_data', $_POST);

            $request = $_GET;
            $self = $_SERVER['PHP_SELF'] ?? '';
            $postData = $session->get('post_data');
            $viewAll = $add = $edit = $delete = false;
            $id = 0;
            if (isset($request['action'])) {
                switch ($request['action']) {
                    case 'view_all':
                        $viewAll = true;
                        break;
                    case 'add_recipe':
                        $add = true;
                        break;
                    case 'edit_recipe':
                        $edit = true;
                        $id = isset($request['id']) ? (int) $request['id'] : 0;
                        $postData = $cooker->get($id);
                        break;
                    case 'delete_recipe':
                        $delete = true;
                        $id = isset($request['id']) ? (int) $request['id'] : 0;
                        break;
                }
            }

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                // TODO(2025): add CSRF verification for upcoming handler form
                /** @var Validator $validator */
                $validator = $container->get(Validator::class);
                $validation = $validator->validate($_POST, [
                    'type' => 'required|numeric',
                    'name' => 'required|regex:/[A-Za-z0-9\:_\-\s]/',
                    'poster' => 'required|url:http,https',
                    'status' => 'required|in:sourcing,ftping,encoding,remuxing,uploaded',
                    'url' => 'required|url:http,https',
                    'expected' => 'required|date:Y-m-d\TH:i',
                    'id' => 'numeric',
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
                    'status' => htmlsafechars($_POST['status'] ?? ''),
                    'url' => htmlsafechars($_POST['url'] ?? ''),
                    'expected' => date('Y-m-d H:i:s', strtotime((string) ($_POST['expected'] ?? 'now'))),
                    'userid' => $user['id'],
                    'show_index' => 1,
                ];
                if ($add) {
                    if ($cooker->insert($values)) {
                        $session->unset('post_data');
                        $session->set('is-success', _fe('Recipe: {0} Added', format_comment($_POST['name'] ?? '')));
                        header('Location: ' . $self);
                        app_halt('Exit called');
                    }
                } elseif ($edit) {
                    if ($cooker->update($values, (int) ($_POST['id'] ?? 0))) {
                        $session->set('is-success', _fe('Recipe: {0} Updated', format_comment($_POST['name'] ?? '')));
                        header('Location: ' . $self);
                        app_halt('Exit called');
                    }
                }
            }

            $HTMLOUT = $addNew = $update = '';
            $today = date('Y-m-d\TH:i', TIME_NOW);
            $futureDate = empty($postData['expected'])
                ? date('Y-m-d\TH:i', strtotime('+7 day'))
                : date('Y-m-d\TH:i', strtotime((string) $postData['expected']));

            $form = "
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Category') . "</div>
                    <div class='column'>
                        " . category_dropdown($config->get('categories.movie')) . "
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Upcoming') . "</div>
                    <div class='column'>
                        <input type='text' class='w-100' name='name' autocomplete='on' value='" . (!empty($postData['name']) ? htmlsafechars($postData['name']) : '') . "' required>
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Poster') . "</div>
                    <div class='column'>
                        <input type='url' id='image_url' placeholder='" . _('External Image URL') . "' class='w-100' onchange=\"return grab_url(event)\" value='" . (!empty($postData['poster']) ? htmlsafechars($postData['poster']) : '') . "'>
                        <input type='url' id='poster' maxlength='255' name='poster' class='w-100 is-hidden' value='" . (!empty($postData['poster']) ? htmlsafechars($postData['poster']) : '') . "'>
                        <div class='poster_container has-text-centered'></div>
                        <div id='droppable' class='droppable bg-03 top20'>
                            <span id='comment'>" . _('Drop images or click here to select images.') . "</span>
                            <div id='loader' class='is-hidden'>
                                <img src='{$config->get('paths.images_baseurl')}/forums/updating.svg' alt='Loading...'>
                            </div>
                        </div>
                        <div class='output-wrapper output'></div>
                    </div>
                </div>
                <div class='columns is-marginless is-paddingless'>
                    <div class='column is-one-quarter has-text-left'>" . _('Status') . "</div>
                    <div class='column'>
                        <select name='status' class='w-100' required>
                            <option value='' disabled selected>" . _('Select Status') . "</option>
                            <option value='sourcing' " . (!empty($postData['status']) && $postData['status'] === 'sourcing' ? 'selected' : '') . '>' . _('Sourcing') . "</option>
                            <option value='ftping' " . (!empty($postData['status']) && $postData['status'] === 'ftping' ? 'selected' : '') . '>' . _('FTPing') . "</option>
                            <option value='encoding' " . (!empty($postData['status']) && $postData['status'] === 'encoding' ? 'selected' : '') . '>' . _('Encoding') . "</option>
                            <option value='remuxing' " . (!empty($postData['status']) && $postData['status'] === 'remuxing' ? 'selected' : '') . '>' . _('Remuxing') . "</option>
                            <option value='uploaded' " . (!empty($postData['status']) && $postData['status'] === 'uploaded' ? 'selected' : '') . '>' . _('Uploaded') . "</option>
                        </select>
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
                    <div class='column is-one-quarter has-text-left'>" . _('Expected') . "</div>
                    <div class='column'>
                        <input type='datetime-local' class='w-100' name='expected' value='$futureDate' min='$today' required>
                    </div>
                </div>";

            if ($hasAccess) {
                if ($add) {
                    $addNew = "
            <h2 class='has-text-centered'>Add Recipe</h2>
            <form class='form-inline table-wrapper' method='post' action='{$_SERVER['PHP_SELF']}?action=add_recipe' enctype='multipart/form-data' accept-charset='utf-8'>$form
                <div class='has-text-centered'>
                    <input type='submit' value='" . _('Add') . "' class='button is-small'>
                </div>
            </form>";
                    $addNew = main_div($addNew, 'has-text-centered w-75 min-350', 'padding20');
                } elseif ($edit && is_valid_id($id)) {
                    $update = "
            <h2 class='has-text-centered'>Edit Recipe</h2>
            <form class='form-inline table-wrapper' method='post' action='{$_SERVER['PHP_SELF']}?action=edit_recipe' enctype='multipart/form-data' accept-charset='utf-8'>$form
                <div class='has-text-centered'>
                    <input type='hidden' name='id' value='{$id}'>
                    <input type='submit' value='" . _('Update') . "' class='button is-small'>
                </div>
            </form>";
                    $update = main_div($update, 'has-text-centered w-75 min-350', 'padding20');
                } elseif ($delete && is_valid_id($id)) {
                    if ($cooker->delete($id, $user['class'] >= UC_STAFF, $user['id']) === 1) {
                        $session->set('is-success', _('Recipe Deleted'));
                    } else {
                        $session->set('is-warning', _('Recipe was NOT Deleted'));
                    }
                }
            }

            $count = $cooker->get_count(false, (bool) $user['hidden']);
            $perPage = 25;
            $pager = pager($perPage, $count, $self . '?');
            $menuTop = $count > $perPage ? $pager['pagertop'] : '';
            $menuBottom = $count > $perPage ? $pager['pagerbottom'] : '';

            $recipes = $cooker->get_all($pager['pdo']['limit'], $pager['pdo']['offset'], 'expected', true, $viewAll, false, (bool) $user['hidden']);

            $HTMLOUT .= "
    <ul class='level-center bg-06 padding10'>
        <li><a href='{$self}?action=add_recipe'>" . _('Add Recipe') . '</a></li>' . ($viewAll ? "
        <li><a href='{$self}'>" . _('View Recipes in the Oven') . '</a></li>' : "
        <li><a href='{$self}?action=view_all'>" . _('View All Recipes') . '</a></li>') . "
    </ul>";

            if ($hasAccess && has_access($user['class'], UC_STAFF, '')) {
                $HTMLOUT .= "
    <div class='level-center top20'>
        <a class='button is-small' href='{$self}?action=add_recipe'>" . _('Add Recipe') . "</a>
        <a class='button is-small margin10' href='{$self}?action=view_all'>" . _('View All Recipes') . "</a>
        <a class='button is-small' href='{$config->get('paths.baseurl')}/uploadapp.php'>" . _('Upload Applications') . "</a>
    </div>";
            }

            if (!empty($session->get('is-success'))) {
                $HTMLOUT .= main_div($session->get('is-success'), 'padding20 has-text-centered', 'bg-success');
                $session->unset('is-success');
            }
            if (!empty($session->get('is-warning'))) {
                $HTMLOUT .= main_div($session->get('is-warning'), 'padding20 has-text-centered', 'bg-warning');
                $session->unset('is-warning');
            }

            if (!empty($addNew)) {
                $HTMLOUT .= $addNew;
            }
            if (!empty($update)) {
                $HTMLOUT .= $update;
            }

            if (!empty($recipes)) {
                $tableRows = '';
                foreach ($recipes as $recipe) {
                    $poster = !empty($recipe['poster']) ? "<img src='" . htmlsafechars(url_proxy($recipe['poster'], true)) . "' alt='Poster'>" : '';
                    $expected = get_date(strtotime((string) $recipe['expected']), 'LONG', 1, 0);
                    $actions = '';
                    if ($hasAccess) {
                        $actions = "
                            <div class='buttons'>
                                <a class='button is-small' href='{$self}?action=edit_recipe&amp;id={$recipe['id']}'>" . _('Edit') . "</a>
                                <a class='button is-small is-danger' href='{$self}?action=delete_recipe&amp;id={$recipe['id']}'>" . _('Delete') . "</a>
                            </div>";
                    }

                    $tableRows .= "
                        <tr>
                            <td class='w-10'>{$poster}</td>
                            <td class='w-20'>" . htmlsafechars($recipe['name']) . "</td>
                            <td class='w-10'>" . htmlsafechars($recipe['status']) . "</td>
                            <td class='w-20'><a href='{$recipe['url']}' target='_blank' rel='noopener noreferrer'>" . _('View on IMDb') . "</a></td>
                            <td class='w-20'>{$expected}</td>
                            <td class='w-20'>{$actions}</td>
                        </tr>";
                }
                $HTMLOUT .= $menuTop;
                $HTMLOUT .= main_table($tableRows, "
                    <tr>
                        <th class='w-10'>" . _('Poster') . "</th>
                        <th class='w-20'>" . _('Name') . "</th>
                        <th class='w-10'>" . _('Status') . "</th>
                        <th class='w-20'>" . _('IMDb') . "</th>
                        <th class='w-20'>" . _('Expected') . "</th>
                        <th class='w-20'>" . _('Actions') . "</th>
                    </tr>");
                $HTMLOUT .= $menuBottom;
            } else {
                $HTMLOUT .= main_div(_('There are no upcoming recipes at this time.'), 'padding20 has-text-centered');
            }

            $title = _('Upcoming Releases');
            $breadcrumbs = [
                sprintf("<a href='%s'>%s</a>", $self, $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs, $stdfoot) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
