<?php
/**
 * AI-powered outline generation.
 * Takes a topic and generates a structured presentation outline.
 */

require_once __DIR__ . '/OpenRouterService.php';

class OutlineService
{
    private OpenRouterService $ai;

    public function __construct()
    {
        $this->ai = new OpenRouterService();
    }

    /**
     * Generate a presentation outline from user input.
     * Returns array of slides with title, content, speaker_notes, layout_type.
     */
    public function generate(string $topic, string $audience, int $duration_minutes, string $tone): ?array
    {
        $slide_count = $this->estimate_slide_count($duration_minutes);

        $system_prompt = <<<PROMPT
You are an expert presentation designer. Generate a structured presentation outline.

Rules:
- Each slide should have a clear, concise title
- Content should be bullet points (3-5 per slide), each starting with "- "
- Speaker notes should be 2-4 natural sentences that a presenter would say
- First slide is always a title slide (layout_type: "title")
- Last slide is always a closing/Q&A slide (layout_type: "title")
- Other slides use "bullets" layout by default
- Keep language professional and engaging
- Tailor content and complexity to the target audience
PROMPT;

        $user_prompt = <<<PROMPT
Create a {$slide_count}-slide presentation outline:

Topic: {$topic}
Target Audience: {$audience}
Duration: {$duration_minutes} minutes
Tone: {$tone}

Return a JSON object with this exact structure:
{
  "title": "Presentation Title",
  "slides": [
    {
      "slide_order": 1,
      "title": "Slide Title",
      "content": "- Bullet point 1\n- Bullet point 2\n- Bullet point 3",
      "speaker_notes": "What the presenter should say for this slide.",
      "layout_type": "title"
    }
  ]
}
PROMPT;

        $result = $this->ai->chat_json($system_prompt, $user_prompt);

        if ($result === null || !isset($result['slides'])) {
            return null;
        }

        return $result;
    }

    /**
     * Estimate number of slides based on duration.
     * Roughly 1 slide per 1-2 minutes.
     */
    private function estimate_slide_count(int $duration_minutes): int
    {
        return match (true) {
            $duration_minutes <= 5  => 5,
            $duration_minutes <= 10 => 8,
            $duration_minutes <= 15 => 12,
            $duration_minutes <= 30 => 20,
            default                 => 30,
        };
    }
}
