<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;

abstract class HttpProvider implements Provider
{
    public function __construct(protected string $key, protected string $model, protected int $timeout) {}

    protected function protectPrompt(string $prompt): string
    {
        return $this->key === '' ? $prompt : str_replace($this->key, '[redacted API credential]', $prompt);
    }

    protected function request(string $url, array $headers, array $body): array
    {
        if ($this->key === '') {
            throw new \RuntimeException('The selected provider API key is missing. Add it to the server-side .env file and restart Apache.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The PHP cURL extension is unavailable. Enable it in XAMPP php.ini and restart Apache.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            error_log('AI provider transport failure or timeout.');
            throw new \RuntimeException('Could not reach the AI provider. Check the server connection and try again.');
        }
        if ($status < 200 || $status >= 300) {
            // Do not log response bodies: they may echo submitted content.
            error_log('AI provider request failed; HTTP status '.$status.'.');
            $errorData = json_decode($raw, true);
            $providerMessage = strtolower((string) ($errorData['error']['message'] ?? ''));
            $badCredential = str_contains($providerMessage, 'api key') || str_contains($providerMessage, 'credential');
            $message = match ($status) {
                400 => $badCredential ? 'The provider rejected its API key. Check that GEMINI_API_KEY is valid for the selected project.' : 'The provider rejected the request. Check model support and structured-output settings.',
                401 => 'The provider rejected its API credential. Check the selected provider key.',
                402 => 'The provider account requires a billing or payment action.',
                403 => 'The provider denied access. Check API access, project eligibility, and account permissions.',
                404 => 'The selected model was not found or is unavailable to this API project. Check the model name and access.',
                429 => 'The provider quota or request rate limit is exhausted. Check project usage and tier, then wait before retrying.',
                408, 504 => 'The provider request timed out. Try again or increase AI_TIMEOUT.',
                500, 502, 503 => 'The provider is temporarily unavailable. Wait and try again.',
                default => 'The provider request failed (HTTP '.$status.'). Check provider configuration and server logs.',
            };
            throw new \RuntimeException($message);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) throw new \RuntimeException('The provider returned an unreadable response.');
        return $data;
    }
}