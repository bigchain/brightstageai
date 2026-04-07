<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Step Indicator -->
    <div class="flex items-center justify-center mb-10">
        <div class="flex items-center space-x-4">
            <div id="step-dot-1" class="flex items-center space-x-2">
                <span class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center text-sm font-bold">1</span>
                <span class="text-sm font-medium text-brand-700">Topic</span>
            </div>
            <div class="w-12 h-0.5 bg-gray-200" id="step-line-1"></div>
            <div id="step-dot-2" class="flex items-center space-x-2 opacity-40">
                <span class="w-8 h-8 rounded-full bg-gray-300 text-white flex items-center justify-center text-sm font-bold">2</span>
                <span class="text-sm font-medium text-gray-500">Preview</span>
            </div>
            <div class="w-12 h-0.5 bg-gray-200" id="step-line-2"></div>
            <div id="step-dot-3" class="flex items-center space-x-2 opacity-40">
                <span class="w-8 h-8 rounded-full bg-gray-300 text-white flex items-center justify-center text-sm font-bold">3</span>
                <span class="text-sm font-medium text-gray-500">Design</span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 1: Topic Input -->
    <!-- ═══════════════════════════════════════ -->
    <div id="step-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">What's your presentation about?</h2>
            <p class="text-gray-500 text-sm mb-6">Type a brief idea — AI will expand it into a compelling presentation brief.</p>

            <!-- Topic -->
            <div class="mb-6">
                <div class="relative">
                    <textarea id="topic" rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none text-lg"
                        placeholder="e.g., dog training tips for new owners"></textarea>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Duration</label>
                    <select id="duration" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        <option value="5">5 min (~5 slides)</option>
                        <option value="10" selected>10 min (~8 slides)</option>
                        <option value="15">15 min (~12 slides)</option>
                        <option value="30">30 min (~20 slides)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Tone</label>
                    <select id="tone" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        <option value="professional" selected>Professional</option>
                        <option value="casual">Casual & Friendly</option>
                        <option value="academic">Academic</option>
                        <option value="inspirational">Inspirational</option>
                        <option value="technical">Technical</option>
                        <option value="sales">Sales & Persuasive</option>
                    </select>
                </div>
            </div>

            <button onclick="enhanceAndGenerate()" id="btn-enhance"
                class="w-full py-3 px-4 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
                <span>&#10024;</span>
                <span>AI Generate Presentation Brief & Outline</span>
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">Uses <?= CREDIT_COSTS['generate_outline'] ?> credits &middot; You have <?= e($user['credits_balance']) ?> available</p>
        </div>

        <!-- Enhanced Preview (hidden until AI responds) -->
        <div id="enhanced-preview" class="hidden mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">AI-Generated Brief</h3>
                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded-full">&#10003; Editable</span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Presentation Title</label>
                    <input type="text" id="gen-title"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg font-semibold focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <textarea id="gen-description" rows="3"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Target Audience</label>
                    <input type="text" id="gen-audience"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
            </div>

            <p id="enhance-status" class="text-sm text-gray-500 mt-4 text-center"></p>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 2: Outline Preview -->
    <!-- ═══════════════════════════════════════ -->
    <div id="step-2" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xl font-bold text-gray-900" id="preview-title"></h2>
                <span class="text-sm text-gray-500" id="preview-count"></span>
            </div>
            <p class="text-gray-500 text-sm mb-6">Review and edit your slides. Click any field to change it.</p>
        </div>

        <!-- Slides Preview -->
        <div id="slides-preview" class="space-y-4"></div>

        <!-- Navigation -->
        <div class="mt-8 flex items-center justify-between">
            <button onclick="goToStep(1)" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                &larr; Back to Topic
            </button>
            <button onclick="goToStep(3)" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
                Looks Good — Choose Design &rarr;
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- STEP 3: Template Selection + Confirm -->
    <!-- ═══════════════════════════════════════ -->
    <div id="step-3" class="hidden">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">Choose Your Design</h2>
            <p class="text-gray-500 text-sm mb-6">Pick a visual style for your slides. You can change this later.</p>

            <div class="grid grid-cols-5 gap-4 mb-8">
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
                    <div class="rounded-xl border-2 border-gray-200 peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-200 p-4 transition hover:border-gray-300 hover:shadow-sm">
                        <div class="h-20 rounded-lg mb-3" style="background: linear-gradient(135deg, <?= $tprimary ?>, <?= $taccent ?>);">
                            <div class="p-3">
                                <div class="h-2 w-16 rounded-full mb-1.5" style="background: <?= $tsecondary ?>; opacity: 0.9"></div>
                                <div class="h-1.5 w-10 rounded-full" style="background: <?= $tsecondary ?>; opacity: 0.5"></div>
                            </div>
                        </div>
                        <p class="text-sm font-medium text-center text-gray-700"><?= $tname ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex items-center justify-between">
            <button onclick="goToStep(2)" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                &larr; Back to Slides
            </button>
            <button onclick="savePresentation()" id="btn-save"
                class="inline-flex items-center px-8 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition disabled:opacity-50">
                &#10003; Create Presentation (<?= CREDIT_COSTS['generate_outline'] ?> credits)
            </button>
        </div>
    </div>

    <!-- Progress Overlay -->
    <div id="progress-overlay" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-center shadow-2xl">
            <div class="animate-spin w-10 h-10 border-4 border-brand-200 border-t-brand-600 rounded-full mx-auto mb-4"></div>
            <p id="progress-text" class="text-gray-700 font-medium">Generating...</p>
            <p class="text-gray-400 text-sm mt-1">This may take 15-30 seconds</p>
        </div>
    </div>
