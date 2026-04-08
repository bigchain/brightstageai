<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'BrightStage Video') ?> — BrightStage</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Poppins:wght@400;600;800&family=Playfair+Display:wght@700&family=Raleway:wght@400;700&family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
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
        @keyframes fadeInUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        /* Live slide preview — scales 1920x1080 to fit card */
        .slide-live-preview { border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .slide-live-preview * { max-width: none !important; }
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
                    <span id="nav-credits-badge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-800">
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

    <!-- Toast Container -->
    <div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9998;display:flex;flex-direction:column;gap:10px;pointer-events:none;"></div>

    <!-- Global JS -->
    <script>
        // CSRF token for AJAX requests
        const CSRF_TOKEN = '<?= e(csrf_token()) ?>';

        // ── Toast Notification System ──
        function toast(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toast-container');
            const colors = {
                success: 'bg-green-600',
                error:   'bg-red-600',
                warning: 'bg-amber-500',
                info:    'bg-brand-600',
            };
            const icons = {
                success: '&#10003;',
                error:   '&#10007;',
                warning: '&#9888;',
                info:    '&#8505;',
            };

            const el = document.createElement('div');
            el.style.cssText = 'pointer-events:auto;transform:translateX(120%);transition:all 0.3s cubic-bezier(0.4,0,0.2,1);';
            el.innerHTML = `
                <div class="${colors[type] || colors.info} text-white px-5 py-3 rounded-xl shadow-2xl flex items-center space-x-3 min-w-[300px] max-w-[420px]" style="backdrop-filter:blur(10px);">
                    <span class="text-lg flex-shrink-0">${icons[type] || icons.info}</span>
                    <span class="text-sm font-medium flex-1">${message}</span>
                    <button onclick="this.closest('[style]').remove()" class="text-white/60 hover:text-white text-lg ml-2 flex-shrink-0">&times;</button>
                </div>
            `;
            container.appendChild(el);

            // Slide in
            requestAnimationFrame(() => {
                el.style.transform = 'translateX(0)';
            });

            // Auto dismiss
            if (duration > 0) {
                setTimeout(() => {
                    el.style.transform = 'translateX(120%)';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }, duration);
            }
        }

        // ── Confirmation Dialog (replaces confirm()) ──
        function confirmAction(message, onConfirm) {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
            overlay.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4" style="animation:fadeInUp 0.2s ease;">
                    <p class="text-gray-900 font-medium mb-6">${message}</p>
                    <div class="flex justify-end space-x-3">
                        <button onclick="this.closest('[style]').remove()" class="px-5 py-2.5 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                        <button id="confirm-yes" class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">Delete</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            overlay.querySelector('#confirm-yes').onclick = () => {
                overlay.remove();
                onConfirm();
            };
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.remove();
            });
        }

        // Helper: make API calls with error handling
        async function api(url, data = null, method = 'POST') {
            try {
                const opts = {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                };
                if (data) opts.body = JSON.stringify(data);
                const res = await fetch(url, opts);
                const json = await res.json();
                if (res.status === 401) {
                    toast('Session expired. Redirecting to login...', 'warning');
                    setTimeout(() => window.location.href = '/login', 1500);
                    return json;
                }
                return json;
            } catch (err) {
                toast('Network error. Check your connection.', 'error');
                return { success: false, error: 'Network error' };
            }
        }

        // Auto-dismiss flash messages after 5s
        document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]').forEach(el => {
            setTimeout(() => el.style.display = 'none', 5000);
        });
    </script>
</body>
</html>
