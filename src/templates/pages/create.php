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
                        class="w-full px-4 py-3 pr-32 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none text-lg"
                        placeholder="e.g., dog training tips for new owners"></textarea>
                    <button type="button" onclick="enhanceTopic()" id="btn-enhance"
                        class="absolute right-3 top-3 inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 shadow-sm transition disabled:opacity-50">
                        &#10024; AI Enhance
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1.5" id="topic-hint">
                    Type a brief idea and click <strong>AI Enhance</strong> — AI will expand it and generate a title &amp; audience.
                </p>
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
        </div>

        <!-- Enhanced Brief (hidden until AI Enhance is clicked) -->
        <div id="enhanced-preview" class="hidden mt-6 bg-white rounded-xl shadow-sm border border-green-200 p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">&#10003; AI-Generated Brief</h3>
                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded-full">All fields editable</span>
            </div>

            <!-- Hidden field to store description (uses topic textarea value) -->
            <input type="hidden" id="gen-description">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Presentation Title</label>
                    <input type="text" id="gen-title"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg font-semibold focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Target Audience</label>
                    <input type="text" id="gen-audience"
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none"
                        style="padding-top: 0.95rem; padding-bottom: 0.95rem;">
                </div>
            </div>

            <div class="mt-6">
                <button onclick="generateOutline()" id="btn-generate"
                    class="w-full py-3 px-4 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
                    <span>&#9654;</span>
                    <span>Generate Slides & Narration (<?= CREDIT_COSTS['generate_outline'] ?> credits)</span>
                </button>
                <p class="text-xs text-gray-400 text-center mt-2">You have <?= e($user['credits_balance']) ?> credits available</p>
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
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500" id="preview-count"></span>
                    <button onclick="addNewSlide()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                        + Add Slide
                    </button>
                </div>
            </div>
            <p class="text-gray-500 text-sm">Review and edit your slides. Add, remove, or reorder as needed.</p>
        </div>

        <!-- Slides Preview -->
        <div id="slides-preview" class="space-y-4"></div>

        <!-- Add Slide Button (bottom) -->
        <div class="mt-4">
            <button onclick="addNewSlide()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm font-medium text-gray-500 hover:border-brand-400 hover:text-brand-600 hover:bg-brand-50 transition">
                + Add Another Slide
            </button>
        </div>

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
            <p class="text-gray-500 text-sm mb-6">Click a template to see a live preview of your first slide.</p>

            <div class="grid grid-cols-4 sm:grid-cols-7 gap-3 mb-6">
                <?php
                $templates = [
                    ['1', 'Clean White', '#ffffff', '#3498db', '#1e293b', 'Inter'],
                    ['2', 'Light Elegant', '#fafafa', '#8b5cf6', '#1e293b', 'Playfair Display'],
                    ['3', 'Corporate', '#1e3a5f', '#3498db', '#ffffff', 'Inter'],
                    ['4', 'Creative', '#ff6b6b', '#6c5ce7', '#ffeaa7', 'Poppins'],
                    ['5', 'Minimal', '#2d3436', '#00b894', '#ffffff', 'Raleway'],
                    ['6', 'Dark', '#0a0a0a', '#e94560', '#e0e0e0', 'Raleway'],
                    ['7', 'Vibrant', '#667eea', '#f093fb', '#ffffff', 'Montserrat'],
                ];
                foreach ($templates as [$tid, $tname, $tprimary, $taccent, $tsecondary, $tfont]):
                ?>
                <label class="cursor-pointer" onclick="previewTemplate('<?= $tprimary ?>', '<?= $taccent ?>', '<?= $tsecondary ?>', '<?= $tfont ?>', '<?= $tname ?>')">
                    <input type="radio" name="template_id" value="<?= $tid ?>" class="sr-only peer" <?= $tid === '1' ? 'checked' : '' ?>>
                    <div class="rounded-xl border-2 border-gray-200 peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-200 p-3 transition hover:border-gray-300 hover:shadow-sm">
                        <?php $isLight = in_array($tprimary, ['#ffffff', '#fafafa', '#f5f5f5']); ?>
                        <div class="h-16 rounded-lg mb-2 <?= $isLight ? 'border border-gray-200' : '' ?>" style="background: <?= $isLight ? $tprimary : "linear-gradient(135deg, {$tprimary}, {$taccent})" ?>;">
                            <div class="p-2.5">
                                <div class="h-1.5 w-10 rounded-full mb-1" style="background: <?= $isLight ? $taccent : $tsecondary ?>; opacity: 0.9"></div>
                                <div class="h-1 w-7 rounded-full" style="background: <?= $isLight ? $tsecondary : $tsecondary ?>; opacity: 0.4"></div>
                            </div>
                        </div>
                        <p class="text-sm font-medium text-center text-gray-700"><?= $tname ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Live Slide Preview -->
            <!-- Live Slide Previews -->
            <div class="mt-8 space-y-5">
                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Preview — Your slides in this design</label>

                <!-- Title Slide Preview -->
                <div class="rounded-2xl overflow-hidden shadow-2xl border border-gray-200/60" style="aspect-ratio: 16/9;">
                    <div id="preview-title-slide" class="preview-slide" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:8% 10%;font-family:'Inter',sans-serif;position:relative;overflow:hidden;transition:all 0.5s cubic-bezier(0.4,0,0.2,1);">
                        <!-- Decorative shapes -->
                        <div id="p-shape-1" style="position:absolute;top:-10%;right:-5%;width:40%;height:65%;border-radius:50%;transition:all 0.5s;"></div>
                        <div id="p-shape-2" style="position:absolute;bottom:-15%;left:-3%;width:30%;height:50%;border-radius:50%;transition:all 0.5s;"></div>
                        <div id="p-shape-3" style="position:absolute;top:15%;left:6%;width:3px;height:25%;border-radius:2px;transition:all 0.5s;"></div>
                        <div id="p-shape-4" style="position:absolute;bottom:8%;right:8%;width:40px;height:40px;border-radius:50%;border:2px solid;opacity:0.15;transition:all 0.5s;"></div>
                        <!-- Content -->
                        <div style="text-align:center;position:relative;z-index:1;max-width:75%;">
                            <div id="p-badge" style="display:inline-block;padding:4px 16px;border-radius:20px;font-size:0.65em;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:1.2em;transition:all 0.5s;"></div>
                            <div id="p-title" style="font-size:2.6em;font-weight:800;line-height:1.1;margin-bottom:0.5em;letter-spacing:-0.02em;transition:all 0.5s;"></div>
                            <div id="p-divider" style="width:60px;height:3px;border-radius:2px;margin:0 auto 0.8em;transition:all 0.5s;"></div>
                            <div id="p-subtitle" style="font-size:0.95em;font-weight:400;line-height:1.5;opacity:0.7;transition:all 0.5s;"></div>
                        </div>
                    </div>
                </div>

                <!-- Bullets Slide Preview -->
                <div class="rounded-2xl overflow-hidden shadow-2xl border border-gray-200/60" style="aspect-ratio: 16/9;">
                    <div id="preview-bullets-slide" class="preview-slide" style="width:100%;height:100%;display:flex;flex-direction:column;justify-content:center;padding:5% 7%;font-family:'Inter',sans-serif;position:relative;overflow:hidden;transition:all 0.5s cubic-bezier(0.4,0,0.2,1);">
                        <!-- Decorative elements -->
                        <div id="p2-stripe" style="position:absolute;top:0;left:0;width:5px;height:100%;transition:all 0.5s;"></div>
                        <div id="p2-corner" style="position:absolute;top:5%;right:5%;width:80px;height:80px;border-radius:16px;opacity:0.06;transform:rotate(15deg);transition:all 0.5s;"></div>
                        <div id="p2-dots" style="position:absolute;bottom:6%;right:6%;display:flex;gap:6px;transition:all 0.5s;"></div>
                        <!-- Content -->
                        <div style="position:relative;z-index:1;padding-left:3%;">
                            <div id="p2-label" style="font-size:0.6em;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;margin-bottom:0.6em;transition:all 0.5s;"></div>
                            <div id="p2-title" style="font-size:1.7em;font-weight:700;margin-bottom:1em;letter-spacing:-0.01em;transition:all 0.5s;"></div>
                            <div id="p2-bullets" style="font-size:0.8em;line-height:1.8;transition:all 0.5s;"></div>
                        </div>
                    </div>
                </div>
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
let isBusy = false;     // Prevents concurrent API calls

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

    // Auto-preview template when entering Step 3
    if (step === 3 && typeof previewTemplate === 'function') {
        const checked = document.querySelector('input[name="template_id"]:checked');
        if (checked) checked.closest('label').click();
    }
}

