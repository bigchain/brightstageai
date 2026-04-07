<?php
/**
 * AI-powered topic enhancement.
 * Takes a brief topic and expands it into a detailed, compelling description.
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
     * Enhance a brief topic into a detailed presentation description.
     */
    public function enhance(string $brief_topic, string $audience, string $tone): ?string
    {
        $system_prompt = <<<'PROMPT'
You are an expert presentation strategist. Your job is to take a brief topic idea and expand it into a compelling, detailed presentation description.

Rules:
- Expand the topic into 2-3 sentences that are specific and actionable
- Include what the audience will learn or take away
- Keep the tone matching the requested style
- Make it sound like a real webinar/presentation description that would attract attendees
- Do NOT add quotes, markdown, or formatting — just plain text
- Do NOT add a title — just the description
- Keep it under 300 characters
PROMPT;

        $user_prompt = "Enhance this brief topic into a detailed presentation description.\n\n"
            . "BRIEF TOPIC: \"{$brief_topic}\"\n"
            . "AUDIENCE: {$audience}\n"
            . "TONE: {$tone}\n\n"
            . "Return ONLY the enhanced description text. No quotes, no labels, no markdown.";

        $result = $this->ai->chat($system_prompt, $user_prompt, 0.7, 500);

        if ($result === null) {
            return null;
        }

        // Clean up — remove quotes, labels, extra whitespace
        $result = trim($result);
        $result = trim($result, '"\'');
        $result = preg_replace('/^(Enhanced description|Description|Topic):\s*/i', '', $result);

        return mb_substr($result, 0, 500);
    }
}
