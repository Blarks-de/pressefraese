
# Die Pressefräse: eine Transparenzmaschine
## Ideensammlung & konzeptionelle Leitlinien

---

## 1. Kernidentität

Die Pressefräse ist kein klassischer News-Aggregator.  
Sie ist ein Vergleichs- und Perspektivensystem.

Ziel:
- Unterschiede sichtbar machen
- Narrative vergleichen
- Strukturen offenlegen
- Transparenz schaffen

Nicht Ziel:
- Wahrheitsurteile fällen
- Politische Bewertung vornehmen
- Moralisch einordnen

---

## 2. Zentrale Analyse-Module

### 2.1 Bias-Skala (politische Einordnung)

Darstellung auf einer horizontalen Skala:

Links —— Mitte-Links —— Neutral —— Mitte-Rechts —— Rechts

Ergänzend:
- Liberal
- Konservativ
- Populistisch
- Öffentlich-rechtlich

Visualisierung:
- Farbskala oder Ampelsystem
- Tooltip mit kurzer Erklärung

#### 2.1.1 Numerischer Bias-Score (für Filter-Logik)

Zusätzlich zur visuellen Skala: numerischer Wert für programmatische Nutzung.

Skala: `-0.8` bis `+0.8`
- `-0.8` bis `-0.4`: stark links / progressiv
- `-0.3` bis `-0.1`: leicht links / liberal
- `-0.0` bis `+0.0`: neutral / ausgewogen
- `+0.1` bis `+0.3`: leicht rechts / konservativ
- `+0.4` bis `+0.8`: stark rechts / populistisch

Beispiel-Einträge (aus `bias.json`):
```json
"ARD Tagesschau": { "bias_score": 0.0 },
"Der Spiegel": { "bias_score": -0.5 },
"FAZ": { "bias_score": +0.5 },
"BILD": { "bias_score": +0.6 }

Anwendung:
Automatisches Zusammenstellen ausgewogener Feed-Mixe (z. B. -0.3 bis +0.3)
Gegenüberstellung konträrer Perspektiven (-0.6 vs. +0.6)
Sortierung nach "wie nah am Mainstream" oder "wie extrem"
Wichtig:
Der Score ist ein Werkzeug, kein Urteil.
Er dient der Filterung, nicht der Bewertung.
Manuelle Feinjustierung bleibt möglich

---

### 2.2 Staatsnähe (separater Faktor)

Unabhängig von der Links/Rechts-Skala.

Mögliche Einstufungen:
- Unabhängig
- Öffentlich-rechtlich
- Teilweise staatsnah
- Staatsnah
- Staatlich kontrolliert

Ziel:
Strukturelle Abhängigkeiten sichtbar machen.

---

### 2.3 Rhetorikscore

Analyse der sprachlichen Gestaltung eines Beitrags.

Mögliche Parameter:
- Emotionalität
- Wertende Begriffe
- Schuldzuweisungen
- Aktiv-/Passiv-Konstruktionen
- Kampfbegriffe
- Dramatisierung

Darstellung:
- Numerischer Score
- Heatmap
- Vergleich nebeneinander

Ziel:
Nicht nur „was“, sondern „wie“ wird berichtet.

---

### 2.4 Auslassungsanalyse

Analyse, welche Medien NICHT über ein relevantes Thema berichten.

Fragestellungen:
- Welche Länder ignorieren das Ereignis?
- Welche politischen Richtungen berichten nicht?
- Gibt es systematische Lücken?

Darstellung:
- Liste fehlender Medien
- Prozentuale Abdeckung
- Zeitliche Verzögerung

Ziel:
Sichtbar machen, wo Stille herrscht.

---

## 3. Themen-Cluster (Fräsen-Kern)

Ereignisse werden automatisch gruppiert.

Pro Thema:
- Medienvergleich
- Bias-Vergleich
- Staatsnähe-Vergleich
- Rhetorikvergleich
- Zeitlicher Veröffentlichungsverlauf

Optional:
- Schlüsselbegriffe nebeneinanderstellen
- Narrative-Vergleich
- Filter nach Bias-Score-Bereich


---

## 4. Video-Integration (YouTube)

Funktionen:
- Transkription
- Übersetzung ins Deutsche
- Sachliche Zusammenfassung
- Verlinkung zum Originalvideo

Kennzeichnung:
- Nachricht
- Analyse
- Kommentar
- Satire

---

## 5. Erweiterte Transparenz-Faktoren

### 5.1 Themengewichtung
Wie häufig berichtet ein Medium über Thema X?

### 5.2 Zeitliche Verschiebung
Wer berichtet zuerst?
Wer reagiert verspätet?

### 5.3 Eigentümerstruktur
Privat, Konzern, Stiftung, Staat?

### 5.4 Finanzierungsmodell
Werbung, Abo, Staat, Mischform?

### 5.5 Pressefreiheits-Kontext
In welchem regulatorischen Umfeld operiert das Medium?

---

## 6. Philosophische Leitlinie

Die Pressefräse:

- analysiert
- strukturiert
- visualisiert
- vergleicht

Sie urteilt nicht.
Sie macht Unterschiede sichtbar.

---

## 7. Langfristige Vision

- Weltkarte mit Medien-Overlay
- Ideologischer Abstand zwischen Berichten
- Dynamische Timeline pro Thema
- Konflikt-Intensitätsanzeige
- Globale Vergleichsansicht pro Ereignis
- "Bias-Balance"-Modus: automatisch ausgewogene Quellenauswahl
- Export-Funktion: "Zeige mir dieses Thema aus 5 verschiedenen Perspektiven"

---

8. Technische Anker

8.1 bias.json-Struktur
>{
  "MediumName": {
    "staat": "Herkunftsland",
    "iran": "Haltung zu Iran",
    "israel": "Haltung zu Israel",
    "golf": "Haltung zu Golf-Region",
    "ton": "Journalistischer Stil",
    "bias_score": -0.5,
    "staatsnaehe": "unabhängig",
    "eigentum": "Stiftung",
    "finanzierung": "Spenden+Abo"
  }
}