// ── Step 1a: AI Enhance (topic → title + description + audience) ──

async function enhanceTopic() {
    if (isBusy) return;
    const topicEl = document.getElementById('topic');
    const topic = topicEl.value.trim();
    if (topic.length < 2) { topicEl.focus(); return; }
    isBusy = true;

    const btn = document.getElementById('btn-enhance');
    btn.disabled = true;
    btn.innerHTML = '&#9889; Enhancing...';

    showProgress('AI is expanding your topic...');

    const tone = document.getElementById('tone').value;
    const enhanced = await api('/api/enhance-topic', { topic, tone });

    hideProgress();
    btn.disabled = false;
    btn.innerHTML = '&#10024; AI Enhance';

    if (!enhanced.success) {
        isBusy = false;
        toast(enhanced.error || 'Failed to enhance. Try again.', 'error');
        return;
    }

    // Fill in all the generated fields
    topicEl.value = enhanced.data.description;
    topicEl.classList.add('border-green-400');
    setTimeout(() => topicEl.classList.remove('border-green-400'), 3000);

    document.getElementById('gen-title').value = enhanced.data.title;
    document.getElementById('gen-description').value = enhanced.data.description;
    document.getElementById('gen-audience').value = enhanced.data.audience;

    // Show the enhanced brief section
    document.getElementById('enhanced-preview').classList.remove('hidden');
    document.getElementById('enhanced-preview').scrollIntoView({ behavior: 'smooth', block: 'center' });

    document.getElementById('topic-hint').innerHTML = '<span class="text-green-600 font-medium">&#10003; Enhanced!</span> Review below, edit if needed, then generate slides.';
    isBusy = false;
}

