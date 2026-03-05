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
