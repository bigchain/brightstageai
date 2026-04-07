<?php
/**
 * Authentication controller.
 * Handles register, login, logout.
 */

require_once APP_ROOT . '/src/models/UserModel.php';

class AuthController
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function show_register(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        $template = 'pages/register.php';
        render($template, ['page_title' => 'Create Account']);
    }

    public function register(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect('/register');
        }

        $email    = trim($_POST['email'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        // Validate
        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if ($name === '' || strlen($name) < 2) {
            $errors[] = 'Name is required (at least 2 characters).';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if ($this->users->email_exists($email)) {
            $errors[] = 'An account with that email already exists.';
        }

        if (!empty($errors)) {
            flash('error', implode(' ', $errors));
            redirect('/register');
        }

        // Create user
        $user_id = $this->users->create($email, $name, $password);

        // Log signup bonus
        $this->users->add_credits($user_id, 0, 'signup_bonus'); // Credits already set in create()

        // Auto login
        $user = $this->users->find_by_id($user_id);
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'email'           => $user['email'],
            'name'            => $user['name'],
            'credits_balance' => $user['credits_balance'],
            'plan'            => $user['plan'],
            'role'            => $user['role'],
        ];

        flash('success', 'Welcome to BrightStage! You have ' . SIGNUP_BONUS_CREDITS . ' free credits to get started.');
        redirect('/dashboard');
    }

    public function show_login(): void
    {
        if (is_logged_in()) {
            redirect('/dashboard');
        }
        $template = 'pages/login.php';
        render($template, ['page_title' => 'Sign In']);
    }

    public function login(): void
    {
        if (!verify_csrf()) {
            flash('error', 'Invalid form submission. Please try again.');
            redirect('/login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->users->find_by_email($email);

        if (!$user || !$this->users->verify_password($user, $password)) {
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        // Set session
        $_SESSION['user'] = [
            'id'              => $user['id'],
            'email'           => $user['email'],
            'name'            => $user['name'],
            'credits_balance' => $user['credits_balance'],
            'plan'            => $user['plan'],
            'role'            => $user['role'],
        ];

        redirect('/dashboard');
    }

    public function logout(): void
    {
        session_destroy();
        redirect('/');
    }
}
