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
            <div class="flex items-center space-x-3 mt-1">
                <p class="text-gray-500 text-sm">
                    <?= e($presentation['audience']) ?> &middot; <?= $presentation['duration_minutes'] ?> min &middot; <?= ucfirst(e($presentation['tone'])) ?>
                </p>
                <!-- Template Switcher -->
                <div class="flex items-center space-x-1.5">
                    <span class="text-xs text-gray-400">Template:</span>
                    <select id="template-switcher" onchange="switchTemplate(this.value)"
                        class="text-xs border border-gray-200 rounded-md px-2 py-1 bg-white text-gray-700 cursor-pointer hover:border-brand-400 transition">
                        <?php
                        $tpl_stmt = get_db()->prepare('SELECT id, name FROM templates WHERE is_active = 1 ORDER BY sort_order');
                        $tpl_stmt->execute();
                        $all_templates = $tpl_stmt->fetchAll();
                        foreach ($all_templates as $tpl):
                        ?>
                        <option value="<?= $tpl['id'] ?>" <?= $tpl['id'] == $presentation['template_id'] ? 'selected' : '' ?>>
                            <?= e($tpl['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2 flex-wrap gap-y-2">
            <button onclick="addSlide()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">+ Add Slide</button>
            <button onclick="openSlideshow()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#9654; Preview</button>
            <button onclick="downloadPDF()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#128196; PDF</button>
            <button onclick="document.getElementById('upload-slides-input').click()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition">&#128228; Upload</button>
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

            <!-- Live Slide Preview (Gamma-style) -->
            <?php if (!empty($slide['html_content'])): ?>
            <div class="border-b border-gray-200 relative group">
                <!-- Wrapper: 16:9 ratio, scales 1920x1080 to fit -->
                <div class="slide-preview-wrapper bg-gray-900 rounded-t-xl overflow-hidden cursor-pointer" onclick="openSlideshow(<?= $slide['slide_order'] - 1 ?>)" style="position:relative;width:100%;padding-bottom:56.25%;" title="Click to preview slideshow">
                    <div class="slide-live-preview" id="live-preview-<?= $slide['id'] ?>"
                        style="position:absolute;top:0;left:0;width:1920px;height:1080px;transform-origin:top left;pointer-events:none;">
                        <?= $slide['html_content'] ?>
                    </div>
                </div>
                <!-- Overlay: Bottom action bar -->
                <div class="absolute bottom-0 left-0 right-0 opacity-0 group-hover:opacity-100 transition-opacity z-10 bg-gradient-to-t from-black/70 to-transparent pt-10 pb-3 px-3">
                    <div class="flex items-center justify-center space-x-2">
                        <button onclick="toggleSlideEdit(<?= $slide['id'] ?>)" id="edit-toggle-<?= $slide['id'] ?>"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-white/20 hover:bg-white/30 backdrop-blur shadow transition">
                            &#9998; Edit Text
                        </button>
                        <button onclick="showImageOptions(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-white/20 hover:bg-white/30 backdrop-blur shadow transition">
                            &#127748; Image
                        </button>
                        <button onclick="showBgOptions(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-white/20 hover:bg-white/30 backdrop-blur shadow transition">
                            &#127912; Background
                        </button>
                        <button onclick="changeSlideLayout(<?= $slide['id'] ?>, '<?= e($slide['layout_type']) ?>')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-white/20 hover:bg-white/30 backdrop-blur shadow transition">
                            &#9638; Layout
                        </button>
                        <button onclick="regenerateSlideDesign(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-purple-600/80 hover:bg-purple-600 backdrop-blur shadow transition">
                            &#10024; Regenerate
                        </button>
                        <button onclick="regenerateWithPrompt(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-brand-600/80 hover:bg-brand-600 backdrop-blur shadow transition">
                            &#10024; AI Edit
                        </button>
                    </div>
                </div>
                <!-- Hidden file inputs -->
                <input type="file" id="img-upload-<?= $slide['id'] ?>" accept="image/png,image/jpeg,image/webp" class="hidden"
                    onchange="uploadSlideImage(<?= $slide['id'] ?>, this.files[0])">
                <input type="file" id="bg-upload-<?= $slide['id'] ?>" accept="image/png,image/jpeg,image/webp" class="hidden"
                    onchange="uploadSlideBg(<?= $slide['id'] ?>, this.files[0])">

                <!-- Save bar (shown during manual edit) -->
                <div id="edit-bar-<?= $slide['id'] ?>" class="hidden absolute top-3 left-3 right-3 flex items-center justify-between z-10">
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-amber-500 shadow-lg">
                        Editing — click text on the slide to change it
                    </span>
                    <div class="flex items-center space-x-2">
                        <button onclick="cancelSlideEdit(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-gray-600 hover:bg-gray-700 shadow-lg transition">Cancel</button>
                        <button onclick="saveSlideEdit(<?= $slide['id'] ?>)"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-green-600 hover:bg-green-700 shadow-lg transition">Save Changes</button>
                    </div>
                </div>
                <!-- Hidden img for rendered PNG -->
                <img id="slide-preview-<?= $slide['id'] ?>" class="hidden"
                    src="<?= !empty($slide['image_url']) ? e($slide['image_url']) : '' ?>">
            </div>
            <?php elseif (!empty($slide['image_url'])): ?>
            <div class="bg-gray-100 p-3 flex justify-center border-b border-gray-200">
                <img id="slide-preview-<?= $slide['id'] ?>"
                    src="<?= e($slide['image_url']) ?>"
                    alt="Slide <?= $slide['slide_order'] ?> preview"
                    class="rounded-lg shadow-md"
                    loading="lazy"
                    style="max-height: 280px; width: auto;">
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
                        <?php if (!empty($slide['audio_url'])): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">audio</span>
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

                <!-- Audio Player (if audio exists) -->
                <?php if (!empty($slide['audio_url'])): ?>
                <div class="mb-4 flex items-center space-x-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <audio id="audio-<?= $slide['id'] ?>" src="<?= e($slide['audio_url']) ?>" preload="none"></audio>
                    <button onclick="toggleAudio(<?= $slide['id'] ?>)" id="audio-btn-<?= $slide['id'] ?>"
                        class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center text-sm hover:bg-amber-600 transition flex-shrink-0">
                        &#9654;
                    </button>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-medium text-amber-800">Narration audio</div>
                        <div class="text-xs text-amber-600">Click to preview</div>
                    </div>
                </div>
                <?php endif; ?>

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
    $has_audio = false;
    foreach ($slides as $s) {
        if (!empty($s['html_content'])) $has_html = true;
        if (!empty($s['image_url'])) $has_images = true;
        if (!empty($s['audio_url'])) $has_audio = true;
    }
    // Check for existing video
    $video = null;
    $video_stmt = get_db()->prepare('SELECT * FROM videos WHERE presentation_id = ? ORDER BY created_at DESC LIMIT 1');
    $video_stmt->execute([$presentation['id']]);
    $video = $video_stmt->fetch() ?: null;
    $has_video = $video && $video['status'] === 'complete';
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
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Pipeline</p>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-brand-100 text-brand-700" id="credits-display">
                        <?= e(current_user()['credits_balance']) ?> credits
                    </span>
                </div>
            </div>

            <!-- Pipeline progress tracker (hidden until action starts) -->
            <div id="pipeline-progress" class="hidden mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <span id="pipeline-step-label" class="text-sm font-medium text-gray-700"></span>
                    <span id="pipeline-percent" class="text-sm font-semibold text-brand-600"></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5">
                    <div id="pipeline-bar" class="h-2.5 rounded-full transition-all duration-300 bg-brand-600" style="width: 0%"></div>
                </div>
                <div class="flex items-center justify-between mt-1.5">
                    <span id="pipeline-detail" class="text-xs text-gray-400"></span>
                    <span id="pipeline-credits-used" class="text-xs text-amber-600 font-medium"></span>
                </div>
            </div>

            <div class="grid grid-cols-4 <?= $has_video ? 'sm:grid-cols-5' : '' ?> gap-3">

                <!-- Step 1: Design -->
                <button onclick="generateSlideDesigns()" id="btn-generate-slides"
                    class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_html ? 'border-green-200 bg-green-50' : 'border-purple-200 bg-purple-50 ring-2 ring-purple-200' ?> transition hover:shadow-sm text-center disabled:opacity-50">
                    <span class="text-2xl mb-2"><?= $has_html ? '&#10003;' : '&#10024;' ?></span>
                    <span class="text-xs font-semibold <?= $has_html ? 'text-green-700' : 'text-purple-700' ?>">
                        <?= $has_html ? 'Redesign' : 'Design Slides' ?>
                    </span>
                    <span class="text-xs text-gray-400 mt-1"><?= count($slides) * CREDIT_COSTS['generate_slide'] ?> cr</span>
                </button>

                <!-- Step 2: Render -->
                <button onclick="renderAllSlides()" id="btn-render-slides" <?= !$has_html ? 'disabled' : '' ?>
                    class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_images ? 'border-green-200 bg-green-50' : ($has_html ? 'border-blue-200 bg-blue-50' : 'border-dashed border-gray-200 opacity-40') ?> transition hover:shadow-sm text-center disabled:opacity-40">
                    <span class="text-2xl mb-2"><?= $has_images ? '&#10003;' : '&#127912;' ?></span>
                    <span class="text-xs font-semibold <?= $has_images ? 'text-green-700' : ($has_html ? 'text-blue-700' : 'text-gray-400') ?>">Render</span>
                    <span class="text-xs text-gray-400 mt-1"><?= !$has_html ? 'Design first' : 'Free' ?></span>
                </button>

                <!-- Step 3: Audio + voice selector -->
                <div class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_audio ? 'border-green-200 bg-green-50' : ($has_html ? 'border-amber-200 bg-amber-50' : 'border-dashed border-gray-200 opacity-40') ?> text-center">
                    <span class="text-2xl mb-1"><?= $has_audio ? '&#10003;' : '&#127908;' ?></span>
                    <?php if ($has_html): ?>
                    <div class="flex items-center space-x-1 mb-1">
                        <select id="voice-selector" class="text-xs border border-gray-300 rounded px-1 py-0.5 bg-white">
                            <option value="alloy">Alloy</option>
                            <option value="nova">Nova</option>
                            <option value="echo">Echo</option>
                            <option value="shimmer">Shimmer</option>
                            <option value="onyx">Onyx</option>
                            <option value="fable">Fable</option>
                        </select>
                        <button onclick="previewVoice(document.getElementById('voice-selector').value)" title="Preview"
                            class="w-5 h-5 rounded-full bg-amber-200 text-amber-700 flex items-center justify-center text-xs hover:bg-amber-300 transition">&#9654;</button>
                    </div>
                    <button onclick="generateAudio()" id="btn-generate-audio"
                        class="text-xs font-semibold <?= $has_audio ? 'text-green-700' : 'text-amber-700' ?> hover:underline">
                        <?= $has_audio ? 'Redo Audio' : 'Audio' ?>
                    </button>
                    <span class="text-xs text-gray-400 mt-0.5"><?= count($slides) * CREDIT_COSTS['generate_audio'] ?> cr</span>
                    <?php else: ?>
                    <span class="text-xs font-semibold text-gray-400">Audio</span>
                    <span class="text-xs text-gray-300 mt-1">Design first</span>
                    <?php endif; ?>
                </div>

                <!-- Step 4: Video — ALWAYS visible -->
                <button onclick="generateVideo()" id="btn-generate-video" <?= !$has_audio ? 'disabled' : '' ?>
                    class="flex flex-col items-center p-4 rounded-xl border-2 <?= $has_video ? 'border-green-200 bg-green-50' : ($has_audio ? 'border-red-200 bg-red-50 ring-2 ring-red-200' : 'border-dashed border-gray-200 opacity-40') ?> transition hover:shadow-sm text-center disabled:opacity-40">
                    <span class="text-2xl mb-2"><?= $has_video ? '&#10003;' : '&#127916;' ?></span>
                    <span class="text-xs font-semibold <?= $has_video ? 'text-green-700' : ($has_audio ? 'text-red-700' : 'text-gray-400') ?>">
                        <?= $has_video ? 'Redo Video' : 'Video' ?>
                    </span>
                    <span class="text-xs text-gray-400 mt-1"><?= !$has_audio ? 'Audio first' : CREDIT_COSTS['assemble_video'] . ' cr' ?></span>
                </button>

                <!-- Download (if video complete) -->
                <?php if ($has_video): ?>
                <a href="<?= e($video['file_url']) ?>" download
                    class="flex flex-col items-center p-4 rounded-xl border-2 border-green-300 bg-green-100 transition hover:shadow-sm text-center">
                    <span class="text-2xl mb-2">&#128229;</span>
                    <span class="text-xs font-semibold text-green-700">Download</span>
                    <span class="text-xs text-gray-400 mt-1"><?= $video['duration_seconds'] ? gmdate('i:s', $video['duration_seconds']) : 'MP4' ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Video Player (if video exists) -->
        <?php if ($has_video && !empty($video['file_url'])): ?>
        <?php if ($video['progress_message'] && str_contains($video['progress_message'], 'skipped')): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <p class="text-sm text-amber-800"><?= e($video['progress_message']) ?> Render all slides before generating video for a complete result.</p>
        </div>
        <?php endif; ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Your Video</p>
                <div class="flex items-center space-x-3">
                    <?php if ($video['duration_seconds']): ?>
                    <span class="text-xs text-gray-500"><?= gmdate('i:s', $video['duration_seconds']) ?></span>
                    <?php endif; ?>
                    <a href="<?= e($video['file_url']) ?>" download class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-medium text-white bg-green-600 hover:bg-green-700 transition">
                        Download MP4
                    </a>
                </div>
            </div>
            <div class="rounded-xl overflow-hidden bg-black" style="aspect-ratio:16/9;">
                <video controls class="w-full h-full" preload="metadata">
                    <source src="<?= e($video['file_url']) ?>" type="video/mp4">
                    Your browser does not support video playback.
                </video>
            </div>
        </div>
        <?php elseif ($video && $video['status'] === 'processing'): ?>
        <div class="bg-white rounded-xl border border-amber-200 p-6 text-center">
            <div class="animate-spin w-8 h-8 border-4 border-amber-200 border-t-amber-600 rounded-full mx-auto mb-3"></div>
            <p class="text-sm font-medium text-amber-700">Video is being assembled...</p>
            <p class="text-xs text-amber-500 mt-1"><?= e($video['progress_message'] ?? 'Processing') ?></p>
        </div>
        <?php endif; ?>
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
const saveTimers = {};
const savingSlides = new Set();

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

    // Debounce auto-save: wait 1.5s after last edit before saving
    if (saveTimers[slideId]) clearTimeout(saveTimers[slideId]);
    saveTimers[slideId] = setTimeout(() => saveSlide(slideId), 1500);
}

async function saveSlide(slideId) {
    // Prevent concurrent saves for the same slide
    if (savingSlides.has(slideId)) return;
    savingSlides.add(slideId);

    if (saveTimers[slideId]) { clearTimeout(saveTimers[slideId]); delete saveTimers[slideId]; }

    const card = document.getElementById(`slide-${slideId}`);
    const fields = card.querySelectorAll('.slide-field');
    const data = {};

    fields.forEach(field => {
        data[field.dataset.field] = field.value;
    });

    const btn = card.querySelector('.save-btn');
    if (btn) { btn.textContent = 'Saving...'; btn.disabled = true; }

    const result = await api(`/api/slides/${slideId}/update`, data);

    savingSlides.delete(slideId);

    if (result.success) {
        dirtySlides.delete(slideId);
        if (btn) {
            btn.textContent = 'Saved!';
            setTimeout(() => { btn.style.display = 'none'; btn.textContent = 'Save Changes'; btn.disabled = false; }, 1500);
        }
    } else {
        toast(result.error || 'Failed to save', 'error');
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
    else toast(result.error || 'Failed to add slide', 'error');
}

function deleteSlide(slideId) {
    confirmAction('Delete this slide?', async () => {
        const result = await api(`/api/slides/${slideId}/delete`);
        if (result.success) {
            document.getElementById(`slide-${slideId}`).remove();
            toast('Slide removed', 'success');
        } else {
            toast(result.error || 'Failed to delete', 'error');
        }
    });
}

// ── Pipeline Progress System ──

const SLIDE_COUNT = SLIDES_DATA.length;
const CREDIT_PER_SLIDE = <?= CREDIT_COSTS['generate_slide'] ?>;

function showPipelineProgress(step, detail, percent, creditsUsed = 0) {
    const el = document.getElementById('pipeline-progress');
    el.classList.remove('hidden');
    document.getElementById('pipeline-step-label').textContent = step;
    document.getElementById('pipeline-percent').textContent = Math.round(percent) + '%';
    document.getElementById('pipeline-bar').style.width = percent + '%';
    document.getElementById('pipeline-detail').textContent = detail;
    if (creditsUsed > 0) {
        document.getElementById('pipeline-credits-used').textContent = `${creditsUsed} credits used`;
    } else {
        document.getElementById('pipeline-credits-used').textContent = '';
    }
}

function hidePipelineProgress() {
    setTimeout(() => {
        document.getElementById('pipeline-progress').classList.add('hidden');
    }, 3000);
}

function updateCreditsDisplay(newBalance) {
    if (newBalance === undefined) return;
    const label = newBalance + ' credits';
    const el = document.getElementById('credits-display');
    const nav = document.getElementById('nav-credits-badge');
    if (el) el.textContent = label;
    if (nav) nav.textContent = label;
}

// Old progress bar (top of page) — keep for backward compat
function showProgress(message, percent) {
    const bar = document.getElementById('progress-bar');
    bar.classList.remove('hidden');
    document.getElementById('progress-message').textContent = message;
    document.getElementById('progress-percent').textContent = Math.round(percent) + '%';
    document.getElementById('progress-fill').style.width = percent + '%';
}
function hideProgress() {
    setTimeout(() => document.getElementById('progress-bar').classList.add('hidden'), 2000);
}

// ── Phase 2: AI Slide Design Generation ──

async function generateSlideDesigns() {
    const btn = document.getElementById('btn-generate-slides');
    btn.disabled = true;

    const totalCredits = SLIDE_COUNT * CREDIT_PER_SLIDE;
    showPipelineProgress('Designing slides with AI...', `0 of ${SLIDE_COUNT} slides`, 5, 0);

    // Simulate per-slide progress (API does all at once, but we show incremental)
    let fakeProgress = 5;
    const progressInterval = setInterval(() => {
        if (fakeProgress < 85) {
            fakeProgress += Math.random() * 8;
            const done = Math.min(SLIDE_COUNT, Math.floor((fakeProgress / 85) * SLIDE_COUNT));
            showPipelineProgress(
                'Designing slides with AI...',
                `${done} of ${SLIDE_COUNT} slides designed`,
                fakeProgress,
                done * CREDIT_PER_SLIDE
            );
        }
    }, 2000);

    const result = await api(`/api/generate/slides/${PRESENTATION_ID}`);
    clearInterval(progressInterval);

    if (result.success) {
        const used = result.data.credits_used || totalCredits;
        showPipelineProgress(
            'Design complete!',
            `${result.data.success_count} of ${SLIDE_COUNT} slides designed`,
            100,
            used
        );
        toast(`${result.data.success_count} slides designed! ${used} credits used.`, 'success', 3000);

        // Refresh credits in nav
        const me = await api('/api/auth/me', null, 'GET');
        if (me.success) updateCreditsDisplay(me.data.credits_balance);

        setTimeout(() => location.reload(), 2000);
    } else {
        toast(result.error || 'Failed to generate slides', 'error');
        btn.disabled = false;
        hidePipelineProgress();
    }
}

async function renderAllSlides() {
    const btn = document.getElementById('btn-render-slides');
    btn.disabled = true;

    const slidesWithHtml = SLIDES_DATA.filter(s => s.html_content);
    if (slidesWithHtml.length === 0) {
        toast('No slides designed yet. Click "Design Slides" first.', 'warning');
        btn.disabled = false;
        return;
    }

    const total = slidesWithHtml.length;

    const result = await SlideRenderer.renderAndUploadAll(
        PRESENTATION_ID,
        slidesWithHtml,
        (current, tot, message) => {
            const pct = (current / tot) * 100;
            showPipelineProgress(
                'Rendering slide images...',
                `${current} of ${tot} slides`,
                pct,
                0
            );
        }
    );

    showPipelineProgress(
        'Rendering complete!',
        `${result.success} rendered, ${result.failed} failed`,
        100,
        0
    );
    toast(`${result.success} slides rendered to images.`, 'success');
    hidePipelineProgress();
    btn.disabled = false;

    if (result.success > 0) {
        setTimeout(() => location.reload(), 1500);
    }
}

// Auto-save: debounced — saves 2 seconds after user stops typing
const autoSaveTimers = {};
document.querySelectorAll('.slide-field').forEach(field => {
    field.addEventListener('input', () => {
        const slideId = parseInt(field.dataset.slideId);
        markDirty(slideId);

        // Clear previous timer for this slide
        if (autoSaveTimers[slideId]) clearTimeout(autoSaveTimers[slideId]);

        // Set new timer — save after 2s of no typing
        autoSaveTimers[slideId] = setTimeout(() => {
            saveSlide(slideId);
        }, 2000);
    });
});

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', (e) => {
    if (dirtySlides.size > 0) { e.preventDefault(); e.returnValue = ''; }
});

