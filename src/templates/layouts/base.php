<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'BrightStage Video') ?> — BrightStage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 200:'#bfdbfe', 300:'#93c5fd', 400:'#60a5fa', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 800:'#1e40af', 900:'#1e3a5f' },
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .slide-card { transition: all 0.2s ease; }
        .slide-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-900">

    <!-- Nav -->
    <?php if (is_logged_in()): ?>
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/dashboard" class="text-xl font-bold text-brand-700">BrightStage</a>
                    <a href="/dashboard" class="text-sm text-gray-600 hover:text-brand-600">Dashboard</a>
                    <a href="/create" class="text-sm text-gray-600 hover:text-brand-600">+ New</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-800">
                        <?= e(current_user()['credits_balance'] ?? 0) ?> credits
                    </span>
                    <span class="text-sm text-gray-500"><?= e(current_user()['name'] ?? '') ?></span>
                    <a href="/logout" class="text-sm text-gray-400 hover:text-red-500">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-brand-700">BrightStage</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-sm text-gray-600 hover:text-brand-600">Sign In</a>
                    <a href="/register" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700">
                        Get Started Free
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php if (!empty($flash)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php if (isset($flash['success'])): ?>
        <div class="rounded-md bg-green-50 border border-green-200 p-4 mb-4">
            <p class="text-sm text-green-800"><?= e($flash['success']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (isset($flash['error'])): ?>
        <div class="rounded-md bg-red-50 border border-red-200 p-4 mb-4">
            <p class="text-sm text-red-800"><?= e($flash['error']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Page Content -->
    <main>
        <?php require TEMPLATE_PATH . '/' . $template; ?>
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gray-200 bg-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-400">
            &copy; <?= date('Y') ?> BrightStage Video. All rights reserved.
        </div>
    </footer>

    <!-- Global JS -->
    <script>
        // CSRF token for AJAX requests
        const CSRF_TOKEN = '<?= e(csrf_token()) ?>';

        // Helper: make API calls
        async function api(url, data = null, method = 'POST') {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
            };
            if (data) opts.body = JSON.stringify(data);
            const res = await fetch(url, opts);
            return res.json();
        }

        // Auto-dismiss flash messages after 5s
        document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]').forEach(el => {
            setTimeout(() => el.style.display = 'none', 5000);
        });
    </script>
</body>
</html>
