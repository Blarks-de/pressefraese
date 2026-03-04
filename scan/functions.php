<?php
// scan/functions.php - Helper-Funktionen für News-Klopper Scanner

define('LOG_FILE', __DIR__ . '/../data/scan.log');
define('NEWS_CACHE', __DIR__ . '/../data/news.json');
define('USER_AGENT', 'News-Klopper/1.0 (+https://deine-domain.de)');
define('REQUEST_TIMEOUT', 15); // Sekunden pro Feed
define('MIN_DESC_LENGTH', 50); // Mindestlänge für sinnvolle Snippets
define('DEBUG_FILTERS', true); // 🔍 Debug: TRUE = zeige匹配的 Filter-Begriffe im Log

/**
 * Log-Nachricht schreiben (mit Timestamp)
 */
function log_msg(string $message, string $level = 'INFO'): void {
    // Verzeichnis sicherstellen
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $line = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

/**
 * HTTP-Request mit Timeout und User-Agent
 */
function fetch_url(string $url): ?string {
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: " . USER_AGENT . "\r\n",
            'timeout' => REQUEST_TIMEOUT,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    return $result === false ? null : $result;
}

/**
 * RSS-Feed parsen (SimpleXML)
 * @return array<array> Gefundene Artikel
 */
function parse_rss_feed(string $rss_content, string $source_name): array {
    $articles = [];
    
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($rss_content);
    libxml_clear_errors();
    
    if (!$xml) {
        log_msg("Failed to parse RSS for $source_name", 'ERROR');
        return [];
    }
    
    // RSS 2.0 / Atom-Handling
    $items = $xml->channel->item ?? $xml->entry ?? [];
    
    foreach ($items as $item) {
        $title = trim((string)($item->title ?? ''));
        $link = trim((string)($item->link ?? $item->attributes()['href'] ?? ''));
        $pubDate = (string)($item->pubDate ?? $item->published ?? date('c'));
        $desc = trim(strip_tags((string)($item->description ?? $item->summary ?? '')));
        
        // Filter: Nur sinnvolle Einträge
        if (empty($title) || empty($link) || strlen($desc) < MIN_DESC_LENGTH) {
            continue;
        }
        
        // URL bereinigen (Tracking-Parameter entfernen)
        $link = preg_replace('/[?&](utm_|fbclid|ref)=.*/i', '', $link);
        
        $articles[] = [
            'title' => $title,
            'url' => $link,
            'source' => $source_name,
            'published' => date('c', strtotime($pubDate)),
            'fetched' => date('c'),
            'snippet' => mb_substr($desc, 0, 280) . (strlen($desc) > 280 ? '...' : ''),
            'hash' => md5($link) // Für Deduplizierung
        ];
    }
    
    return $articles;
}

/**
 * Artikel deduplizieren (gleiche URL = gleicher Artikel)
 */
function deduplicate_articles(array $articles): array {
    $seen = [];
    $unique = [];
    
    foreach ($articles as $article) {
        $hash = $article['hash'];
        if (!isset($seen[$hash])) {
            $seen[$hash] = true;
            $unique[] = $article;
        }
    }
    
    return $unique;
}

/**
 * News-Cache speichern (JSON)
 */
function save_news_cache(array $articles): bool {
    $cache = [
        'updated' => date('c'),
        'count' => count($articles),
        'articles' => $articles
    ];
    
    // Verzeichnis sicherstellen
    $cacheDir = dirname(NEWS_CACHE);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $result = file_put_contents(NEWS_CACHE, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $result !== false;
}

/**
 * News-Cache laden
 */
function load_news_cache(): ?array {
    if (!file_exists(NEWS_CACHE)) return null;
    $data = json_decode(file_get_contents(NEWS_CACHE), true);
    return is_array($data) ? $data : null;
}

/**
 * Filter-Liste aus config/filters.txt laden
 * @return array<string> Array mit Regex-Patterns
 */
function load_filters(): array {
    $filterFile = __DIR__ . '/../config/filters.txt';
    $filters = [];
    
    if (!file_exists($filterFile)) {
        log_msg("Filter-Datei nicht gefunden: $filterFile", 'WARN');
        return [];
    }
    
    $lines = file($filterFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Kommentare und leere Zeilen überspringen
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Als Regex-Pattern speichern (case-insensitive)
        $filters[] = '/' . preg_quote($line, '/') . '/i';
    }
    
    return $filters;
}

/**
 * Whitelist aus config/whitelist.txt laden
 * @return array<string> Array mit Regex-Patterns
 */
function load_whitelist(): array {
    $whitelistFile = __DIR__ . '/../config/whitelist.txt';
    $patterns = [];
    
    if (!file_exists($whitelistFile)) {
        // Kein Fehler, Whitelist ist optional
        return [];
    }
    
    $lines = file($whitelistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Kommentare und leere Zeilen überspringen
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Als Regex-Pattern speichern (case-insensitive)
        $patterns[] = '/' . preg_quote($line, '/') . '/i';
    }
    
    return $patterns;
}

/**
 * Prüft, ob ein Artikel gefiltert werden soll
 * Reihenfolge: 1. Whitelist prüfen → 2. Filter prüfen
 * @return bool TRUE = Artikel verwerfen, FALSE = behalten
 */
function should_filter_article(string $title, string $desc): bool {
    static $filters = null;
    static $whitelist = null;
    
    // Einmaliges Laden der Listen (Performance)
    if ($filters === null) {
        $filters = load_filters();
    }
    if ($whitelist === null) {
        $whitelist = load_whitelist();
    }
    
    $text = $title . ' ' . $desc;
    
    // 1. WHITELIST PRÜFEN (Ausnahmen zuerst!)
    if (!empty($whitelist)) {
        foreach ($whitelist as $pattern) {
            if (preg_match($pattern, $text)) {
                // 🔍 Debug: Zeige Whitelist-Treffer
                if (DEBUG_FILTERS) {
                    $matched_term = clean_pattern_for_log($pattern);
                    log_msg("✓ Whitelist-Treffer ('$matched_term') → Artikel behalten: $title", 'DEBUG');
                }
                return false; // Whitelist-Treffer → Artikel NICHT filtern
            }
        }
    }
    
    // 2. FILTER PRÜFEN (nur wenn keine Whitelist-Exception)
    if (!empty($filters)) {
        foreach ($filters as $pattern) {
            if (preg_match($pattern, $text)) {
                // 🔍 Debug: Zeige GENAU, welcher Filter-Begriff getriggert hat
                if (DEBUG_FILTERS) {
                    $matched_term = clean_pattern_for_log($pattern);
                    log_msg("⊘ Filter-Treffer ('$matched_term') → Artikel verworfen: $title", 'FILTER');
                }
                return true; // Filter-Treffer → Artikel verwerfen
            }
        }
    }
    
    // Kein Treffer in beiden Listen → Artikel behalten
    return false;
}

/**
 * 🔍 Helper: Bereinigt Regex-Pattern für lesbares Logging
 * Wandelt '/verkehr/i' → 'verkehr' um
 */
function clean_pattern_for_log(string $pattern): string {
    // Entferne Regex-Delimiter und Flags
    $clean = preg_replace('/^\/(.+)\/[a-z]*$/i', '$1', $pattern);
    // Entferne preg_quote-Escapes für bessere Lesbarkeit
    $clean = str_replace(['\\/', '\\.', '\\-', '\\?'], ['/', '.', '-', '?'], $clean);
    return $clean;
}