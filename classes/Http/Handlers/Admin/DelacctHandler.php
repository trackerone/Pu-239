<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Session;
use PU239\Security\AuthZ;

final class DelacctHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion
        try {
            global $container, $CURUSER;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = $escaper((string) $config->get('paths.baseurl'));

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // TODO(2025): csrf
                $userid = (int) trim((string) ($_POST['userid'] ?? ''));
                $username = trim(htmlsafechars((string) ($_POST['username'] ?? '')));
                if ($username === '' || $userid === 0) {
                    stderr(_('Error'), _('Please fill out the form correctly.'));
                }

                $row = $db->fetch(
                    'SELECT id FROM users WHERE username = :username AND id = :id',
                    [
                        ':username' => $username,
                        ':id' => $userid,
                    ]
                );
                $id = $row['id'] ?? null;

                if (!$id) {
                    stderr(_('Error'), _('Invalid UserID/Username Combination'));
                }

                if (account_delete((int) $id)) {
                    write_log("User: $username Was deleted by {$CURUSER['username']}");
                    /** @var Session $session */
                    $session = $container->get(Session::class);
                    $session->set('is-success', _('The account was deleted.'));
                    audit_log($CURUSER['id'] ?? null, 'user.ban', ['target' => (int) $id, 'reason' => 'delete_account']);
                } else {
                    stderr(_('Error'), _('Unable to delete the account.'));
                }
            }

            $HTMLOUT = "
<script>
    function deleteConfirm(){
        var result = confirm('Are you sure to delete this user?');
        if (result) {
            return true;
        } else {
            return false;
        }
    }
</script>
<div class='row'>
    <div class='col-md-12'>
        <h1 class='has-text-centered'>" . _('Delete account') . "</h1>
            <form method='post' action='{$self}?tool=delacct&amp;action=delacct' onsubmit='return deleteConfirm();' enctype='multipart/form-data' accept-charset='utf-8'>
                <table class='table table-bordered'>
                    <tr>
                        <td class='rowhead'>" . _('User ID') . "</td>
                        <td><input class='w-100' name='userid'></td>
                    </tr>
                    <tr>
                        <td class='rowhead'>" . _('Username') . "</td>
                        <td><input class='w-100' name='username'></td>
                    </tr>
                    <tr>
                        <td colspan='2' class='has-text-centered'><input type='submit' class='button is-small' value='" . _('Delete') . "'></td>
                    </tr>
                </table>
            </form>
        </div>
</div>";
            $title = _('Delete Account');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $escaper($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
