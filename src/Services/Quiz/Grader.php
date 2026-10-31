<?php
declare(strict_types=1);
namespace QuizCreator\Services\Quiz;

final class Grader
{
    public function grade(array $question, string $answer): ?bool
    {
        if(in_array($question['question_type'],['multiple_choice','true_false'],true)) {
            $key=json_decode($question['answer_key_json'],true); return mb_strtolower(trim($answer))===mb_strtolower(trim((string)$key['answer']));
        }
        $key=json_decode($question['answer_key_json'],true); $accepted=$key['accepted']??[$key['answer']??''];
        $normal=static fn($s)=>mb_strtolower(trim(preg_replace('/[^\pL\pN]+/u',' ',(string)$s)??''));
        foreach($accepted as $value) if($normal($answer)===$normal($value)) return true;
        return null;
    }
}
