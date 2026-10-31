<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;
interface Provider { public function generate(string $prompt, array $schema): string; public function test(): void; }
