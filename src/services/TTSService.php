<?php
/**
 * Text-to-Speech service via OpenRouter.
 * Uses OpenRouter's audio-capable models (gpt-4o-audio-preview)
 * to generate MP3 narration from slide speaker notes.
 * No extra API key needed — uses the same OPENROUTER_API_KEY.
 */

class TTSService
{
    private string $api_key;
    private string $base_url;
    private string $model;

    public function __construct()
    {
        $this->api_key = env('OPENROUTER_API_KEY', '');
        $this->base_url = OPENROUTER_BASE_URL;
        $this->model = 'openai/gpt-4o-mini-audio-preview'; // Cheapest audio model
    }

    /**
     * Check if TTS is available.
     */
    public function is_available(): bool
    {
        return $this->api_key !== '';
    }

    /**
     * Generate TTS audio for a text string.
     * Returns the MP3 binary data, or null on failure.
     */
    public function generate(string $text, string $voice = 'alloy'): ?string
    {
        if (!$this->is_available()) {
            error_log('BrightStage TTS: OPENROUTER_API_KEY not configured');
            return null;
        }

        $text = trim($text);
        if ($text === '' || mb_strlen($text) < 5) {
            return null;
        }

        // Split long text into chunks if needed (4000 char safety limit)
        $chunks = $this->split_text($text, 4000);
        $audio_parts = [];

        foreach ($chunks as $chunk) {
            $audio = $this->call_audio_api($chunk, $voice);
            if ($audio === null) {
                return null;
            }
            $audio_parts[] = $audio;
        }

        // Single chunk — return directly
        if (count($audio_parts) === 1) {
            return $audio_parts[0];
        }

        // Multiple chunks — concatenate MP3 data
        return implode('', $audio_parts);
    }

    /**
     * Generate audio for all slides in a presentation.
     */
    public function generate_for_slides(array $slides, int $user_id, int $presentation_id, string $voice = 'alloy'): array
    {
        $results = [];
        $storage_path = user_storage_path($user_id, $presentation_id) . '/audio';
        if (!is_dir($storage_path)) {
            mkdir($storage_path, 0755, true);
        }

        foreach ($slides as $slide) {
            $text = trim($slide['speaker_notes'] ?? '');
            if ($text === '') {
                $results[] = ['slide_id' => $slide['id'], 'success' => false, 'error' => 'No narration text'];
                continue;
            }

            $audio_data = $this->generate($text, $voice);

            if ($audio_data === null) {
                $results[] = ['slide_id' => $slide['id'], 'success' => false, 'error' => 'TTS generation failed'];
                continue;
            }

            $filename = "slide_{$slide['slide_order']}.mp3";
            $filepath = $storage_path . '/' . $filename;
            file_put_contents($filepath, $audio_data);

            $relative_url = "/storage/users/{$user_id}/presentations/{$presentation_id}/audio/{$filename}";

            $results[] = [
                'slide_id'  => $slide['id'],
                'success'   => true,
                'audio_url' => $relative_url,
                'file_size' => strlen($audio_data),
            ];
        }

        return $results;
    }

    /**
     * Call OpenRouter audio API.
     * Uses gpt-4o-mini-audio-preview with audio modality.
     */
    private function call_audio_api(string $text, string $voice): ?string
    {
        $payload = [
            'model'      => $this->model,
            'modalities' => ['text', 'audio'],
            'audio'      => [
                'voice'  => $voice,
                'format' => 'mp3',
            ],
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You are a professional presentation narrator. Read the following text aloud exactly as written. Do not add any commentary, introduction, or extra words. Just read the text naturally and clearly as a voiceover narration.',
                ],
                [
                    'role'    => 'user',
                    'content' => 'Read this aloud as a presentation voiceover: ' . $text,
                ],
            ],
        ];

        $ch = curl_init($this->base_url . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'HTTP-Referer: ' . APP_URL,
                'X-Title: BrightStage Video',
            ],
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $http_code >= 400) {
            error_log("BrightStage TTS: OpenRouter audio error HTTP {$http_code}");
            return null;
        }

        $response = json_decode($body, true);

        if (!$response || !isset($response['choices'][0]['message'])) {
            error_log('BrightStage TTS: Invalid response structure');
            return null;
        }

        $message = $response['choices'][0]['message'];

        // Audio data is in message.audio.data as base64
        if (isset($message['audio']['data'])) {
            $audio_base64 = $message['audio']['data'];
            $audio_binary = base64_decode($audio_base64);
            if ($audio_binary === false) {
                error_log('BrightStage TTS: Failed to decode audio base64');
                return null;
            }
            return $audio_binary;
        }

        error_log('BrightStage TTS: No audio data in response');
        return null;
    }

    /**
     * Split text into chunks at sentence boundaries.
     */
    private function split_text(string $text, int $max_chars): array
    {
        if (mb_strlen($text) <= $max_chars) {
            return [$text];
        }

        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current . ' ' . $sentence) > $max_chars) {
                if ($current !== '') $chunks[] = trim($current);
                $current = $sentence;
            } else {
                $current .= ($current !== '' ? ' ' : '') . $sentence;
            }
        }

        if ($current !== '') $chunks[] = trim($current);

        return $chunks;
    }
}
