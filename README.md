# 📰 Die Pressefräse

Ein privates Framework zur Aggregation, Analyse und Verdichtung internationaler Nachrichtenquellen. Die Pressefräse ist darauf ausgelegt, mediale Ausrichtungen (Bias) sichtbar zu machen und komplexe Nachrichtenlagen mittels lokaler KI (Ollama) zu "verdauen".

## 🛠️ Technische Architektur

### Backend & Logik
* **Core:** PHP 8.x (Dashboard & AJAX-Steuerung)
* **Datenschicht:** Dateibasiert via JSON (`news.json`, `rss-status.json`, `bias.json`)
* **Scraping-Engine:** `scraper.php` (getriggert via Background-Prozess aus dem UI oder Cron)
* **Infrastruktur:** Docker-basiert, läuft aktuell auf einem Strato VPS.

### KI-Integration (Ollama)
Die Pressefräse nutzt eine Anbindung an **Ollama** (gehostet auf dem *Dockfish*), um:
* Artikel inhaltlich zu gruppieren.
* Länder-Digests im HTML-Format zu generieren (`data/digest-*.html`).
* Nachrichten "zu verdauen" und wesentliche Narrative zu extrahieren.

## 📊 Features
* **Multi-Tab Dashboard:**
    * `News Roh`: Aktuelle Meldungen nach Regionen und Medien sortiert.
    * `News Verdichtet`: KI-generierte Zusammenfassungen der Weltlage.
    * `Quellenstatus`: Live-Check der RSS-Feeds inkl. Bias-Analyse (Staatshintergrund, Tonlage, politische Ausrichtung).
* **Vibe-Coding:** Das Projekt wurde unter Einbeziehung moderner KI-Unterstützung entwickelt.
* **Responsive UI:** Dark-Mode Dashboard mit Sticky-Navigation für schnellen Zugriff auf Regionen (Lokal bis Global).

## 🚀 Setup & Betrieb
1. **Konfiguration:** Quellen werden in `config/quellen.php` und `quellen.md` verwaltet.
2. **Scan-Trigger:** Der Scan kann manuell über den "🔁 Scannen"-Button (AJAX) oder via `rss-cron.php` gestartet werden.
3. **Datenfluss:** Scraper -> `news.json` -> UI / Digest-Generator.

## 🛠️ Deep Dive: Scraper & KI-Pipeline

### Der Scraper (`scan/scraper.php`)
Das Herzstück der Datengewinnung. Er arbeitet modular und robust:
* **Rate-Limiting:** Eingebaute `usleep()`-Pausen, um Quellen nicht zu fluten.
* **Deduplizierung:** Verhindert doppelte Meldungen in der `news.json`.
* **Flexible Filter:** `should_filter_article()` sortiert Rauschen (z.B. Werbung oder irrelevante Themen) bereits beim Scan aus.
* **CLI & Web:** Läuft sowohl als Cronjob auf dem Strato-Server als auch via AJAX-Trigger aus dem Dashboard.

### Hybride KI-Infrastruktur 🧠
Die Pressefräse nutzt eine verteilte Architektur, um Performance und Erreichbarkeit zu optimieren:

1. **Frontend/Crawler (Strato VPS):** Das Dashboard und der Scraper laufen 24/7 im Netz.
2. **KI-Rechenpower (Dockfish):** Die rechenintensive "Verdauung" der News erfolgt lokal auf dem Dockfish.
3. **Vernetzung (Tailscale):** Die Kommunikation zwischen dem PHP-Frontend auf Strato und der Ollama-API auf dem Dockfish erfolgt sicher über ein Tailscale-Mesh-VPN.

### Docker & Ollama
Die `compose.yml` integriert Ollama in das bestehende n8n-Netzwerk, was automatisierte Workflows (z.B. via n8n für komplexere Agenten-Logik) ermöglicht.

```bash
# Beispiel: Den Digest manuell vom VPS am Dockfish triggern
curl http://[DOCKFISH-TAILSCALE-IP]:11434/api/generate -d '{
  "model": "llama3",
  "prompt": "Fasse diese News zusammen: ..."
}'

## 🚀 TODO / Roadmap

### 1. YouTube-Fräse (Intelligente Video-Analyse)
* **Status:** In Planung 
* **Ziel:** Integration von Video-Content ohne den Zeitaufwand von 45-Minuten-Sessions.
* **Workflow:** * `scraper.php` extrahiert Video-URLs aus RSS-Feeds.
    * **Gemini-Integration:** Übergabe der URLs an Gemini zur Inhaltsanalyse.
    * **Output:** Automatisierte Zusammenfassung (3-5 Kernpunkte) inkl. Direktlink zum Video im Dashboard.

### 2. Automatisierte "Verdauung" (Dockfish-Pipeline)
* **Ziel:** Den "News Verdichtet"-Tab vollautomatisch via Cronjob befüllen.
* **Workflow:** Scraper (Strato) -> Trigger via Tailscale -> Ollama (Dockfish) -> Rückgabe der `digest-*.html`.

### 3. Erweiterung der Bias-Datenbank
* Kontinuierliche Pflege der `bias.json`, um noch mehr internationale Perspektiven (Asien, Südamerika) mit Metadaten zu hinterlegen.

### 4. UI-Optimierung für Multimedia
* Implementierung von Video-Cards im "News Roh"-Tab.
* Anzeige von Thumbnail-Placeholdern für analysierte YouTube-Beiträge.

---
*„Die Pressefräse fräst, damit du nicht alles selbst lesen (oder sehen) musst.“*