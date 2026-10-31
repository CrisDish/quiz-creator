<?php
declare(strict_types=1);

function env_value(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value !== false) return $value;
    if (isset($_SERVER[$name])) return (string) $_SERVER[$name];
    if (isset($_ENV[$name])) return (string) $_ENV[$name];
    return $default;
}

function app_config(): array
{
    return [
        'env' => env_value('APP_ENV', 'production'),
        'debug' => filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
        'url' => rtrim((string) env_value('APP_URL', 'http://localhost/quiz-creator/'), '/') . '/',
        'provider' => strtolower((string) env_value('AI_PROVIDER', 'openai')),
        'openai_key' => env_value('OPENAI_API_KEY', ''),
        'openai_model' => env_value('OPENAI_MODEL', 'gpt-4.1-mini'),
        'gemini_key' => env_value('GEMINI_API_KEY', ''),
        'gemini_model' => env_value('GEMINI_MODEL', 'gemini-2.5-flash'),
        'timeout' => max(10, (int) env_value('AI_TIMEOUT', '90')),
        'max_upload' => max(1, (int) env_value('MAX_UPLOAD_MB', '25')),
        'storage' => dirname(__DIR__) . '/storage',
    ];
}
