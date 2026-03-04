<?php
// digest_country.php – erzeugt länderspezifischen News-Digest
date_default_timezone_set('Europe/Berlin');

function logMsg($msg) {
    echo date('H:i:s') . " $msg\n";
}

// ===== Parameter prüfen =====
if (php_sapi_name() === 'cli') {
    $land = $argv[1] ?? null;
} else {
    $land = $_GET['land'] ?? null;
}

if (!$land) {
    echo "Kein Land angegeben.\n";
    exit(1);
}

$land = trim($land);
$landSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $land));

// ===== Modell =====
$model = "llama3.2:latest";
$ollamaHost = "http://100.92.3.18:11434";

// ===== Modell prüfen =====
$ch = curl_init("$ollamaHost/api/tags");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$tagResponse = curl_exec($ch);
curl_close($ch);

$data = json_decode($tagResponse, true);
$models = array_column($data['models'] ?? [], 'name');

if (!in_array($model, $models)) {
    logMsg("Fehler: Modell nicht gefunden.");
    exit(1);
}

// ===== News laden =====
$newsFile = __DIR__ . '/data/news.json';

if (!file_exists($newsFile)) {
    logMsg("Fehler: news.json nicht gefunden.");
    exit(1);
}

$newsData = json_decode(file_get_contents($newsFile), true);
$articles = $newsData['articles'] ?? [];

$since = time() - 86400;

// Nur letzte 24h
$filtered = array_filter($articles, function($a) use ($since, $land) {
    $published = strtotime($a['published'] ?? '');
    $countryMatch = stripos($a['country'] ?? '', $land) !== false;
    return $published >= $since && $countryMatch;
});

// Max 2 Artikel pro Quelle
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

// LLM Input
$llmInput = "";
foreach ($grouped as $source => $items) {
    foreach ($items as $a) {
        $llmInput .= "Quelle: {$a['source']}\n";
        $llmInput .= "{$a['title']}\n";
        $llmInput .= "{$a['snippet']}\n\n";
    }
}

if (empty($llmInput)) {
    logMsg("Keine aktuellen Artikel für $land.");
    exit(0);
}

// ===== Vollständiger strukturierter Prompt =====
$prompt = "Erstelle eine sachliche Zusammenfassung der wichtigsten Themen aus den drei reichweitenstärksten überregionalen Nachrichtenmedien in $land vom heutigen Tag.\n"
        . "Gib keine einzelnen Schlagzeilen wieder und zitiere nicht wörtlich.\n"
        . "Fasse zusammen, welche Themen die öffentliche Wahrnehmung dominieren.\n"
        . "Sportberichte und Klatsch vollständig ignorieren.\n"
        . "Wirtschaftliche Kennzahlen und Geldbeträge in Euro angeben.\n"
        . "Ordne nach Relevanz.\n"
        . "Kennzeichne innenpolitische, außenpolitische und wirtschaftliche Themen getrennt.\n"
        . "Wenn mehrere Medien dasselbe Thema bringen, als Schwerpunkt hervorheben.\n"
        . "Nenne die Medien beim Namen.\n\n"
        . $llmInput;

// ===== Ollama Anfrage =====
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
curl_close($ch);

$result = json_decode($response, true)['response'] ?? null;

if (!$result) {
    logMsg("Fehler: Keine gültige Antwort.");
    exit(1);
}

// ===== Speicherung mit Zeitstempel =====
$timestamp = date('Y-m-d H:i:s');
$storageFile = __DIR__ . "/data/digest-$landSlug.html";

$newEntry  = "<div class='digest-entry'>";
$newEntry .= "<h3>$land – $timestamp</h3>";
$newEntry .= "<pre>" . htmlspecialchars($result) . "</pre>";
$newEntry .= "</div>\n<hr>\n";

// Neueste Einträge oben einfügen
$existing = file_exists($storageFile) ? file_get_contents($storageFile) : "";
file_put_contents($storageFile, $newEntry . $existing);

logMsg("Digest für $land gespeichert.");
exit(0);