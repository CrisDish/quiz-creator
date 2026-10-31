<?php
declare(strict_types=1);
ob_start();
require __DIR__.'/../src/Support/Security.php';
require __DIR__.'/../src/Services/Quiz/Validator.php';
require __DIR__.'/../src/Services/Quiz/Grader.php';
if(!function_exists('mb_strtolower')) { function mb_strtolower(string $s, ?string $encoding=null): string { return strtolower($s); } }
if(!function_exists('mb_stripos')) { function mb_stripos(string $h,string $n,int $o=0,?string $encoding=null): int|false { return stripos($h,$n,$o); } }
use QuizCreator\Services\Quiz\Validator;
use QuizCreator\Services\Quiz\Grader;
use QuizCreator\Support\Security;
$passed=0;$failed=0;
function check(bool $ok,string $name):void{global $passed,$failed;if($ok){echo "PASS {$name}\n";$passed++;}else{echo "FAIL {$name}\n";$failed++;}}
$v=new Validator();$bad=$v->validate(['items'=>[['id'=>'x','question'=>'q','type'=>'essay','difficulty'=>'hard','answer'=>'x','explanation'=>'x','sources'=>[]]]]);check(count($bad['items'])===0,'reject unsupported type and missing evidence');
$valid=$v->validate(['items'=>[['id'=>'x','question'=>'Pick?','type'=>'multiple_choice','difficulty'=>'easy','options'=>array_fill_keys(['A','B','C','D'],['text'=>'x','explanation'=>'why']),'answer'=>'A','explanation'=>'Because','grading_notes'=>'','sources'=>[['excerpt'=>'Handout words','location'=>'Page 1']]]]],'Handout words are the reference.');check(count($valid['items'])===1,'accept structurally valid sourced item');
$unsupportedRef=$v->validate(['items'=>[['id'=>'y','question'=>'Pick?','type'=>'identification','difficulty'=>'easy','options'=>[],'answer'=>'x','explanation'=>'Because','grading_notes'=>'','sources'=>[['excerpt'=>'invented quotation','location'=>'Page 9']]]]],'A short source paragraph.');check(count($unsupportedRef['items'])===0,'reject citation excerpt not in source');
$g=new Grader();$q=['question_type'=>'multiple_choice','answer_key_json'=>'{"answer":"A"}'];check($g->grade($q,'a')===true,'objective answer comparison case-insensitive');$q=['question_type'=>'identification','answer_key_json'=>'{"answer":"Term"}'];check($g->grade($q,' term! ')===true,'identification normalizes punctuation and case');check($g->grade($q,'another phrase')===null,'uncertain free response requires review');
$_SESSION=[];$token=Security::csrf();$_POST=['_csrf'=>$token];try{Security::verifyCsrf();$ok=true;}catch(Throwable){$ok=false;}check($ok,'CSRF token accepted');$_POST=['_csrf'=>'bad'];try{Security::verifyCsrf();$ok=false;}catch(Throwable){$ok=true;}check($ok,'invalid CSRF token rejected');
echo "{$passed} passed, {$failed} failed\n";ob_end_flush();exit($failed?1:0);

