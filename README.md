# 📰 Die Pressefräse

Ein privates Framework zur Aggregation, Analyse und Verdichtung internationaler Nachrichtenquellen. Die Pressefräse ist darauf ausgelegt, mediale Ausrichtungen (Bias) sichtbar zu machen und komplexe Nachrichtenlagen mittels lokaler KI (Ollama) zu "verdauen".

### Die Geburtsstunde & der Kern-Prompt
Der Grundgedanke entstand, als mir eine KI zum ersten Mal Nachrichten basierend auf folgendem System-Prompt aufbereitet hat:

> **Prompt:** "Erstelle eine sachliche Zusammenfassung der wichtigsten Themen aus den drei reichweitenstärksten überregionalen Nachrichtenmedien in **{LAND}** vom heutigen Tag.
>
> Gib keine einzelnen Schlagzeilen wieder und zitiere nicht wörtlich. Fasse stattdessen zusammen, welche Themen die öffentliche Wahrnehmung heute dominieren. Sportberichte und Klatsch bitte vollständig ignorieren. Wirtschaftliche Kennzahlen und Geldbeträge bitte in Euro umrechnen.
> 
> Ordne die Themen nach Relevanz. Kennzeichne innenpolitische, außenpolitische und wirtschaftliche Themen getrennt. Wenn mehrere Medien dasselbe Thema bringen, hebe das als Schwerpunkt hervor. Die Zusammenfassung soll kurz und präzise sein. Nenne das berichtende Medium beim Namen."

---

## 🛠️ Technische Architektur

### Backend & Logik
* **Core:** PHP 8.x (Dashboard & AJAX-Steuerung)
* **Datenschicht:** Dateibasiert via JSON (`news.json`, `rss-status.json`, `bias.json`)
* **Scraping-Engine:** `scraper.php` (getriggert via Background-Prozess aus dem UI oder Cron)
* **Infrastruktur:** Docker-basiert, läuft aktuell auf einem Strato VPS.

### KI-Integration (Ollama)
Die Pressefräse nutzt eine Anbindung an **Ollama** (gehostet auf dem **Dockfish**), um:
* Artikel inhaltlich zu gruppieren.
* Länder-Digests im HTML-Format zu generieren (`data/digest-*.html`).
* Nachrichten zu "verdauen" und wesentliche Narrative zu extrahieren.

## 📊 Features
* **Multi-Tab Dashboard:**
    * `News Roh`: Aktuelle Meldungen nach Regionen und Medien sortiert.
    * `News Verdichtet`: KI-generierte Zusammenfassungen der Weltlage.
    * `Quellenstatus`: Live-Check der RSS-Feeds inkl. Bias-Analyse (Staatshintergrund, Tonlage, politische Ausrichtung).
* **Vibe-Coding:** Das Projekt wurde unter Einbeziehung moderner KI-Unterstützung (Gemini/Claude) entwickelt.
* **Responsive UI:** Dark-Mode Dashboard mit Sticky-Navigation für schnellen Zugriff auf Regionen (Lokal bis Global).

---

## 🏗️ Deep Dive: Scraper & KI-Pipeline

### Der Scraper (`scan/scraper.php`)
Das Herzstück der Datengewinnung. Er arbeitet modular und robust:
* **Rate-Limiting:** Eingebaute `usleep()`-Pausen, um Quellen zu schonen.
* **Deduplizierung:** Verhindert redundante Meldungen in der `news.json`.
* **Flexible Filter:** `should_filter_article()` sortiert Rauschen (z.B. Werbung) bereits beim Scan aus.
* **Hybrider Aufruf:** Läuft als Cronjob oder via AJAX-Trigger direkt aus dem UI.

### Hybride KI-Infrastruktur 🧠
Die Pressefräse nutzt eine verteilte Architektur, um Performance und Erreichbarkeit zu optimieren:

1. **Frontend/Crawler (Strato VPS):** Das Dashboard und der Scraper laufen 24/7 im Netz.
2. **KI-Rechenpower (Dockfish):** Die rechenintensive "Verdauung" erfolgt lokal auf dem Dockfish (Ryzen 7 7700).
3. **Vernetzung (Tailscale):** Die Kommunikation zwischen VPS und Ollama-API erfolgt sicher über ein Tailscale-Mesh-VPN.



### Docker & Ollama
Die `compose.yml` integriert Ollama in das bestehende n8n-Netzwerk für automatisierte Workflows.

```bash
# Beispiel: Den Digest manuell vom VPS am Dockfish triggern
curl http://[DOCKFISH-TAILSCALE-IP]:11434/api/generate -d '{
  "model": "llama3",
  "prompt": "Fasse diese News zusammen: ..."
}'
```


## 🚀 TODO / Roadmap

### 1. YouTube-Fräse (Intelligente Video-Analyse)
* **Status:** In Planung
* **Ziel:** Integration von Video-Content ohne den Zeitaufwand von 45-Minuten-Sessions.
* **Workflow:**
    * `scraper.php` extrahiert Video-URLs aus RSS-Feeds.
    * **Gemini-Integration:** Übergabe der URLs zur Inhaltsanalyse.
    * **Output:** Automatisierte Zusammenfassung inkl. Direktlink zum Video im Dashboard.

### 2. Automatisierte "Verdauung" (Dockfish-Pipeline)
* **Ziel:** Den "News Verdichtet"-Tab vollautomatisch via Cronjob befüllen.
* **Workflow:** Scraper (Strato) → Trigger via Tailscale → Ollama (Dockfish) → Rückgabe der `digest-*.html`.

### 3. Erweiterung der Bias-Datenbank
* Kontinuierliche Pflege der `bias.json`, um mehr internationale Perspektiven (Asien, Südamerika) abzubilden.

### 4. UI-Optimierung für Multimedia
* Implementierung von Video-Cards und Thumbnail-Placeholdern im Dashboard.

---
*„Die Pressefräse fräst, damit du nicht alles selbst lesen (oder sehen) musst.“*