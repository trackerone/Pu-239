<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5

namespace PU239\Http\Handlers\Public;

use Delight\Auth\Auth;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use PU239\Support\Audit;
use Pu239\Config\ConfigRepository;
use Pu239\Session;

final class VerifyEmailHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-06 via handler-convert batch=50-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Session $session */
            $session = $container->get(Session::class);
            /** @var Auth $auth */
            $auth = $container->get(Auth::class);
            if ($auth->isLoggedIn()) {
                $auth->logOutEverywhere();
                $auth->destroySession();
            }
            $selector = $_GET['selector'] ?? '';
            $token = $_GET['token'] ?? '';
            if ($selector === '' || $token === '') {
                stderr(_('Error'), _('Invalid verification link'));
            }
            $emails = [];
            try {
                $emails = $auth->confirmEmail($selector, $token);
            } catch (InvalidSelectorTokenPairException $e) {
                stderr(_('Error'), _('Invalid token'));
            } catch (TokenExpiredException $e) {
                stderr(_('Error'), _('Token expired'));
            } catch (UserAlreadyExistsException $e) {
                stderr(_('Error'), _('Email address already exists'));
            } catch (TooManyRequestsException $e) {
                stderr(_('Error'), _('Too many requests from your IP'));
            }
            if (empty($emails[0])) {
                $session->set('is-success', _('Your email has been confirmed'));
            } else {
                $session->set('is-success', _fe('Your email has been changed to {0}', $emails[1] ?? ''));
            }
            $userid = $auth->getUserId();
            Audit::log($userid, 'config.update', ['target' => $userid, 'keys' => ['email']]);
            $baseUrl = (string) $config->get('paths.baseurl');
            header(sprintf('Location: %s/usercp.php?action=security', $baseUrl));
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