// ── Step 1b: Generate Outline (separate button) ──

async function generateOutline() {
    if (isBusy) return;
    isBusy = true;
    const btn = document.getElementById('btn-generate');
    btn.disabled = true;

    const tone = document.getElementById('tone').value;
    const duration = document.getElementById('duration').value;
    const topic = document.getElementById('topic').value.trim();
    const audience = document.getElementById('gen-audience').value.trim();

    if (!topic) {
        toast('Click AI Enhance first to generate your description.', 'warning');
        btn.disabled = false;
        return;
    }

    showProgress('Generating slides and narration scripts...');

    const outline = await api('/api/generate/outline-preview', {
        topic,
        audience,
        duration: parseInt(duration),
        tone,
    });

    hideProgress();
    btn.disabled = false;

    if (!outline.success) {
        isBusy = false;
        toast(outline.error || 'Failed to generate outline. Try again.', 'error');
        return;
    }

    // Store outline data with the enhanced title
    outlineData = outline.data;
    outlineData.title = document.getElementById('gen-title').value || outlineData.title;

    // Render slides preview
    renderSlidesPreview(outlineData);

    isBusy = false;

    // Auto-advance to Step 2
    goToStep(2);
}

// ── Step 2: Render Slides Preview ──

function renderSlidesPreview(data) {
    document.getElementById('preview-title').textContent = data.title;
    updateSlideCount();

    const container = document.getElementById('slides-preview');
    container.innerHTML = '';

    data.slides.forEach((slide, i) => {
        container.appendChild(createSlideCard(slide, i));
    });
}

function createSlideCard(slide, index) {
    const isTitle = slide.layout_type === 'title';
    const card = document.createElement('div');
    card.className = 'bg-white rounded-xl border border-gray-200 overflow-hidden slide-preview-card';
    card.dataset.index = index;
    card.innerHTML = `
        <div class="flex items-center justify-between px-6 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center">
                <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold mr-3 slide-number">${index + 1}</span>
                <select class="text-xs text-gray-500 bg-transparent border-none outline-none cursor-pointer"
                    onchange="outlineData.slides[${index}].layout_type = this.value">
                    <option value="title" ${slide.layout_type === 'title' ? 'selected' : ''}>Title Slide</option>
                    <option value="bullets" ${slide.layout_type === 'bullets' ? 'selected' : ''}>Bullets</option>
                    <option value="quote" ${slide.layout_type === 'quote' ? 'selected' : ''}>Quote</option>
                    <option value="image_left" ${slide.layout_type === 'image_left' ? 'selected' : ''}>Image Left</option>
                    <option value="image_right" ${slide.layout_type === 'image_right' ? 'selected' : ''}>Image Right</option>
                    <option value="two_column" ${slide.layout_type === 'two_column' ? 'selected' : ''}>Two Column</option>
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="polishSlide(${index})" class="text-xs text-purple-600 hover:text-purple-700 hover:bg-purple-50 px-2 py-1 rounded transition font-medium polish-btn" id="polish-btn-${index}"
                    title="AI will improve grammar, make bullets punchier, and smooth narration">&#10024; AI Polish</button>
                <button onclick="removeSlide(${index})" class="text-xs text-red-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded transition"
                    title="Remove this slide">&#10005; Remove</button>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1">SLIDE TITLE</label>
                <input type="text" value="${escHtml(slide.title)}"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg font-semibold text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none"
                    onchange="outlineData.slides[${index}].title = this.value">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">SLIDE CONTENT <span class="text-gray-300">(appears on slide)</span></label>
                    <textarea rows="${isTitle ? 2 : 5}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-y"
                        onchange="outlineData.slides[${index}].content = this.value">${escHtml(slide.content)}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">NARRATION SCRIPT <span class="text-gray-300">(voiceover)</span></label>
                    <textarea rows="${isTitle ? 2 : 5}"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-y"
                        onchange="outlineData.slides[${index}].speaker_notes = this.value">${escHtml(slide.speaker_notes)}</textarea>
                </div>
            </div>
        </div>
    `;
    return card;
}

