# Raummonitor für IP-Symcon 9.x

Ein eigenständiges Modul für eine moderne Kachelübersicht zu Heizung und
Raumklima. Mehrere Räume lassen sich nach Etage oder beliebigen Gruppen
zusammenfassen. Geeignet für KNX-Anlagen, funktioniert aber ebenso mit
beliebigen IP-Symcon-Variablen aus anderen Systemen.

**Entwickler/GitHub:** [Abracoor](https://github.com/Abracoor)  
**Repository:** `https://github.com/Abracoor/IPSymcon-Raummonitor`

## Funktionen

- Responsive HTML-SDK-Kachel mit heller und dunkler Symcon-Darstellung.
- Kennzahlen: konfigurierte Räume, offene Fenster, Komfort, Standby, Nacht,
  Frostschutz.
- Tabelle nach Raumname sortiert, optional nach Gruppen/Etagen gegliedert.
- Je Raum: Isttemperatur, Solltemperatur, Lichtanzahl, Betriebsart,
  Fensterzustand, Ventilstellung.
- Zusätzliche optionale Spalten: Luftfeuchte, CO₂, Taupunkt, Heizen/Kühlen.
- Live-Aktualisierung bei Variablenänderungen, optional periodischer Abgleich.
- Keine Fremdmodule, kein KNX-Herstellerbezug, kein Cloud-Zugang.
- Schreibgeschützt: Das Modul steuert keine Ventile oder Lichter.

## Voraussetzungen

- IP-Symcon **9.0 oder neuer**, inklusive HTML-SDK der Kachelvisualisierung.
- PHP 8.x (Bestandteil von Symcon 9.x).
- Bereits vorhandene, korrekt typisierte Symcon-Variablen.
- Die erweiterte HTML-Vollbilddarstellung steht in Symcon **ab 9.1** zur
  Verfügung. Unter 9.0 wird der normale HTML-Kachelmodus verwendet.

## Installation von GitHub

1. In IP-Symcon die **Kerninstanzen → Module** öffnen.
2. Mit **„+“ → Modul hinzufügen** ein eigenes Repository hinzufügen.
3. Als URL eintragen:

   `https://github.com/Abracoor/IPSymcon-Raummonitor.git`

4. Anschließend im Objektbaum eine neue Instanz **Raummonitor** anlegen.
5. Gruppen und Räume konfigurieren und die Instanz übernehmen.
6. Die Instanz in der **Kachelvisualisierung** sichtbar machen.

**Hinweis:** Die URL funktioniert erst, nachdem das Repository auf GitHub
angelegt und die Dateien dieses Pakets auf dem Hauptbranch `main` liegen.

## Konfiguration der Räume

In der Instanz gibt es die Liste **„Räume und Messwerte“**. Mit dem
**Hinzufügen**-Button eine Zeile je Raum anlegen. Die kompakte Liste zeigt nur
Gruppe, Raumname und Isttemperatur. Beim Hinzufügen oder Bearbeiten öffnet sich
ein Dialog mit allen Feldern; zusätzliche Werte sind in zwei aufklappbaren
Bereichen untergebracht. So bleibt der Hinzufügen-Button auch bei normalem
Browser-Zoom erreichbar.

| Feld | Erforderlich | Verwendung |
| --- | --- | --- |
| Gruppe / Etage | Nein | z. B. Erdgeschoss, Obergeschoss |
| Raumname | **Ja** | z. B. Wohnzimmer; innerhalb einer Gruppe eindeutig |
| Isttemperatur | **Ja** | Variable in Grad Celsius |
| Solltemperatur | Nein | Variable in Grad Celsius |
| Betriebsart | Nein | Integerwert; KNX DPT 20.102 als Vorgabe 1–4 |
| Fensterkontakt | Nein | Bool: `true` bedeutet offen, optional invertierbar |
| Ventilstellung | Nein | Numerisch; standardmäßig 0–100 % |
| Lichtanzahl | Nein | Bereits vorhandene numerische Gesamtzahl |
| Feuchte/CO₂/Taupunkt | Nein | Numerische Messwerte |
| Heizen/Kühlen | Nein | Bool: `true` bedeutet standardmäßig Kühlen |

Die Liste kann direkt über die Benutzeroberfläche gepflegt werden; die
numerischen IDs sind daher nicht von Hand in PHP-Dateien einzutragen.

### Lichtanzahl

Es gibt zwei gleichwertige Wege:

1. **Fertige Zahl:** Eine Integer-Variable bei „Lichtanzahl“ auswählen. Diese
   wird direkt angezeigt.
2. **Mehrere Schaltzustände:** Das Feld „Lichtanzahl“ frei lassen. In der
   zweiten Liste **„Lichtvariablen je Raum“** pro Leuchte eine Zeile mit
   derselben Gruppe und demselben Raumnamen sowie ihrer Bool-Variable anlegen.
   Der Raummonitor berechnet die Anzahl selbst; invertierte Kontakte lassen
   sich einzeln markieren.

Wenn beides vorhanden ist, gilt die fertige Lichtanzahl. Ohne beides steht
in der Lichtspalte „–“; fehlende Zuordnungen zählen ausdrücklich **nicht** als
„0 Lichter“.

### Betriebsarten und Farben

| Integer (Standard) | Modus | Farbe |
| --- | --- | --- |
| `0` | Automatik | Derzeit keine gesonderte Modusanzeige |
| `1` | Komfort | Grün |
| `2` | Standby | Gelb |
| `3` | Nacht / Economy | Grau |
| `4` | Frost-/Hitzeschutz | Blau |

Diese vier Werte lassen sich im Formular einzeln verändern. Jeder Wert muss
einzigartig sein. Auch Stringwerte wie `Komfort`, `Eco` oder `Frostschutz`
werden erkannt; bei unbekannten Codes wird ein neutraler Status angezeigt.

Die KNX-Belegung entspricht der offiziellen
[KNX-System-Spezifikation für DPT 20.102](https://support.knx.org/hc/en-us/article_attachments/15392631105682).
**Bestehende Raummonitor-Instanzen mit zuvor gespeicherter Belegung 0/1/2/3
werden durch ein Modul-Update nicht automatisch verändert.** Hier die vier
Werte einmalig manuell auf 1/2/3/4 umstellen und übernehmen. Individuelle
Belegungen anderer Systeme bleiben unverändert.

Fenster offen wird blau, geschlossen grün hervorgehoben. Bei Kontakten, die
`true` als *geschlossen* senden, **„Fenster invertieren“** einschalten.

### Ventilstellung

Der maximale Rohwert, der 100 % entspricht, kann je Raum festgelegt werden:

- `ValveMax = 100` für 0–100 % (Standard).
- `ValveMax = 255` für 0–255 Rohwerte.

Es erfolgt **keine** automatische Erkennung des Skalierungsformats, um
falsche Anzeigen zu vermeiden.

## Beispielkonfiguration

Unter [docs/beispielkonfiguration.json](docs/beispielkonfiguration.json) gibt es ein kleines Beispiel.
Die IDs **12345 bis 12351 sind frei erfundene Platzhalter** und dürfen nicht
ungeprüft in einer vorhandenen Symcon-Installation eingesetzt werden.

## Anzeige / Kachel

Oberhalb stehen sechs Kennzahlen. Darunter folgt die nach Gruppe und Raumname
sortierte Tabelle. Nicht konfigurierte Werte erscheinen als „–“. Eine
Warnmeldung in der Kachel und der Instanzstatus zeigen fehlende oder ungültige
Messwerte an. Auf schmalen Kacheln ist die Tabelle horizontal scrollbar.

Die Erweiterungsspalten sind standardmäßig ausgeblendet und können im Formular
aktiviert werden.

## PHP-Funktionen

```php
RMON_Update(12345);       // Instanz neu auswerten (12345 = Beispiel-Instanz-ID)
$data = RMON_GetSnapshot(12345);  // Aggregierte Messwerte als Array
```

Die Funktionen benutzen ausschließlich die vorhandenen Symcon-Variablen.

## Fehlerdiagnose

| Instanzstatus | Bedeutung |
| --- | --- |
| 102 | Alles betriebsbereit |
| 104 | Noch keine Räume eingerichtet |
| 201 | Fehlerhafte Raum-/Lichtliste oder doppelte Betriebsartwerte |
| 202 | Konfigurierter Messwert fehlt oder ist nicht plausibel lesbar |

Details zu fehlenden Variablen erscheinen im **Debug der Raummonitor-Instanz**.
Das Modul zeigt nie einen vermeintlich geschlossenen Fensterstatus, wenn
der zugehörige Wert nicht verfügbar ist.

## Tests

Die Auswertungslogik ist als eigenständige Klasse testbar und benötigt dafür
keinen laufenden Symcon-Server:

```bash
php tests/run.php
php tests/module_smoke.php
```

Alle PHP-Dateien können zusätzlich mit `php -l` und alle JSON-Dateien mit
einem JSON-Validator geprüft werden. Der abschließende Praxistest muss in
einer tatsächlichen Symcon-9.x-Instanz stattfinden.

## Projektstruktur

```text
IPSymcon-Raummonitor/
├── library.json
├── README.md
├── LICENSE
├── CHANGELOG.md
├── Raummonitor/
│   ├── module.json
│   ├── form.json
│   ├── module.php
│   ├── module.html
│   └── RaummonitorModel.php
├── docs/
│   ├── MODULKONZEPT.md
│   └── beispielkonfiguration.json
└── tests/
    ├── run.php
    └── module_smoke.php
```

## Herkunft und Lizenz

Die **konzeptionelle Anregung** stammt vom Projekt
[Zustandszaehler von Schnittcher](https://github.com/Schnittcher/Zustandszaehler).
Der Raummonitor wurde eigenständig neu entwickelt, ohne Dateien oder
Programmcode des Referenzprojekts zu kopieren oder das bestehende Modul zu
erweitern. Er verwendet eine eigene Bibliotheks- und Modul-GUID.

Dieses eigenständige Projekt steht unter der [MIT-Lizenz](LICENSE).

## Ideen für Version 1.1+

- Assistent zur automatischen Zuordnung nach Kategorie, Variablennamen oder
  Profilen mit prüfbarer Vorschau.
- Farbige Anzeige der Abweichung zwischen Ist- und Solltemperatur.
- Stale-Data-Meldungen, Fenster-lange-offen-Hinweise und Heizanforderung.
- Pro-Raum-Detailansicht und Verlaufskurven über das Symcon-Archiv.

Details zur Architektur stehen in [docs/MODULKONZEPT.md](docs/MODULKONZEPT.md).
