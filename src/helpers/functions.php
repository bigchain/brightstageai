<?php
/**
 * Global helper functions.
 */

/**
 * Escape output for HTML display.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL.
 */
function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

/**
 * Return a JSON response.
 */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Return a success JSON response.
 */
function json_success(mixed $data = null, int $status = 200): never
{
    json_response(['success' => true, 'data' => $data], $status);
}

/**
 * Return an error JSON response.
 */
function json_error(string $message, int $status = 400): never
{
    json_response(['success' => false, 'error' => $message], $status);
}

/**
 * Get the current authenticated user or null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is authenticated.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Check if current user is admin.
 */
function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Require authentication or redirect to login.
 */
function require_auth(): void
{
    if (!is_logged_in()) {
        redirect('/login');
    }
}

/**
 * Require admin role or return 403.
 */
function require_admin(): void
{
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

/**
 * Generate a CSRF token and store in session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from POST request.
 */
function verify_csrf(): bool
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Flash a message to session for next request.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash messages.
 */
function get_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Render a PHP template with data.
 */
function render(string $template, array $data = []): void
{
    extract($data);
    $flash = get_flash();
    require TEMPLATE_PATH . '/layouts/base.php';
}

/**
 * Get user storage path, creating directories if needed.
 */
function user_storage_path(int $user_id, int $presentation_id = 0): string
{
    $path = STORAGE_PATH . "/users/{$user_id}";
    if ($presentation_id > 0) {
        $path .= "/presentations/{$presentation_id}";
    }
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}
