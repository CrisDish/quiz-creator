<?php
declare(strict_types=1);
namespace QuizCreator\Services\AI;

abstract class HttpProvider implements Provider
{
    public function __construct(protected string $key, protected string $model, protected int $timeout) {}
    protected function request(string $url, array $headers, array $body): array
    {
        if ($this->key === '') throw new \RuntimeException('The selected provider API key is missing. Configure it in the server environment and restart Apache.');
        $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),CURLOPT_TIMEOUT=>$this->timeout,CURLOPT_CONNECTTIMEOUT=>15]);
        $raw=curl_exec($ch); $err=curl_error($ch); $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
        if ($raw===false) throw new \RuntimeException('Provider network request failed or timed out.');
        if ($status < 200 || $status >= 300) {
            error_log('AI provider HTTP '.$status.': '.substr(preg_replace('/(key|authorization)[^,}]*/i','$1=[redacted]',$raw) ?? '',0,500));
            throw new \RuntimeException(match($status){401,403=>'Provider rejected credentials. Check the API key and account access.',429=>'Provider rate limit reached. Wait and try again.',default=>'Provider request failed (HTTP '.$status.'). Check server logs for sanitized details.'});
        }
        $data=json_decode($raw,true); if(!is_array($data)) throw new \RuntimeException('Provider returned an unreadable response.'); return $data;
    }
}
