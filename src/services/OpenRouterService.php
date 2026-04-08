<?php
/**
 * OpenRouter API client.
 * Sends prompts to AI models via OpenRouter and returns responses.
 */

class OpenRouterService
{
    private string $api_key;
    private string $base_url;
    private string $model;

    public function __construct()
    {
        $this->api_key  = env('OPENROUTER_API_KEY', '');
        $this->base_url = OPENROUTER_BASE_URL;
        $this->model    = env('OPENROUTER_MODEL', OPENROUTER_DEFAULT_MODEL);

        if ($this->api_key === '') {
            error_log('BrightStage: OPENROUTER_API_KEY not set in .env');
        }
    }

    /**
     * Send a chat completion request to OpenRouter.
     */
    public function chat(string $system_prompt, string $user_prompt, float $temperature = 0.7, int $max_tokens = 4096): ?string
    {
        $payload = [
            'model'       => $this->model,
            'messages'    => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_prompt],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        ];

        $response = $this->request('/chat/completions', $payload);

        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        return null;
    }

    /**
     * Send a chat completion and parse JSON from the response.
     */
    public function chat_json(string $system_prompt, string $user_prompt, float $temperature = 0.4, int $max_tokens = 8192): ?array
    {
        $system_prompt .= "\n\nIMPORTANT: Respond ONLY with valid JSON. No markdown, no code fences, no explanations.";

        $raw = $this->chat($system_prompt, $user_prompt, $temperature, $max_tokens);

        if ($raw === null) {
            return null;
        }

        // Strip markdown code fences if AI added them anyway
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);

        $parsed = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('BrightStage OpenRouter: JSON parse error - ' . json_last_error_msg() . ' | First 200 chars: ' . substr($raw, 0, 200));
            return null;
        }

        return $parsed;
    }

    /**
     * Make an HTTP request to the OpenRouter API.
     */
    private function request(string $endpoint, array $payload): ?array
    {
        $url = $this->base_url . $endpoint;

        $ch = curl_init($url);
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
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("BrightStage OpenRouter: cURL error - {$error}");
            return null;
        }

        if ($http_code >= 400) {
            error_log("BrightStage OpenRouter: API error HTTP {$http_code} | " . substr($body, 0, 300));
            return null;
        }

        $decoded = json_decode($body, true);
        if ($decoded === null && $body !== '') {
            error_log("BrightStage OpenRouter: Response not JSON | HTTP {$http_code} | " . substr($body, 0, 300));
            return null;
        }

        return $decoded;
    }
}
