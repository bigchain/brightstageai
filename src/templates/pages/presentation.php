<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <a href="/dashboard" class="text-gray-400 hover:text-brand-600 text-sm">&larr; Dashboard</a>
                <span class="text-gray-300">/</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?php
                    echo match ($presentation['status']) {
                        'draft'         => 'bg-gray-100 text-gray-700',
                        'outline_ready' => 'bg-blue-100 text-blue-700',
                        'slides_ready'  => 'bg-purple-100 text-purple-700',
                        'audio_ready'   => 'bg-green-100 text-green-700',
                        'video_ready'   => 'bg-emerald-100 text-emerald-700',
                        'exported'      => 'bg-amber-100 text-amber-700',
                        default         => 'bg-gray-100 text-gray-700',
                    };
                    ?>">
                    <?= ucfirst(str_replace('_', ' ', e($presentation['status']))) ?>
                </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($presentation['title']) ?></h1>
            <p class="text-gray-500 text-sm mt-1">
                <?= e($presentation['audience']) ?> &middot; <?= $presentation['duration_minutes'] ?> min &middot; <?= ucfirst(e($presentation['tone'])) ?>
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="addSlide()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">+ Add Slide</button>
            <button onclick="openSlideshow()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#9654; Preview</button>
            <button onclick="downloadPDF()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#128196; PDF</button>
            <button onclick="document.getElementById('upload-slides-input').click()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#128228; Upload Slides</button>
            <button onclick="duplicatePresentation()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-500 bg-white hover:bg-gray-50 transition">Duplicate</button>
            <button onclick="deletePresentation()" class="inline-flex items-center px-3 py-2 border border-red-200 rounded-lg text-xs font-medium text-red-500 bg-white hover:bg-red-50 transition">Delete</button>
            <input type="file" id="upload-slides-input" accept="image/png,image/jpeg,image/webp" multiple class="hidden" onchange="uploadSlides(this.files)">
        </div>
    </div>

    <!-- Generation Progress Bar (hidden by default) -->
    <div id="progress-bar" class="hidden mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <span id="progress-message" class="text-sm font-medium text-gray-700">Generating...</span>
                <span id="progress-percent" class="text-sm text-gray-500">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progress-fill" class="bg-brand-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Slides -->
    <?php if (empty($slides)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <p class="text-gray-500">No slides yet. Something went wrong with outline generation.</p>
        <a href="/create" class="text-brand-600 hover:text-brand-700 text-sm mt-2 inline-block">Try creating again</a>
    </div>
    <?php else: ?>
    <div class="space-y-6" id="slides-container">
        <?php foreach ($slides as $slide): ?>
        <div class="slide-card bg-white rounded-xl border border-gray-200 overflow-hidden" data-slide-id="<?= $slide['id'] ?>" id="slide-<?= $slide['id'] ?>">

            <!-- Slide Preview -->
            <?php if (!empty($slide['image_url'])): ?>
            <div class="bg-gray-100 p-3 flex justify-center border-b border-gray-200">
                <img id="slide-preview-<?= $slide['id'] ?>"
                    src="<?= e($slide['image_url']) ?>"
                    alt="Slide <?= $slide['slide_order'] ?> preview"
                    class="rounded-lg shadow-md"
                    style="max-height: 280px; width: auto;">
            </div>
            <?php elseif (!empty($slide['html_content'])): ?>
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="text-xs text-purple-600">&#10024; Design ready — click <strong>Render Slides</strong> below to see preview</span>
                <img id="slide-preview-<?= $slide['id'] ?>" class="hidden rounded-lg shadow-md" style="max-height: 280px;">
            </div>
            <?php endif; ?>

            <div class="p-6">
                <!-- Slide Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-sm font-bold">
                            <?= $slide['slide_order'] ?>
                        </span>
                        <span class="text-xs text-gray-400 uppercase tracking-wide"><?= e($slide['layout_type']) ?></span>
                        <?php if (!empty($slide['html_content'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700">designed</span>
                        <?php endif; ?>
                        <?php if (!empty($slide['image_url'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">rendered</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="saveSlide(<?= $slide['id'] ?>)" class="text-xs text-brand-600 hover:text-brand-700 font-medium px-2 py-1 rounded hover:bg-brand-50 transition save-btn" data-slide-id="<?= $slide['id'] ?>" style="display:none;">
                            Save Changes
                        </button>
                        <button onclick="deleteSlide(<?= $slide['id'] ?>)" class="text-xs text-red-400 hover:text-red-600 px-2 py-1 rounded hover:bg-red-50 transition">
                            Remove
                        </button>
                    </div>
                </div>

                <!-- Title -->
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Slide Title</label>
                    <input type="text" value="<?= e($slide['title']) ?>"
                        class="slide-field w-full px-3 py-2 border border-gray-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition"
                        data-slide-id="<?= $slide['id'] ?>" data-field="title"
                        onchange="markDirty(<?= $slide['id'] ?>)">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Content -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Content (bullet points)</label>
                        <textarea rows="4"
                            class="slide-field w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none font-mono"
                            data-slide-id="<?= $slide['id'] ?>" data-field="content"
                            onchange="markDirty(<?= $slide['id'] ?>)"><?= e($slide['content']) ?></textarea>
                    </div>

                    <!-- Speaker Notes -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Speaker Notes (narration script)</label>
                        <textarea rows="4"
                            class="slide-field w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none"
                            data-slide-id="<?= $slide['id'] ?>" data-field="speaker_notes"
                            onchange="markDirty(<?= $slide['id'] ?>)"><?= e($slide['speaker_notes']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php
    $has_html = false;
    $has_images = false;
    foreach ($slides as $s) {
        if (!empty($s['html_content'])) $has_html = true;
        if (!empty($s['image_url'])) $has_images = true;
    }
    ?>

    <!-- Next Steps Bar -->
    <div class="mt-8 space-y-3">
        <!-- Save -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                <?= count($slides) ?> slide<?= count($slides) !== 1 ? 's' : '' ?> &middot; ~<?= $presentation['duration_minutes'] ?> min
            </div>
            <button onclick="saveAllSlides()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                Save All Changes
            </button>
        </div>

        <!-- Step-by-step pipeline -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-4">Pipeline</p>
            <div class="grid grid-cols-<?= $has_html ? ($has_images ? '4' : '3') : '2' ?> gap-3">

                <!-- Step 1: Design -->
                <button onclick="generateSlideDesigns()" id="btn-generate-slides"
                    class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_html ? 'border-green-200 bg-green-50' : 'border-purple-200 bg-purple-50 ring-2 ring-purple-200' ?> transition hover:shadow-sm text-center">
                    <span class="text-2xl mb-2"><?= $has_html ? '&#10003;' : '&#10024;' ?></span>
                    <span class="text-xs font-semibold <?= $has_html ? 'text-green-700' : 'text-purple-700' ?>">
                        <?= $has_html ? 'Redesign Slides' : 'Design Slides' ?>
                    </span>
                    <span class="text-xs text-gray-400 mt-1"><?= count($slides) * CREDIT_COSTS['generate_slide'] ?> credits</span>
                </button>

                <!-- Step 2: Render -->
                <?php if ($has_html): ?>
                <button onclick="renderAllSlides()" id="btn-render-slides"
                    class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_images ? 'border-green-200 bg-green-50' : 'border-blue-200 bg-blue-50 ring-2 ring-blue-200' ?> transition hover:shadow-sm text-center">
                    <span class="text-2xl mb-2"><?= $has_images ? '&#10003;' : '&#127912;' ?></span>
                    <span class="text-xs font-semibold <?= $has_images ? 'text-green-700' : 'text-blue-700' ?>">Render Previews</span>
                    <span class="text-xs text-gray-400 mt-1">Free</span>
                </button>
                <?php endif; ?>

                <!-- Step 3: Audio (Phase 3 — coming soon) -->
                <div class="flex flex-col items-center p-4 rounded-xl border-2 border-dashed border-gray-200 text-center opacity-50">
                    <span class="text-2xl mb-2">&#127908;</span>
                    <span class="text-xs font-semibold text-gray-400">Generate Audio</span>
                    <span class="text-xs text-gray-300 mt-1">Coming soon</span>
                </div>

                <!-- Step 4: Video (Phase 3 — coming soon) -->
                <?php if ($has_html): ?>
                <div class="flex flex-col items-center p-4 rounded-xl border-2 border-dashed border-gray-200 text-center opacity-50">
                    <span class="text-2xl mb-2">&#127916;</span>
                    <span class="text-xs font-semibold text-gray-400">Generate Video</span>
                    <span class="text-xs text-gray-300 mt-1">Coming soon</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="/assets/js/slide-renderer.js"></script>
<script src="/assets/js/slideshow.js"></script>

<script>
const PRESENTATION_ID = <?= $presentation['id'] ?>;
const dirtySlides = new Set();

// Slide data for rendering
const SLIDES_DATA = <?= json_encode(array_map(function($s) {
    return [
        'id' => $s['id'],
        'slide_order' => $s['slide_order'],
        'title' => $s['title'] ?? '',
        'html_content' => $s['html_content'] ?? null,
        'image_url' => $s['image_url'] ?? null,
    ];
}, $slides), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?>;

function markDirty(slideId) {
    dirtySlides.add(slideId);
    const btn = document.querySelector(`.save-btn[data-slide-id="${slideId}"]`);
    if (btn) btn.style.display = '';
}

async function saveSlide(slideId) {
    const card = document.getElementById(`slide-${slideId}`);
    const fields = card.querySelectorAll('.slide-field');
    const data = {};

    fields.forEach(field => {
        data[field.dataset.field] = field.value;
    });

    const btn = card.querySelector('.save-btn');
    if (btn) { btn.textContent = 'Saving...'; btn.disabled = true; }

    const result = await api(`/api/slides/${slideId}/update`, data);

    if (result.success) {
        dirtySlides.delete(slideId);
        if (btn) {
            btn.textContent = 'Saved!';
            setTimeout(() => { btn.style.display = 'none'; btn.textContent = 'Save Changes'; btn.disabled = false; }, 1500);
        }
    } else {
        alert(result.error || 'Failed to save');
        if (btn) { btn.textContent = 'Save Changes'; btn.disabled = false; }
    }
}

async function saveAllSlides() {
    for (const slideId of dirtySlides) { await saveSlide(slideId); }
}

async function addSlide() {
    const result = await api('/api/slides/add', {
        presentation_id: PRESENTATION_ID,
        title: 'New Slide',
        content: '- Add your content here\n- Second point\n- Third point',
        speaker_notes: '',
        layout_type: 'bullets',
    });
    if (result.success) location.reload();
    else alert(result.error || 'Failed to add slide');
}

async function deleteSlide(slideId) {
    if (!confirm('Delete this slide?')) return;
    const result = await api(`/api/slides/${slideId}/delete`);
    if (result.success) document.getElementById(`slide-${slideId}`).remove();
    else alert(result.error || 'Failed to delete');
}

// ── Phase 2: AI Slide Design Generation ──

function showProgress(message, percent) {
    const bar = document.getElementById('progress-bar');
    bar.classList.remove('hidden');
    document.getElementById('progress-message').textContent = message;
    document.getElementById('progress-percent').textContent = Math.round(percent) + '%';
    document.getElementById('progress-fill').style.width = percent + '%';
}

function hideProgress() {
    setTimeout(() => {
        document.getElementById('progress-bar').classList.add('hidden');
    }, 2000);
}

async function generateSlideDesigns() {
    const btn = document.getElementById('btn-generate-slides');
    btn.disabled = true;
    btn.textContent = 'Generating...';

    showProgress('Generating slide designs with AI...', 10);

    const result = await api(`/api/generate/slides/${PRESENTATION_ID}`);

    if (result.success) {
        showProgress(`${result.data.success_count} slides designed! Reloading...`, 100);
        setTimeout(() => location.reload(), 1500);
    } else {
        alert(result.error || 'Failed to generate slides');
        btn.disabled = false;
        btn.innerHTML = '&#10024; Design Slides';
        hideProgress();
    }
}

async function renderAllSlides() {
    const btn = document.getElementById('btn-render-slides');
    btn.disabled = true;
    btn.textContent = 'Rendering...';

    const slidesWithHtml = SLIDES_DATA.filter(s => s.html_content);
    if (slidesWithHtml.length === 0) {
        alert('No slides have been designed yet. Click "Design Slides" first.');
        btn.disabled = false;
        return;
    }

    const result = await SlideRenderer.renderAndUploadAll(
        PRESENTATION_ID,
        slidesWithHtml,
        (current, total, message) => {
            const pct = (current / total) * 100;
            showProgress(message, pct);
        }
    );

    showProgress(`Done! ${result.success} slides rendered, ${result.failed} failed.`, 100);
    hideProgress();
    btn.disabled = false;
    btn.innerHTML = '&#127912; Render Slides to Images';
}

// Auto-save on field change
document.querySelectorAll('.slide-field').forEach(field => {
    field.addEventListener('input', () => markDirty(parseInt(field.dataset.slideId)));
});

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', (e) => {
    if (dirtySlides.size > 0) { e.preventDefault(); e.returnValue = ''; }
});

// ── Project Management ──

async function deletePresentation() {
    if (!confirm('Delete this presentation and all its slides? This cannot be undone.')) return;
    const result = await api(`/api/presentations/${PRESENTATION_ID}`, { _action: 'delete' });
    if (result.success) window.location.href = '/dashboard';
    else alert(result.error || 'Failed to delete');
}

async function duplicatePresentation() {
    const result = await api(`/api/presentations/${PRESENTATION_ID}`, { _action: 'duplicate' });
    if (result.success) window.location.href = result.data.redirect;
    else alert(result.error || 'Failed to duplicate');
}

// ── Slideshow Preview ──

function openSlideshow() {
    const slidesForShow = SLIDES_DATA.map(s => ({
        image_url: s.image_url || null,
        title: s.title || `Slide ${s.slide_order}`,
        slide_order: s.slide_order,
    })).filter(s => s.image_url);

    if (slidesForShow.length === 0) {
        alert('No rendered slide images yet. Render your slides first.');
        return;
    }
    Slideshow.open(slidesForShow);
}

// ── Upload Custom Slides ──

async function uploadSlides(files) {
    if (!files || files.length === 0) return;

    showProgress('Uploading slides...', 10);

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('slides[]', files[i]);
    }
    formData.append('_csrf', CSRF_TOKEN);

    try {
        const res = await fetch(`/api/presentations/${PRESENTATION_ID}/upload-slides`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: formData,
        });
        const result = await res.json();

        if (result.success) {
            showProgress(`${result.data.uploaded} slides uploaded! Reloading...`, 100);
            setTimeout(() => location.reload(), 1000);
        } else {
            hideProgress();
            alert(result.error || 'Upload failed');
        }
    } catch (err) {
        hideProgress();
        alert('Upload failed. Check your connection.');
    }

    // Reset file input
    document.getElementById('upload-slides-input').value = '';
}

// ── Download PDF ──

function downloadPDF() {
    const hasImages = SLIDES_DATA.some(s => s.image_url);
    if (!hasImages) {
        alert('No rendered slides yet. Render your slides first.');
        return;
    }
    window.open(`/api/presentations/${PRESENTATION_ID}/download-pdf`, '_blank');
}
</script>
