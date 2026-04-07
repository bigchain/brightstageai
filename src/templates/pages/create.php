<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">New Presentation</h1>
        <p class="text-gray-500 mt-1">Tell us your topic — AI will enhance it and generate a complete outline with slide content and narration.</p>
    </div>

    <!-- Credit Cost Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-blue-800">
            <strong>Cost:</strong> <?= CREDIT_COSTS['generate_outline'] ?> credits to generate outline.
            You have <strong><?= e($user['credits_balance']) ?> credits</strong> available.
        </p>
    </div>

    <form method="POST" action="/create" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-6">
        <?= csrf_field() ?>

        <!-- Topic -->
        <div>
            <label for="topic" class="block text-sm font-medium text-gray-700 mb-1">
                What is your presentation about? <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <textarea id="topic" name="topic" rows="4" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none pr-28"
                    placeholder="e.g., dog training tips, AI in healthcare, marketing strategies for startups..."></textarea>
                <button type="button" id="enhance-btn" onclick="enhanceTopic()"
                    class="absolute right-2 top-2 inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium text-purple-700 bg-purple-100 hover:bg-purple-200 transition">
                    &#10024; AI Enhance
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1" id="topic-hint">
                Type a brief idea and click <strong>AI Enhance</strong> to expand it, or write a detailed description yourself.
            </p>
        </div>

        <!-- Title (optional) -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Presentation Title <span class="text-gray-400">(optional — AI will generate one)</span>
            </label>
            <input type="text" id="title" name="title"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                placeholder="Leave blank for AI-generated title">
        </div>

        <!-- Audience -->
        <div>
            <label for="audience" class="block text-sm font-medium text-gray-700 mb-1">Target Audience</label>
            <input type="text" id="audience" name="audience"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                placeholder="e.g., Marketing professionals, C-suite executives, University students"
                value="General audience">
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Duration -->
            <div>
                <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                <select id="duration" name="duration"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <option value="5">5 minutes (~5 slides)</option>
                    <option value="10" selected>10 minutes (~8 slides)</option>
                    <option value="15">15 minutes (~12 slides)</option>
                    <option value="30">30 minutes (~20 slides)</option>
                </select>
            </div>

            <!-- Tone -->
            <div>
                <label for="tone" class="block text-sm font-medium text-gray-700 mb-1">Tone</label>
                <select id="tone" name="tone"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <option value="professional" selected>Professional</option>
                    <option value="casual">Casual & Friendly</option>
                    <option value="academic">Academic</option>
                    <option value="inspirational">Inspirational</option>
                    <option value="technical">Technical</option>
                    <option value="sales">Sales & Persuasive</option>
                </select>
            </div>
        </div>

        <!-- Template -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">Design Template</label>
            <div class="grid grid-cols-5 gap-3">
                <?php
                $templates = [
                    ['1', 'Corporate', '#1e3a5f', '#3498db', '#ffffff'],
                    ['2', 'Creative', '#ff6b6b', '#6c5ce7', '#ffeaa7'],
                    ['3', 'Minimal', '#2d3436', '#00b894', '#ffffff'],
                    ['4', 'Dark', '#0a0a0a', '#e94560', '#e0e0e0'],
                    ['5', 'Vibrant', '#667eea', '#f093fb', '#ffffff'],
                ];
                foreach ($templates as [$tid, $tname, $tprimary, $taccent, $tsecondary]):
                ?>
                <label class="cursor-pointer">
                    <input type="radio" name="template_id" value="<?= $tid ?>" class="sr-only peer" <?= $tid === '1' ? 'checked' : '' ?>>
                    <div class="rounded-lg border-2 border-gray-200 peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-200 p-3 transition hover:border-gray-300">
                        <div class="h-16 rounded-md mb-2" style="background: linear-gradient(135deg, <?= $tprimary ?>, <?= $taccent ?>);">
                            <div class="p-2">
                                <div class="h-1.5 w-12 rounded-full mb-1" style="background: <?= $tsecondary ?>; opacity: 0.9"></div>
                                <div class="h-1 w-8 rounded-full" style="background: <?= $tsecondary ?>; opacity: 0.5"></div>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-center text-gray-700"><?= $tname ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submit -->
        <div class="pt-4">
            <button type="submit" id="submit-btn"
                class="w-full py-3 px-4 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition disabled:opacity-50 disabled:cursor-not-allowed">
                Generate Outline with Slides & Narration (<?= CREDIT_COSTS['generate_outline'] ?> credits)
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">
                AI will create slide content, bullet points, and speaker narration scripts — all editable before video generation.
            </p>
        </div>
    </form>
</div>

<script>
async function enhanceTopic() {
    const topicEl = document.getElementById('topic');
    const btn = document.getElementById('enhance-btn');
    const hint = document.getElementById('topic-hint');
    const topic = topicEl.value.trim();

    if (topic.length < 2) {
        topicEl.focus();
        topicEl.classList.add('border-red-300');
        setTimeout(() => topicEl.classList.remove('border-red-300'), 2000);
        return;
    }

    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '&#9889; Enhancing...';
    btn.classList.add('opacity-50');
    topicEl.classList.add('bg-gray-50');

    try {
        const result = await api('/api/enhance-topic', {
            topic: topic,
            audience: document.getElementById('audience').value,
            tone: document.getElementById('tone').value,
        });

        if (result.success && result.data.enhanced_topic) {
            topicEl.value = result.data.enhanced_topic;
            topicEl.classList.add('border-green-300', 'bg-green-50');
            hint.innerHTML = '<span class="text-green-600 font-medium">&#10003; Enhanced!</span> Review and edit if needed, then generate your outline.';
            setTimeout(() => {
                topicEl.classList.remove('border-green-300', 'bg-green-50');
            }, 3000);
        } else {
            hint.innerHTML = '<span class="text-red-500">Enhancement failed. You can still use your topic as-is.</span>';
        }
    } catch (err) {
        hint.innerHTML = '<span class="text-red-500">Network error. Try again.</span>';
    }

    btn.disabled = false;
    btn.innerHTML = '&#10024; AI Enhance';
    btn.classList.remove('opacity-50');
    topicEl.classList.remove('bg-gray-50');
}

// Prevent double-submit on form
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Generating outline with slides & narration... This may take 20-40 seconds';
});
</script>
