<?php
declare(strict_types=1);

/** Load project-local .env values for Apache/PHP without printing or logging their values. */
function load_local_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;
    $contents = file_get_contents($path);
    if ($contents === false) return;
    if (str_starts_with($contents, "\xEF\xBB\xBF")) $contents = substr($contents, 3);

    foreach (preg_split('/\r\n|\n|\r/', $contents) ?: [] as $lineNumber => $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_starts_with($line, 'export ')) $line = ltrim(substr($line, 7));
        $equals = strpos($line, '=');
        if ($equals === false) continue;
        $name = trim(substr($line, 0, $equals));
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) continue;
        $raw = trim(substr($line, $equals + 1));
        $value = $raw;

        if ($raw !== '' && ($raw[0] === '"' || $raw[0] === "'")) {
            $quote = $raw[0];
            $closing = null;
            for ($i = 1, $length = strlen($raw); $i < $length; $i++) {
                if ($raw[$i] !== $quote) continue;
                $slashes = 0;
                for ($j = $i - 1; $j > 0 && $raw[$j] === '\\'; $j--) $slashes++;
                if (($slashes % 2) === 0) { $closing = $i; break; }
            }
            if ($closing === null) {
                error_log('Ignored malformed .env entry at line '.($lineNumber + 1).'.');
                continue;
            }
            $tail = trim(substr($raw, $closing + 1));
            if ($tail !== '' && !str_starts_with($tail, '#')) continue;
            $value = substr($raw, 1, $closing - 1);
        } else {
            $value = preg_replace('/\s+#.*$/', '', $raw) ?? $raw;
            $value = trim($value);
        }

        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}