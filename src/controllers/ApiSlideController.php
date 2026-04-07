<?php
/**
 * API controller for slide operations.
 * Returns JSON responses for AJAX calls.
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
     * Update a slide's content.
     * POST /api/slides/{id}/update
     */
    public function update(int $id): void
    {
        if (!is_logged_in()) {
            json_error('Unauthorized', 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            json_error('Invalid JSON body');
        }

        // Verify slide belongs to user
        $slide = $this->slides->find_by_id($id);
        if (!$slide) {
            json_error('Slide not found', 404);
        }

        $user = current_user();
        $pres = $this->presentations->find_by_id($slide['presentation_id'], $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        // Update allowed fields
        $data = [];
        if (isset($input['title']))         $data['title'] = $input['title'];
        if (isset($input['content']))       $data['content'] = $input['content'];
        if (isset($input['speaker_notes'])) $data['speaker_notes'] = $input['speaker_notes'];
        if (isset($input['layout_type']))   $data['layout_type'] = $input['layout_type'];

        if (empty($data)) {
            json_error('No fields to update');
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
        if (!is_logged_in()) {
            json_error('Unauthorized', 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['presentation_id']) || !isset($input['slide_ids'])) {
            json_error('presentation_id and slide_ids required');
        }

        $user = current_user();
        $pres = $this->presentations->find_by_id((int)$input['presentation_id'], $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        $this->slides->reorder((int)$input['presentation_id'], $input['slide_ids']);
        json_success(['message' => 'Slides reordered']);
    }

    /**
     * Delete a slide.
     * POST /api/slides/{id}/delete
     */
    public function delete(int $id): void
    {
        if (!is_logged_in()) {
            json_error('Unauthorized', 401);
        }

        $slide = $this->slides->find_by_id($id);
        if (!$slide) {
            json_error('Slide not found', 404);
        }

        $user = current_user();
        $pres = $this->presentations->find_by_id($slide['presentation_id'], $user['id']);
        if (!$pres) {
            json_error('Not authorized', 403);
        }

        $this->slides->delete($id, $slide['presentation_id']);
        json_success(['message' => 'Slide deleted']);
    }

    /**
     * Add a new slide.
     * POST /api/slides/add
     */
    public function add(): void
    {
        if (!is_logged_in()) {
            json_error('Unauthorized', 401);
        }

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

        $order = $this->slides->count_by_presentation($pres_id) + 1;
        $slide_id = $this->slides->create(
            $pres_id,
            $input['after_order'] ?? $order,
            $input['title'] ?? 'New Slide',
            $input['content'] ?? '- Add your content here',
            $input['speaker_notes'] ?? '',
            $input['layout_type'] ?? 'bullets'
        );

        $slide = $this->slides->find_by_id($slide_id);
        json_success($slide);
    }
}
