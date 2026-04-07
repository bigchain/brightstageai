<!-- Landing Page -->
<div class="relative overflow-hidden">
    <!-- Hero -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
        <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight">
            <span class="text-gray-900">Topic In.</span>
            <span class="text-brand-600"> Video Out.</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-500">
            Turn any topic into a professional video presentation with AI-generated slides, narration, and a complete marketing media kit — in minutes.
        </p>
        <div class="mt-10 flex justify-center space-x-4">
            <a href="/register" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-brand-600 hover:bg-brand-700 shadow-lg shadow-brand-200">
                Start Free — 100 Credits
            </a>
            <a href="#how-it-works" class="inline-flex items-center px-8 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                See How It Works
            </a>
        </div>
        <p class="mt-4 text-sm text-gray-400">No credit card required. Create your first video presentation free.</p>
    </div>

    <!-- How It Works -->
    <div id="how-it-works" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-16">5 Steps. One Platform. Everything You Need.</h2>
            <div class="grid md:grid-cols-5 gap-6">
                <?php
                $steps = [
                    ['1', 'Enter Topic', 'Tell us your topic, audience, and duration. AI generates a complete outline.', 'bg-blue-50 text-blue-700'],
                    ['2', 'Design Slides', 'Pick a template. AI creates beautiful, designer-quality slides.', 'bg-purple-50 text-purple-700'],
                    ['3', 'Add Voice', 'Select a voice. AI writes and records professional narration.', 'bg-green-50 text-green-700'],
                    ['4', 'Export', 'Download as MP4 video, PowerPoint, or PDF. Share with a link.', 'bg-orange-50 text-orange-700'],
                    ['5', 'Media Kit', 'Generate social posts, emails, articles, and images to promote it.', 'bg-pink-50 text-pink-700'],
                ];
                foreach ($steps as [$num, $title, $desc, $color]):
                ?>
                <div class="text-center p-6 rounded-xl <?= $color ?> bg-opacity-50">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full font-bold text-sm mb-4 <?= $color ?>">
                        <?= $num ?>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2"><?= $title ?></h3>
                    <p class="text-sm text-gray-600"><?= $desc ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-16">Not Just a Video. A Complete Launch Kit.</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#127916;</div>
                    <h3 class="font-semibold text-lg mb-2">Pro Video Presentations</h3>
                    <p class="text-gray-500 text-sm">AI-designed slides with narration assembled into polished MP4 videos. Works on every device.</p>
                </div>
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#128231;</div>
                    <h3 class="font-semibold text-lg mb-2">Email Sequences</h3>
                    <p class="text-gray-500 text-sm">5 pre-event + 3 post-event emails. Ready to paste into Mailchimp, ConvertKit, or any ESP.</p>
                </div>
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#128247;</div>
                    <h3 class="font-semibold text-lg mb-2">Social Media Images</h3>
                    <p class="text-gray-500 text-sm">Platform-perfect images for Twitter, LinkedIn, Facebook, Instagram, and YouTube. Every size, ready to post.</p>
                </div>
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#128240;</div>
                    <h3 class="font-semibold text-lg mb-2">Blog Articles</h3>
                    <p class="text-gray-500 text-sm">SEO-friendly HTML articles ready for WordPress or any CMS. Promote your webinar organically.</p>
                </div>
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#128196;</div>
                    <h3 class="font-semibold text-lg mb-2">PowerPoint Export</h3>
                    <p class="text-gray-500 text-sm">Two options: beautiful visual PPTX or fully editable PowerPoint. Your slides, your way.</p>
                </div>
                <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100">
                    <div class="text-3xl mb-4">&#128200;</div>
                    <h3 class="font-semibold text-lg mb-2">Credit Dashboard</h3>
                    <p class="text-gray-500 text-sm">Track usage, manage subscriptions, top up credits. Full control over your account.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing -->
    <div class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 mb-4">Simple Pricing</h2>
            <p class="text-center text-gray-500 mb-16">Start free. Upgrade when you need more.</p>
            <div class="grid md:grid-cols-4 gap-6 max-w-5xl mx-auto">
                <?php
                $plans = [
                    ['Free', '$0', 'forever', '100 credits (one-time)', ['1 full presentation', 'All export formats', 'Try everything'], false],
                    ['Starter', '$19', '/month', '500 credits/month', ['~5 full presentations/mo', 'Media kit generator', 'Credit top-ups'], false],
                    ['Pro', '$49', '/month', '1,500 credits/month', ['~15 full presentations/mo', 'Everything in Starter', 'Priority support'], true],
                    ['Business', '$99', '/month', '4,000 credits/month', ['~40 full presentations/mo', 'Everything in Pro', 'Volume discounts'], false],
                ];
                foreach ($plans as [$name, $price, $period, $credits, $features, $popular]):
                ?>
                <div class="rounded-xl border <?= $popular ? 'border-brand-500 ring-2 ring-brand-200' : 'border-gray-200' ?> p-6 relative">
                    <?php if ($popular): ?>
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-xs font-medium px-3 py-1 rounded-full">Most Popular</span>
                    <?php endif; ?>
                    <h3 class="font-semibold text-lg"><?= $name ?></h3>
                    <div class="mt-4">
                        <span class="text-4xl font-bold"><?= $price ?></span>
                        <span class="text-gray-500 text-sm"><?= $period ?></span>
                    </div>
                    <p class="text-sm text-brand-600 font-medium mt-2"><?= $credits ?></p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <?php foreach ($features as $f): ?>
                        <li class="flex items-start">
                            <span class="text-green-500 mr-2">&#10003;</span>
                            <?= $f ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/register" class="mt-8 block text-center py-2 px-4 rounded-lg text-sm font-medium <?= $popular ? 'bg-brand-600 text-white hover:bg-brand-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <?= $name === 'Free' ? 'Start Free' : 'Get Started' ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="py-20 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Create Your First Video?</h2>
        <p class="text-gray-500 mb-8">100 free credits. No credit card. Takes 2 minutes.</p>
        <a href="/register" class="inline-flex items-center px-8 py-3 text-base font-medium rounded-lg text-white bg-brand-600 hover:bg-brand-700 shadow-lg shadow-brand-200">
            Get Started Free
        </a>
    </div>
</div>