function addNewSlide() {
    if (!outlineData) return;

    const newSlide = {
        slide_order: outlineData.slides.length + 1,
        title: 'New Slide',
        content: '- Add your first point here\n- Add your second point\n- Add your third point',
        speaker_notes: 'Write what the presenter should say for this slide.',
        layout_type: 'bullets',
    };

    outlineData.slides.push(newSlide);
    const container = document.getElementById('slides-preview');
    container.appendChild(createSlideCard(newSlide, outlineData.slides.length - 1));
    updateSlideCount();

    // Scroll to the new slide
    container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
    container.lastElementChild.classList.add('ring-2', 'ring-brand-400');
    setTimeout(() => container.lastElementChild.classList.remove('ring-2', 'ring-brand-400'), 2000);
}

function removeSlide(index) {
    if (!outlineData || outlineData.slides.length <= 2) {
        toast('You need at least 2 slides.', 'warning');
        return;
    }

    outlineData.slides.splice(index, 1);
    // Re-number slide_order
    outlineData.slides.forEach((s, i) => s.slide_order = i + 1);
    // Re-render all (simplest way to keep indices correct)
    renderSlidesPreview(outlineData);
}

function updateSlideCount() {
    if (!outlineData) return;
    document.getElementById('preview-count').textContent = `${outlineData.slides.length} slides`;
}

// ── AI Polish per slide ──

async function polishSlide(index) {
    if (!outlineData || !outlineData.slides || !outlineData.slides[index]) return;
    const slide = outlineData.slides[index];

    const btn = document.getElementById(`polish-btn-${index}`);
    btn.disabled = true;
    btn.innerHTML = '&#9889; Polishing...';
    btn.classList.add('opacity-50');

    const tone = document.getElementById('tone').value;
    const result = await api('/api/polish-slide', {
        title: slide.title,
        content: slide.content,
        speaker_notes: slide.speaker_notes,
        tone,
    });

    btn.disabled = false;
    btn.innerHTML = '&#10024; AI Polish';
    btn.classList.remove('opacity-50');

    if (result.success) {
        // Update data
        outlineData.slides[index].title = result.data.title;
        outlineData.slides[index].content = result.data.content;
        outlineData.slides[index].speaker_notes = result.data.speaker_notes;

        // Re-render just this card
        const container = document.getElementById('slides-preview');
        const cards = container.querySelectorAll('.slide-preview-card');
        const newCard = createSlideCard(outlineData.slides[index], index);
        cards[index].replaceWith(newCard);

        // Flash green to show it worked
        newCard.classList.add('ring-2', 'ring-green-400');
        setTimeout(() => newCard.classList.remove('ring-2', 'ring-green-400'), 2000);
    } else {
        toast(result.error || 'Polish failed. Try again.', 'error');
    }
}

// ── Step 3: Template Preview ──

