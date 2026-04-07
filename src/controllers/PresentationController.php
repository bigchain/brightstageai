<?php
/**
 * Presentation controller.
 * Handles creating, viewing, editing presentations and generating outlines.
 */

require_once APP_ROOT . '/src/models/PresentationModel.php';
require_once APP_ROOT . '/src/models/SlideModel.php';
require_once APP_ROOT . '/src/models/UserModel.php';
require_once APP_ROOT . '/src/services/OutlineService.php';

class PresentationController
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

    /**
     * Show create wizard (Step 1: Content Input).
     */
    public function create(): void
    {
        require_auth();
        $template = 'pages/create.php';
        render($template, [
            'page_title' => 'New Presentation',
            'user'       => current_user(),
        ]);
    }

    /**
     * Handle create form submission — save presentation and generate outline.
     */
    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/create');
        }

        $user = current_user();

        // Server-side duplicate submit protection
        $submit_key = 'last_outline_' . $user['id'];
        if (isset($_SESSION[$submit_key]) && (time() - $_SESSION[$submit_key]) < 10) {
            flash('error', 'Please wait before submitting again.');
            redirect('/create');
        }

        $topic    = mb_substr(trim($_POST['topic'] ?? ''), 0, 2000);
        $audience = mb_substr(trim($_POST['audience'] ?? ''), 0, 200);
        $title    = mb_substr(trim($_POST['title'] ?? ''), 0, 255);

        // Validate duration against allowed values
        $allowed_durations = [5, 10, 15, 30];
        $duration = (int)($_POST['duration'] ?? 10);
        if (!in_array($duration, $allowed_durations, true)) {
            $duration = 10;
        }

        // Validate tone against allowed values
        $allowed_tones = ['professional', 'casual', 'academic', 'inspirational', 'technical', 'sales'];
        $tone = trim($_POST['tone'] ?? 'professional');
        if (!in_array($tone, $allowed_tones, true)) {
            $tone = 'professional';
        }

        // Validate topic
        if ($topic === '' || mb_strlen($topic) < 3) {
            flash('error', 'Topic is required (at least 3 characters).');
            redirect('/create');
        }

        if ($title === '') {
            $title = mb_substr($topic, 0, 100);
        }

        // Check credits (uses SELECT FOR UPDATE in deduct to prevent race condition)
        $cost = CREDIT_COSTS['generate_outline'];
        $balance = $this->users->get_credits($user['id']);
        if ($balance < $cost) {
            flash('error', "Not enough credits. You need {$cost} credits but have {$balance}.");
            redirect('/create');
        }

        // Mark submission time to prevent double-submit
        $_SESSION[$submit_key] = time();

        // Create presentation
        $pres_id = $this->presentations->create(
            $user['id'], $title, $topic, $audience, $duration, $tone
        );

        // Generate outline via AI
        $outline_service = new OutlineService();
        $outline = $outline_service->generate($topic, $audience, $duration, $tone);

        if ($outline === null) {
            flash('error', 'Failed to generate outline. Please try again.');
            $this->presentations->delete($pres_id, $user['id']);
            redirect('/create');
        }

        // Update title if AI provided one
        if (!empty($outline['title'])) {
            $this->presentations->update($pres_id, $user['id'], ['title' => $outline['title']]);
        }

        // Create slides from outline
        $this->slides->create_batch($pres_id, $outline['slides']);

        // Update status
        $this->presentations->update_status($pres_id, 'outline_ready');

        // Deduct credits
        $this->users->deduct_credits($user['id'], $cost, 'generate_outline', $pres_id);

        flash('success', 'Outline generated! Edit your slides below.');
        redirect("/presentation/{$pres_id}");
    }

    /**
     * View/edit a presentation and its slides.
     */
    public function show(int $id): void
    {
        require_auth();
        $user = current_user();

        $presentation = $this->presentations->find_by_id($id, $user['id']);
        if (!$presentation) {
            flash('error', 'Presentation not found.');
            redirect('/dashboard');
        }

        $slides = $this->slides->list_by_presentation($id);

        $template = 'pages/presentation.php';
        render($template, [
            'page_title'   => e($presentation['title']),
            'user'         => $user,
            'presentation' => $presentation,
            'slides'       => $slides,
        ]);
    }

    /**
     * Delete a presentation.
     */
    public function destroy(int $id): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('/dashboard');
        }

        $user = current_user();
        $this->slides->delete_by_presentation($id);
        $this->presentations->delete($id, $user['id']);

        flash('success', 'Presentation deleted.');
        redirect('/dashboard');
    }
}
