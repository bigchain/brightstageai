<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome Back</h1>
            <p class="mt-2 text-gray-500">Sign in to your BrightStage account</p>
        </div>

        <form method="POST" action="/login" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-6">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required autofocus
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                    placeholder="you@example.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                    placeholder="Your password">
            </div>

            <button type="submit"
                class="w-full py-3 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition">
                Sign In
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Don't have an account?
            <a href="/register" class="text-brand-600 hover:text-brand-700 font-medium">Create one free</a>
        </p>
    </div>
</div>
