<?php
date_default_timezone_set('Europe/Berlin');
$mdFile = __DIR__ . '/quellen.md';
$jsonFile = __DIR__ . '/rss-status.json';

if (!file_exists($mdFile)) {
    exit("quellen.md nicht gefunden\n");
}

$markdown = file($mdFile);
$feeds = [];

foreach ($markdown as $line) {
    if (preg_match('/\|\s*https?:\/\/[^|]+\|\s*(https?:\/\/[^|\s]+)/', $line, $matches)) {
        $feeds[] = trim($matches[1]);
    }
}

$results = [];
$results['checked_at'] = date('c');
$results['feeds'] = [];

foreach ($feeds as $url) {

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'Blarks-RSS-Checker/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = "fail";

    if ($httpCode === 200 && $response) {
        if (strpos($response, '<rss') !== false || 
            strpos($response, '<feed') !== false) {
            $status = "ok";
        }
    }

    $results['feeds'][$url] = $status;
}

file_put_contents($jsonFile, json_encode($results, JSON_PRETTY_PRINT));

echo "RSS-Check abgeschlossen\n";
