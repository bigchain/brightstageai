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

// Security headers — prevent clickjacking, XSS, MIME sniffing
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

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

    // Generation API
    if (preg_match('#^/api/generate/slides/(\d+)$#', $uri, $m) && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiGenerateController.php';
        (new ApiGenerateController())->generate_slides((int)$m[1]);
    }

    if (preg_match('#^/api/slides/(\d+)/upload-image$#', $uri, $m) && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiGenerateController.php';
        (new ApiGenerateController())->upload_slide_image((int)$m[1]);
    }

    // AI Polish slide content
    if ($uri === '/api/polish-slide' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $speaker_notes = trim($input['speaker_notes'] ?? '');
        $tone = trim($input['tone'] ?? 'professional');
        if (!in_array($tone, ['professional','casual','academic','inspirational','technical','sales'], true)) $tone = 'professional';

        if ($title === '' && $content === '') json_error('Nothing to polish');

        require_once APP_ROOT . '/src/services/SlidePolishService.php';
        $service = new SlidePolishService();
        $result = $service->polish($title, $content, $speaker_notes, $tone);

        if ($result === null) json_error('Failed to polish. Try again.');
        json_success($result);
    }

    // Topic enhancement API — returns title + audience + description
    if ($uri === '/api/enhance-topic' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $topic = trim($input['topic'] ?? '');
        $tone = trim($input['tone'] ?? 'professional');

        if ($topic === '') json_error('Topic is required');

        require_once APP_ROOT . '/src/services/TopicEnhancerService.php';
        $service = new TopicEnhancerService();
        $result = $service->enhance($topic, $tone);

        if ($result === null) json_error('Failed to enhance topic. Try again.');
        json_success($result);
    }

    // Generate outline preview (AJAX — returns slides without saving)
    if ($uri === '/api/generate/outline-preview' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $topic    = mb_substr(trim($input['topic'] ?? ''), 0, 2000);
        $audience = mb_substr(trim($input['audience'] ?? ''), 0, 200);
        $duration = (int)($input['duration'] ?? 10);
        $tone     = trim($input['tone'] ?? 'professional');

        if ($topic === '') json_error('Topic is required');

        // Check credits
        $user = current_user();
        require_once APP_ROOT . '/src/models/UserModel.php';
        $users = new UserModel();
        $balance = $users->get_credits($user['id']);
        $cost = CREDIT_COSTS['generate_outline'];
        if ($balance < $cost) json_error("Not enough credits. Need {$cost}, have {$balance}.");

        require_once APP_ROOT . '/src/services/OutlineService.php';
        $service = new OutlineService();
        $outline = $service->generate($topic, $audience, $duration, $tone);

        if ($outline === null) json_error('Failed to generate outline. Try again.');
        json_success($outline);
    }

    // Save confirmed outline (after preview)
    if ($uri === '/api/presentations/create' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $user = current_user();

        $title       = mb_substr(trim($input['title'] ?? ''), 0, 255);
        $topic       = mb_substr(trim($input['topic'] ?? ''), 0, 2000);
        $audience    = mb_substr(trim($input['audience'] ?? ''), 0, 200);
        $duration    = (int)($input['duration'] ?? 10);
        $tone        = trim($input['tone'] ?? 'professional');
        if (!in_array($tone, ['professional','casual','academic','inspirational','technical','sales'], true)) $tone = 'professional';
        $template_id = trim($input['template_id'] ?? '1');
        if (!in_array($template_id, ['1','2','3','4','5','6','7'], true)) $template_id = '1';
        $slides      = $input['slides'] ?? [];

        if ($title === '' || $topic === '' || empty($slides)) {
            json_error('Title, topic, and slides are required');
        }

        // Deduct credits
        require_once APP_ROOT . '/src/models/UserModel.php';
        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/SlideModel.php';

        $users = new UserModel();
        $cost = CREDIT_COSTS['generate_outline'];
        if (!$users->deduct_credits($user['id'], $cost, 'generate_outline')) {
            json_error('Not enough credits');
        }

        // Create presentation
        $presentations = new PresentationModel();
        $pres_id = $presentations->create($user['id'], $title, $topic, $audience, $duration, $tone);
        $presentations->update($pres_id, $user['id'], ['template_id' => $template_id, 'status' => 'outline_ready']);

        // Create slides
        $slideModel = new SlideModel();
        $slideModel->create_batch($pres_id, $slides);

        json_success(['presentation_id' => $pres_id, 'redirect' => "/presentation/{$pres_id}"]);
    }

    // Presentation management API
    if (preg_match('#^/api/presentations/(\d+)$#', $uri, $m) && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['_action'] ?? 'update';
        $pres_id = (int)$m[1];
        $user = current_user();

        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/SlideModel.php';
        $presentations = new PresentationModel();
        $slides = new SlideModel();

        $pres = $presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) json_error('Not found', 404);

        if ($action === 'delete') {
            $slides->delete_by_presentation($pres_id);
            $presentations->delete($pres_id, $user['id']);
            json_success(['message' => 'Deleted', 'redirect' => '/dashboard']);
        }

        if ($action === 'duplicate') {
            $new_id = $presentations->create(
                $user['id'],
                $pres['title'] . ' (Copy)',
                $pres['topic'],
                $pres['audience'],
                $pres['duration_minutes'],
                $pres['tone']
            );
            $presentations->update($new_id, $user['id'], [
                'template_id' => $pres['template_id'],
                'status'      => $pres['status'],
            ]);
            // Duplicate slides
            $original_slides = $slides->list_by_presentation($pres_id);
            foreach ($original_slides as $s) {
                $slides->create(
                    $new_id, $s['slide_order'], $s['title'],
                    $s['content'], $s['speaker_notes'], $s['layout_type']
                );
            }
            json_success(['message' => 'Duplicated', 'presentation_id' => $new_id, 'redirect' => "/presentation/{$new_id}"]);
        }

        // Default: update
        $data = [];
        if (isset($input['title']))    $data['title'] = mb_substr(trim($input['title']), 0, 255);
        if (isset($input['topic']))    $data['topic'] = mb_substr(trim($input['topic']), 0, 2000);
        if (isset($input['audience'])) $data['audience'] = mb_substr(trim($input['audience']), 0, 200);
        if (isset($input['tone']))     $data['tone'] = trim($input['tone']);
        if (isset($input['template_id'])) $data['template_id'] = trim($input['template_id']);

        if (!empty($data)) {
            $presentations->update($pres_id, $user['id'], $data);
        }
        json_success(['message' => 'Updated']);
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

// View/edit presentation
if (preg_match('#^/presentation/(\d+)$#', $uri, $m) && $method === 'GET') {
    require_once APP_ROOT . '/src/controllers/PresentationController.php';
    (new PresentationController())->show((int)$m[1]);
    exit;
}

// ──────────────────────────────────────────────
// 404
// ──────────────────────────────────────────────
http_response_code(404);
$template = 'pages/404.php';
render($template, ['page_title' => 'Page Not Found']);
