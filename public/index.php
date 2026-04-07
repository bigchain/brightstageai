<?php
/**
 * BrightStage Video — Front Controller / Router
 * All requests route through here via .htaccess
 */

// Bootstrap
require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/middleware/auth.php';

// Start session
init_session();

// Parse request
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ──────────────────────────────────────────────
// API Routes (return JSON)
// ──────────────────────────────────────────────

if (str_starts_with($uri, '/api/')) {
    header('Content-Type: application/json');

    // Slide API
    if ($uri === '/api/slides/reorder' && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiSlideController.php';
        (new ApiSlideController())->reorder();
    }

    if ($uri === '/api/slides/add' && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiSlideController.php';
        (new ApiSlideController())->add();
    }

    if (preg_match('#^/api/slides/(\d+)/update$#', $uri, $m) && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiSlideController.php';
        (new ApiSlideController())->update((int)$m[1]);
    }

    if (preg_match('#^/api/slides/(\d+)/delete$#', $uri, $m) && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiSlideController.php';
        (new ApiSlideController())->delete((int)$m[1]);
    }

    // Auth API
    if ($uri === '/api/auth/me' && $method === 'GET') {
        if (!is_logged_in()) {
            json_error('Not authenticated', 401);
        }
        refresh_user_session();
        json_success(current_user());
    }

    // Catch-all for unknown API routes
    json_error('Not found', 404);
}

// ──────────────────────────────────────────────
// Page Routes (return HTML)
// ──────────────────────────────────────────────

// Public pages
if ($uri === '/' && $method === 'GET') {
    $template = 'pages/home.php';
    render($template, ['page_title' => 'AI Video Presentations']);
    exit;
}

// Auth
if ($uri === '/register' && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/AuthController.php';
    (new AuthController())->show_register();
    exit;
}

if ($uri === '/register' && $method === 'POST') {
    require_once APP_ROOT . '/src/controllers/AuthController.php';
    (new AuthController())->register();
    exit;
}

if ($uri === '/login' && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/AuthController.php';
    (new AuthController())->show_login();
    exit;
}

if ($uri === '/login' && $method === 'POST') {
    require_once APP_ROOT . '/src/controllers/AuthController.php';
    (new AuthController())->login();
    exit;
}

if ($uri === '/logout') {
    require_once APP_ROOT . '/src/controllers/AuthController.php';
    (new AuthController())->logout();
    exit;
}

// Dashboard
if ($uri === '/dashboard' && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/DashboardController.php';
    (new DashboardController())->index();
    exit;
}

// Create new presentation
if ($uri === '/create' && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/PresentationController.php';
    (new PresentationController())->create();
    exit;
}

if ($uri === '/create' && $method === 'POST') {
    require_once APP_ROOT . '/src/controllers/PresentationController.php';
    (new PresentationController())->store();
    exit;
}

// View/edit presentation
if (preg_match('#^/presentation/(\d+)$#', $uri, $m) && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/PresentationController.php';
    (new PresentationController())->show((int)$m[1]);
    exit;
}

// Delete presentation
if (preg_match('#^/presentation/(\d+)/delete$#', $uri, $m) && $method === 'POST') {
    require_once APP_ROOT . '/src/controllers/PresentationController.php';
    (new PresentationController())->destroy((int)$m[1]);
    exit;
}

// ──────────────────────────────────────────────
// 404
// ──────────────────────────────────────────────
http_response_code(404);
$template = 'pages/404.php';
render($template, ['page_title' => 'Page Not Found']);
