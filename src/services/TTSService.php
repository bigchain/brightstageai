<?php
/**
 * Text-to-Speech service.
 * Converts slide narration scripts to MP3 audio files.
 * Supports OpenAI TTS and ElevenLabs.
 */

class TTSService
{
    private string $provider;
    private string $api_key;

    public function __construct()
    {
        // Check which TTS provider is configured
        $openai_key = env('OPENAI_API_KEY', '');
        $elevenlabs_key = env('ELEVENLABS_API_KEY', '');

        if ($openai_key !== '') {
            $this->provider = 'openai';
            $this->api_key = $openai_key;
        } elseif ($elevenlabs_key !== '') {
            $this->provider = 'elevenlabs';
            $this->api_key = $elevenlabs_key;
        } else {
            $this->provider = 'none';
            $this->api_key = '';
        }
    }

    /**
     * Check if TTS is available.
     */
    public function is_available(): bool
    {
        return $this->provider !== 'none';
    }

    /**
     * Generate TTS audio for a text string.
     * Returns the MP3 binary data, or null on failure.
     */
    public function generate(string $text, string $voice = 'alloy'): ?string
    {
        if (!$this->is_available()) {
            error_log('BrightStage TTS: No TTS provider configured (set OPENAI_API_KEY or ELEVENLABS_API_KEY in .env)');
            return null;
        }

        // Trim and validate
        $text = trim($text);
        if ($text === '' || mb_strlen($text) < 5) {
            return null;
        }

        // OpenAI TTS has 4096 char limit per request — split if needed
        if ($this->provider === 'openai') {
            return $this->generate_openai($text, $voice);
        }

        if ($this->provider === 'elevenlabs') {
            return $this->generate_elevenlabs($text, $voice);
        }

        return null;
    }

    /**
     * Generate audio for all slides in a presentation.
     * Saves MP3 files to storage and returns results.
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

            // Save MP3 file
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
     * OpenAI TTS API.
     * Model: tts-1 (fast) or tts-1-hd (high quality)
     * Voices: alloy, echo, fable, onyx, nova, shimmer
     */
    private function generate_openai(string $text, string $voice): ?string
    {
        // Split long text into chunks (OpenAI limit: 4096 chars)
        $chunks = $this->split_text($text, 4000);
        $audio_parts = [];

        foreach ($chunks as $chunk) {
            $payload = [
                'model' => 'tts-1',
                'input' => $chunk,
                'voice' => $voice,
                'response_format' => 'mp3',
            ];

            $ch = curl_init('https://api.openai.com/v1/audio/speech');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key,
                ],
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $http_code >= 400) {
                error_log("BrightStage TTS OpenAI error: HTTP {$http_code}");
                return null;
            }

            $audio_parts[] = $body;
        }

        // If single chunk, return directly
        if (count($audio_parts) === 1) {
            return $audio_parts[0];
        }

        // Multiple chunks — concatenate MP3 data (simple append works for MP3)
        return implode('', $audio_parts);
    }

    /**
     * ElevenLabs TTS API.
     */
    private function generate_elevenlabs(string $text, string $voice): ?string
    {
        // Default ElevenLabs voice ID (Rachel)
        $voice_id = ' 21m00Tcm4TlvDq8ikWAM';

        $voice_map = [
            'alloy'   => '21m00Tcm4TlvDq8ikWAM', // Rachel
            'echo'    => 'AZnzlk1XvdvUeBnXmlld', // Domi
            'nova'    => 'EXAVITQu4vr4xnSDxMaL', // Bella
            'onyx'    => 'VR6AewLTigWG4xSOukaG', // Arnold
            'shimmer' => 'pNInz6obpgDQGcFmaJgB', // Adam
        ];

        if (isset($voice_map[$voice])) {
            $voice_id = $voice_map[$voice];
        }

        $payload = [
            'text' => $text,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ],
        ];

        $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/{$voice_id}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'xi-api-key: ' . $this->api_key,
                'Accept: audio/mpeg',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400) {
            error_log("BrightStage TTS ElevenLabs error: HTTP {$http_code}");
            return null;
        }

        return $body;
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
                if ($current !== '') {
                    $chunks[] = trim($current);
                }
                $current = $sentence;
            } else {
                $current .= ($current !== '' ? ' ' : '') . $sentence;
            }
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        return $chunks;
    }
}
