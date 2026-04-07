<?php
/**
 * Dashboard controller.
 * Shows user's presentations and credits.
 */

require_once APP_ROOT . '/src/models/PresentationModel.php';

class DashboardController
{
    private PresentationModel $presentations;

    public function __construct()
    {
        $this->presentations = new PresentationModel();
    }

    public function index(): void
    {
        require_auth();
        refresh_user_session();

        $user = current_user();
        $presentations = $this->presentations->list_by_user($user['id']);
        $count = $this->presentations->count_by_user($user['id']);

        $template = 'pages/dashboard.php';
        render($template, [
            'page_title'    => 'Dashboard',
            'user'          => $user,
            'presentations' => $presentations,
            'total_count'   => $count,
        ]);
    }
}
