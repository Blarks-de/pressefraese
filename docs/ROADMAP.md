## 🚀 Pressefräse – TODO / Roadmap

## 1. Fundament & Datenfluss (Backend)
- [ ] Titelseite: Was gibts neues in der Welt?
- [x] `quellen.md` füllen ✅
- [x] Sport & Klatsch filtern ✅
- [x] RSS-Feed hinzufügen ✅
- [x] RSS-Feeds regelmäßig automatisch prüfen ✅
- [x] Rohfassung aller Quellen anzeigen ✅
- [ ] Aktuelle RSS-Feeds zu allen Quellen vervollständigen (Mapping-Arbeit)
- [ ] `bias.json` mit allen Medien füllen (Initial-Einstufung: Links/Mitte/Rechts)
- [ ] Staatsnähe-Mapping (Metadaten zur Finanzierung/Eigentümer der Quellen)

## 2. KI-Analyse & "Verdauung" (Ollama-Integration)
- [x] Alle Quellen verdauen und in "verdichtet" zusammenfassen ✅
- [ ] Ausländische News nach Deutsch übersetzen (Ollama-Modul)
- [ ] News einzelner Länder verdauen (Cross-Border-Vergleich)
- [ ] Themen-Clustering (Logik, um verschiedene Artikel demselben Ereignis zuzuordnen)
- [ ] Rhetorikscore-Engine (Berechnung von Emotionalität, Kampfbegriffen & Aktiv/Passiv)
- [ ] Auslassungsanalyse (Mechanik: Wer berichtet über ein Top-Thema *nicht*?)

## 3. Architektur & Infrastruktur (Szenario B)
- [ ] VPN-Tunnel-Stabilisierung (Verbindung Strato <-> Heim-PC/Monsterklopper)
- [ ] Modularisierung (Dateien entwirren, Funktionen klar voneinander trennen)
- [ ] Error-Logging (Zentrales Log-File, um Brüche in der Kette sofort zu sehen)
- [ ] Backup-Routine für Datenbanken/JSON-Files (Schutz vor Fehlkonfigurationen)

## 4. Visualisierung & Transparenz (Frontend)
- [ ] Skalen-Visualisierung (Grafische Darstellung von Bias & Rhetorikscore)
- [ ] Divergenz-Ansicht (Zwei Medien zu einem Thema direkt gegenüberstellen)
- [ ] Video-Modul (Invidious-Integration: Transkripte -> Analyse)
- [ ] Timeline-Ansicht (Wer hat wann zuerst über ein Thema berichtet?)

## 5. Langfristige Vision
- [ ] Weltkarte mit Medien-Overlay
- [ ] Selbst hostbare Open-Source-Version (Modularer Aufbau)
- [ ] Community-Module für neue Analyse-Kriterien

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