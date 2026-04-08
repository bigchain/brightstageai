<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Presentations</h1>
            <p class="text-gray-500 mt-1"><?= $total_count ?> presentation<?= $total_count !== 1 ? 's' : '' ?></p>
        </div>
        <a href="/create" class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition">
            + New Presentation
        </a>
    </div>

    <!-- Credits Banner -->
    <div class="bg-gradient-to-r from-brand-600 to-brand-800 rounded-xl p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-brand-200 text-sm">Available Credits</p>
                <p class="text-3xl font-bold mt-1"><?= e($user['credits_balance']) ?></p>
                <p class="text-brand-200 text-xs mt-1">Plan: <?= ucfirst(e($user['plan'])) ?> &middot; A full presentation uses ~80-110 credits</p>
            </div>
            <div class="text-right">
                <a href="/billing" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-white text-brand-700 hover:bg-brand-50 transition">
                    Get More Credits
                </a>
            </div>
        </div>
    </div>

    <!-- Presentations Grid -->
    <?php if (empty($presentations)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
        <div class="text-5xl mb-4">&#127916;</div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">No presentations yet</h3>
        <p class="text-gray-500 mb-6">Create your first AI-powered video presentation — it takes about 2 minutes.</p>
        <a href="/create" class="inline-flex items-center px-6 py-3 rounded-lg text-sm font-medium text-white bg-brand-600 hover:bg-brand-700">
            Create Your First Presentation
        </a>
    </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($presentations as $pres): ?>
        <div class="slide-card bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-brand-300" data-pres-id="<?= $pres['id'] ?>">
            <a href="/presentation/<?= $pres['id'] ?>" class="block p-6 pb-3">
                <div class="flex items-start justify-between mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        <?php
                        echo match ($pres['status']) {
                            'draft'         => 'bg-gray-100 text-gray-700',
                            'outline_ready' => 'bg-blue-100 text-blue-700',
                            'slides_ready'  => 'bg-purple-100 text-purple-700',
                            'audio_ready'   => 'bg-green-100 text-green-700',
                            'video_ready'   => 'bg-emerald-100 text-emerald-700',
                            'exported'      => 'bg-amber-100 text-amber-700',
                            default         => 'bg-gray-100 text-gray-700',
                        };
                        ?>">
                        <?= ucfirst(str_replace('_', ' ', e($pres['status']))) ?>
                    </span>
                    <span class="text-xs text-gray-400"><?= $pres['slide_count'] ?> slides</span>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2"><?= e($pres['title']) ?></h3>
                <p class="text-sm text-gray-500 line-clamp-2 mb-2"><?= e($pres['topic']) ?></p>
                <div class="flex items-center text-xs text-gray-400 space-x-3">
                    <span><?= $pres['duration_minutes'] ?> min</span>
                    <span>&middot;</span>
                    <span><?= date('M j, Y', strtotime($pres['updated_at'])) ?></span>
                </div>
            </a>
            <!-- Actions -->
            <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                <a href="/presentation/<?= $pres['id'] ?>" class="flex-1 py-2.5 text-center text-xs font-medium text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition">
                    Edit
                </a>
                <button onclick="duplicateProject(<?= $pres['id'] ?>)" class="flex-1 py-2.5 text-center text-xs font-medium text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition">
                    Duplicate
                </button>
                <button onclick="renameProject(<?= $pres['id'] ?>, <?= e(json_encode($pres['title'])) ?>)" class="flex-1 py-2.5 text-center text-xs font-medium text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition">
                    Rename
                </button>
                <button onclick="deleteProject(<?= $pres['id'] ?>, <?= e(json_encode($pres['title'])) ?>)" class="flex-1 py-2.5 text-center text-xs font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 transition">
                    Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteProject(id, title) {
    confirmAction(`Delete "${title}"? This removes all slides and cannot be undone.`, async () => {
        const result = await api(`/api/presentations/${id}`, { _action: 'delete' });
        if (result.success) {
            document.querySelector(`[data-pres-id="${id}"]`).remove();
            toast('Presentation deleted', 'success');
        } else {
            toast(result.error || 'Failed to delete', 'error');
        }
    });
}

async function duplicateProject(id) {
    toast('Duplicating...', 'info', 2000);
    const result = await api(`/api/presentations/${id}`, { _action: 'duplicate' });
    if (result.success) {
        toast('Duplicated! Redirecting...', 'success');
        setTimeout(() => window.location.href = result.data.redirect, 800);
    } else {
        toast(result.error || 'Failed to duplicate', 'error');
    }
}

function renameProject(id, currentTitle) {
    promptInput('New title:', currentTitle, async (newTitle) => {
        const result = await api(`/api/presentations/${id}`, { title: newTitle });
        if (result.success) {
            toast('Renamed!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            toast(result.error || 'Failed to rename', 'error');
        }
    });
}
</script>
