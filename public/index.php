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
require_once __DIR__ . '/../src/middleware/rate_limit.php';

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

    // Voice preview — generates a short sample (free, no credits)
    if ($uri === '/api/preview-voice' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $voice = trim($input['voice'] ?? 'alloy');
        $valid = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'nova', 'onyx', 'sage', 'shimmer'];
        if (!in_array($voice, $valid, true)) $voice = 'alloy';

        require_once APP_ROOT . '/src/services/TTSService.php';
        $tts = new TTSService();
        $audio = $tts->generate('Hello, this is a preview of the ' . $voice . ' voice. How does this sound for your presentation?', $voice);

        if ($audio === null) json_error('Voice preview failed. TTS service may be unavailable.');

        // Return as base64 data URL (detect WAV vs MP3)
        $b64 = base64_encode($audio);
        $mime = (substr($audio, 0, 4) === 'RIFF') ? 'audio/wav' : 'audio/mpeg';
        json_success(['audio_data' => "data:{$mime};base64,{$b64}"]);
    }

    // AI Image Generation
    if ($uri === '/api/generate/image' && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);
        if (!check_rate_limit('generate_image', 20, 60)) json_error('Too many requests. Please wait a moment.', 429);

        $input = json_decode(file_get_contents('php://input'), true);
        $prompt = mb_substr(trim($input['prompt'] ?? ''), 0, 1000);
        $slide_id = (int)($input['slide_id'] ?? 0);

        if ($prompt === '') json_error('Image description is required');

        $user = current_user();

        // Check credits
        require_once APP_ROOT . '/src/models/UserModel.php';
        $users = new UserModel();
        $cost = CREDIT_COSTS['generate_image'];
        if (!$users->deduct_credits($user['id'], $cost, 'generate_image')) {
            json_error("Not enough credits. Need {$cost}.");
        }

        require_once APP_ROOT . '/src/services/ImageGenerationService.php';
        $service = new ImageGenerationService();
        $data_url = $service->generate($prompt);

        if ($data_url === null) {
            json_error('Image generation failed. Try again.');
        }

        json_success(['image_url' => $data_url, 'credits_used' => $cost]);
    }

    // Generation API
    if (preg_match('#^/api/generate/slides/(\d+)$#', $uri, $m) && $method === 'POST') {
        if (is_logged_in() && !check_rate_limit('generate_slide', 10, 60)) json_error('Too many requests. Please wait a moment.', 429);
        require_once APP_ROOT . '/src/controllers/ApiGenerateController.php';
        (new ApiGenerateController())->generate_slides((int)$m[1]);
    }

    // Regenerate single slide design (with optional AI prompt)
    if (preg_match('#^/api/slides/(\d+)/regenerate-design$#', $uri, $m) && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);
        if (!check_rate_limit('regenerate_slide', 20, 60)) json_error('Too many requests. Please wait a moment.', 429);

        $slide_id = (int)$m[1];
        $user = current_user();
        $input = json_decode(file_get_contents('php://input'), true);

        require_once APP_ROOT . '/src/models/SlideModel.php';
        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/UserModel.php';
        require_once APP_ROOT . '/src/services/SlideGenerationService.php';

        $slideModel = new SlideModel();
        $presentations = new PresentationModel();
        $users = new UserModel();

        $slide = $slideModel->find_by_id($slide_id);
        if (!$slide) json_error('Slide not found', 404);

        $pres = $presentations->find_by_id($slide['presentation_id'], $user['id']);
        if (!$pres) json_error('Not authorized', 403);

        // Check credits (1 slide = generate_slide cost)
        $cost = CREDIT_COSTS['generate_slide'];
        if (!$users->deduct_credits($user['id'], $cost, 'regenerate_slide', $pres['id'])) {
            json_error("Not enough credits. Need {$cost}.");
        }

        // Get template config
        $template_config = ['primary'=>'#1e3a5f','secondary'=>'#ffffff','accent'=>'#3498db','font_heading'=>'Inter','font_body'=>'Inter','style'=>'clean'];
        if ($pres['template_id']) {
            $t = get_db()->prepare('SELECT config_json FROM templates WHERE id = ?');
            $t->execute([$pres['template_id']]);
            $row = $t->fetch();
            if ($row) $template_config = json_decode($row['config_json'], true) ?: $template_config;
        }

        $total_slides = $slideModel->count_by_presentation($pres['id']);
        $service = new SlideGenerationService();

        // If user provided a custom prompt, modify the slide data
        $slide_data = $slide;
        $custom_prompt = trim($input['prompt'] ?? '');
        if ($custom_prompt !== '') {
            // Append user's design instruction to the slide content for AI context
            $slide_data['_design_instruction'] = $custom_prompt;
        }

        $html = $service->generate_slide_html($slide_data, $template_config, $slide['slide_order'], $total_slides);

        if ($html === null) {
            json_error('Failed to regenerate design. Try again.');
        }

        // Save new HTML, clear old image (needs re-render)
        $slideModel->update($slide_id, ['html_content' => $html, 'image_url' => '']);

        json_success([
            'html'         => $html,
            'credits_used' => $cost,
        ]);
    }

    if (preg_match('#^/api/slides/(\d+)/upload-image$#', $uri, $m) && $method === 'POST') {
        require_once APP_ROOT . '/src/controllers/ApiGenerateController.php';
        (new ApiGenerateController())->upload_slide_image((int)$m[1]);
    }

    // Generate TTS audio for all slides
    if (preg_match('#^/api/generate/audio/(\d+)$#', $uri, $m) && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);
        if (!check_rate_limit('generate_audio', 5, 60)) json_error('Too many requests. Please wait a moment.', 429);

        $pres_id = (int)$m[1];
        $user = current_user();

        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/SlideModel.php';
        require_once APP_ROOT . '/src/models/UserModel.php';
        require_once APP_ROOT . '/src/services/TTSService.php';

        $presentations = new PresentationModel();
        $slideModel = new SlideModel();
        $users = new UserModel();

        $pres = $presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) json_error('Not found', 404);

        $slides = $slideModel->list_by_presentation($pres_id);
        if (empty($slides)) json_error('No slides');

        $tts = new TTSService();
        if (!$tts->is_available()) {
            json_error('TTS not configured. Add OPENAI_API_KEY or ELEVENLABS_API_KEY to .env');
        }

        // Check credits
        $cost = count($slides) * CREDIT_COSTS['generate_audio'];
        $balance = $users->get_credits($user['id']);
        if ($balance < $cost) json_error("Not enough credits. Need {$cost}, have {$balance}.");

        $input = json_decode(file_get_contents('php://input'), true);
        $voice = $input['voice'] ?? 'alloy';
        $allowed_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
        if (!in_array($voice, $allowed_voices, true)) $voice = 'alloy';

        // Generate audio
        $results = $tts->generate_for_slides($slides, $user['id'], $pres_id, $voice);

        // Update slide records and count successes
        $success_count = 0;
        foreach ($results as $r) {
            if ($r['success']) {
                $slideModel->update($r['slide_id'], ['audio_url' => $r['audio_url']]);
                $success_count++;
            }
        }

        if ($success_count === 0) json_error('Failed to generate any audio. Check TTS configuration.');

        // Deduct credits
        $actual_cost = $success_count * CREDIT_COSTS['generate_audio'];
        $users->deduct_credits($user['id'], $actual_cost, 'generate_audio', $pres_id);

        $presentations->update_status($pres_id, 'audio_ready');

        json_success([
            'success_count' => $success_count,
            'total'         => count($slides),
            'credits_used'  => $actual_cost,
        ]);
    }

    // Queue video generation
    if (preg_match('#^/api/generate/video/(\d+)$#', $uri, $m) && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);
        if (!check_rate_limit('assemble_video', 3, 60)) json_error('Too many requests. Please wait a moment.', 429);

        $pres_id = (int)$m[1];
        $user = current_user();

        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/UserModel.php';

        $presentations = new PresentationModel();
        $users = new UserModel();

        $pres = $presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) json_error('Not found', 404);

        // Check credits
        $cost = CREDIT_COSTS['assemble_video'];
        $balance = $users->get_credits($user['id']);
        if ($balance < $cost) json_error("Not enough credits. Need {$cost}, have {$balance}.");

        // Deduct credits
        if (!$users->deduct_credits($user['id'], $cost, 'assemble_video', $pres_id)) {
            json_error('Credit deduction failed');
        }

        // Create video job
        $db = get_db();
        $stmt = $db->prepare(
            'INSERT INTO videos (presentation_id, status, progress_message, created_at, updated_at)
             VALUES (?, "queued", "Queued for processing...", NOW(), NOW())'
        );
        $stmt->execute([$pres_id]);
        $video_id = (int)$db->lastInsertId();

        json_success([
            'video_id'     => $video_id,
            'credits_used' => $cost,
            'message'      => 'Video queued! Processing will start shortly.',
        ]);
    }

    // Poll video status
    if (preg_match('#^/api/videos/(\d+)/status$#', $uri, $m) && $method === 'GET') {
        if (!is_logged_in()) json_error('Unauthorized', 401);

        $video_id = (int)$m[1];
        $db = get_db();

        $stmt = $db->prepare(
            'SELECT v.*, p.user_id FROM videos v
             JOIN presentations p ON p.id = v.presentation_id
             WHERE v.id = ? AND p.user_id = ?'
        );
        $stmt->execute([$video_id, current_user()['id']]);
        $video = $stmt->fetch();

        if (!$video) json_error('Not found', 404);

        json_success([
            'status'           => $video['status'],
            'progress_message' => $video['progress_message'],
            'file_url'         => $video['file_url'],
            'duration_seconds' => $video['duration_seconds'],
            'file_size_bytes'  => $video['file_size_bytes'],
        ]);
    }

    // Upload custom slide image (file upload, not base64)
    if (preg_match('#^/api/presentations/(\d+)/upload-slides$#', $uri, $m) && $method === 'POST') {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        // CSRF via header (can't use JSON body for file uploads)
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);

        $pres_id = (int)$m[1];
        $user = current_user();

        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/SlideModel.php';
        $presentations = new PresentationModel();
        $slideModel = new SlideModel();

        $pres = $presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) json_error('Not found', 404);

        if (empty($_FILES['slides'])) json_error('No files uploaded');

        $files = $_FILES['slides'];
        $uploaded = 0;
        $slides = $slideModel->list_by_presentation($pres_id);

        // Process each uploaded file
        $file_count = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < $file_count; $i++) {
            $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

            if ($error !== UPLOAD_ERR_OK) continue;
            if ($size > 10 * 1024 * 1024) continue; // 10MB max per file

            // Validate image type
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) continue;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) continue;

            // Save to storage
            $storage_path = user_storage_path($user['id'], $pres_id);
            $slides_dir = $storage_path . '/slides';
            if (!is_dir($slides_dir)) mkdir($slides_dir, 0755, true);

            $slide_order = $i + 1;
            $filename = "slide_{$slide_order}.{$ext}";
            move_uploaded_file($tmp, $slides_dir . '/' . $filename);

            $relative_url = "/storage/users/{$user['id']}/presentations/{$pres_id}/slides/{$filename}";

            // Update existing slide or create new one
            if (isset($slides[$i])) {
                $slideModel->update($slides[$i]['id'], ['image_url' => $relative_url]);
            } else {
                $sid = $slideModel->create($pres_id, $slide_order, "Slide {$slide_order}", '', '', 'bullets');
                $slideModel->update($sid, ['image_url' => $relative_url]);
            }
            $uploaded++;
        }

        if ($uploaded > 0) {
            $presentations->update_status($pres_id, 'slides_ready');
        }

        json_success(['uploaded' => $uploaded, 'message' => "{$uploaded} slides uploaded"]);
    }

    // Download slides as PDF
    if (preg_match('#^/api/presentations/(\d+)/download-pdf$#', $uri, $m) && $method === 'GET') {
        if (!is_logged_in()) json_error('Unauthorized', 401);

        $pres_id = (int)$m[1];
        $user = current_user();

        require_once APP_ROOT . '/src/models/PresentationModel.php';
        require_once APP_ROOT . '/src/models/SlideModel.php';
        $presentations = new PresentationModel();
        $slideModel = new SlideModel();

        $pres = $presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) json_error('Not found', 404);

        $slides = $slideModel->list_by_presentation($pres_id);
        $image_urls = [];
        foreach ($slides as $s) {
            if (!empty($s['image_url'])) {
                // Check both symlink path and direct storage path
                $paths = [
                    APP_ROOT . '/public' . $s['image_url'],
                    STORAGE_PATH . '/' . ltrim(str_replace('/storage/', '', $s['image_url']), '/'),
                ];
                foreach ($paths as $p) {
                    if (file_exists($p)) { $image_urls[] = $p; break; }
                }
            }
        }

        // Build slides — use PNG if available, fall back to HTML content
        $has_content = false;
        $slide_entries = [];
        foreach ($slides as $s) {
            $img_path = null;
            if (!empty($s['image_url'])) {
                $paths = [
                    APP_ROOT . '/public' . $s['image_url'],
                    STORAGE_PATH . '/' . ltrim(str_replace('/storage/', '', $s['image_url']), '/'),
                ];
                foreach ($paths as $p) {
                    if (file_exists($p)) { $img_path = $p; break; }
                }
            }

            if ($img_path) {
                $data = base64_encode(file_get_contents($img_path));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $img_path);
                finfo_close($finfo);
                $slide_entries[] = ['type' => 'image', 'data' => "data:{$mime};base64,{$data}"];
                $has_content = true;
            } elseif (!empty($s['html_content'])) {
                $slide_entries[] = ['type' => 'html', 'html' => $s['html_content']];
                $has_content = true;
            }
        }

        if (!$has_content) json_error('No slides to export.');

        // Generate printable HTML — browser print-to-PDF
        $safe_title = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $pres['title']);
        header('Content-Type: text/html');
        header('Content-Disposition: inline; filename="' . ($safe_title ?: 'presentation') . '.html"');
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($pres['title']) . '</title>';
        echo '<style>@page{size:landscape;margin:0}body{margin:0}.slide{width:100vw;height:100vh;page-break-after:always;overflow:hidden;position:relative}img.slide-img{width:100%;height:100%;object-fit:contain;display:block}.slide-html{width:1920px;height:1080px;transform-origin:top left;}@media print{.no-print{display:none!important}}</style>';
        echo '<script>window.onload=function(){document.querySelectorAll(".slide-html").forEach(function(el){var s=Math.min(window.innerWidth/1920,window.innerHeight/1080);el.style.transform="scale("+s+")";});window.print();}</script>';
        echo '</head><body>';
        foreach ($slide_entries as $entry) {
            if ($entry['type'] === 'image') {
                echo '<div class="slide"><img class="slide-img" src="' . $entry['data'] . '"></div>';
            } else {
                echo '<div class="slide"><div class="slide-html">' . $entry['html'] . '</div></div>';
            }
        }
        echo '</body></html>';
        exit;
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
        if (!check_rate_limit('generate_outline', 5, 60)) json_error('Too many requests. Please wait a moment.', 429);

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
