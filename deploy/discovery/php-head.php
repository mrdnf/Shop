<?php
declare(strict_types=1);
// Public metadata only: never serialize sessions, users, account records, or credentials.
$seoConfig = json_decode((string) file_get_contents(__DIR__.'/site.json'), true, 512, JSON_THROW_ON_ERROR);
$seoPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$seoPage = $seoConfig['pages'][$seoPath] ?? null;
if ($seoPage === null) {
    echo '<meta name="robots" content="noindex, nofollow">';
} else {
    if (!$seoConfig['has_description']) {
        echo '<meta name="description" content="'.htmlspecialchars($seoPage['description'], ENT_QUOTES, 'UTF-8').'">';
    }
    echo (string) file_get_contents(__DIR__.'/public/head/'.hash('sha256', $seoPath).'.html');
}
