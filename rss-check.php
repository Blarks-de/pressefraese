<?php
header('Content-Type: application/json');

if (!isset($_GET['url'])) {
    echo json_encode(["status" => "error", "message" => "Keine URL"]);
    exit;
}

$url = filter_var($_GET['url'], FILTER_VALIDATE_URL);

if (!$url) {
    echo json_encode(["status" => "error", "message" => "Ungültige URL"]);
    exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'RSS-Checker/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(["status" => "error"]);
    exit;
}

// primitive XML-Prüfung
if (strpos($response, '<rss') !== false || strpos($response, '<feed') !== false) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "error"]);
}
