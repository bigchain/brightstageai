<?php
/**
 * Application constants and configuration.
 */

require_once __DIR__ . '/env.php';

// App
define('APP_NAME', 'BrightStage Video');
define('APP_URL', env('APP_URL', 'https://ai.brightstageai.com'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_ROOT', dirname(__DIR__, 2));

// Paths
define('STORAGE_PATH', APP_ROOT . '/storage');
define('TEMPLATE_PATH', APP_ROOT . '/src/templates');

// Session
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Credits
define('SIGNUP_BONUS_CREDITS', 100);

// Credit costs
define('CREDIT_COSTS', [
    'generate_outline'      => 5,
    'generate_slide'        => 2,  // per slide
    'generate_image'        => 3,  // per slide
    'generate_audio'        => 2,  // per slide
    'assemble_video'        => 10,
    'export_visual_pptx'    => 3,
    'export_editable_pptx'  => 5,
    'export_pdf'            => 3,
    'generate_media_kit'    => 25,
    'generate_social_posts' => 8,
    'generate_emails'       => 10,
    'generate_article'      => 8,
    'generate_press'        => 5,
    'generate_images'       => 10,
]);

// OpenRouter
define('OPENROUTER_API_KEY', env('OPENROUTER_API_KEY'));
define('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1');
define('OPENROUTER_MODEL', env('OPENROUTER_MODEL', 'anthropic/claude-sonnet-4'));

// Stripe
define('STRIPE_SECRET_KEY', env('STRIPE_SECRET_KEY'));
define('STRIPE_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY'));
define('STRIPE_WEBHOOK_SECRET', env('STRIPE_WEBHOOK_SECRET'));
