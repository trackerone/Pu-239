<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use PU239\Config\ConfigRepository;
use Pu239\Database;

$user = check_user_status();
global $container, $top_links, $mailbox, $HTMLOUT;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
/** @var Database $db */
$db = $container->get(Database::class);

// TODO(2025): csrf

$keywords = isset($_POST['keywords']) ? trim((string) $_POST['keywords']) : '';
$subjectFilter = isset($_POST['subject']) ? trim((string) $_POST['subject']) : '';
$textFilter = isset($_POST['text']) ? trim((string) $_POST['text']) : '';
$member = isset($_POST['member']) ? trim((string) $_POST['member']) : '';
$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 25;
$limit = $limit > 0 ? min($limit, 100) : 25;
$sort = isset($_POST['sort']) ? (string) $_POST['sort'] : 'relevance';
$direction = isset($_POST['direction']) ? strtoupper((string) $_POST['direction']) : 'DESC';
$direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'DESC';
$searchAllBoxes = !empty($_POST['all_boxes']);
$include_system = !empty($_POST['system']);
$selected_box = isset($_POST['box']) ? (int) $_POST['box'] : (isset($mailbox) ? (int) $mailbox : 1);

$member_notice = '';
$results = [];
$search_performed = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($search_performed) {
    $params = ['user_id' => (int) $user['id']];
    $conditions = [];

    if ($searchAllBoxes) {
        $conditions[] = '(m.receiver = :user_id OR m.sender = :user_id)';
    } else {
        $sentBox = $config->get('pm.sent') ?? -1;
        $inboxLocation = $config->get('pm.inbox') ?? 1;
        if ($selected_box === $sentBox) {
            $conditions[] = 'm.sender = :user_id';
            $conditions[] = 'm.location = :sent_location';
            $params['sent_location'] = (int) $inboxLocation;
        } else {
            $conditions[] = 'm.receiver = :user_id';
            $conditions[] = 'm.location = :location';
            $params['location'] = $selected_box;
        }
    }

    if ($include_system) {
        $conditions[] = 'm.sender = 0';
    }

    if ($member !== '') {
        $member_row = $db->fetch(
            'SELECT id FROM users WHERE username = :username',
            [
                'username' => $member,
            ],
        );
        if ($member_row === null) {
            $member_notice = sprintf(_('No member found matching "%s".'), htmlsafechars($member));
        } else {
            $params['member_id'] = (int) $member_row['id'];
            if ($searchAllBoxes) {
                $conditions[] = '(m.sender = :member_id OR m.receiver = :member_id)';
            } else {
                $sentBox = $config->get('pm.sent') ?? -1;
                if ($selected_box === $sentBox) {
                    $conditions[] = 'm.receiver = :member_id';
                } else {
                    $conditions[] = 'm.sender = :member_id';
                }
            }
        }
    }

    if ($keywords !== '') {
        $params['keywords'] = '%' . $keywords . '%';
        $conditions[] = '(m.subject LIKE :keywords OR m.msg LIKE :keywords)';
    }

    if ($subjectFilter !== '') {
        $params['subject_search'] = '%' . $subjectFilter . '%';
        $conditions[] = 'm.subject LIKE :subject_search';
    }

    if ($textFilter !== '') {
        $params['body_search'] = '%' . $textFilter . '%';
        $conditions[] = 'm.msg LIKE :body_search';
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sortColumns = [
        'relevance' => 'm.added',
        'added' => 'm.added',
        'subject' => 'm.subject',
        'sender' => 'sender_name',
        'receiver' => 'receiver_name',
    ];
    $orderColumn = $sortColumns[$sort] ?? $sortColumns['relevance'];
    if ($sort === 'relevance') {
        $direction = 'DESC';
    }

    $sql = <<<SQL
        SELECT m.id, m.subject, m.msg, m.added, m.unread, m.urgent, m.location, m.sender, m.receiver,
               s.username AS sender_name, r.username AS receiver_name
        FROM messages AS m
        LEFT JOIN users AS s ON s.id = m.sender
        LEFT JOIN users AS r ON r.id = m.receiver
        $where
        ORDER BY $orderColumn $direction
        LIMIT $limit
    SQL;

    $results = $member_notice === '' ? $db->fetchAll($sql, $params) : [];
}

$form_action = $config->get('paths.baseurl') . '/messages.php?action=search';
$keyword_value = htmlsafechars($keywords);
$subject_value = htmlsafechars($subjectFilter);
$text_value = htmlsafechars($textFilter);
$member_value = htmlsafechars($member);
$limit_value = $limit;
$checked_all_boxes = $searchAllBoxes ? 'checked' : '';
$checked_system = $include_system ? 'checked' : '';

$sort_options = [
    'relevance' => _('Most Recent'),
    'added' => _('Date'),
    'subject' => _('Subject'),
    'sender' => _('Sender'),
    'receiver' => _('Receiver'),
];

$direction_options = [
    'DESC' => _('Descending'),
    'ASC' => _('Ascending'),
];

$HTMLOUT .= $top_links . "
    <h1>" . _('Search Messages') . "</h1>
    <form method='post' action='{$form_action}' accept-charset='utf-8'>
        <input type='hidden' name='box' value='{$selected_box}'>
        <div class='table-wrapper'>
            <table class='table table-bordered table-striped'>
                <tr>
                    <td class='w-25'><label for='keywords'>" . _('Keywords') . "</label></td>
                    <td><input type='text' class='w-100' id='keywords' name='keywords' value='{$keyword_value}'></td>
                </tr>
                <tr>
                    <td><label for='subject'>" . _('Subject contains') . "</label></td>
                    <td><input type='text' class='w-100' id='subject' name='subject' value='{$subject_value}'></td>
                </tr>
                <tr>
                    <td><label for='text'>" . _('Message text contains') . "</label></td>
                    <td><input type='text' class='w-100' id='text' name='text' value='{$text_value}'></td>
                </tr>
                <tr>
                    <td><label for='member'>" . _('Member') . "</label></td>
                    <td><input type='text' class='w-100' id='member' name='member' value='{$member_value}'></td>
                </tr>
                <tr>
                    <td>" . _('Options') . "</td>
                    <td>
                        <label class='right10'>
                            <input type='checkbox' name='all_boxes' value='1' {$checked_all_boxes}> " . _('Search all mailboxes') . "
                        </label>
                        <label>
                            <input type='checkbox' name='system' value='1' {$checked_system}> " . _('Only system messages') . "
                        </label>
                    </td>
                </tr>
                <tr>
                    <td><label for='limit'>" . _('Max results') . "</label></td>
                    <td><input type='number' id='limit' name='limit' value='{$limit_value}' min='1' max='100'></td>
                </tr>
                <tr>
                    <td><label for='sort'>" . _('Sort by') . "</label></td>
                    <td>
                        <select id='sort' name='sort'>";
foreach ($sort_options as $value => $label) {
    $selected = $sort === $value ? 'selected' : '';
    $HTMLOUT .= "<option value='{$value}' {$selected}>{$label}</option>";
}
$HTMLOUT .= "</select>
                        <select name='direction'>";
foreach ($direction_options as $value => $label) {
    $selected = $direction === $value ? 'selected' : '';
    $HTMLOUT .= "<option value='{$value}' {$selected}>{$label}</option>";
}
$HTMLOUT .= "</select>
                    </td>
                </tr>
                <tr>
                    <td colspan='2' class='has-text-centered'>
                        <input type='submit' class='button is-small' value='" . _('Search') . "'>
                    </td>
                </tr>
            </table>
        </div>
    </form>";

if ($member_notice !== '') {
    $HTMLOUT .= "<div class='top20 has-text-danger'>{$member_notice}</div>";
}

if ($search_performed) {
    $HTMLOUT .= "<h2 class='top20'>" . _('Results') . "</h2>";
    if (empty($results)) {
        $HTMLOUT .= "<div class='top10'>" . _('No messages matched your search.') . "</div>";
    } else {
        $HTMLOUT .= "<div class='table-wrapper'>
            <table class='table table-bordered table-striped'>
                <thead>
                    <tr>
                        <th>" . _('Subject') . "</th>
                        <th>" . _('From') . "</th>
                        <th>" . _('To') . "</th>
                        <th>" . _('Date') . "</th>
                        <th>" . _('Preview') . "</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($results as $row) {
            $subject_text = $row['subject'] !== '' ? htmlsafechars($row['subject']) : _('No Subject');
            $view_url = $config->get('paths.baseurl') . '/messages.php?action=view_message&id=' . (int) $row['id'];
            $from_display = (int) $row['sender'] === 0 ? _('System') : format_username((int) $row['sender']);
            $to_display = (int) $row['receiver'] === 0 ? _('System') : format_username((int) $row['receiver']);
            $date_display = get_date((int) $row['added'], '');
            $preview = htmlsafechars(mb_substr($row['msg'], 0, 120));
            if (mb_strlen($row['msg']) > 120) {
                $preview .= '&hellip;';
            }
            $tags = [];
            if ($row['unread'] === 'yes') {
                $tags[] = "<span class='tag is-info'>" . _('Unread') . "</span>";
            }
            if ($row['urgent'] === 'yes') {
                $tags[] = "<span class='tag is-danger'>" . _('Urgent') . "</span>";
            }
            $tag_display = empty($tags) ? '' : '<div class="tags">' . implode('', $tags) . '</div>';

            $HTMLOUT .= "<tr>
                    <td><a class='is-link' href='{$view_url}'>{$subject_text}</a>{$tag_display}</td>
                    <td>{$from_display}</td>
                    <td>{$to_display}</td>
                    <td>{$date_display}</td>
                    <td>{$preview}</td>
                </tr>";
        }
        $HTMLOUT .= "</tbody>
            </table>
        </div>";
    }
}
