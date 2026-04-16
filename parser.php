<?php
/**
 * SEO PARSER ULTIMATE EDITION 2026
 * Оптимизировано для обхода блокировок и логирования
 */

// --- СЕРВЕРНАЯ ЛОГИКА (API) ---
if (isset($_GET['action']) && $_GET['action'] == 'parse') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');

    $keyword = $_GET['keyword'] ?? 'Salon';
    $page = intval($_GET['page'] ?? 1);
    $outputFile = __DIR__ . '/results.txt';
    $logFile = __DIR__ . '/error_log.txt';
    $cookieFile = __DIR__ . '/cookie.txt';

    $url = "https://statonline.ru/domains?search=" . urlencode($keyword) . "&tld=ru&registered=REGISTERED&page=" . $page;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    // Имитация мобильного устройства
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Mobile Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, 'https://statonline.ru/');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Запись лога для дебага
    $log = "[" . date('H:i:s') . "] Page $page: HTTP $httpCode | Len: " . strlen($html) . " | Err: $curlError\n";
    file_put_contents($logFile, $log, FILE_APPEND);

    $added = 0;
    preg_match_all('/class="domain-name".*?>(.*?)<\/a>/si', $html, $matches);
    
    if (!empty($matches[1])) {
        $current = file_exists($outputFile) ? file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        $currentSet = array_flip($current);
        foreach ($matches[1] as $domain) {
            $domain = strtolower(trim(strip_tags($domain)));
            if (!empty($domain) && !isset($currentSet[$domain])) {
                file_put_contents($outputFile, $domain . PHP_EOL, FILE_APPEND | LOCK_EX);
                $added++;
            }
        }
    }
    
    $total = file_exists($outputFile) ? count(file($outputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
    echo json_encode(['added' => $added, 'total' => $total, 'http' => $httpCode]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Parser Pro</title>
    <style>
        :root { --p: #2563eb; --bg: #f8fafc; }
        body { font-family: sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; width: 100%; max-width: 380px; padding: 40px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); text-align: center; }
        #total { font-size: 72px; font-weight: 800; color: var(--p); display: block; margin: 10px 0; }
        .btn { background: var(--p); color: #fff; border: none; width: 100%; padding: 18px; border-radius: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn:hover { background: #1d4ed8; }
        .links { margin-top: 20px; font-size: 13px; }
        .links a { color: #64748b; margin: 0 10px; text-decoration: none; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="card">
    <div style="font-size: 12px; font-weight: 700; color: #94a3b8; letter-spacing: 1px;">DATABASE ENGINE</div>
    <span id="total">0</span>
    <button class="btn" onclick="run()">START PARSING</button>
    <div id="st" style="margin-top: 15px; font-size: 14px; color: #64748b;">Ready</div>
    <div class="links">
        <a href="results.txt" target="_blank">Results</a>
        <a href="error_log.txt" target="_blank">Logs</a>
    </div>
</div>
<script>
    async function run() {
        document.querySelector('.btn').disabled = true;
        for(let p=1; p<=137; p++) {
            document.getElementById('st').innerText = 'Processing page ' + p;
            try {
                const r = await fetch('?action=parse&page=' + p);
                const d = await r.json();
                document.getElementById('total').innerText = d.total;
                if(d.http == 0) { document.getElementById('st').innerText = 'Network Error (Timeout)'; break; }
            } catch(e) { break; }
            await new Promise(res => setTimeout(res, 2000));
        }
        document.querySelector('.btn').disabled = false;
    }
</script>
</body>
</html>
