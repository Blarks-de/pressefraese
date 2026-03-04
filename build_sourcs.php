<?php
// build_sources.php – generiert config/quellen.php aus deiner quellen.md

$mdFile = __DIR__ . '/quellen.md';
$outFile = __DIR__ . '/config/quellen.php';

if (!file_exists($mdFile)) {
    exit("quellen.md nicht gefunden\n");
}

$lines = file($mdFile);
$sources = [];
$currentRegion = null;

foreach ($lines as $line) {
    $line = trim($line);

    if (empty($line) || !str_starts_with($line, '|')) {
        continue;
    }

    $cols = array_map('trim', explode('|', trim($line, '|')));

    // Region erkennen (z.B. | **DE** | | | | | |
    if (preg_match('/^\*\*(.+)\*\*$/', $cols[0] ?? '', $match)) {
        $currentRegion = strtoupper($match[1]);
        $sources[$currentRegion] = [];
        continue;
    }

    if (!$currentRegion) continue;
    if (($cols[1] ?? '') === 'Medium / Quelle') continue;
    if (count($cols) < 6) continue;

    $flag = trim($cols[0]);
    $name = trim(strip_tags($cols[1]));
    $ausrichtung = trim($cols[2]);
    $url = trim($cols[4]);
    $rss = trim($cols[5]);

    // RSS normalisieren
    if (
        empty($rss) ||
        str_contains(strtolower($rss), 'kein rss') ||
        str_contains(strtolower($rss), 'youtube')
    ) {
        $rss = null;
    }

    $sources[$currentRegion][] = [
        'name' => str_replace(['**'], '', $name),
        'flag' => $flag,
        'url' => $url,
        'rss' => $rss,
        'ausrichtung' => $ausrichtung,
    ];
}

$output = "<?php\nreturn " . var_export($sources, true) . ";\n";
file_put_contents($outFile, $output);

echo "config/quellen.php aktualisiert\n";