// ── Project Management ──

// ── Scale live slide previews to fit their container ──

function scaleSlidePreviews() {
    document.querySelectorAll('.slide-preview-wrapper').forEach(wrapper => {
        const preview = wrapper.querySelector('.slide-live-preview');
        if (!preview) return;
        const scale = wrapper.offsetWidth / 1920;
        preview.style.transform = `scale(${scale})`;
    });
}
scaleSlidePreviews();
window.addEventListener('resize', scaleSlidePreviews);

// ── Template Switcher ──

async function switchTemplate(templateId) {
    // Save template choice
    const saveResult = await api(`/api/presentations/${PRESENTATION_ID}`, { template_id: templateId });
    if (!saveResult.success) {
        toast('Failed to save template', 'error');
        return;
    }

    // Ask if they want to redesign all slides with new template
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4" style="animation:fadeInUp 0.2s ease;">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Template Changed</h3>
            <p class="text-sm text-gray-500 mb-3">Do you want to redesign all slides with the new template?</p>
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg mb-4">
                <p class="text-xs text-amber-800"><strong>Warning:</strong> Redesigning will replace all slide visuals including any custom images you uploaded. Text content and narration are preserved.</p>
            </div>
            <p class="text-xs text-gray-400 mb-4">Cost: ${SLIDE_COUNT * CREDIT_PER_SLIDE} credits</p>
            <div class="flex justify-end space-x-3">
                <button onclick="this.closest('[style]').remove(); toast('Template saved for new slides. Current designs unchanged.', 'info')"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Keep Current Designs</button>
                <button onclick="this.closest('[style]').remove(); generateSlideDesigns()"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 transition">
                    &#10024; Redesign All Slides
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

// ── Drag to Reorder Slides ──

let draggedSlide = null;

function initDragReorder() {
    const container = document.getElementById('slides-container');
    if (!container) return;

    container.querySelectorAll('.slide-card').forEach(card => {
        // Add drag handle
        const header = card.querySelector('.flex.items-start');
        if (!header) return;

        const handle = document.createElement('div');
        handle.className = 'drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 mr-2 select-none';
        handle.innerHTML = '&#8942;&#8942;';
        handle.style.cssText = 'font-size:16px;line-height:1;letter-spacing:2px;padding:4px;';
        handle.draggable = true;
        header.insertBefore(handle, header.firstChild);

        card.addEventListener('dragstart', (e) => {
            draggedSlide = card;
            card.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', () => {
            draggedSlide = null;
            card.style.opacity = '1';
            container.querySelectorAll('.slide-card').forEach(c => c.classList.remove('border-t-4', 'border-brand-400'));
        });

        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            card.classList.add('border-t-4', 'border-brand-400');
        });

        card.addEventListener('dragleave', () => {
            card.classList.remove('border-t-4', 'border-brand-400');
        });

        card.addEventListener('drop', async (e) => {
            e.preventDefault();
            card.classList.remove('border-t-4', 'border-brand-400');

            if (!draggedSlide || draggedSlide === card) return;

            // Move in DOM
            container.insertBefore(draggedSlide, card);

            // Collect new order
            const slideIds = Array.from(container.querySelectorAll('.slide-card'))
                .map(c => parseInt(c.dataset.slideId));

            // Update slide numbers visually
            container.querySelectorAll('.slide-card').forEach((c, i) => {
                const numBadge = c.querySelector('.rounded-full.bg-brand-100');
                if (numBadge) numBadge.textContent = i + 1;
            });

            // Save to server
            const result = await api('/api/slides/reorder', {
                presentation_id: PRESENTATION_ID,
                slide_ids: slideIds,
            });

            if (result.success) {
                toast('Slides reordered', 'success', 2000);
            } else {
                toast('Failed to save order', 'error');
                location.reload();
            }
        });

        // Make the card draggable via handle only
        handle.addEventListener('dragstart', (e) => {
            e.stopPropagation();
            draggedSlide = card;
            card.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        card.draggable = false; // Only handle triggers drag
        handle.draggable = true;
    });
}

// Init on page load
document.addEventListener('DOMContentLoaded', initDragReorder);

// ── Image Options (AI generate, upload, background color) ──

function showImageOptions(slideId) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg mx-4 w-full" style="animation:fadeInUp 0.2s ease;">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Add Image to Slide</h3>
            <p class="text-sm text-gray-500 mb-4">Add a photo or illustration. It will be placed as the image element in your slide.</p>

            <!-- AI Generate -->
            <div class="mb-4 p-4 bg-purple-50 rounded-xl border border-purple-200">
                <label class="block text-xs font-semibold text-purple-700 mb-2">Generate with AI (<?= CREDIT_COSTS['generate_image'] ?> credits)</label>
                <div class="flex space-x-2">
                    <input type="text" id="ai-image-prompt-${slideId}" placeholder="e.g., dogs and cats playing together, professional photo"
                        class="flex-1 px-3 py-2 border border-purple-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-400 outline-none">
                    <button onclick="generateSlideImage(${slideId}); this.closest('[style]').remove();"
                        class="px-4 py-2 rounded-lg text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 transition whitespace-nowrap">
                        &#10024; Generate
                    </button>
                </div>
            </div>

            <!-- Upload -->
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Upload Your Own</label>
                <button onclick="document.getElementById('img-upload-${slideId}').click(); this.closest('[style]').remove();"
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-brand-400 hover:text-brand-600 transition">
                    Choose File (PNG, JPG, WebP)
                </button>
            </div>

            <button onclick="this.closest('[style]').remove()" class="mt-4 w-full py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

function showBgOptions(slideId) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md mx-4 w-full" style="animation:fadeInUp 0.2s ease;">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Slide Background</h3>
            <p class="text-sm text-gray-500 mb-4">Change the background. All text and content stays the same.</p>

            <!-- Color -->
            <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Solid Color</label>
                <div class="flex items-center space-x-2">
                    <input type="color" id="bg-color-${slideId}" value="#1e3a5f" class="w-10 h-10 rounded border cursor-pointer">
                    <button onclick="applyBgColor(${slideId}); this.closest('[style]').remove();"
                        class="flex-1 py-2.5 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 transition">Apply Color</button>
                </div>
            </div>

            <!-- Gradient presets -->
            <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Gradient Presets</label>
                <div class="flex space-x-2">
                    ${['linear-gradient(135deg,#667eea,#764ba2)','linear-gradient(135deg,#1e3a5f,#3498db)','linear-gradient(135deg,#0a0a0a,#1a1a2e)','linear-gradient(135deg,#ff6b6b,#ee5a24)','linear-gradient(135deg,#00b894,#00cec9)','linear-gradient(135deg,#ffeaa7,#fdcb6e)']
                        .map(g => `<button onclick="applyBgGradient(${slideId},'${g}'); this.closest('[style]').remove();"
                            style="width:36px;height:36px;border-radius:8px;background:${g};border:2px solid #e5e7eb;cursor:pointer;" class="hover:scale-110 transition"></button>`).join('')}
                </div>
            </div>

            <!-- Upload BG image -->
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Background Image</label>
                <button onclick="document.getElementById('bg-upload-${slideId}').click(); this.closest('[style]').remove();"
                    class="w-full py-2.5 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-brand-400 hover:text-brand-600 transition">
                    Upload Image
                </button>
            </div>

            <button onclick="this.closest('[style]').remove()" class="mt-4 w-full py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

async function generateSlideImage(slideId) {
    const prompt = document.getElementById(`ai-image-prompt-${slideId}`)?.value?.trim();
    if (!prompt) { toast('Describe the image you want', 'warning'); return; }

    const wrapper = document.querySelector(`#slide-${slideId} .slide-preview-wrapper`);
    let loadingEl = showSlideLoading(wrapper, 'Generating image...');

    const result = await api('/api/generate/image', { prompt, slide_id: slideId });
    if (loadingEl) loadingEl.remove();

    if (result.success && result.data.image_url) {
        const preview = document.getElementById(`live-preview-${slideId}`);
        if (preview) {
            applyImageToSlide(preview, result.data.image_url);
            await api(`/api/slides/${slideId}/update`, { html_content: preview.innerHTML, image_url: '' });
        }

        const me = await api('/api/auth/me', null, 'GET');
        if (me.success) updateCreditsDisplay(me.data.credits_balance);

        toast(`Image generated! ${result.data.credits_used} credits used.`, 'success');
    } else {
        toast(result.error || 'Image generation failed', 'error');
    }
}

/**
 * Apply an image to the correct place in a slide.
 * Priority: 1) Replace existing <img> src  2) Replace placeholder div  3) Set as background
 */
function applyImageToSlide(preview, imageUrl) {
    // 1. Try to find an existing <img> tag and replace its src
    const existingImg = preview.querySelector('img');
    if (existingImg) {
        existingImg.src = imageUrl;
        existingImg.style.objectFit = 'cover';
        return;
    }

    // 2. Try to find a placeholder div (gradient box with no text, likely the image area)
    //    Look for divs that have a gradient/solid background and no meaningful text
    const allDivs = preview.querySelectorAll('div');
    for (const div of allDivs) {
        const bg = div.style.background || div.style.backgroundImage || '';
        const hasGradient = bg.includes('gradient') || bg.includes('linear');
        const hasNoText = (div.innerText || '').trim().length < 5;
        const isLargeEnough = div.offsetWidth > 200 && div.offsetHeight > 200;

        if (hasGradient && hasNoText && isLargeEnough) {
            // Replace placeholder with image
            div.style.backgroundImage = `url(${imageUrl})`;
            div.style.backgroundSize = 'cover';
            div.style.backgroundPosition = 'center';
            div.innerHTML = ''; // Remove any placeholder icon
            return;
        }
    }

    // 3. Fallback: set as background on the root slide element
    const slideRoot = preview.firstElementChild;
    if (slideRoot) {
        slideRoot.style.backgroundImage = `url(${imageUrl})`;
        slideRoot.style.backgroundSize = 'cover';
        slideRoot.style.backgroundPosition = 'center';
    }
}

/**
 * Show a loading overlay on a slide wrapper. Returns the element to remove later.
 */
function showSlideLoading(wrapper, message) {
    if (!wrapper) return null;
    const el = document.createElement('div');
    el.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:20;border-radius:12px;';
    el.innerHTML = `<div style="text-align:center;"><div class="animate-spin" style="width:32px;height:32px;border:3px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;margin:0 auto 12px;"></div><div style="color:#fff;font-size:13px;">${message}</div></div>`;
    wrapper.style.position = 'relative';
    wrapper.appendChild(el);
    return el;
}

// Upload image → replaces <img> or placeholder in the slide (smart placement)
async function uploadSlideImage(slideId, file) {
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { toast('Max 10MB', 'warning'); return; }

    const reader = new FileReader();
    reader.onload = async function(e) {
        const preview = document.getElementById(`live-preview-${slideId}`);
        if (!preview) return;
        applyImageToSlide(preview, e.target.result);
        const result = await api(`/api/slides/${slideId}/update`, { html_content: preview.innerHTML, image_url: '' });
        if (result.success) toast('Image added to slide!', 'success');
        else toast(result.error || 'Failed to save', 'error');
    };
    reader.readAsDataURL(file);
    document.getElementById(`img-upload-${slideId}`).value = '';
}

// Upload background → sets as background-image on the root slide element
async function uploadSlideBg(slideId, file) {
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { toast('Max 10MB', 'warning'); return; }

    const reader = new FileReader();
    reader.onload = async function(e) {
        const preview = document.getElementById(`live-preview-${slideId}`);
        if (!preview) return;
        const slideRoot = preview.firstElementChild;
        if (slideRoot) {
            slideRoot.style.backgroundImage = `url(${e.target.result})`;
            slideRoot.style.backgroundSize = 'cover';
            slideRoot.style.backgroundPosition = 'center';
        }
        const result = await api(`/api/slides/${slideId}/update`, { html_content: preview.innerHTML, image_url: '' });
        if (result.success) toast('Background updated!', 'success');
        else toast(result.error || 'Failed to save', 'error');
    };
    reader.readAsDataURL(file);
    document.getElementById(`bg-upload-${slideId}`).value = '';
}

function applyBgGradient(slideId, gradient) {
    const preview = document.getElementById(`live-preview-${slideId}`);
    if (!preview) return;
    const slideRoot = preview.firstElementChild;
    if (slideRoot) {
        slideRoot.style.background = gradient;
        slideRoot.style.backgroundImage = gradient;
    }
    api(`/api/slides/${slideId}/update`, { html_content: preview.innerHTML, image_url: '' })
        .then(r => r.success ? toast('Gradient applied!', 'success') : toast('Failed to save', 'error'));
}

function applyBgColor(slideId) {
    const color = document.getElementById(`bg-color-${slideId}`).value;
    const preview = document.getElementById(`live-preview-${slideId}`);
    if (!preview) return;

    const slideRoot = preview.firstElementChild;
    if (slideRoot) {
        slideRoot.style.background = color;
        slideRoot.style.backgroundImage = 'none';
    }

    // Save
    api(`/api/slides/${slideId}/update`, {
        html_content: preview.innerHTML,
        image_url: '',
    }).then(result => {
        if (result.success) toast('Background color applied!', 'success');
        else toast(result.error || 'Failed to save', 'error');
    });
}

// ── Change Layout Type ──

function changeSlideLayout(slideId, currentLayout) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';

    const layouts = [
        { id: 'title', name: 'Title Slide', desc: 'Big centered title with subtitle' },
        { id: 'bullets', name: 'Bullets', desc: 'Title + bullet points' },
        { id: 'quote', name: 'Quote', desc: 'Featured quote or key insight' },
        { id: 'image_left', name: 'Image Left', desc: 'Visual on left, text on right' },
        { id: 'image_right', name: 'Image Right', desc: 'Text on left, visual on right' },
        { id: 'two_column', name: 'Two Column', desc: 'Side-by-side comparison' },
    ];

    const layoutHtml = layouts.map(l => `
        <button onclick="applyLayout(${slideId}, '${l.id}'); this.closest('[style]').remove();"
            class="flex items-center p-3 rounded-lg border ${l.id === currentLayout ? 'border-brand-500 bg-brand-50' : 'border-gray-200 hover:border-brand-300 hover:bg-gray-50'} transition text-left w-full">
            <div>
                <div class="text-sm font-medium text-gray-900">${l.name}</div>
                <div class="text-xs text-gray-500">${l.desc}</div>
            </div>
            ${l.id === currentLayout ? '<span class="ml-auto text-brand-600 text-xs font-medium">Current</span>' : ''}
        </button>
    `).join('');

    overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm mx-4 w-full" style="animation:fadeInUp 0.2s ease;">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Change Layout</h3>
            <div class="space-y-2">${layoutHtml}</div>
            <p class="text-xs text-gray-400 mt-4 text-center">Changing layout will regenerate this slide's design (${CREDIT_PER_SLIDE} credits)</p>
            <button onclick="this.closest('[style]').remove()" class="mt-3 w-full py-2 text-sm text-gray-500 hover:text-gray-700">Cancel</button>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

async function applyLayout(slideId, layout) {
    // Update layout in DB first
    await api(`/api/slides/${slideId}/update`, { layout_type: layout });
    // Then regenerate design with new layout
    toast(`Switching to ${layout} layout...`, 'info', 2000);
    await regenerateSlideDesign(slideId);
}

// ── Replace Entire Slide (nuclear option — from header Upload button) ──

async function uploadSingleSlide(slideId, file) {
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { toast('Max 10MB', 'warning'); return; }

    toast('Replacing slide...', 'info', 2000);
    const reader = new FileReader();
    reader.onload = async function(e) {
        const result = await api(`/api/slides/${slideId}/upload-image`, { image_data: e.target.result });
        if (result.success) { toast('Slide replaced!', 'success'); location.reload(); }
        else toast(result.error || 'Failed', 'error');
    };
    reader.readAsDataURL(file);
}

// ── Inline Slide Editing (WYSIWYG) ──

const editBackups = {}; // Store original HTML before editing

function toggleSlideEdit(slideId) {
    const preview = document.getElementById(`live-preview-${slideId}`);
    const editBar = document.getElementById(`edit-bar-${slideId}`);
    const toggleBtn = document.getElementById(`edit-toggle-${slideId}`);
    if (!preview) return;

    const isEditing = preview.style.pointerEvents === 'auto';

    if (isEditing) {
        // Exit edit mode
        cancelSlideEdit(slideId);
    } else {
        // Enter edit mode — backup HTML, make text editable
        editBackups[slideId] = preview.innerHTML;

        // Enable pointer events on the scaled slide
        preview.style.pointerEvents = 'auto';
        preview.style.cursor = 'text';

        // Make all text elements contenteditable
        preview.querySelectorAll('h1,h2,h3,h4,h5,h6,p,span,li,div').forEach(el => {
            // Only make leaf text nodes editable (not containers with children that are also text)
            if (el.children.length === 0 || el.innerText.trim().length > 0) {
                el.contentEditable = 'true';
                el.style.outline = 'none';
                el.addEventListener('focus', function() {
                    this.style.outline = '2px solid rgba(59,130,246,0.5)';
                    this.style.outlineOffset = '2px';
                    this.style.borderRadius = '4px';
                });
                el.addEventListener('blur', function() {
                    this.style.outline = 'none';
                });
            }
        });

        // Show save bar, update button
        editBar.classList.remove('hidden');
        toggleBtn.innerHTML = '&#10005; Exit Edit';
        toggleBtn.classList.replace('bg-gray-700', 'bg-amber-600');

        toast('Click any text on the slide to edit it directly', 'info', 3000);
    }
}

function cancelSlideEdit(slideId) {
    const preview = document.getElementById(`live-preview-${slideId}`);
    const editBar = document.getElementById(`edit-bar-${slideId}`);
    const toggleBtn = document.getElementById(`edit-toggle-${slideId}`);
    if (!preview) return;

    // Restore original HTML
    if (editBackups[slideId]) {
        preview.innerHTML = editBackups[slideId];
        delete editBackups[slideId];
    }

    // Disable editing
    preview.style.pointerEvents = 'none';
    preview.style.cursor = 'default';
    editBar.classList.add('hidden');
    toggleBtn.innerHTML = '&#9998; Edit Slide';
    toggleBtn.classList.replace('bg-amber-600', 'bg-gray-700');

    scaleSlidePreviews();
}

async function saveSlideEdit(slideId) {
    const preview = document.getElementById(`live-preview-${slideId}`);
    const editBar = document.getElementById(`edit-bar-${slideId}`);
    const toggleBtn = document.getElementById(`edit-toggle-${slideId}`);
    if (!preview) return;

    // Remove contenteditable and outlines before saving
    preview.querySelectorAll('[contenteditable]').forEach(el => {
        el.contentEditable = 'false';
        el.style.outline = 'none';
        el.style.outlineOffset = '';
        el.style.borderRadius = '';
    });

    // Get the modified HTML
    const newHtml = preview.innerHTML;

    // Disable editing UI
    preview.style.pointerEvents = 'none';
    preview.style.cursor = 'default';
    editBar.classList.add('hidden');
    toggleBtn.innerHTML = '&#9998; Edit Slide';
    toggleBtn.classList.replace('bg-amber-600', 'bg-gray-700');

    // Save to server
    const result = await api(`/api/slides/${slideId}/update`, {
        html_content: newHtml,
        image_url: '', // Clear rendered image since design changed
    });

    if (result.success) {
        delete editBackups[slideId];
        toast('Slide design saved!', 'success');
    } else {
        toast(result.error || 'Failed to save', 'error');
        // Restore backup on failure
        if (editBackups[slideId]) {
            preview.innerHTML = editBackups[slideId];
            delete editBackups[slideId];
        }
    }

    scaleSlidePreviews();
}

// ── Gamma-style: Regenerate individual slide design ──

async function regenerateSlideDesign(slideId) {
    // Show loading overlay on the slide
    const wrapper = document.querySelector(`#slide-${slideId} .slide-preview-wrapper`);
    let loadingEl = null;
    if (wrapper) {
        loadingEl = document.createElement('div');
        loadingEl.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:20;border-radius:12px;';
        loadingEl.innerHTML = '<div style="text-align:center;"><div class="animate-spin" style="width:32px;height:32px;border:3px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;margin:0 auto 12px;"></div><div style="color:#fff;font-size:13px;font-weight:500;">Regenerating design...</div></div>';
        wrapper.style.position = 'relative';
        wrapper.appendChild(loadingEl);
    }

    const result = await api(`/api/slides/${slideId}/regenerate-design`);

    // Remove loading overlay
    if (loadingEl) loadingEl.remove();

    if (result.success) {
        const preview = document.getElementById(`live-preview-${slideId}`);
        if (preview) { preview.innerHTML = result.data.html; scaleSlidePreviews(); }

        const me = await api('/api/auth/me', null, 'GET');
        if (me.success) updateCreditsDisplay(me.data.credits_balance);

        toast(`Slide redesigned! ${result.data.credits_used} credits used.`, 'success');
    } else {
        toast(result.error || 'Failed to regenerate', 'error');
    }
}

function regenerateWithPrompt(slideId) {
    // Show prompt input overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-lg mx-4 w-full" style="animation:fadeInUp 0.2s ease;">
            <h3 class="text-lg font-bold text-gray-900 mb-2">Edit Slide Design with AI</h3>
            <p class="text-sm text-gray-500 mb-4">Describe how you want this slide to look different.</p>
            <textarea id="design-prompt" rows="3" placeholder="e.g., Make it more colorful, add a gradient background, use larger text, make it minimal..."
                class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none resize-none mb-4"></textarea>
            <div class="flex justify-end space-x-3">
                <button onclick="this.closest('[style]').remove()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button onclick="submitDesignPrompt(${slideId}, this)" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 transition">
                    &#10024; Regenerate (${CREDIT_PER_SLIDE} credits)
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    document.getElementById('design-prompt').focus();
}

async function submitDesignPrompt(slideId, btn) {
    const prompt = document.getElementById('design-prompt').value.trim();
    if (!prompt) {
        toast('Please describe what you want changed', 'warning');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Generating...';

    const result = await api(`/api/slides/${slideId}/regenerate-design`, { prompt });

    // Close overlay
    btn.closest('[style]').remove();

    if (result.success) {
        const preview = document.getElementById(`live-preview-${slideId}`);
        if (preview) { preview.innerHTML = result.data.html; scaleSlidePreviews(); }

        const me = await api('/api/auth/me', null, 'GET');
        if (me.success) updateCreditsDisplay(me.data.credits_balance);

        toast('Slide redesigned with your instructions!', 'success');
    } else {
        toast(result.error || 'Failed to regenerate', 'error');
    }
}

// ── Phase 3: Audio Generation ──

// ── Voice Preview ──

let previewAudioEl = null;
async function previewVoice(voice) {
    // Stop any current preview
    if (previewAudioEl) { previewAudioEl.pause(); previewAudioEl = null; }

    toast('Generating voice preview...', 'info', 3000);

    const result = await api('/api/preview-voice', { voice });

    if (result.success && result.data.audio_data) {
        previewAudioEl = new Audio(result.data.audio_data);
        previewAudioEl.play();
        previewAudioEl.onended = () => { previewAudioEl = null; };
    } else {
        toast(result.error || 'Voice preview failed', 'error');
    }
}

// ── Audio Playback ──

function toggleAudio(slideId) {
    const audio = document.getElementById(`audio-${slideId}`);
    const btn = document.getElementById(`audio-btn-${slideId}`);
    if (!audio) return;

    // Stop all other audio first
    document.querySelectorAll('audio').forEach(a => {
        if (a.id !== `audio-${slideId}`) { a.pause(); a.currentTime = 0; }
    });
    document.querySelectorAll('[id^="audio-btn-"]').forEach(b => b.innerHTML = '&#9654;');

    if (audio.paused) {
        audio.play();
        btn.innerHTML = '&#9646;&#9646;';
        audio.onended = () => { btn.innerHTML = '&#9654;'; };
    } else {
        audio.pause();
        btn.innerHTML = '&#9654;';
    }
}

async function generateAudio() {
    const btn = document.getElementById('btn-generate-audio');
    btn.disabled = true;

    const voice = document.getElementById('voice-selector')?.value || 'alloy';
    const audioCost = SLIDE_COUNT * <?= CREDIT_COSTS['generate_audio'] ?>;
    showPipelineProgress('Generating narration audio...', `0 of ${SLIDE_COUNT} slides`, 5, 0);

    let fakeProgress = 5;
    const interval = setInterval(() => {
        if (fakeProgress < 85) {
            fakeProgress += Math.random() * 6;
            const done = Math.min(SLIDE_COUNT, Math.floor((fakeProgress / 85) * SLIDE_COUNT));
            showPipelineProgress('Generating narration audio...', `${done} of ${SLIDE_COUNT} slides`, fakeProgress, done * <?= CREDIT_COSTS['generate_audio'] ?>);
        }
    }, 3000);

    const result = await api(`/api/generate/audio/${PRESENTATION_ID}`, { voice });
    clearInterval(interval);

    if (result.success) {
        showPipelineProgress('Audio complete!', `${result.data.success_count} slides narrated`, 100, result.data.credits_used);
        toast(`Audio generated for ${result.data.success_count} slides. ${result.data.credits_used} credits used.`, 'success');

        const me = await api('/api/auth/me', null, 'GET');
        if (me.success) updateCreditsDisplay(me.data.credits_balance);

        setTimeout(() => location.reload(), 2000);
    } else {
        toast(result.error || 'Audio generation failed', 'error');
        btn.disabled = false;
        hidePipelineProgress();
    }
}

// ── Phase 3: Video Generation ──

async function generateVideo() {
    const btn = document.getElementById('btn-generate-video');
    btn.disabled = true;

    showPipelineProgress('Queueing video assembly...', 'Preparing', 10, <?= CREDIT_COSTS['assemble_video'] ?>);

    const result = await api(`/api/generate/video/${PRESENTATION_ID}`);

    if (!result.success) {
        toast(result.error || 'Failed to queue video', 'error');
        btn.disabled = false;
        hidePipelineProgress();
        return;
    }

    toast('Video queued! Processing will begin shortly.', 'info');
    const videoId = result.data.video_id;

    const me = await api('/api/auth/me', null, 'GET');
    if (me.success) updateCreditsDisplay(me.data.credits_balance);

    // Poll for status
    pollVideoStatus(videoId);
}

async function pollVideoStatus(videoId) {
    const poll = async () => {
        const res = await api(`/api/videos/${videoId}/status`, null, 'GET');
        if (!res.success) return;

        const { status, progress_message, file_url } = res.data;

        if (status === 'processing') {
            showPipelineProgress('Assembling video...', progress_message || 'Processing...', 50, <?= CREDIT_COSTS['assemble_video'] ?>);
            setTimeout(poll, 3000);
        } else if (status === 'complete') {
            showPipelineProgress('Video complete!', 'Ready to download', 100, <?= CREDIT_COSTS['assemble_video'] ?>);
            toast('Video is ready! Reloading...', 'success');
            setTimeout(() => location.reload(), 2000);
        } else if (status === 'failed') {
            showPipelineProgress('Video failed', progress_message || 'An error occurred', 100, 0);
            toast('Video generation failed. Try again.', 'error');
            hidePipelineProgress();
        } else if (status === 'queued') {
            showPipelineProgress('Waiting in queue...', 'Your video will be processed shortly', 15, <?= CREDIT_COSTS['assemble_video'] ?>);
            setTimeout(poll, 5000);
        }
    };

    poll();
}

// ── Project Management ──

function deletePresentation() {
    confirmAction('Delete this presentation and all its slides? This cannot be undone.', async () => {
        const result = await api(`/api/presentations/${PRESENTATION_ID}`, { _action: 'delete' });
        if (result.success) {
            toast('Deleted. Redirecting...', 'success');
            setTimeout(() => window.location.href = '/dashboard', 800);
        } else {
            toast(result.error || 'Failed to delete', 'error');
        }
    });
}

async function duplicatePresentation() {
    const result = await api(`/api/presentations/${PRESENTATION_ID}`, { _action: 'duplicate' });
    if (result.success) window.location.href = result.data.redirect;
    else toast(result.error || 'Failed to duplicate', 'error');
}

// ── Slideshow Preview ──

function openSlideshow(startAt = 0) {
    // Build slides from what's actually on the page (not from JS data which may be stale/truncated)
    const slidesForShow = SLIDES_DATA.map(s => {
        const livePreview = document.getElementById(`live-preview-${s.id}`);
        return {
            image_url: s.image_url || null,
            html_content: livePreview ? livePreview.innerHTML : (s.html_content || null),
            title: s.title || `Slide ${s.slide_order}`,
            slide_order: s.slide_order,
        };
    });

    if (slidesForShow.filter(s => s.image_url || s.html_content).length === 0) {
        toast('No slides to preview. Design your slides first.', 'warning');
        return;
    }
    Slideshow.open(slidesForShow, startAt);
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
            toast(result.error || 'Upload failed', 'error');
        }
    } catch (err) {
        hideProgress();
        toast('Upload failed. Check your connection.', 'error');
    }

    // Reset file input
    document.getElementById('upload-slides-input').value = '';
}

// ── Download PDF ──

function downloadPDF() {
    const hasContent = SLIDES_DATA.some(s => s.image_url || s.html_content);
    if (!hasContent) {
        toast('No slides to export. Design your slides first.', 'warning');
        return;
    }
    window.open(`/api/presentations/${PRESENTATION_ID}/download-pdf`, '_blank');
}
</script>
