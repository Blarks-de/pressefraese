<?php
// config/quellen.php - Synchronisiert die quellen.md mit dem System

$mdFile = __DIR__ . '/../quellen.md';
$quellen = [];
$currentRegion = 'WELT'; // Default

if (file_exists($mdFile)) {
    $lines = file($mdFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // 1. Region finden (z.B. **HAMBURG**, **DE**, **EU**)
        if (preg_match('/\*\*([A-Z\s]+)\*\*/', $line, $matches)) {
            $currentRegion = trim($matches[1]);
            continue;
        }

        // 2. Tabellenzeile parsen
        if (str_contains($line, '|') && !str_contains($line, ':---') && !str_contains($line, 'Medium / Quelle')) {
            $parts = explode('|', $line);
            if (count($parts) < 7) continue;

            $rss = trim($parts[6]);
            
            // Nur Quellen mit RSS oder YouTube aufnehmen
            if (!empty($rss) && !str_contains($rss, 'kein RSS')) {
                $quellen[$currentRegion][] = [
                    'name'        => trim(str_replace('**', '', $parts[2])),
                    'flag'        => trim($parts[1]),
                    'url'         => trim($parts[5]),
                    'rss'         => ($rss === '*(YouTube)*') ? trim($parts[5]) : $rss,
                    'ausrichtung' => trim($parts[3]),
                ];
            }
        }
    }
}

return $quellen;