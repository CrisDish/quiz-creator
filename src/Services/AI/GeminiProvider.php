<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;

final class GeminiProvider extends HttpProvider
{
    public function generate(string $prompt, array $schema): string
    {
        $prompt = $this->protectPrompt($prompt);
        $response = $this->request(
            'https://generativelanguage.googleapis.com/v1beta/interactions',
            ['x-goog-api-key: '.$this->key, 'Content-Type: application/json'],
            [
                'model' => $this->model,
                'input' => $prompt,
                'store' => false,
                'response_format' => [
                    'type' => 'text',
                    'mime_type' => 'application/json',
                    'schema' => $schema,
                ],
            ]
        );
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        $text = '';
        foreach (($response['steps'] ?? []) as $step) {
            if (($step['type'] ?? '') !== 'model_output') continue;
            foreach (($step['content'] ?? []) as $part) {
                if (($part['type'] ?? '') === 'text' && is_string($part['text'] ?? null)) {
                    $text .= $part['text'];
                }
            }
        }
        if ($text === '') throw new \RuntimeException('Gemini returned no structured text.');
        return $text;
    }

    public function test(): void
    {
        $this->generate('Return an empty items array for a connection test.', [
            'type' => 'object',
            'properties' => ['items' => ['type' => 'array', 'items' => ['type' => 'string']]],
            'required' => ['items'],
            'additionalProperties' => false,
        ]);
    }
}