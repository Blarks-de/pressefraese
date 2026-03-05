<?php
// scan/scraper.php - RSS Scanner für News-Klopper
// Aufruf: php scraper.php  ODER  https://blarks.de/news-klopper/scan/scraper.php

// Pfad-Anpassung für CLI vs. Web
if (php_sapi_name() === 'cli') {
    chdir(dirname(__DIR__));
}
require_once __DIR__ . '/functions.php';

// Konfiguration laden
$quellenFile = __DIR__ . '/../config/quellen.php';
if (!file_exists($quellenFile)) {
    die("ERROR: Quellen-Datei nicht gefunden: $quellenFile\n");
}
$quellen = require $quellenFile;

log_msg("=== Scan gestartet ===");
$start_time = microtime(true);
$all_articles = [];
$stats = ['success' => 0, 'failed' => 0, 'skipped' => 0, 'filtered' => 0];

// Hauptschleife: Geht durch jede Region (z.B. HAMBURG, DE, WELT)
foreach ($quellen as $region => $sources) { 
    if (!is_array($sources) || empty($sources)) continue;
    
    log_msg("Verarbeite Region: $region (" . count($sources) . " Quellen)");
    
    // Untergeordnete Schleife: Geht durch jedes Medium in dieser Region
    foreach ($sources as $source) {
        if (!is_array($source)) continue;

        $name = $source['name'] ?? 'Unbekannt';
        $rss_url = $source['rss'] ?? null;
    
        // Skip wenn kein RSS-Feed vorhanden
        if (empty($rss_url)) {
            log_msg("⊘ $name: Kein RSS-Feed definiert", 'SKIP');
            $stats['skipped']++;
            continue;
        }
        
        // YouTube-Feeds speziell behandeln
        if (str_contains($rss_url, 'youtube.com') || str_contains($rss_url, 'youtu.be')) {
            log_msg("⚠ $name: YouTube-Feed benötigt spezielle Behandlung (TODO)", 'INFO');
            $stats['skipped']++;
            continue;
        }
        
        log_msg("📡 Fetch: $name → $rss_url");
        
        $rss_content = fetch_url($rss_url);
        if (!$rss_content) {
            log_msg("❌ $name: Feed nicht abrufbar", 'ERROR');
            $stats['failed']++;
            continue;
        }
        
        $articles = parse_rss_feed($rss_content, $name);
        if (empty($articles)) {
            log_msg("⚠ $name: Keine validen Artikel gefunden", 'WARN');
            $stats['skipped']++;
            continue;
        }

        // Region (z.B. HAMBURG) in jeden Artikel schreiben für die spätere Anzeige
        foreach ($articles as &$article) {
            $article['country'] = $region; 
        }
        unset($article);

        // Filterung (unerwünschte Begriffe aussortieren)
        $unfiltered_articles = [];
        foreach ($articles as $article) {
            if (should_filter_article($article['title'], $article['snippet'])) {
                log_msg("⊘ $name: Gefiltert → " . $article['title'], 'FILTER');
                $stats['filtered']++;
            } else {
                $unfiltered_articles[] = $article;
            }
        }
        
        $all_articles = array_merge($all_articles, $unfiltered_articles);
        $stats['success']++;
        
        log_msg("✅ $name: " . count($unfiltered_articles) . " Artikel");
        
        // Rate-Limiting: 300ms Pause zwischen den Quellen
        usleep(300000); 
    } 
}

// Deduplizieren (doppelte URLs entfernen) & Sortieren (neueste zuerst)
$all_articles = deduplicate_articles($all_articles);
usort($all_articles, fn($a, $b) => strtotime($b['published']) <=> strtotime($a['published']));

// Cache speichern (data/news.json)
if (save_news_cache($all_articles)) {
    log_msg("💾 Cache gespeichert: " . count($all_articles) . " Artikel in " . NEWS_CACHE);
} else {
    log_msg("❌ Cache konnte nicht gespeichert werden", 'ERROR');
}

// Statistik ausgeben
$duration = round(microtime(true) - $start_time, 2);
$summary = sprintf(
    "Scan beendet: %d erfolgreich, %d fehlgeschlagen, %d übersprungen, %d gefiltert | %d Artikel gesamt | %.2fs",
    $stats['success'], $stats['failed'], $stats['skipped'], $stats['filtered'], count($all_articles), $duration
);
log_msg($summary);

// CLI-Output für das Terminal
if (php_sapi_name() === 'cli') {
    echo "\n$summary\n";
    exit($stats['failed'] > 0 ? 1 : 0);
}

// Web-Output (JSON für AJAX/Dashboard)
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'stats' => $stats,
    'articles_count' => count($all_articles),
    'duration_sec' => $duration
], JSON_UNESCAPED_UNICODE);