function previewTemplate(primary, accent, secondary, font, name) {
    const isLight = ['#ffffff', '#fafafa', '#f5f5f5'].includes(primary);
    const fontStack = `'${font}', 'Inter', -apple-system, sans-serif`;

    // ── Title Slide ──
    const ts = document.getElementById('preview-title-slide');
    ts.style.fontFamily = fontStack;

    if (isLight) {
        ts.style.background = `linear-gradient(160deg, ${primary} 0%, #f8f9fa 100%)`;
    } else {
        ts.style.background = `linear-gradient(135deg, ${primary} 0%, ${mixColor(primary, accent, 0.25)} 60%, ${mixColor(accent, primary, 0.4)} 100%)`;
    }

    // Shapes
    document.getElementById('p-shape-1').style.background = isLight ? `${accent}0a` : `${secondary}08`;
    document.getElementById('p-shape-2').style.background = isLight ? `${accent}06` : `${secondary}05`;
    document.getElementById('p-shape-3').style.background = accent;
    document.getElementById('p-shape-4').style.borderColor = isLight ? accent : secondary;

    // Badge
    const badge = document.getElementById('p-badge');
    badge.style.background = isLight ? `${accent}15` : `${secondary}15`;
    badge.style.color = isLight ? accent : secondary;
    badge.textContent = 'Presentation';

    // Text
    document.getElementById('p-title').style.color = secondary;
    document.getElementById('p-divider').style.background = accent;
    document.getElementById('p-subtitle').style.color = secondary;

    // ── Bullets Slide ──
    const bs = document.getElementById('preview-bullets-slide');
    bs.style.fontFamily = fontStack;

    if (isLight) {
        bs.style.background = primary;
    } else {
        bs.style.background = `linear-gradient(170deg, ${primary} 0%, ${mixColor(primary, '#000', 0.15)} 100%)`;
    }

    document.getElementById('p2-stripe').style.background = `linear-gradient(180deg, ${accent}, ${mixColor(accent, primary, 0.5)})`;
    document.getElementById('p2-corner').style.background = isLight ? accent : secondary;

    const label = document.getElementById('p2-label');
    label.style.color = accent;

    document.getElementById('p2-title').style.color = secondary;
    document.getElementById('p2-bullets').style.color = isLight ? `${secondary}bb` : `${secondary}cc`;

    // Dots decoration
    const dotsEl = document.getElementById('p2-dots');
    dotsEl.innerHTML = [1,2,3].map(() =>
        `<div style="width:6px;height:6px;border-radius:50%;background:${accent};opacity:0.3;"></div>`
    ).join('');

    // ── Fill with real content ──
    if (outlineData && outlineData.slides.length > 0) {
        const title = outlineData.title || outlineData.slides[0].title;
        document.getElementById('p-title').textContent = title;

        const firstContent = outlineData.slides[0].content || '';
        const firstLine = firstContent.split('\n')[0]?.replace(/^-\s*/, '') || '';
        document.getElementById('p-subtitle').textContent = firstLine || 'Your presentation subtitle';

        // Bullets slide
        const bSlide = outlineData.slides[1] || outlineData.slides[0];
        label.textContent = `Slide ${bSlide === outlineData.slides[1] ? '02' : '01'}`;
        document.getElementById('p2-title').textContent = bSlide.title;

        const bullets = (bSlide.content || '').split('\n')
            .filter(l => l.trim())
            .slice(0, 4)
            .map((l, i) => {
                const text = l.replace(/^-\s*/, '');
                return `<div style="display:flex;align-items:flex-start;margin-bottom:0.6em;padding:0.4em 0.6em;border-radius:8px;${i === 0 ? `background:${accent}0d;` : ''}">
                    <span style="width:22px;height:22px;border-radius:6px;background:${accent}${i === 0 ? '' : '22'};color:${i === 0 ? (isLight ? '#fff' : '#fff') : accent};display:flex;align-items:center;justify-content:center;font-size:0.7em;font-weight:700;margin-right:0.8em;flex-shrink:0;">${i + 1}</span>
                    <span style="padding-top:2px;">${escHtml(text)}</span>
                </div>`;
            }).join('');
        document.getElementById('p2-bullets').innerHTML = bullets;
    }
}

function mixColor(hex1, hex2, ratio) {
    const parse = (h, s) => parseInt(h.slice(s, s+2), 16);
    const r = Math.round(parse(hex1,1) + (parse(hex2,1) - parse(hex1,1)) * ratio);
    const g = Math.round(parse(hex1,3) + (parse(hex2,3) - parse(hex1,3)) * ratio);
    const b = Math.round(parse(hex1,5) + (parse(hex2,5) - parse(hex1,5)) * ratio);
    return `#${[r,g,b].map(v => Math.max(0,Math.min(255,v)).toString(16).padStart(2,'0')).join('')}`;
}

// Preview is triggered inline — goToStep handles it directly

// ── Step 3: Save Presentation ──

async function savePresentation() {
    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    showProgress('Creating your presentation...');

    // Collect final data
    const title = document.getElementById('gen-title').value;
    const topic = document.getElementById('topic').value;
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
        toast(result.error || 'Failed to create presentation', 'error');
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
