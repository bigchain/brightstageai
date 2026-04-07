<?php
/**
 * Authentication middleware.
 * Starts session and refreshes user data from DB.
 */

function init_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => APP_ENV === 'production',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Refresh user session data from database (credits, plan, etc.)
 */
function refresh_user_session(): void
{
    if (!is_logged_in()) {
        return;
    }

    $user_id = $_SESSION['user']['id'];
    $db = get_db();
    $stmt = $db->prepare('SELECT id, email, name, credits_balance, plan, role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
    } else {
        // User deleted — destroy session
        session_destroy();
    }
}