</div>

<script>
let outlineData = null; // Stores the generated outline

// ── Step Navigation ──

function goToStep(step) {
    document.getElementById('step-1').classList.add('hidden');
    document.getElementById('step-2').classList.add('hidden');
    document.getElementById('step-3').classList.add('hidden');
    document.getElementById(`step-${step}`).classList.remove('hidden');

    // Update step dots
    for (let i = 1; i <= 3; i++) {
        const dot = document.getElementById(`step-dot-${i}`);
        const circle = dot.querySelector('span:first-child');
        if (i <= step) {
            dot.classList.remove('opacity-40');
            circle.classList.remove('bg-gray-300');
            circle.classList.add('bg-brand-600');
        } else {
            dot.classList.add('opacity-40');
            circle.classList.remove('bg-brand-600');
            circle.classList.add('bg-gray-300');
        }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Step 1: Enhance + Generate ──

async function enhanceAndGenerate() {
    const topic = document.getElementById('topic').value.trim();
    if (topic.length < 2) {
        document.getElementById('topic').focus();
        return;
    }

    const btn = document.getElementById('btn-enhance');
    btn.disabled = true;

    // Show progress overlay
    showProgress('Enhancing your topic with AI...');

    const tone = document.getElementById('tone').value;
    const duration = document.getElementById('duration').value;

    // Step 1: Enhance topic
    const enhanced = await api('/api/enhance-topic', { topic, tone });

    if (!enhanced.success) {
        hideProgress();
        btn.disabled = false;
        alert(enhanced.error || 'Failed to enhance topic');
        return;
    }

    // Fill in the enhanced fields
    document.getElementById('gen-title').value = enhanced.data.title;
    document.getElementById('gen-description').value = enhanced.data.description;
    document.getElementById('gen-audience').value = enhanced.data.audience;
    document.getElementById('enhanced-preview').classList.remove('hidden');

    // Step 2: Generate outline
    showProgress('Generating slides and narration...');

    const outline = await api('/api/generate/outline-preview', {
        topic: enhanced.data.description,
        audience: enhanced.data.audience,
        duration: parseInt(duration),
        tone,
    });

    hideProgress();
    btn.disabled = false;

    if (!outline.success) {
        document.getElementById('enhance-status').textContent = 'Outline generation failed. Try again.';
        document.getElementById('enhance-status').classList.add('text-red-500');
        return;
    }

    // Store outline data
    outlineData = outline.data;
    outlineData.title = enhanced.data.title; // Use enhanced title

    // Render slides preview
    renderSlidesPreview(outlineData);

    // Auto-advance to Step 2
    goToStep(2);
}

// ── Step 2: Render Slides Preview ──

function renderSlidesPreview(data) {
    document.getElementById('preview-title').textContent = data.title;
    document.getElementById('preview-count').textContent = `${data.slides.length} slides`;

    const container = document.getElementById('slides-preview');
    container.innerHTML = '';

    data.slides.forEach((slide, i) => {
        const isTitle = slide.layout_type === 'title';
        const card = document.createElement('div');
        card.className = 'bg-white rounded-xl border border-gray-200 overflow-hidden';
        card.innerHTML = `
            <div class="flex items-center px-6 py-3 bg-gray-50 border-b border-gray-100">
                <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold mr-3">${i + 1}</span>
                <span class="text-xs text-gray-400 uppercase tracking-wide">${slide.layout_type || 'bullets'}</span>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">SLIDE TITLE</label>
                    <input type="text" value="${escHtml(slide.title)}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg font-semibold text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none"
                        onchange="outlineData.slides[${i}].title = this.value">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">SLIDE CONTENT</label>
                        <textarea rows="${isTitle ? 2 : 5}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none"
                            onchange="outlineData.slides[${i}].content = this.value">${escHtml(slide.content)}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">NARRATION SCRIPT</label>
                        <textarea rows="${isTitle ? 2 : 5}"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none"
                            onchange="outlineData.slides[${i}].speaker_notes = this.value">${escHtml(slide.speaker_notes)}</textarea>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

// ── Step 3: Save Presentation ──

async function savePresentation() {
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    showProgress('Creating your presentation...');

    // Collect final data
    const title = document.getElementById('gen-title').value;
    const topic = document.getElementById('gen-description').value;
    const audience = document.getElementById('gen-audience').value;
    const duration = parseInt(document.getElementById('duration').value);
    const tone = document.getElementById('tone').value;
    const template_id = document.querySelector('input[name="template_id"]:checked')?.value || '1';

    const result = await api('/api/presentations/create', {
        title,
        topic,
        audience,
        duration,
        tone,
        template_id,
        slides: outlineData.slides,
    });

    hideProgress();

    if (result.success) {
        window.location.href = result.data.redirect;
    } else {
        btn.disabled = false;
        alert(result.error || 'Failed to create presentation');
    }
}

// ── Helpers ──

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showProgress(text) {
    document.getElementById('progress-text').textContent = text;
    document.getElementById('progress-overlay').classList.remove('hidden');
}

function hideProgress() {
    document.getElementById('progress-overlay').classList.add('hidden');
}
</script>
