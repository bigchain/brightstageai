<?php
/**
 * Text-to-Speech service via OpenRouter.
 *
 * VERIFIED: Uses openai/gpt-audio-mini model through OpenRouter chat completions.
 * Audio output requires modalities: ["text", "audio"] and audio config.
 * Streaming may be required — we try non-streaming first, fall back to streaming.
 *
 * Model: openai/gpt-audio-mini ($0.60/M input, $2.40/M audio output tokens)
 * Voices: alloy, ash, ballad, coral, echo, fable, nova, onyx, sage, shimmer
 * Formats: mp3, wav, flac, opus, aac
 */

class TTSService
{
    private string $api_key;
    private string $base_url;
    private string $model;

    public function __construct()
    {
        $this->api_key = env('OPENROUTER_API_KEY', '');
        $this->base_url = OPENROUTER_BASE_URL; // https://openrouter.ai/api/v1
        $this->model = 'openai/gpt-audio-mini'; // Verified: exists on OpenRouter, supports audio output
    }

    public function is_available(): bool
    {
        return $this->api_key !== '';
    }

    /**
     * Generate TTS audio for a text string.
     * Returns MP3 binary data, or null on failure.
     */
    public function generate(string $text, string $voice = 'alloy'): ?string
    {
        if (!$this->is_available()) {
            error_log('BrightStage TTS: OPENROUTER_API_KEY not configured');
            return null;
        }

        $text = trim($text);
        if ($text === '' || mb_strlen($text) < 5) return null;

        // Validate voice
        $valid_voices = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'nova', 'onyx', 'sage', 'shimmer'];
        if (!in_array($voice, $valid_voices, true)) $voice = 'alloy';

        // Split long text into chunks (safety limit)
        $chunks = $this->split_text($text, 4000);
        $audio_parts = [];

        foreach ($chunks as $chunk) {
            // Try non-streaming first, fall back to streaming
            $audio = $this->call_non_streaming($chunk, $voice);
            if ($audio === null) {
                $audio = $this->call_streaming($chunk, $voice);
            }
            if ($audio === null) return null;
            $audio_parts[] = $audio;
        }

        return count($audio_parts) === 1 ? $audio_parts[0] : implode('', $audio_parts);
    }

    /**
     * Generate audio for all slides in a presentation.
     */
    public function generate_for_slides(array $slides, int $user_id, int $presentation_id, string $voice = 'alloy'): array
    {
        $results = [];
        $storage_path = user_storage_path($user_id, $presentation_id) . '/audio';
        if (!is_dir($storage_path)) mkdir($storage_path, 0755, true);

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
            file_put_contents($storage_path . '/' . $filename, $audio_data);

            $results[] = [
                'slide_id'  => $slide['id'],
                'success'   => true,
                'audio_url' => "/storage/users/{$user_id}/presentations/{$presentation_id}/audio/{$filename}",
                'file_size' => strlen($audio_data),
            ];
        }

        return $results;
    }

    /**
     * Non-streaming approach.
     * Response: choices[0].message.audio.data = base64 audio
     */
    private function call_non_streaming(string $text, string $voice): ?string
    {
        $payload = [
            'model'      => $this->model,
            'modalities' => ['text', 'audio'],
            'audio'      => ['voice' => $voice, 'format' => 'mp3'],
            'messages'   => [
                ['role' => 'system', 'content' => 'Read the following text aloud exactly as written. Natural pace, clear pronunciation. No commentary.'],
                ['role' => 'user', 'content' => $text],
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
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $http_code >= 400) {
            error_log("BrightStage TTS non-streaming: HTTP {$http_code}");
            return null;
        }

        $response = json_decode($body, true);

        // Check for audio data in response
        if (isset($response['choices'][0]['message']['audio']['data'])) {
            $decoded = base64_decode($response['choices'][0]['message']['audio']['data']);
            if ($decoded !== false && strlen($decoded) > 100) {
                return $decoded;
            }
        }

        error_log('BrightStage TTS non-streaming: No audio.data in response, trying streaming');
        return null;
    }

    /**
     * Streaming approach (OpenRouter docs say audio output may require this).
     * SSE chunks: delta.audio.data = base64 fragments, concatenate then decode.
     */
    private function call_streaming(string $text, string $voice): ?string
    {
        $payload = [
            'model'      => $this->model,
            'modalities' => ['text', 'audio'],
            'audio'      => ['voice' => $voice, 'format' => 'mp3'],
            'stream'     => true,
            'messages'   => [
                ['role' => 'system', 'content' => 'Read the following text aloud exactly as written. Natural pace, clear pronunciation. No commentary.'],
                ['role' => 'user', 'content' => $text],
            ],
        ];

        $ch = curl_init($this->base_url . '/chat/completions');

        $audio_base64_chunks = [];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'HTTP-Referer: ' . APP_URL,
                'X-Title: BrightStage Video',
                'Accept: text/event-stream',
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use (&$audio_base64_chunks) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line === 'data: [DONE]') continue;
                    if (!str_starts_with($line, 'data: ')) continue;

                    $json_str = substr($line, 6);
                    $chunk = json_decode($json_str, true);

                    if (isset($chunk['choices'][0]['delta']['audio']['data'])) {
                        $audio_base64_chunks[] = $chunk['choices'][0]['delta']['audio']['data'];
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $http_code >= 400) {
            error_log("BrightStage TTS streaming: HTTP {$http_code}");
            return null;
        }

        if (empty($audio_base64_chunks)) {
            error_log('BrightStage TTS streaming: No audio chunks received');
            return null;
        }

        // Concatenate base64 chunks and decode
        $full_base64 = implode('', $audio_base64_chunks);
        $decoded = base64_decode($full_base64);

        if ($decoded === false || strlen($decoded) < 100) {
            error_log('BrightStage TTS streaming: Failed to decode audio (' . strlen($full_base64) . ' chars base64)');
            return null;
        }

        return $decoded;
    }

    /**
     * Split text at sentence boundaries.
     */
    private function split_text(string $text, int $max_chars): array
    {
        if (mb_strlen($text) <= $max_chars) return [$text];

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
