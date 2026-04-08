<?php
/**
 * AI Image Generation via OpenRouter.
 *
 * VERIFIED: Uses OpenRouter /chat/completions with modalities: ["image"]
 * Model: black-forest-labs/flux.2-klein-4b (~$0.015 per image, cheapest)
 * Response: choices[0].message.images[0].image_url.url = base64 PNG data URL
 */

class ImageGenerationService
{
    private string $api_key;
    private string $base_url;
    private string $model;

    public function __construct()
    {
        $this->api_key = env('OPENROUTER_API_KEY', '');
        $this->base_url = OPENROUTER_BASE_URL;
        $this->model = 'black-forest-labs/flux.2-klein-4b'; // Verified: cheapest, ~$0.015/image
    }

    public function is_available(): bool
    {
        return $this->api_key !== '';
    }

    /**
     * Generate an image from a text prompt.
     * Returns base64 PNG data URL, or null on failure.
     */
    public function generate(string $prompt, string $aspect_ratio = '16:9'): ?string
    {
        if (!$this->is_available()) return null;

        $prompt = mb_substr(trim($prompt), 0, 1000);
        if ($prompt === '') return null;

        $payload = [
            'model'    => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'modalities'   => ['image'],
            'image_config'  => [
                'aspect_ratio' => $aspect_ratio,
                'image_size'   => '1K',
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
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $http_code >= 400) {
            error_log("BrightStage ImageGen: HTTP {$http_code}");
            return null;
        }

        $response = json_decode($body, true);

        // Extract image from response
        if (isset($response['choices'][0]['message']['images'][0]['image_url']['url'])) {
            return $response['choices'][0]['message']['images'][0]['image_url']['url'];
        }

        // Some models return image in content as markdown
        $content = $response['choices'][0]['message']['content'] ?? '';
        if (preg_match('/!\[.*?\]\((data:image\/[^)]+)\)/', $content, $m)) {
            return $m[1];
        }

        error_log('BrightStage ImageGen: No image in response');
        return null;
    }

    /**
     * Generate an image and save it to storage.
     * Returns relative URL or null.
     */
    public function generate_and_save(string $prompt, int $user_id, int $presentation_id, string $filename): ?string
    {
        $data_url = $this->generate($prompt);
        if ($data_url === null) return null;

        // Decode base64
        if (!preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $data_url, $m)) {
            return null;
        }

        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $binary = base64_decode($m[2]);
        if ($binary === false || strlen($binary) < 100) return null;

        $storage_path = user_storage_path($user_id, $presentation_id) . '/images';
        if (!is_dir($storage_path)) mkdir($storage_path, 0755, true);

        $filepath = $storage_path . '/' . $filename . '.' . $ext;
        file_put_contents($filepath, $binary);

        return "/storage/users/{$user_id}/presentations/{$presentation_id}/images/{$filename}.{$ext}";
    }
}
