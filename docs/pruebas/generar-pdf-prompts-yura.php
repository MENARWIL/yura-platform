<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$markdownPath = __DIR__ . '/prompts-pruebas-yura-adaptados.md';
$pdfPath = __DIR__ . '/prompts-pruebas-yura-adaptados.pdf';
$lines = file($markdownPath, FILE_IGNORE_NEW_LINES);
$html = '';
$inList = false;
$paragraph = [];

$flushParagraph = function () use (&$html, &$paragraph): void {
    if ($paragraph === []) {
        return;
    }
    $text = implode(' ', $paragraph);
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    $html .= '<p>' . $text . '</p>';
    $paragraph = [];
};

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '') {
        $flushParagraph();
        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }
        continue;
    }

    if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $match)) {
        $flushParagraph();
        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }
        $level = strlen($match[1]);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($match[2], ENT_QUOTES, 'UTF-8'));
        $html .= '<h' . $level . '>' . $text . '</h' . $level . '>';
        continue;
    }

    if ($trimmed === '---') {
        $flushParagraph();
        $html .= '<hr>';
        continue;
    }

    if (preg_match('/^-\s+(.+)$/', $trimmed, $match) || preg_match('/^\d+\.\s+(.+)$/', $trimmed, $match)) {
        $flushParagraph();
        if (!$inList) {
            $html .= '<ul>';
            $inList = true;
        }
        $text = htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        $html .= '<li>' . $text . '</li>';
        continue;
    }

    if ($inList) {
        $html .= '</ul>';
        $inList = false;
    }

    $paragraph[] = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
}

$flushParagraph();
if ($inList) {
    $html .= '</ul>';
}

$document = '<!doctype html><html><head><meta charset="UTF-8"><style>
@page { margin: 18mm 16mm; }
body { font-family: DejaVu Sans, sans-serif; color: #20252b; font-size: 10pt; line-height: 1.42; }
h1 { color: #164e63; font-size: 20pt; border-bottom: 2px solid #0e7490; padding-bottom: 6px; }
h2 { color: #155e75; font-size: 15pt; margin-top: 18px; }
h3 { color: #334155; font-size: 11.5pt; margin-top: 13px; }
p { margin: 6px 0; text-align: justify; }
ul { margin-top: 4px; margin-bottom: 8px; }
li { margin: 3px 0; }
strong { color: #0f3d4c; }
code { font-family: DejaVu Sans Mono, monospace; font-size: 8.5pt; background: #eef3f5; padding: 1px 3px; }
hr { border: 0; border-top: 1px solid #b7c8ce; margin: 14px 0; }
</style></head><body>' . $html . '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($document, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
file_put_contents($pdfPath, $dompdf->output());

echo "PDF generado: {$pdfPath}" . PHP_EOL;
