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
        <div class="flex items-center space-x-3">
            <button onclick="addSlide()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                + Add Slide
            </button>
            <form method="POST" action="/presentation/<?= $presentation['id'] ?>/delete" onsubmit="return confirm('Delete this presentation and all its slides?')">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-200 rounded-lg text-sm font-medium text-red-600 bg-white hover:bg-red-50 transition">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Slides -->
    <?php if (empty($slides)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <p class="text-gray-500">No slides yet. Something went wrong with outline generation.</p>
        <a href="/create" class="text-brand-600 hover:text-brand-700 text-sm mt-2 inline-block">Try creating again</a>
    </div>
    <?php else: ?>
    <div class="space-y-4" id="slides-container">
        <?php foreach ($slides as $slide): ?>
        <div class="slide-card bg-white rounded-xl border border-gray-200 p-6" data-slide-id="<?= $slide['id'] ?>" id="slide-<?= $slide['id'] ?>">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-sm font-bold">
                        <?= $slide['slide_order'] ?>
                    </span>
                    <span class="text-xs text-gray-400 uppercase tracking-wide"><?= e($slide['layout_type']) ?></span>
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
                    <textarea rows="5"
                        class="slide-field w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none font-mono"
                        data-slide-id="<?= $slide['id'] ?>" data-field="content"
                        onchange="markDirty(<?= $slide['id'] ?>)"><?= e($slide['content']) ?></textarea>
                </div>

                <!-- Speaker Notes -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Speaker Notes (narration script)</label>
                    <textarea rows="5"
                        class="slide-field w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition resize-none"
                        data-slide-id="<?= $slide['id'] ?>" data-field="speaker_notes"
                        onchange="markDirty(<?= $slide['id'] ?>)"><?= e($slide['speaker_notes']) ?></textarea>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Actions -->
    <div class="mt-8 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            <?= count($slides) ?> slide<?= count($slides) !== 1 ? 's' : '' ?> &middot;
            ~<?= $presentation['duration_minutes'] ?> minutes
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="saveAllSlides()" class="inline-flex items-center px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                Save All Changes
            </button>
            <!-- Phase 2+ buttons will go here (Generate Slides, Generate Audio, Generate Video) -->
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const PRESENTATION_ID = <?= $presentation['id'] ?>;
const dirtySlides = new Set();

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

    const btn = card.querySelector(`.save-btn`);
    if (btn) {
        btn.textContent = 'Saving...';
        btn.disabled = true;
    }

    const result = await api(`/api/slides/${slideId}/update`, data);

    if (result.success) {
        dirtySlides.delete(slideId);
        if (btn) {
            btn.textContent = 'Saved!';
            setTimeout(() => {
                btn.style.display = 'none';
                btn.textContent = 'Save Changes';
                btn.disabled = false;
            }, 1500);
        }
    } else {
        alert(result.error || 'Failed to save slide');
        if (btn) {
            btn.textContent = 'Save Changes';
            btn.disabled = false;
        }
    }
}

async function saveAllSlides() {
    for (const slideId of dirtySlides) {
        await saveSlide(slideId);
    }
}

async function addSlide() {
    const result = await api('/api/slides/add', {
        presentation_id: PRESENTATION_ID,
        title: 'New Slide',
        content: '- Add your content here\n- Second point\n- Third point',
        speaker_notes: '',
        layout_type: 'bullets',
    });

    if (result.success) {
        location.reload();
    } else {
        alert(result.error || 'Failed to add slide');
    }
}

async function deleteSlide(slideId) {
    if (!confirm('Delete this slide?')) return;

    const result = await api(`/api/slides/${slideId}/delete`);
    if (result.success) {
        document.getElementById(`slide-${slideId}`).remove();
    } else {
        alert(result.error || 'Failed to delete slide');
    }
}

// Auto-save on field change
document.querySelectorAll('.slide-field').forEach(field => {
    field.addEventListener('input', () => markDirty(parseInt(field.dataset.slideId)));
});

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', (e) => {
    if (dirtySlides.size > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
