<?php
/**
 * AI-powered slide content polishing.
 * Takes rough slide content/narration and cleans it up.
 */

require_once __DIR__ . '/OpenRouterService.php';

class SlidePolishService
{
    private OpenRouterService $ai;

    public function __construct()
    {
        $this->ai = new OpenRouterService();
    }

    /**
     * Polish a slide's content and narration.
     */
    public function polish(string $title, string $content, string $speaker_notes, string $tone): ?array
    {
        $system_prompt = <<<'PROMPT'
You are an expert presentation editor. Polish and improve slide content.

Rules:
- Fix grammar, spelling, and punctuation
- Make bullet points concise, punchy, and impactful (start with action verbs where possible)
- Each bullet should be 1-2 lines max — cut fluff
- Improve the narration to sound natural and conversational (this will be spoken aloud)
- Add smooth transitions in narration ("Now let's look at...", "The key takeaway here is...")
- Keep the same meaning and tone — just make it better
- Keep the same number of bullet points (roughly)
- Return ONLY valid JSON with: title, content, speaker_notes
PROMPT;

        $user_prompt = "Polish this slide content. Tone: {$tone}\n\n"
            . "TITLE: \"{$title}\"\n"
            . "CONTENT:\n{$content}\n\n"
            . "NARRATION:\n{$speaker_notes}\n\n"
            . "Return JSON: {\"title\": \"...\", \"content\": \"...\", \"speaker_notes\": \"...\"}";

        $result = $this->ai->chat_json($system_prompt, $user_prompt, 0.5, 2000);

        if ($result === null) {
            return null;
        }

        return [
            'title'         => mb_substr(trim($result['title'] ?? $title), 0, 255),
            'content'       => mb_substr(trim($result['content'] ?? $content), 0, 5000),
            'speaker_notes' => mb_substr(trim($result['speaker_notes'] ?? $speaker_notes), 0, 5000),
        ];
    }
}
