<?php
/**
 * AI-powered slide HTML/CSS generation.
 * Takes slide data + template config and generates beautiful HTML/CSS per slide.
 * This is the SurfSense-quality approach: AI designs each slide as a web page.
 */

require_once __DIR__ . '/OpenRouterService.php';

class SlideGenerationService
{
    private OpenRouterService $ai;

    public function __construct()
    {
        $this->ai = new OpenRouterService();
    }

    /**
     * Generate HTML/CSS for a single slide.
     */
    public function generate_slide_html(array $slide, array $template_config, int $slide_number, int $total_slides): ?string
    {
        $system_prompt = <<<'PROMPT'
You are an expert web designer creating presentation slides. Generate a single slide as a self-contained HTML div.

GENERAL RULES:
- Output ONLY the HTML for the slide. No doctype, no head, no body tags.
- The slide must be exactly 1920x1080 pixels (16:9 aspect ratio).
- Use inline CSS only (no external stylesheets).
- Use modern CSS: gradients, flexbox, shadows, border-radius.
- Import Google Fonts using @import in a <style> tag inside the div.
- Text must be large and readable from a distance (titles: 48-72px, body: 24-32px).
- Maximum 5 bullet points per slide. Keep text concise.
- Use the provided color scheme and font choices.
- Add subtle decorative elements (shapes, accent lines, corner dots) for visual interest.
- NEVER include JavaScript, forms, inputs, links, or interactive elements.
- NEVER include <script> tags or event handlers.
- The output should look like a professionally designed Canva/Pitch slide.

LAYOUT TYPES — design MUST match the requested layout:
- "title": Large centered title with subtitle below. Full gradient background. Minimal text, maximum impact. Big bold heading (72px+), elegant subtitle (28px). Centered vertically and horizontally.
- "bullets": Title at top-left, bullet points below with generous spacing (40px between items). Each bullet has a colored accent dot/icon. Clean left-aligned layout.
- "quote": Large quotation mark icon (decorative). The content is displayed as a featured quote in large italic text (36-48px), centered. Attribution/source below in smaller text. Elegant, spacious layout.
- "image_left": Split layout — left 45% has a colored placeholder area (use a gradient box with an icon or pattern as image placeholder), right 55% has the title and bullet points. Content flows on the right side.
- "image_right": Split layout — left 55% has the title and bullet points, right 45% has a colored placeholder area (gradient box with icon/pattern). Mirror of image_left.
- "two_column": Title spans full width at top. Below: two equal columns side by side. Split the content bullets roughly in half between columns. Each column has its own subtle background or border.
PROMPT;

        $colors = $template_config;
        $primary   = $colors['primary'] ?? '#1e3a5f';
        $secondary = $colors['secondary'] ?? '#ffffff';
        $accent    = $colors['accent'] ?? '#3498db';
        $font_heading = $colors['font_heading'] ?? 'Inter';
        $font_body    = $colors['font_body'] ?? 'Inter';
        $style        = $colors['style'] ?? 'clean';

        $layout = $slide['layout_type'] ?? 'bullets';
        $title  = $slide['title'] ?? '';
        $content = $slide['content'] ?? '';

        $user_prompt = "Create an HTML slide with these specifications:\n\n"
            . "SLIDE DATA:\n"
            . "- Slide number: {$slide_number} of {$total_slides}\n"
            . "- Layout type: {$layout}\n"
            . "- Title: \"{$title}\"\n"
            . "- Content: \"{$content}\"\n\n"
            . "DESIGN PARAMETERS:\n"
            . "- Primary color: {$primary}\n"
            . "- Secondary/text color: {$secondary}\n"
            . "- Accent color: {$accent}\n"
            . "- Heading font: {$font_heading}\n"
            . "- Body font: {$font_body}\n"
            . "- Style: {$style}\n"
            . "- Dimensions: 1920x1080px\n\n";

        // Add custom design instruction if provided
        $custom = $slide['_design_instruction'] ?? '';
        if ($custom !== '') {
            $user_prompt .= "SPECIAL DESIGN INSTRUCTION: {$custom}\n\n";
        }

        $user_prompt .= "Return ONLY the HTML div. No explanation, no markdown code fences.";

        $html = $this->ai->chat($system_prompt, $user_prompt, 0.6, 4096);

        if ($html === null) {
            return null;
        }

        return $this->sanitize_html($html);
    }

    /**
     * Generate HTML/CSS for ALL slides in a presentation.
     */
    public function generate_all(array $slides, array $template_config): array
    {
        $total = count($slides);
        $results = [];

        foreach ($slides as $i => $slide) {
            $html = $this->generate_slide_html($slide, $template_config, $i + 1, $total);
            $results[] = [
                'slide_id' => $slide['id'],
                'html'     => $html,
                'success'  => $html !== null,
            ];
        }

        return $results;
    }

    /**
     * Sanitize AI-generated HTML.
     * Remove dangerous elements while keeping visual markup.
     */
    private function sanitize_html(string $html): string
    {
        // Strip markdown code fences if present
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html);
        $html = preg_replace('/\s*```$/', '', $html);

        // Remove script tags and event handlers
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\bon\w+\s*=\s*\S+/i', '', $html);

        // Remove form elements
        $html = preg_replace('/<(form|input|button|select|textarea)\b[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<(form|input|button|select|textarea)\b[^>]*\/?>/si', '', $html);

        // Remove iframe, object, embed
        $html = preg_replace('/<(iframe|object|embed|applet)\b[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<(iframe|object|embed|applet)\b[^>]*\/?>/si', '', $html);

        // Remove javascript: URLs
        $html = preg_replace('/javascript\s*:/i', '', $html);

        return trim($html);
    }
}
