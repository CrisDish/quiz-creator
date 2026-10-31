<?php
declare(strict_types=1);
namespace QuizCreator\Services\Documents;

final class Extractor
{
    public function extract(string $path, string $ext): array
    {
        $ext = strtolower($ext); $text = '';
        if (in_array($ext, ['txt','md'], true)) {
            $text = file_get_contents($path) ?: '';
            if (!mb_check_encoding($text, 'UTF-8')) $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        } elseif ($ext === 'pdf') {
            if (!class_exists(\Smalot\PdfParser\Parser::class)) throw new \RuntimeException('PDF extraction needs Composer dependencies. Run composer install.');
            $pdf = (new \Smalot\PdfParser\Parser())->parseFile($path);
            foreach ($pdf->getPages() as $i => $page) $text .= "\n[Page " . ($i + 1) . "]\n" . $page->getText() . "\n";
        } elseif (in_array($ext, ['docx','pptx'], true)) {
            if (!class_exists(\ZipArchive::class)) throw new \RuntimeException('Enable the PHP zip extension in XAMPP.');
            $zip = new \ZipArchive(); if ($zip->open($path) !== true) throw new \RuntimeException('This Office document is corrupt or unreadable.');
            if ($ext === 'docx') {
                $xml = $zip->getFromName('word/document.xml');
                if ($xml === false) { $zip->close(); throw new \RuntimeException('The DOCX document body was not found.'); }
                $text = $this->xmlText($xml, 'w:');
            } else {
                $slides = [];
                for ($i=1; ($xml=$zip->getFromName("ppt/slides/slide{$i}.xml")) !== false; $i++) $slides[] = "[Slide {$i}]\n".$this->xmlText($xml,'a:');
                $text = implode("\n\n", $slides);
            }
            $zip->close();
        } elseif (in_array($ext, ['png','jpg','jpeg','webp'], true)) {
            throw new \RuntimeException('Image uploaded. OCR is not configured in this application yet; image text was not extracted. Configure a documented vision/OCR provider before using this image as source.');
        } else throw new \RuntimeException('Unsupported document type.');
        $text = trim(preg_replace("/[\\t ]+\n/", "\n", $text) ?? $text);
        if (mb_strlen(preg_replace('/\s/u','',$text) ?? '') < 20) throw new \RuntimeException('Very little readable text was extracted. This may be a scanned or image-only document; no content was invented.');
        return ['text'=>$text, 'status'=>'success'];
    }
    private function xmlText(string $xml, string $prefix): string
    {
        $dom = new \DOMDocument(); $dom->resolveExternals = false; $dom->substituteEntities = false;
        if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) throw new \RuntimeException('The Office XML document is corrupt.');
        $xpath = new \DOMXPath($dom); $nodes = $xpath->query('//*[local-name()="p"]'); $out=[];
        foreach ($nodes ?: [] as $node) { $line=''; foreach ($node->childNodes as $child) if ($child->localName === 'r' || $child->localName === 'fld') foreach ($child->childNodes as $part) if ($part->localName === 't') $line .= $part->textContent; if ($line !== '') $out[]=$line; }
        return implode("\n",$out);
    }
}
