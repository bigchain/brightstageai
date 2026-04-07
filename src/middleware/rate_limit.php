<?php
/**
 * Simple rate limiting using database.
 * Tracks requests per user per action within a time window.
 */

function check_rate_limit(string $action, int $max_requests = 10, int $window_seconds = 60): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $user_id = current_user()['id'];
    $db = get_db();
    $since = date('Y-m-d H:i:s', time() - $window_seconds);

    $stmt = $db->prepare(
        'SELECT COUNT(*) as cnt FROM credit_transactions
         WHERE user_id = ? AND action = ? AND created_at > ?'
    );
    $stmt->execute([$user_id, $action, $since]);
    $count = (int)$stmt->fetch()['cnt'];

    return $count < $max_requests;
}
