<?php
/**
 * AI-powered topic enhancement.
 * Takes a brief topic and returns: enhanced description, title, and suggested audience.
 */

require_once __DIR__ . '/OpenRouterService.php';

class TopicEnhancerService
{
    private OpenRouterService $ai;

    public function __construct()
    {
        $this->ai = new OpenRouterService();
    }

    /**
     * Enhance a brief topic into a full presentation brief.
     * Returns: enhanced_topic, title, audience
     */
    public function enhance(string $brief_topic, string $tone): ?array
    {
        $system_prompt = <<<'PROMPT'
You are an expert presentation strategist. Take a brief topic idea and create a complete presentation brief.

Return a JSON object with exactly these 3 fields:
- "title": A compelling, professional presentation title (5-10 words max)
- "audience": The ideal target audience for this topic (be specific, e.g., "Small business owners and marketing managers")
- "description": An expanded, detailed description of what the presentation will cover (2-3 sentences, specific and actionable)

Rules:
- The title should be catchy and clear — something that would look great on a webinar landing page
- The audience should be specific enough to tailor content, not just "everyone"
- The description should mention specific topics, takeaways, or value the audience will get
- Match the requested tone
- Output ONLY valid JSON. No markdown, no code fences.
PROMPT;

        $user_prompt = "Create a presentation brief from this topic idea.\n\n"
            . "TOPIC IDEA: \"{$brief_topic}\"\n"
            . "TONE: {$tone}\n\n"
            . "Return JSON with: title, audience, description";

        $result = $this->ai->chat_json($system_prompt, $user_prompt, 0.7, 500);

        if ($result === null) {
            return null;
        }

        return [
            'title'       => mb_substr(trim($result['title'] ?? ''), 0, 255),
            'audience'    => mb_substr(trim($result['audience'] ?? 'General audience'), 0, 200),
            'description' => mb_substr(trim($result['description'] ?? $brief_topic), 0, 500),
        ];
    }
}
