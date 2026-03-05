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
  - [ ] **Bias-Score-Feld ergänzen** (numerisch: -0.8 bis +0.8)
  - [ ] **Staatsnähe-Feld ergänzen** (unabhängig / öR / staatsnah / staatlich)
  - [ ] **Herkunft dokumentieren** (manuell kuratiert vs. automatisch geschätzt)
- [ ] Staatsnähe-Mapping (Metadaten zur Finanzierung/Eigentümer der Quellen)
  - [ ] Eigentümerstruktur pro Medium erfassen
  - [ ] Finanzierungsmodell pro Medium erfassen (Werbung, Abo, Staat, Mischform)
  - [ ] Pressefreiheits-Kontext pro Land hinterlegen

## 2. KI-Analyse & "Verdauung" (Ollama-Integration)
- [x] Alle Quellen verdauen und in "verdichtet" zusammenfassen ✅
- [ ] Ausländische News nach Deutsch übersetzen (Ollama-Modul)
- [ ] News einzelner Länder verdauen (Cross-Border-Vergleich)
- [ ] Themen-Clustering (Logik, um verschiedene Artikel demselben Ereignis zuzuordnen)
  - [ ] **Cluster nach Bias-Score filtern** (z. B. "zeige nur -0.3 bis +0.3")
  - [ ] **Konträre Perspektiven automatisch gegenüberstellen** (-0.6 vs. +0.6)
- [ ] Rhetorikscore-Engine (Berechnung von Emotionalität, Kampfbegriffen & Aktiv/Passiv)
  - [ ] **Rhetorikscore als separates Feld** (unabhängig von Bias-Score)
  - [ ] **Heatmap-Darstellung vorbereiten**
- [ ] Auslassungsanalyse (Mechanik: Wer berichtet über ein Top-Thema *nicht*?)
  - [ ] **Filter nach Bias-Richtung** (welche politische Richtung schweigt?)
  - [ ] **Filter nach Staatsnähe** (welche staatsnahen Medien schweigen?)

## 3. Architektur & Infrastruktur (Szenario B)
- [ ] VPN-Tunnel-Stabilisierung (Verbindung Strato <-> Heim-PC/Monsterklopper)
- [ ] Modularisierung (Dateien entwirren, Funktionen klar voneinander trennen)
- [ ] Error-Logging (Zentrales Log-File, um Brüche in der Kette sofort zu sehen)
- [ ] Backup-Routine für Datenbanken/JSON-Files (Schutz vor Fehlkonfigurationen)
  - [ ] **Versionierung der `bias.json`** (für Nachvollziehbarkeit von Score-Änderungen)
  - [ ] **Change-Log für Bias-Score-Anpassungen**

## 4. Visualisierung & Transparenz (Frontend)
- [ ] Skalen-Visualisierung (Grafische Darstellung von Bias & Rhetorikscore)
  - [ ] **Horizontale Bias-Skala** (Links —— Neutral —— Rechts)
  - [ ] **Numerischer Bias-Score als Tooltip** (-0.8 bis +0.8)
  - [ ] **Farbskala oder Ampelsystem** für schnelle Orientierung
- [ ] Divergenz-Ansicht (Zwei Medien zu einem Thema direkt gegenüberstellen)
  - [ ] **Bias-Abstand anzeigen** (z. B. "Δ 1.2 Punkte")
  - [ ] **Staatsnähe-Vergleich** (unabhängig vs. staatlich)
- [ ] Video-Modul (Invidious-Integration: Transkripte -> Analyse)
  - [ ] **Bias-Score auch für YouTube-Kanäle** (mit Hinweis auf Bandbreite)
  - [ ] **Kennzeichnung: Nachricht / Analyse / Kommentar / Satire**
- [ ] Timeline-Ansicht (Wer hat wann zuerst über ein Thema berichtet?)
  - [ ] **Zeitliche Verschiebung nach Bias-Richtung** (wer berichtet zuerst/später?)

## 5. Langfristige Vision
- [ ] Weltkarte mit Medien-Overlay
  - [ ] **Farbkodierung nach Bias-Score**
  - [ ] **Größenkodierung nach Reichweite**
- [ ] Selbst hostbare Open-Source-Version (Modularer Aufbau)
  - [ ] **`bias.json` als Community-Pflege-Modul**
  - [ ] **Feedback-Mechanismus für Score-Korrekturen**
- [ ] Community-Module für neue Analyse-Kriterien
- [ ] **"Bias-Balance"-Modus** (automatisch ausgewogene Quellenauswahl)
- [ ] **Export-Funktion** ("Zeige mir dieses Thema aus 5 verschiedenen Perspektiven")
- [ ] **Ideologischer Abstand zwischen Berichten** (berechnet aus Bias-Scores)
- [ ] **Konflikt-Intensitätsanzeige** (Rhetorikscore-Aggregation pro Thema)

---

### 1. YouTube-Fräse (Intelligente Video-Analyse)
* **Status:** In Planung
* **Ziel:** Integration von Video-Content ohne den Zeitaufwand von 45-Minuten-Sessions.
* **Workflow:**
    * `scraper.php` extrahiert Video-URLs aus RSS-Feeds.
    * **Gemini-Integration:** Übergabe der URLs zur Inhaltsanalyse.
    * **Output:** Automatisierte Zusammenfassung inkl. Direktlink zum Video im Dashboard.
    * **Bias-Score:** YouTube-Kanäle in `bias.json` mit aufnehmen (mit Caveat)

### 2. Automatisierte "Verdauung" (Dockfish-Pipeline)
* **Ziel:** Den "News Verdichtet"-Tab vollautomatisch via Cronjob befüllen.
* **Workflow:** Scraper (Strato) → Trigger via Tailscale → Ollama (Dockfish) → Rückgabe der `digest-*.html`.
* **Erweiterung:** Bias-Score-Metadaten an Digest weitergeben

### 3. Erweiterung der Bias-Datenbank
* **Status:** Laufend
* **Ziel:** Kontinuierliche Pflege der `bias.json`, um mehr internationale Perspektiven (Asien, Südamerika) abzubilden.
* **Neu:**
  * [ ] **Bias-Score für alle Medien** (konsistente Skala -0.8 bis +0.8)
  * [ ] **Staatsnähe-Feld für alle Medien** (5-Stufen-Skala)
  * [ ] **Herkunfts-Flag** (manuell vs. automatisch)
  * [ ] **Versionierung** (jede Änderung wird geloggt)

### 4. UI-Optimierung für Multimedia
* **Status:** In Planung
* **Ziel:** Implementierung von Video-Cards und Thumbnail-Placeholdern im Dashboard.
* **Erweiterung:**
  * [ ] **Bias-Score als Badge** auf jeder News-Card
  * [ ] **Farbkodierung nach Bias-Richtung**
  * [ ] **Tooltip mit detaillierter Einordnung**

### 5. Bias-Score-System (NEU)
* **Status:** Konzeptionell abgeschlossen
* **Ziel:** Numerische Filter-Logik für ausgewogene Perspektiven-Mixe.
* **Komponenten:**
  * [ ] **`bias.json`-Schema erweitern** (bias_score-Feld hinzufügen)
  * [ ] **Filter-Logik implementieren** (Python/PHP: -0.3 bis +0.3 = "ausgewogen")
  * [ ] **UI-Elemente bauen** (Slider, Dropdown, Presets)
  * [ ] **Dokumentation schreiben** (Score ist Werkzeug, nicht Urteil)
  * [ ] **Manuelle Override-Funktion** (für Feinjustierung)