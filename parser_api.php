<?php
/**
 * BACKEND API - SEO PARSER 2026
 * Размещается на GitHub / Деплоится как API
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Позволяет фронтенду с i-tv.top делать запросы

if (isset($_GET['action']) && $_GET['action'] == 'parse') {
    $keyword = $_GET['keyword'] ?? 'Salon';
    $page = intval($_GET['page'] ?? 1);
    $outputFile = __DIR__ . '/results.txt';
    $logFile = __DIR__ . '/error_log.txt';

    $url = "https://statonline.ru/domains?search=" . urlencode($keyword) . "&tld=ru&registered=REGISTERED&page=" . $page;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $added = 0;
    preg_match_all('/class="domain-name".*?>(.*?)<\/a>/si', $html, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $domain) {
            $domain = strtolower(trim(strip_tags($domain)));
            if (!empty($domain)) {
                file_put_contents($outputFile, $domain . PHP_EOL, FILE_APPEND | LOCK_EX);
                $added++;
            }
        }
    }

    $total = file_exists($outputFile) ? count(file($outputFile, FILE_IGNORE_NEW_LINES)) : 0;
    
    echo json_encode([
        'status' => 'success',
        'added' => $added,
        'total' => $total,
        'page' => $page
    ]);
    exit;
}
