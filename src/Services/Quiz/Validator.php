<?php
declare(strict_types=1);
namespace QuizCreator\Services\Quiz;

final class Validator
{
    public function validate(array $data, string $sourceText = ''): array
    {
        $errors=[]; $items=$data['items']??null;
        if(!is_array($items)) return ['items'=>[],'errors'=>['Response has no items array.']];
        $seen=[]; $valid=[];
        foreach($items as $i=>$q) {
            $why=[]; $types=['multiple_choice','true_false','identification','short_answer'];
            if(!is_array($q)) $why[]='Question is not an object.';
            else {
                foreach(['id','question','type','difficulty','answer','explanation','sources'] as $k) if(!array_key_exists($k,$q)) $why[]="Missing {$k}.";
                if(!in_array($q['type']??'', $types, true)) $why[]='Unsupported question type.';
                if(trim((string)($q['question']??''))==='') $why[]='Question text is empty.';
                if(isset($q['sources']) && (!is_array($q['sources']) || count($q['sources'])===0)) $why[]='No source evidence.';
                foreach(($q['sources']??[]) as $source) {
                    $excerpt=(string)($source['excerpt']??'');
                    if($excerpt==='' || !isset($source['location']) || ($sourceText!=='' && strpos($sourceText,$excerpt)===false)) $why[]='Source excerpt is empty, lacks a location, or is not present verbatim in the handout.';
                }
                if(($q['type']??'')==='multiple_choice') {
                    if(count($q['options']??[])!==4) $why[]='Multiple choice requires four options.';
                    if(!in_array($q['answer']??'', ['A','B','C','D'],true)) $why[]='Answer key must be A, B, C, or D.';
                    foreach(['A','B','C','D'] as $label) if(empty($q['options'][$label]['text']) || !isset($q['options'][$label]['explanation'])) $why[]="Option {$label} needs text and explanation.";
                }
                $fingerprint=mb_strtolower(preg_replace('/\W/u','',(string)($q['question']??''))??'');
                if($fingerprint!=='' && isset($seen[$fingerprint])) $why[]='Duplicate question.'; $seen[$fingerprint]=true;
            }
            if($why) $errors[$i]=$why; else { $q['validation_status']='needs_review'; $valid[]=$q; }
        }
        return ['items'=>$valid,'errors'=>$errors];
    }
    public function schema(): array
    {
        return ['type'=>'object','properties'=>['items'=>['type'=>'array','items'=>['type'=>'object','properties'=>[
            'id'=>['type'=>'string'],'question'=>['type'=>'string'],'type'=>['type'=>'string','enum'=>['multiple_choice','true_false','identification','short_answer']],
            'difficulty'=>['type'=>'string','enum'=>['easy','moderate','hard']], 'options'=>['type'=>'object','properties'=>[
                'A'=>['type'=>'object','properties'=>['text'=>['type'=>'string'],'explanation'=>['type'=>'string']],'required'=>['text','explanation'],'additionalProperties'=>false],
                'B'=>['type'=>'object','properties'=>['text'=>['type'=>'string'],'explanation'=>['type'=>'string']],'required'=>['text','explanation'],'additionalProperties'=>false],
                'C'=>['type'=>'object','properties'=>['text'=>['type'=>'string'],'explanation'=>['type'=>'string']],'required'=>['text','explanation'],'additionalProperties'=>false],
                'D'=>['type'=>'object','properties'=>['text'=>['type'=>'string'],'explanation'=>['type'=>'string']],'required'=>['text','explanation'],'additionalProperties'=>false]],'required'=>['A','B','C','D'],'additionalProperties'=>false],
            'answer'=>['type'=>'string'],'explanation'=>['type'=>'string'],'grading_notes'=>['type'=>'string'],'sources'=>['type'=>'array','items'=>['type'=>'object','properties'=>['excerpt'=>['type'=>'string'],'location'=>['type'=>'string']],'required'=>['excerpt','location'],'additionalProperties'=>false]]
        ],'required'=>['id','question','type','difficulty','options','answer','explanation','grading_notes','sources'],'additionalProperties'=>false]]],'required'=>['items'],'additionalProperties'=>false];
    }
}
