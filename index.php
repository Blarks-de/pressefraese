<?php
// index.php - Finale Tab-Version mit Top-Navigation + Titelbild-Placeholder
header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('Europe/Berlin');

// === AJAX-ENDPOINT FÜR SCAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_scan'])) {
    header('Content-Type: application/json');
    $triggerFile = __DIR__ . '/trigger/scan.flag';
    $logFile = __DIR__ . '/data/scan.log';
    if (!is_dir(dirname($triggerFile))) mkdir(dirname($triggerFile), 0755, true);
    file_put_contents($triggerFile, date('c'));
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/scan/scraper.php') . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &';
    @exec($cmd);
    echo json_encode(['success' => true, 'triggered' => true, 'timestamp' => date('c')]);
    exit;
}

// === NORMALE SEITEN-LOGIK ===
$tab = $_GET['tab'] ?? $_POST['tab'] ?? 'raw';
$quellenFile = __DIR__ . '/config/quellen.php';
$quellen = file_exists($quellenFile) ? require $quellenFile : [];

$regionMap = ['DE' => 'National', 'EU' => 'International', 'WELT' => 'Global'];
$structuredSources = [];
foreach ($quellen as $mdRegion => $sources) {
    $uiRegion = $regionMap[$mdRegion] ?? 'Global';
    if (!isset($structuredSources[$uiRegion])) $structuredSources[$uiRegion] = [];
    $structuredSources[$uiRegion][$mdRegion] = $sources;
}
$quellen = $structuredSources;

$scanTriggered = false;
$triggerFile = __DIR__ . '/trigger/scan.flag';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_now']) && !isset($_POST['ajax_scan'])) {
    if (!is_dir(dirname($triggerFile))) mkdir(dirname($triggerFile), 0755, true);
    file_put_contents($triggerFile, date('c'));
    $scanTriggered = true;
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/scan/scraper.php') . ' >> ' . __DIR__ . '/data/scan.log 2>&1 &';
    @exec($cmd);
}

$newsFile = __DIR__ . '/data/news.json';
$newsData = null;
$allArticles = [];
if (file_exists($newsFile)) {
    $newsData = json_decode(file_get_contents($newsFile), true);
    $allArticles = is_array($newsData['articles'] ?? null) ? $newsData['articles'] : [];
}

function group_articles_by_source(array $articles): array {
    $grouped = [];
    foreach ($articles as $article) {
        $source = trim($article['source'] ?? 'Unbekannt');
        $sourceKey = strtolower($source);
        if (!isset($grouped[$sourceKey])) $grouped[$sourceKey] = ['name' => $source, 'articles' => []];
        $grouped[$sourceKey]['articles'][] = $article;
    }
    foreach ($grouped as &$sourceData) {
        usort($sourceData['articles'], fn($a, $b) => strtotime($b['published']) <=> strtotime($a['published']));
    }
    return $grouped;
}
$articlesBySource = group_articles_by_source($allArticles);

function build_source_to_region_map(array $quellen): array {
    $map = [];
    foreach ($quellen as $uiRegion => $countries) {
        foreach ($countries as $countryCode => $sources) {
            foreach ($sources as $source) {
                $name = $source['name'] ?? null;
                if ($name) {
                    $map[strtolower(trim($name))] = [
                        'region' => $uiRegion, 'country' => $countryCode,
                        'flag' => $source['flag'] ?? '🌍', 'ausrichtung' => $source['ausrichtung'] ?? '', 'url' => $source['url'] ?? ''
                    ];
                }
            }
        }
    }
    return $map;
}
$sourceToRegion = build_source_to_region_map($quellen);

$regionOrder = ['Lokal', 'Regional', 'National', 'International', 'Global'];
$regionsWithData = [];
foreach ($quellen as $uiRegion => $countries) {
    foreach ($countries as $countryCode => $sources) {
        foreach ($sources as $source) {
            $sourceKey = strtolower(trim($source['name']));
            if (!isset($regionsWithData[$uiRegion][$countryCode])) $regionsWithData[$uiRegion][$countryCode] = [];
            $regionsWithData[$uiRegion][$countryCode][$sourceKey] = [
                'meta' => $source, 'articles' => $articlesBySource[$sourceKey]['articles'] ?? []
            ];
        }
    }
}

