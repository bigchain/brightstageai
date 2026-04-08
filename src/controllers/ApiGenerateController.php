<?php
/**
 * API controller for AI generation endpoints.
 * Handles slide HTML generation, image uploads, etc.
 */

require_once APP_ROOT . '/src/models/PresentationModel.php';
require_once APP_ROOT . '/src/models/SlideModel.php';
require_once APP_ROOT . '/src/models/UserModel.php';
require_once APP_ROOT . '/src/services/SlideGenerationService.php';

class ApiGenerateController
{
    private PresentationModel $presentations;
    private SlideModel $slides;
    private UserModel $users;

    public function __construct()
    {
        $this->presentations = new PresentationModel();
        $this->slides = new SlideModel();
        $this->users = new UserModel();
    }

    private function require_api_auth(): void
    {
        if (!is_logged_in()) json_error('Unauthorized', 401);
        if (!verify_csrf()) json_error('Invalid CSRF token', 403);
    }

    /**
     * Generate HTML/CSS for all slides in a presentation.
     * POST /api/generate/slides/{presentation_id}
     */
    public function generate_slides(int $presentation_id): void
    {
        $this->require_api_auth();
        $user = current_user();

        $pres = $this->presentations->find_by_id($presentation_id, $user['id']);
        if (!$pres) json_error('Presentation not found', 404);

        $slides = $this->slides->list_by_presentation($presentation_id);
        if (empty($slides)) json_error('No slides to generate');

        // Calculate credit cost
        $cost = count($slides) * CREDIT_COSTS['generate_slide'];
        $balance = $this->users->get_credits($user['id']);
        if ($balance < $cost) {
            json_error("Not enough credits. Need {$cost}, have {$balance}.");
        }

        // Get template config
        $template_config = $this->get_template_config($pres['template_id']);

        // Generate HTML for all slides
        $service = new SlideGenerationService();
        $results = $service->generate_all($slides, $template_config);

        // Count successes and save HTML to database
        $success_count = 0;
        foreach ($results as $result) {
            if ($result['success'] && $result['html']) {
                $this->slides->update($result['slide_id'], [
                    'html_content' => $result['html'],
                ]);
                $success_count++;
            }
        }

        if ($success_count === 0) {
            json_error('Failed to generate any slides. Please try again.');
        }

        // Deduct credits for successful generations only
        $actual_cost = $success_count * CREDIT_COSTS['generate_slide'];
        $this->users->deduct_credits($user['id'], $actual_cost, 'generate_slides', $presentation_id);

        // Update presentation status
        $this->presentations->update_status($presentation_id, 'slides_ready');

        json_success([
            'message'       => "{$success_count}/" . count($slides) . " slides generated",
            'success_count' => $success_count,
            'total'         => count($slides),
            'credits_used'  => $actual_cost,
        ]);
    }

    /**
     * Save a rendered slide PNG (uploaded from browser after html2canvas).
     * POST /api/slides/{id}/upload-image
     */
    public function upload_slide_image(int $slide_id): void
    {
        $this->require_api_auth();
        $user = current_user();

        // Verify ownership
        $slide = $this->slides->find_by_id($slide_id);
        if (!$slide) json_error('Slide not found', 404);

        $pres = $this->presentations->find_by_id($slide['presentation_id'], $user['id']);
        if (!$pres) json_error('Not authorized', 403);

        // Get the base64 PNG data from request body
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['image_data'])) {
            json_error('image_data required (base64 PNG)');
        }

        $image_data = $input['image_data'];

        // Validate image data URL (PNG, JPG, or WebP)
        if (!preg_match('/^data:image\/(png|jpeg|webp);base64,/', $image_data, $format_match)) {
            json_error('Invalid image format. Must be base64 PNG, JPG, or WebP.');
        }

        $ext_map = ['png' => 'png', 'jpeg' => 'jpg', 'webp' => 'webp'];
        $ext = $ext_map[$format_match[1]] ?? 'png';

        // Decode
        $base64 = preg_replace('/^data:image\/[a-z]+;base64,/', '', $image_data);
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 100) {
            json_error('Invalid image data');
        }

        // Max 10MB per slide image
        if (strlen($binary) > 10 * 1024 * 1024) {
            json_error('Image too large (max 10MB)');
        }

        // Save to storage
        $storage_path = user_storage_path($user['id'], $slide['presentation_id']);
        $slides_dir = $storage_path . '/slides';
        if (!is_dir($slides_dir)) {
            mkdir($slides_dir, 0755, true);
        }

        $filename = "slide_{$slide['slide_order']}.{$ext}";
        $filepath = $slides_dir . '/' . $filename;
        file_put_contents($filepath, $binary);

        // Save relative URL to database
        $relative_url = "/storage/users/{$user['id']}/presentations/{$slide['presentation_id']}/slides/{$filename}";
        $this->slides->update($slide_id, ['image_url' => $relative_url]);

        json_success([
            'message'   => 'Slide image saved',
            'image_url' => $relative_url,
        ]);
    }

    /**
     * Get template config JSON, with fallback to default.
     */
    private function get_template_config(?string $template_id): array
    {
        if (!$template_id) {
            return [
                'primary'      => '#1e3a5f',
                'secondary'    => '#ffffff',
                'accent'       => '#3498db',
                'font_heading' => 'Inter',
                'font_body'    => 'Inter',
                'style'        => 'clean',
            ];
        }

        $db = get_db();
        $stmt = $db->prepare('SELECT config_json FROM templates WHERE id = ? AND is_active = 1');
        $stmt->execute([$template_id]);
        $row = $stmt->fetch();

        if ($row && $row['config_json']) {
            $config = json_decode($row['config_json'], true);
            if (is_array($config)) return $config;
        }

        return [
            'primary'      => '#1e3a5f',
            'secondary'    => '#ffffff',
            'accent'       => '#3498db',
            'font_heading' => 'Inter',
            'font_body'    => 'Inter',
            'style'        => 'clean',
        ];
    }
}
