<?php
/**
 * API controller for slide operations.
 * Returns JSON responses for AJAX calls.
 * All endpoints require auth + CSRF.
 */

require_once APP_ROOT . '/src/models/SlideModel.php';
require_once APP_ROOT . '/src/models/PresentationModel.php';

class ApiSlideController
{
    private SlideModel $slides;
    private PresentationModel $presentations;

    public function __construct()
    {
        $this->slides = new SlideModel();
        $this->presentations = new PresentationModel();
    }

    /**
     * Enforce auth + CSRF on every API call.
     */
    private function require_api_auth(): void
    {
        if (!is_logged_in()) {
            json_error('Unauthorized', 401);
        }
        if (!verify_csrf()) {
            json_error('Invalid CSRF token', 403);
        }
    }

    /**
     * Verify a slide belongs to the current user via its presentation.
     * Returns [slide, presentation] or calls json_error and exits.
     */
    private function verify_slide_ownership(int $slide_id): array
    {
        $slide = $this->slides->find_by_id($slide_id);
        if (!$slide) {
            json_error('Slide not found', 404);
        }

        $user = current_user();
        $pres = $this->presentations->find_by_id($slide['presentation_id'], $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        return [$slide, $pres];
    }

    /**
     * Sanitize and truncate a string field.
     */
    private function sanitize_field(string $value, int $max_length): string
    {
        return mb_substr(trim($value), 0, $max_length);
    }

    /**
     * Update a slide's content.
     * POST /api/slides/{id}/update
     */
    public function update(int $id): void
    {
        $this->require_api_auth();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            json_error('Invalid JSON body');
        }

        [$slide, $pres] = $this->verify_slide_ownership($id);

        // Update allowed fields with length limits
        $data = [];
        if (isset($input['title']))         $data['title']         = $this->sanitize_field($input['title'], 255);
        if (isset($input['content']))       $data['content']       = $this->sanitize_field($input['content'], 10000);
        if (isset($input['speaker_notes'])) $data['speaker_notes'] = $this->sanitize_field($input['speaker_notes'], 10000);
        if (isset($input['layout_type'])) {
            $allowed_layouts = ['title', 'bullets', 'image_left', 'image_right', 'full_image', 'two_column', 'quote'];
            if (in_array($input['layout_type'], $allowed_layouts, true)) {
                $data['layout_type'] = $input['layout_type'];
            }
        }

        if (empty($data)) {
            json_error('No valid fields to update');
        }

        $this->slides->update($id, $data);
        json_success(['message' => 'Slide updated']);
    }

    /**
     * Reorder slides.
     * POST /api/slides/reorder
     */
    public function reorder(): void
    {
        $this->require_api_auth();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['presentation_id']) || !isset($input['slide_ids'])) {
            json_error('presentation_id and slide_ids required');
        }

        $user = current_user();
        $pres_id = (int)$input['presentation_id'];
        $pres = $this->presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        // Validate all slide IDs belong to this presentation
        $slide_ids = array_map('intval', (array)$input['slide_ids']);
        $existing_slides = $this->slides->list_by_presentation($pres_id);
        $valid_ids = array_column($existing_slides, 'id');

        foreach ($slide_ids as $sid) {
            if (!in_array($sid, $valid_ids, true)) {
                json_error('Invalid slide ID: slide does not belong to this presentation', 403);
            }
        }

        $this->slides->reorder($pres_id, $slide_ids);
        json_success(['message' => 'Slides reordered']);
    }

    /**
     * Delete a slide.
     * POST /api/slides/{id}/delete
     */
    public function delete(int $id): void
    {
        $this->require_api_auth();

        [$slide, $pres] = $this->verify_slide_ownership($id);
        $this->slides->delete($id, $slide['presentation_id']);
        json_success(['message' => 'Slide deleted']);
    }

    /**
     * Add a new slide.
     * POST /api/slides/add
     */
    public function add(): void
    {
        $this->require_api_auth();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['presentation_id'])) {
            json_error('presentation_id required');
        }

        $user = current_user();
        $pres_id = (int)$input['presentation_id'];
        $pres = $this->presentations->find_by_id($pres_id, $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        // Cap slides per presentation to prevent abuse
        $count = $this->slides->count_by_presentation($pres_id);
        if ($count >= 50) {
            json_error('Maximum 50 slides per presentation');
        }

        $order = $count + 1;
        $slide_id = $this->slides->create(
            $pres_id,
            (int)($input['after_order'] ?? $order),
            $this->sanitize_field($input['title'] ?? 'New Slide', 255),
            $this->sanitize_field($input['content'] ?? '- Add your content here', 10000),
            $this->sanitize_field($input['speaker_notes'] ?? '', 10000),
            'bullets'
        );

        $slide = $this->slides->find_by_id($slide_id);
        json_success($slide);
    }
}