$sourcesWithArticles = [];
foreach ($regionsWithData as $regionData) {
    foreach ($regionData as $countryData) {
        foreach ($countryData as $sourceKey => $sourceData) {
            if (!empty($sourceData['articles'])) {
                $sourcesWithArticles[$sourceKey] = $sourceData['meta']['name'] ?? ucfirst($sourceKey);
            }
        }
    }
}

$timestamp = date('d.m.Y, H:i:s');
$lastScanTime = $newsData['updated'] ?? null;
$activeTab = in_array($tab, ['raw', 'digest', 'sources']) ? $tab : 'raw';

function make_anchor_id($str) { return 'source-' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $str), '-')); }
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Die Pressefr&auml;se</title>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js  "></script>
<style>
/* === BASIS === */
body { 
    font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
    background:#111; 
    color:#eee; 
    margin:0; 
    line-height:1.6; 
    /* ↑ Erhöht von 100px auf 220px, damit Titel unter allen Sticky-Navs sichtbar ist */
    padding-top: 220px; 
}
.container { max-width: 1000px; margin: 0 auto; padding: 20px; }
h1 { color:#00ccff; margin-bottom:10px; font-size: 2rem; }
h2 { color:#00ccff; font-size:1.5rem; margin:60px 0 20px; border-bottom:2px solid #00ccff; padding-bottom:10px; scroll-margin-top: 120px; }
h3 { margin:0 0 5px; font-size:1.1rem; }
h4 { margin:0 0 8px; font-size:1rem; color:#00ccff; }
a { color:#00ccff; text-decoration:none; transition: 0.2s; }
a:hover { color: #fff; text-decoration:underline; }
p { margin:5px 0; }
small { color:#888; }

/* === TITELBILD-PLACEHOLDER === */
.title-image-placeholder {
    width: 80%;
    height: 80%;
    object-fit: contain;
    border-radius: 0 0 8px 8px;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-bottom: 2px solid #00ccff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #00ccff;
    font-size: 0.9rem;
    margin-bottom: 20px;
}
.title-image-placeholder.active { display: flex; }
.title-image-placeholder img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0 0 8px 8px;
}

/* === TOP NAVIGATION (Tabs + Actions) === */
.top-nav {
    position: fixed; top: 0; left: 0; right: 0;
    background: #1a1a1a; border-bottom: 2px solid #00ccff;
    display: flex; align-items: center; gap: 4px;
    padding: 8px 20px; z-index: 10000;
    overflow-x: auto; scrollbar-width: none;
}
.top-nav::-webkit-scrollbar { display: none; }
.tab-btn {
    background: #222; color: #aaa; border: none; border-radius: 6px 6px 0 0;
    padding: 10px 16px; font-size: 0.95rem; font-weight: 500; cursor: pointer;
    transition: all 0.2s; white-space: nowrap; border-bottom: 2px solid transparent;
}
.tab-btn:hover { background: #2a2a2a; color: #fff; }
.tab-btn.active { background: #111; color: #00ccff; border-bottom-color: #00ccff; }
.nav-spacer { flex: 1; }
.action-btn {
    background: #00cc66; color: #111; border: none; border-radius: 4px;
    padding: 8px 14px; font-weight: 600; cursor: pointer; font-size: 0.9rem;
    transition: all 0.2s; white-space: nowrap;
}
.action-btn:hover { background: #00ee77; box-shadow: 0 2px 8px rgba(0,204,102,0.4); }
.action-btn:disabled { background: #444; color: #888; cursor: not-allowed; }
.admin-btn {
    background: #222; color: #ffcc00; border: 1px solid #444; border-radius: 4px;
    padding: 8px 12px; cursor: pointer; font-size: 1.1rem; transition: 0.2s;
}
.admin-btn:hover { background: #333; color: #fff; }

/* === TAB-PANELS === */
.tab-panel { display: none; animation: fadeIn 0.3s ease; }
.tab-panel.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* === STICKY NAV (nur im Raw-Tab) === */
.nav-sticky, .nav-sticky-sources, .nav-sticky-countries, .nav-sticky-media {
    position: fixed; left: 0; right: 0; display: flex; gap: 12px;
    overflow-x: auto; scrollbar-width: none; padding: 8px 20px; z-index: 999;
}
.nav-sticky { top: 48px; background: #222; border-bottom: 2px solid #00ccff; }
.nav-sticky-sources { top: 84px; background: #1a1a1a; border-bottom: 1px solid #333; font-size: 0.85rem; }
.nav-sticky-countries { top: 120px; background: #1a1a1a; border-bottom: 1px solid #333; font-size: 0.85rem; }
.nav-sticky-media { top: 156px; background: #141414; border-bottom: 1px solid #222; font-size: 0.85rem; }
.nav-sticky::-webkit-scrollbar, .nav-sticky-sources::-webkit-scrollbar { display: none; }
.nav-link, .nav-link-source, .nav-item {
    color: #00ccff !important; border: 1px solid #333; padding: 2px 8px;
    border-radius: 4px; white-space: nowrap; font-size: 0.9rem; text-decoration: none;
}
.nav-link-source { color: #ffcc00 !important; border-color: #444; font-weight: normal; }
.nav-link:hover, .nav-link-source:hover, .nav-item:hover { background: #333; color: #fff !important; }

/* === QUELLEN-BLOCK === */
.source-block { margin: 0 0 40px 0; background:#1a1a1a; border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5); overflow: hidden; scroll-margin-top: 180px; }
.source-header { padding:12px 15px; background:#252525; border-bottom:1px solid #333;
    display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px; }
.source-name { font-weight:bold; font-size:1.1rem; color: #00ccff; }
.source-meta { font-size:0.85em; color:#aaa; margin-left:10px; }

/* === ARTIKEL === */
.article { padding:15px 20px; border-bottom:1px solid #2a2a2a; transition: background 0.2s; }
.article:hover { background: #222; }
.article:last-child { border-bottom:none; }
.article-title { color:#fff; font-size:1.05rem; margin:0 0 8px; font-weight: 600; }
.article-snippet { color:#bbb; font-size:0.95em; margin:8px 0; }
.article-meta { color:#666; font-size:0.85em; }
.back-to-top { font-size: 0.8rem; float: right; color: #444; margin-top: -10px; }

/* === INFO-BOX === */
.info-box { background: #2a2a1a; border: 1px solid #ffcc00; border-radius: 8px;
    padding: 15px 20px; margin: 15px 0 25px; color: #ffcc00; font-size: 0.95rem; }
.info-box p { margin: 8px 0; }
.info-box small { color: #ffd700; opacity: 0.9; }

/* === QUELLEN-TAB: TABELLE === */
#quellen-content table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #111; }
#quellen-content th, #quellen-content td { border: 1px solid #333; padding: 10px; text-align: left; }
#quellen-content th { background: #222; color: #00ccff; }
#quellen-content tr:hover { background: #1a1a1a; }
#quellen-content strong { color: #00ccff; }
#quellen-content .ok { color: #00ff88; font-weight: bold; }
#quellen-content .fail { color: #ff4444; font-weight: bold; }
#quellen-content .error { color: #ff4444; border: 1px solid #ff4444; padding: 10px; }
#quellen-content .fresh { color: #00ff88; }
#quellen-content .warning { color: #ffaa00; }
#quellen-content .stale { color: #ff4444; }
#quellen-content details summary { cursor: pointer; font-weight: bold; color: #00ccff; }
#quellen-content details { margin: 4px 0; }
#quellen-content details > div { margin-top: 8px; font-size: 0.9em; color: #ccc; }
#last-check { margin: 10px 0; color: #aaa; font-size: 0.9em; }

/* === RESPONSIVE === */
@media (max-width: 768px) {
    /* ↑ Erhöht von 140px auf 260px für Mobile */
    body { padding-top: 260px; }
    h2 { scroll-margin-top: 110px; }
    .source-block { scroll-margin-top: 220px; }
    .top-nav { padding: 6px 10px; flex-wrap: wrap; }
    .tab-btn { padding: 8px 12px; font-size: 0.85rem; }
    .action-btn, .admin-btn { padding: 6px 10px; font-size: 0.85rem; }
    .nav-sticky, .nav-sticky-sources, .nav-sticky-countries, .nav-sticky-media {
        padding: 6px 10px; font-size: 0.8rem;
    }
    .nav-sticky { top: 40px; }
    .nav-sticky-sources { top: 76px; }
    .nav-sticky-countries { top: 112px; }
    .nav-sticky-media { top: 148px; }
    .source-header { flex-direction:column; align-items:flex-start; }
    .info-box { font-size: 0.9rem; padding: 12px 15px; }
    .title-image-placeholder { height: 120px; font-size: 0.8rem; }
}

/* === DISCLAIMER-BOX === */
.disclaimer-box {
    background: #2a1a1a;
    border: 1px solid #ff4444;
    border-radius: 8px;
    padding: 15px 20px;
    margin: 15px 0 25px;
    color: #ff8888;
    font-size: 0.95rem;
}
.disclaimer-box strong {
    color: #ff4444;
}
.disclaimer-box small {
    color: #ffaaaa;
    opacity: 0.9;
}

</style>
</head>
<body>

<!-- TOP NAVIGATION: Tabs + Scan + Admin -->
<nav class="top-nav">
    <button class="tab-btn <?= $activeTab === 'raw' ? 'active' : '' ?>" data-tab="raw">📰 News Roh</button>
    <button class="tab-btn <?= $activeTab === 'digest' ? 'active' : '' ?>" data-tab="digest">🧠 News Verdichtet</button>
    <button class="tab-btn <?= $activeTab === 'sources' ? 'active' : '' ?>" data-tab="sources">🗂️ Quellenstatus</button>
    <div class="nav-spacer"></div>
    <button class="action-btn" id="scanBtn">🔁 Scannen</button>
    <a href="setup.php" class="admin-btn" title="Einstellungen">⚙️</a>
</nav>

<!-- STICKY NAVS (nur im Raw-Tab sichtbar) -->
<nav class="nav-sticky" id="regionNav" style="display: <?= $activeTab === 'raw' ? 'flex' : 'none' ?>;">
    <span style="color:#00ccff;font-weight:bold;margin-right:10px;">Region:</span>
    <?php foreach ($regionOrder as $region): if (!empty($regionsWithData[$region])): ?>
        <a href="#" data-region="<?= htmlspecialchars($region) ?>" class="nav-link">#<?= htmlspecialchars($region) ?></a>
    <?php endif; endforeach; ?>
</nav>
<nav class="nav-sticky-countries" id="countryNav" style="display: <?= $activeTab === 'raw' ? 'flex' : 'none' ?>;"></nav>
<nav class="nav-sticky-media" id="mediaNav" style="display: <?= $activeTab === 'raw' ? 'flex' : 'none' ?>;"></nav>
<nav class="nav-sticky-sources" id="sourceNav" style="display: <?= $activeTab === 'raw' && !empty($sourcesWithArticles) ? 'flex' : 'none' ?>;">
    <span style="color: #ffcc00; font-weight: bold; margin-right: 10px;">Medien:</span>
    <?php foreach ($sourcesWithArticles as $sourceKey => $sourceName): 
        $anchorId = make_anchor_id($sourceKey);
    ?>
        <a href="#<?= $anchorId ?>" class="nav-link nav-link-source"><?= htmlspecialchars($sourceName) ?></a>
    <?php endforeach; ?>
</nav>

<div class="container">

    <!-- TAB: NEWS ROH -->
    <div id="tab-raw" class="tab-panel <?= $activeTab === 'raw' ? 'active' : '' ?>">
        <?php if ($scanTriggered): ?>
            <div style="background:#1a2a1a; color:#00ff88; padding:10px; margin-bottom:20px; border-radius:4px;">✅ Scan angestoßen!</div>
        <?php endif; ?>

        <!-- === TITELBILD-PLACEHOLDER === -->
        <div class="title-image-placeholder">
            <img src="/pic/pressefraese_titel2.webp" alt="Die Pressefräse">
        </div>

        <h1><u>Die Pressefr&auml;se</u></h1>
        <p style="color:#888; margin-bottom:5px;">Nachrichten aus Barmbek, Hamburg, Deutschland, Europa und der Welt</p>
        <small>Letztes Update: <?= htmlspecialchars($timestamp) ?></small>

        <div class="disclaimer-box">
        <p><strong>Hinweis:</strong> Die „Pressefräse“ ist ein rein privates, nicht-kommerzielles Experimentier- und Lernprojekt.</p>
        <p>Die dargestellten Inhalte werden automatisiert aus öffentlich zugänglichen Quellen aggregiert und<br>
        dienen ausschließlich technischen Testzwecken.</p>
        
        <p>Es besteht kein Anspruch auf redaktionelle Prüfung, Vollständigkeit oder Neutralität.<br>
        Fehlerhafte oder verkürzte Darstellungen sind möglich.</p>
        <p><strong>Dieses Projekt steht in keiner Verbindung zu den genannten Medien oder Institutionen.</strong></p>
        </div>

        <div class="info-box">
            <p>Die Pressefräse fräst sich zu jeder vollen Stunde durch die weltweite Presselandschaft und sammelt<br>
            dabei im ersten Schritt Meldungen von <strong>Medien mit liberaler und konservativer Ausrichtung</strong>, um<br>
            sie miteinander vergleichen zu können.</p>
            <p>Im nächsten Schritt soll eine KI diese Meldungen dann inhaltlich zusammenfassen.</p>
            <p><small>Diese Routine wurde per Vibe-Coding erstellt.</small></p>
        </div>

        <?php foreach ($regionOrder as $region): ?>
        <?php if (empty($regionsWithData[$region])) continue; ?>
        <h2 id="<?= strtolower($region) ?>">
            <?= htmlspecialchars($region) ?>
            <a href="#" class="back-to-top">↑ nach oben</a>
        </h2>
        <?php foreach ($regionsWithData[$region] as $countryCode => $sources): ?>
            <h3 style="margin-top:30px; color:#ffcc00;"><?= htmlspecialchars($countryCode) ?></h3>
            <?php foreach ($sources as $sourceKey => $data): 
                $meta = $data['meta']; $articles = $data['articles'];
                $sourceAnchor = make_anchor_id($meta['name']);
            ?>
                <div class="source-block" id="<?= $sourceAnchor ?>">
                    <div class="source-header">
                        <div>
                            <span class="source-name">
                                <?= htmlspecialchars($meta['flag']) ?> <?= htmlspecialchars($meta['name']) ?>
                            </span>
                            <?php if (!empty($meta['ausrichtung'])): ?>
                                <span class="source-meta">• <?= htmlspecialchars($meta['ausrichtung']) ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= htmlspecialchars($meta['url']) ?>" target="_blank" style="font-size:0.85rem;">Direkt zur Quelle →</a>
                    </div>
                    <?php if (!empty($articles)): ?>
                        <?php foreach ($articles as $article): ?>
                            <div class="article">
                                <h4 class="article-title">
                                    <a href="<?= htmlspecialchars($article['url']) ?>" target="_blank">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h4>
                                <p class="article-snippet"><?= nl2br(htmlspecialchars($article['snippet'])) ?></p>
                                <div class="article-meta">🕐 <?= date('d.m.Y H:i', strtotime($article['published'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:15px; color:#666; font-style:italic;">🔍 Keine aktuellen News im Cache</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <!-- TAB: NEWS VERDICHTET -->
    <div id="tab-digest" class="tab-panel <?= $activeTab === 'digest' ? 'active' : '' ?>">
        <h1>🧠 Länder-Digests</h1>

<?php
$digestFiles = glob(__DIR__ . '/data/digest-*.html');

if (!$digestFiles) {
    echo '<div class="info-box">⚠️ Noch keine Länder-Digests vorhanden.</div>';
} else {

    // Nach Änderungsdatum sortieren (neueste zuerst)
    usort($digestFiles, function($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    foreach ($digestFiles as $file) {
        echo file_get_contents($file);
    }
}
?>
    </div>

    <!-- TAB: QUELLENSTATUS -->
    <div id="tab-sources" class="tab-panel <?= $activeTab === 'sources' ? 'active' : '' ?>">
        <h1>🗂️ Masterliste Nachrichtenquellen</h1>
        <div id="last-check"></div>
        <div id="quellen-content">Lade Quellen...</div>
    </div>

    <footer style="margin-top: 40px; padding: 20px; color: #666; font-size: 0.9rem; border-top: 1px solid #333;">
        <div>News-Klopper • Strato VPS • <a href="data/scan.log" target="_blank">Log</a></div>
        <div id="last-scan">Letzter Scan: <?= $lastScanTime ? date('d.m.Y H:i', strtotime($lastScanTime)) : '--' ?></div>
    </footer>
</div>

<script>
// === TAB-LOGIK ===
const tabButtons = document.querySelectorAll('.tab-btn');
const tabPanels = document.querySelectorAll('.tab-panel');
const stickyNavs = {
    region: document.getElementById('regionNav'),
    country: document.getElementById('countryNav'),
    media: document.getElementById('mediaNav'),
    source: document.getElementById('sourceNav')
};

function switchTab(tabId) {
    tabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabId));
    tabPanels.forEach(panel => panel.classList.toggle('active', panel.id === `tab-${tabId}`));
    const showSticky = (tabId === 'raw');
    Object.values(stickyNavs).forEach(nav => { if (nav) nav.style.display = showSticky ? 'flex' : 'none'; });
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    history.replaceState({}, '', url);
    if (tabId === 'sources' && !window.quellenLoaded) { loadQuellenContent(); window.quellenLoaded = true; }
}

tabButtons.forEach(btn => btn.addEventListener('click', (e) => { e.preventDefault(); switchTab(btn.dataset.tab); }));
const urlParams = new URLSearchParams(window.location.search);
switchTab(urlParams.get('tab') || 'raw');

// === AJAX-SCAN ===
const scanBtn = document.getElementById('scanBtn');
scanBtn?.addEventListener('click', function() {
    const originalText = scanBtn.innerHTML;
    const originalDisabled = scanBtn.disabled;
    scanBtn.innerHTML = '⏳ Starte...';
    scanBtn.disabled = true;
    
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax_scan=1'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            scanBtn.innerHTML = '✅ Gestartet!';
            const lastScanEl = document.getElementById('last-scan');
            if (lastScanEl) {
                const now = new Date();
                lastScanEl.textContent = 'Letzter Scan: ' + now.toLocaleString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
            }
            setTimeout(() => { scanBtn.innerHTML = originalText; scanBtn.disabled = originalDisabled; }, 2500);
        } else throw new Error();
    })
    .catch(() => {
        scanBtn.innerHTML = '❌ Fehler!';
        scanBtn.style.background = '#cc3333';
        setTimeout(() => { scanBtn.innerHTML = originalText; scanBtn.style.background = ''; scanBtn.disabled = originalDisabled; }, 4000);
    });
});

// Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const navHeight = 180;
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
            window.scrollTo({ top: targetPosition, behavior: 'smooth' });
        }
    });
});

// === QUELLEN-TAB: MARKDOWN + STATUS + BIAS ===
async function loadQuellenContent() {
    const contentDiv = document.getElementById('quellen-content');
    try {
        const response = await fetch('quellen.md');
        if (!response.ok) throw new Error("quellen.md nicht gefunden");
        const markdownText = await response.text();
        contentDiv.innerHTML = marked.parse(markdownText);
        insertStatusColumn();
        await applyStatus();
        await applyBias();
    } catch (err) { contentDiv.innerHTML = `<div class="error">${err.message}</div>`; }
}

function insertStatusColumn() {
    const table = document.querySelector('#quellen-content table');
    if (!table) return;
    const headerRow = table.querySelector('tr');
    if (!headerRow.querySelector('.status-header')) {
        const th = document.createElement('th');
        th.textContent = "Status"; th.classList.add('status-header');
        headerRow.insertBefore(th, headerRow.children[5]);
    }
}

async function applyStatus() {
    try {
        const res = await fetch('rss-status.json');
        if (!res.ok) throw new Error();
        const data = await res.json();
        if (data.checked_at) {
            const info = getScanAgeInfo(data.checked_at);
            document.getElementById('last-check').innerHTML = `Letzter Scan: <span class="${info.cssClass}">${info.text}</span>`;
        }
        const rows = document.querySelectorAll('#quellen-content table tr');
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].querySelectorAll('td');
            if (cells.length < 6) continue;
            const rssCell = cells[5]; const link = rssCell.querySelector('a');
            if (!rows[i].querySelector('.status-cell')) {
                const statusCell = document.createElement('td');
                statusCell.classList.add('status-cell');
                rows[i].insertBefore(statusCell, rssCell);
            }
            const statusCell = rows[i].querySelector('.status-cell');
            if (!link) { statusCell.textContent = "–"; continue; }
            const url = link.href; const status = data.feeds?.[url];
            statusCell.innerHTML = status === "ok" ? '<span class="ok">✔</span>' : '<span class="fail">✖</span>';
        }
    } catch { document.getElementById('last-check').textContent = "Kein Status verfügbar."; }
}

function getScanAgeInfo(dateString) {
    const now = new Date(), past = new Date(dateString);
    const diffMin = Math.floor((now - past) / 60000), diffHrs = Math.floor(diffMin / 60), diffDays = Math.floor(diffHrs / 24);
    let text = diffMin < 1 ? "gerade eben" : diffMin < 60 ? `vor ${diffMin} Minute${diffMin!==1?'n':''}` : diffHrs < 24 ? `vor ${diffHrs} Stunde${diffHrs!==1?'n':''}` : `vor ${diffDays} Tag${diffDays!==1?'en':''}`;
    let cssClass = diffMin < 30 ? "fresh" : diffMin < 120 ? "warning" : "stale";
    return { text, cssClass };
}

async function applyBias() {
    try {
        const res = await fetch('bias.json');
        if (!res.ok) return;
        const raw = await res.json();
        const biasData = {};
        for (const key in raw) biasData[normalize(key)] = raw[key];
        const rows = document.querySelectorAll('#quellen-content table tr');
        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].querySelectorAll('td');
            if (cells.length < 2) continue;
            const nameCell = cells[1];
            const mediumName = nameCell.textContent.trim();
            const normalized = normalize(mediumName);
            if (!biasData[normalized]) continue;
            if (nameCell.querySelector('details')) continue;
            const b = biasData[normalized];
            const details = document.createElement('details'), summary = document.createElement('summary');
            summary.textContent = mediumName;
            const info = document.createElement('div');
            info.innerHTML = `<strong>Staat:</strong> ${b.staat}<br><strong>Iran:</strong> ${b.iran}<br><strong>Israel:</strong> ${b.israel}<br><strong>Golfstaaten:</strong> ${b.golf}<br><strong>Ton:</strong> ${b.ton}`;
            details.appendChild(summary); details.appendChild(info);
            nameCell.innerHTML = ''; nameCell.appendChild(details);
        }
    } catch (e) { console.warn("Bias-Daten konnten nicht geladen werden."); }
}

function normalize(str) { return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]/g, ""); }

// === DYNAMISCHE NAVIGATION (nur im Raw-Tab) ===
const structure = <?= json_encode($regionsWithData) ?>;
function buildCountries(region) {
    const countryNav = document.getElementById('countryNav'), mediaNav = document.getElementById('mediaNav');
    countryNav.innerHTML = ''; mediaNav.innerHTML = '';
    if (!structure[region]) return;
    Object.keys(structure[region]).forEach(country => {
        const btn = document.createElement('div');
        btn.className = 'nav-item'; btn.textContent = country;
        btn.onclick = () => buildMedia(region, country);
        countryNav.appendChild(btn);
    });
}
function buildMedia(region, country) {
    const mediaNav = document.getElementById('mediaNav'); mediaNav.innerHTML = '';
    if (!structure[region]?.[country]) return;
    Object.values(structure[region][country]).forEach(source => {
        const btn = document.createElement('div');
        btn.className = 'nav-item'; btn.textContent = source.meta.name;
        btn.onclick = () => {
            const id = 'source-' + source.meta.name.toLowerCase().replace(/[^a-z0-9]+/g,'-');
            const el = document.getElementById(id);
            if (el) window.scrollTo({ top: el.offsetTop - 180, behavior: 'smooth' });
        };
        mediaNav.appendChild(btn);
    });
}
document.querySelectorAll('[data-region]').forEach(link => {
    link.addEventListener('click', function(e) { e.preventDefault(); buildCountries(this.dataset.region); });
});
</script>
</body>
</html>