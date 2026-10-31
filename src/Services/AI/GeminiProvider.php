<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;

final class GeminiProvider extends HttpProvider
{
    public function generate(string $prompt, array $schema): string
    {
        $url='https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($this->model).':generateContent';
        $r=$this->request($url,['x-goog-api-key: '.$this->key,'Content-Type: application/json'],['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['responseMimeType'=>'application/json','responseJsonSchema'=>$schema]]);
        return (string)($r['candidates'][0]['content']['parts'][0]['text']??throw new \RuntimeException('The provider returned no structured text.'));
    }
    public function test(): void { $this->generate('Return an empty items array for a connection test.', ['type'=>'object','properties'=>['items'=>['type'=>'array','items'=>['type'=>'string']]],'required'=>['items']]); }
}
