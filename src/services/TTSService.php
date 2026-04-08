<?php
/**
 * Text-to-Speech service via OpenRouter.
 *
 * VERIFIED: openai/gpt-audio-mini requires stream: true for audio output.
 * Response comes as SSE chunks with delta.audio.data containing base64 fragments.
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
        $this->model = 'openai/gpt-audio-mini';
    }

    public function is_available(): bool
    {
        return $this->api_key !== '';
    }

    public function generate(string $text, string $voice = 'alloy'): ?string
    {
        if (!$this->is_available()) return null;

        $text = trim($text);
        if ($text === '' || mb_strlen($text) < 5) return null;

        $valid_voices = ['alloy', 'ash', 'ballad', 'coral', 'echo', 'fable', 'nova', 'onyx', 'sage', 'shimmer'];
        if (!in_array($voice, $valid_voices, true)) $voice = 'alloy';

        $chunks = $this->split_text($text, 4000);
        $audio_parts = [];

        foreach ($chunks as $chunk) {
            $audio = $this->call_streaming_api($chunk, $voice);
            if ($audio === null) return null;
            $audio_parts[] = $audio;
        }

        return count($audio_parts) === 1 ? $audio_parts[0] : implode('', $audio_parts);
    }

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
                $results[] = ['slide_id' => $slide['id'], 'success' => false, 'error' => 'TTS failed'];
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
     * Streaming API call — the ONLY way audio works on OpenRouter.
     * Collects SSE chunks, extracts base64 audio fragments, decodes.
     */
    private function call_streaming_api(string $text, string $voice): ?string
    {
        $payload = json_encode([
            'model'      => $this->model,
            'stream'     => true,
            'modalities' => ['text', 'audio'],
            'audio'      => ['voice' => $voice, 'format' => 'pcm16'],
            'messages'   => [
                ['role' => 'system', 'content' => 'Read the following text aloud exactly as written. Natural pace, clear pronunciation. No extra words or commentary.'],
                ['role' => 'user', 'content' => $text],
            ],
        ]);

        // Use a temp file to collect the streaming response
        $tmp_file = tempnam(sys_get_temp_dir(), 'tts_');
        $fp = fopen($tmp_file, 'w');

        $ch = curl_init($this->base_url . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_FILE           => $fp,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'HTTP-Referer: ' . APP_URL,
                'X-Title: BrightStage Video',
            ],
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        // Read the collected response
        $raw = file_get_contents($tmp_file);
        unlink($tmp_file);

        if ($error) {
            error_log("BrightStage TTS: curl error: {$error}");
            return null;
        }

        if ($http_code >= 400) {
            error_log("BrightStage TTS: HTTP {$http_code} body: " . substr($raw, 0, 300));
            return null;
        }

        // Parse SSE lines and extract audio base64 chunks
        $audio_b64 = '';
        $lines = explode("\n", $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === 'data: [DONE]') continue;
            if (!str_starts_with($line, 'data: ')) continue;

            $json = json_decode(substr($line, 6), true);
            if (isset($json['choices'][0]['delta']['audio']['data'])) {
                $audio_b64 .= $json['choices'][0]['delta']['audio']['data'];
            }
        }

        if ($audio_b64 === '') {
            error_log('BrightStage TTS: No audio data in stream. Raw first 500 chars: ' . substr($raw, 0, 500));
            return null;
        }

        $pcm_data = base64_decode($audio_b64);
        if ($pcm_data === false || strlen($pcm_data) < 100) {
            error_log('BrightStage TTS: base64 decode failed. Length: ' . strlen($audio_b64));
            return null;
        }

        // Convert PCM16 to MP3 using FFmpeg
        return $this->pcm16_to_mp3($pcm_data);
    }

    /**
     * Convert raw PCM16 audio to MP3 using FFmpeg.
     * PCM16 from OpenRouter: 24000Hz, mono, signed 16-bit little-endian.
     */
    private function pcm16_to_mp3(string $pcm_data): ?string
    {
        $ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '');
        if ($ffmpeg === '') $ffmpeg = '/usr/bin/ffmpeg';

        $pcm_file = tempnam(sys_get_temp_dir(), 'pcm_');
        $mp3_file = tempnam(sys_get_temp_dir(), 'mp3_') . '.mp3';

        file_put_contents($pcm_file, $pcm_data);

        $cmd = sprintf(
            '%s -y -f s16le -ar 24000 -ac 1 -i %s -codec:a libmp3lame -b:a 128k %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($pcm_file),
            escapeshellarg($mp3_file)
        );

        shell_exec($cmd);
        unlink($pcm_file);

        if (!file_exists($mp3_file) || filesize($mp3_file) < 100) {
            error_log('BrightStage TTS: FFmpeg PCM→MP3 conversion failed');
            if (file_exists($mp3_file)) unlink($mp3_file);
            return null;
        }

        $mp3_data = file_get_contents($mp3_file);
        unlink($mp3_file);

        return $mp3_data;
    }

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
