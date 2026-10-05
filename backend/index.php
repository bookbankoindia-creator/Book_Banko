<?php
/**
 * Main Backend Router / Entrypoint
 * Book Banko
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/helpers.php';

if (isLoggedIn()) {
    header('Location: ' . url('modules/dashboard/index.php'));
} else {
    header('Location: ' . url('auth/login.php'));
}
exit;
