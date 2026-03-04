<?php
// digest.php – erzeugt täglichen News-Digest
date_default_timezone_set('Europe/Berlin');

// Helper: Log mit Zeitstempel hh:mm:ss
function logMsg($msg) {
    echo date('H:i:s') . " $msg\n";
}

// Modell definieren
$model = "llama3.2:latest";

// Sicherheitsprüfung
if (!isset($model) || empty($model)) {
    logMsg("Fehler: Kein Modell definiert.");
    exit(1);
}

// Ollama-Host (zentrale Variable – wird überall verwendet)
$ollamaHost = "http://100.92.3.18:11434";
// Adresse für Ollama Strato:   172.16.6.4
// Adresse für Ollama Dockfish: 100.92.3.18

// Prüfen, ob Ollama erreichbar ist und Modell existiert
$ch = curl_init("$ollamaHost/api/tags");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$tagResponse = curl_exec($ch);

if ($tagResponse === false) {
    logMsg("Fehler: Ollama nicht erreichbar - " . curl_error($ch));
    curl_close($ch);
    exit(1);
}

curl_close($ch);

$data = json_decode($tagResponse, true);
$models = array_column($data['models'] ?? [], 'name');

if (!in_array($model, $models)) {
    logMsg("Fehler: Modell '$model' nicht gefunden.");
    exit(1);
}

$newsFile = __DIR__ . '/data/news.json';

if (!file_exists($newsFile)) {
    logMsg("Fehler: news.json nicht gefunden.");
    exit(1);
}

$newsData = json_decode(file_get_contents($newsFile), true);
$articles = $newsData['articles'] ?? [];

$since = time() - 86400; // letzte 24 Stunden

// 1. Nur Artikel der letzten 24h
$filtered = array_filter($articles, function($a) use ($since) {
    return strtotime($a['published'] ?? '') >= $since;
});

// 2. Maximal 2 Artikel pro Quelle
$grouped = [];
foreach ($filtered as $a) {
    $source = strtolower(trim($a['source'] ?? 'unbekannt'));
    if (!isset($grouped[$source])) {
        $grouped[$source] = [];
    }
    if (count($grouped[$source]) < 2) {
        $grouped[$source][] = $a;
    }
}

// 3. LLM-Input bauen
$llmInput = "";
foreach ($grouped as $source => $items) {
    foreach ($items as $a) {
        $llmInput .= "Quelle: {$a['source']}\n";
        $llmInput .= "{$a['title']}\n";
        $llmInput .= "{$a['snippet']}\n\n";
    }
}

if (empty($llmInput)) {
    logMsg("Keine aktuellen Artikel gefunden.");
    exit(0);
}

$prompt = "Erstelle eine sachliche Zusammenfassung der wichtigsten Themen aus diesen Meldungen.\n"
        . "Ordne nach Relevanz. Ignoriere Sport und Klatsch.\n"
        . "Kennzeichne innenpolitisch, außenpolitisch und wirtschaftlich.\n\n"
        . $llmInput;

// 4. Ollama ansprechen – AUSSCHLIESSLICH über Variable $ollamaHost
$ch = curl_init("$ollamaHost/api/generate");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        "model" => $model,
        "prompt" => $prompt,
        "stream" => false
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    logMsg("Fehler: Ollama API returned HTTP $httpCode");
    exit(1);
}

$result = json_decode($response, true)['response'] ?? null;

if ($result === null) {
    logMsg("Fehler: Keine gültige Antwort von Ollama.");
    exit(1);
}

// 5. Speichern
$today = date('Y-m-d');
$digestPath = __DIR__ . "/data/digest-$today.txt";

if (file_put_contents($digestPath, $result) === false) {
    logMsg("Fehler: Konnte Digest nicht speichern.");
    exit(1);
}

logMsg("Digest erstellt: digest-$today.txt");
exit(0);