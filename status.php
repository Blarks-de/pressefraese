<?php
date_default_timezone_set('Europe/Berlin');

$quellen = require __DIR__ . '/config/quellen.php';
$newsData = json_decode(file_get_contents(__DIR__ . '/data/news.json'), true);
$articles = $newsData['articles'] ?? [];

$articleSources = [];
foreach ($articles as $a) {
    $articleSources[strtolower($a['source'])] = true;
}

echo "<h1>Status News-Klopper</h1>";

$total = 0;
$withRSS = 0;
$active = 0;

foreach ($quellen as $region => $sources) {
    echo "<h2>$region</h2>";
    foreach ($sources as $s) {
        $total++;
        $hasRSS = !empty($s['rss']);
        if ($hasRSS) $withRSS++;

        $isActive = isset($articleSources[strtolower($s['name'])]);
        if ($isActive) $active++;

        echo "<div>";
        echo $s['flag'] . " " . $s['name'];
        echo $hasRSS ? " 📡" : " ❌ RSS";
        echo $isActive ? " ✅ Artikel" : " ⚪ leer";
        echo "</div>";
    }
}

echo "<hr>";
echo "Gesamtquellen: $total<br>";
echo "Mit RSS: $withRSS<br>";
echo "Aktiv im Cache: $active<br>";
