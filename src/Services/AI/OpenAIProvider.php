<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;

final class OpenAIProvider extends HttpProvider
{
    public function generate(string $prompt, array $schema): string
    {
        $r=$this->request('https://api.openai.com/v1/responses',['Authorization: Bearer '.$this->key,'Content-Type: application/json'],[
          'model'=>$this->model,'input'=>$prompt,'text'=>['format'=>['type'=>'json_schema','name'=>'quiz_items','strict'=>true,'schema'=>$schema]]
        ]);
        foreach($r['output']??[] as $item) foreach($item['content']??[] as $c) if(isset($c['text'])) return (string)$c['text'];
        throw new \RuntimeException('The provider response did not contain structured text.');
    }
    public function test(): void { $this->generate('Return an empty items array for a connection test.', ['type'=>'object','properties'=>['items'=>['type'=>'array','items'=>['type'=>'string']]],'required'=>['items'],'additionalProperties'=>false]); }
}
