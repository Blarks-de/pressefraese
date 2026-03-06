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
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 9) continue; // Jetzt 8 Spalten + Ränder = 9

            // RSS-URL säubern: Backticks und Whitespace entfernen
            $rss = trim(str_replace('`', '', $parts[6]));
            $metaRaw = trim($parts[8], '`');
            
            // Nur Quellen mit RSS oder YouTube aufnehmen
            if (!empty($rss) && stripos($rss, 'kein rss') === false) {
                
                // Meta parsen (optional, aber nützlich)
                $meta = [];
                if (!empty($metaRaw)) {
                    foreach (explode('|', $metaRaw) as $pair) {
                        if (strpos($pair, ':') !== false) {
                            [$k, $v] = explode(':', $pair, 2);
                            $meta[trim($k)] = trim($v);
                        }
                    }
                }
                
                $quellen[$currentRegion][] = [
                    'name'        => trim(str_replace('**', '', $parts[2])),
                    'flag'        => trim($parts[1]),
                    'url'         => trim($parts[5]),
                    'rss' => (stripos($rss, 'youtube') !== false) ? trim($parts[5]) : $rss, // $rss ist bereits gesäubert
                    'ausrichtung' => trim($parts[3]),
                    'status'      => trim($parts[7]), // ✅ 💰 ❌
                    'bias_score'  => isset($meta['bias']) ? floatval($meta['bias']) : 0.0,
                    'state_type'  => $meta['state'] ?? 'unknown',
                    'owner'       => $meta['owner'] ?? 'unknown',
                    'paywall'     => $meta['paywall'] ?? null,
                ];
            }
        }
    }
}

return $quellen;