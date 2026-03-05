<?php
// index.php - Newsfräse Dashboard
require_once __DIR__ . '/scan/functions.php';

// 1. Konfiguration & Status laden
$quellenFile = __DIR__ . '/config/quellen.php';
$quellen = file_exists($quellenFile) ? require $quellenFile : [];
$regionOrder = array_keys($quellen);

$activeTab = $_GET['tab'] ?? 'raw';
$scanTriggered = isset($_GET['scan']);

// 2. Cache-Daten laden
$newsFile = __DIR__ . '/data/news.json';
$allArticles = [];
$timestamp = "--:--";

if (file_exists($newsFile)) {
    $newsData = json_decode(file_get_contents($newsFile), true);
    $allArticles = $newsData['articles'] ?? [];
    $timestamp = isset($newsData['last_scan']) ? date('d.m.Y H:i', strtotime($newsData['last_scan'])) : date('d.m.Y H:i', filemtime($newsFile));
}

// 3. Artikel nach Quelle gruppieren
$articlesBySource = [];
foreach ($allArticles as $article) {
    $sourceKey = strtolower(trim($article['source'] ?? 'Unbekannt'));
    $articlesBySource[$sourceKey][] = $article;
}

// 4. Struktur für das UI aufbauen
$regionsWithData = [];
foreach ($quellen as $regionName => $sources) {
    foreach ($sources as $source) {
        $sourceKey = strtolower(trim($source['name']));
        if (!isset($regionsWithData[$regionName])) $regionsWithData[$regionName] = [];

        $regionsWithData[$regionName][$sourceKey] = [
            'meta'     => $source,
            'articles' => $articlesBySource[$sourceKey] ?? []
        ];
    }
}

function make_anchor_id($name) {
    return strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Die Pressefräse</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="main-header">
    <div class="title-image-placeholder">
        <img src="/pic/pressefraese_titel2.webp" alt="Die Pressefräse">
    </div>
    <h1><u>Die Pressefräse</u></h1>
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
</header>

<nav class="top-nav">
    <button class="tab-btn <?= $activeTab === 'raw' ? 'active' : '' ?>" data-tab="raw">📰 News Roh</button>
    <button class="tab-btn <?= $activeTab === 'digest' ? 'active' : '' ?>" data-tab="digest">🧠 verdaute News</button>
    <button class="tab-btn <?= $activeTab === 'sources' ? 'active' : '' ?>" data-tab="sources">🗂️ RSS Quellenstatus</button>
    <div class="nav-spacer"></div>
    <button class="action-btn" id="scanBtn">🔁 Scannen</button>
    <a href="setup.php" class="admin-btn">⚙️</a>
</nav>

<main class="container">
    <div id="tab-raw" class="tab-panel <?= $activeTab === 'raw' ? 'active' : '' ?>">
        <?php foreach ($regionOrder as $region): ?>
            <?php if (empty($regionsWithData[$region])) continue; ?>
            
            <section class="region-section">
                <h2 id="<?= strtolower($region) ?>" class="region-title">
                    <?= htmlspecialchars($region) ?>
                    <a href="#" class="back-to-top">↑</a>
                </h2>

                <?php foreach ($regionsWithData[$region] as $sourceKey => $data): 
                    $meta = $data['meta']; 
                    $articles = $data['articles'];
                    if (empty($articles)) continue; 
                    $sourceAnchor = make_anchor_id($meta['name']);
                ?>
                    <div class="source-block" id="<?= $sourceAnchor ?>">
                        <h3 class="source-header">
                            <span class="flag"><?= htmlspecialchars($meta['flag'] ?? '') ?></span>
                            <?= htmlspecialchars($meta['name']) ?>
                            <span class="article-count">(<?= count($articles) ?>)</span>
                        </h3>

                        <div class="news-grid">
                            <?php foreach ($articles as $article): ?>
                                <article class="news-card">
                                    <div class="card-content">
                                        <h4><a href="<?= htmlspecialchars($article['link'] ?? $article['url']) ?>" target="_blank"><?= htmlspecialchars($article['title']) ?></a></h4>
                                        <p><?= htmlspecialchars($article['snippet'] ?? '') ?></p>
                                        <div class="card-meta">
                                            <span class="time"><?= date('H:i', strtotime($article['published'])) ?> Uhr</span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>

    <div id="tab-digest" class="tab-panel <?= $activeTab === 'digest' ? 'active' : '' ?>">
        <h1>🧠 Länder-Digests</h1>
        <?php
        $digestFiles = glob(__DIR__ . '/data/digest-*.html');
        if ($digestFiles) {
            usort($digestFiles, fn($a, $b) => filemtime($b) <=> filemtime($a));
            foreach ($digestFiles as $file) echo file_get_contents($file);
        }
        ?>
    </div>
</main>

<footer class="container" style="margin-top: 40px; padding: 20px; color: #666; font-size: 0.9rem; border-top: 1px solid #333; text-align: center;">
    <div>News-Klopper • Strato VPS • <a href="data/scan.log" target="_blank">Log</a></div>
    <div>Letzter Scan: <?= htmlspecialchars($timestamp) ?></div>
</footer>

<script src="assets/script.js"></script>
</body>
</html>