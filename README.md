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
- Mehrere Fensterkontakte pro Raum mit Anzeige der geöffneten Kontakte.
- Optionale Spalten automatisch ausblenden, wenn sie in keinem Raum konfiguriert sind.
- Zusätzliche optionale Spalten: Luftfeuchte, CO₂, Taupunkt, Heizen/Kühlen.
- Live-Aktualisierung bei Variablenänderungen, optional periodischer Abgleich.
- Konfigurierbare Plausibilitätsprüfung für Ist- und Solltemperaturen.
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
| Fensterkontakt | Nein | Optionaler Einzelkontakt; zusätzliche Kontakte in der Fensterliste |
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

### Mehrere Fensterkontakte pro Raum

Die Liste **„Fensterkontakte je Raum“** enthält beliebig viele Bool-Variablen
mit `Group`, `Room`, einer optionalen Fensterbezeichnung und `VariableID`.
Die Schreibweisen für Gruppe und Raum müssen mit einem Eintrag der Raumliste
übereinstimmen. Falls ein Fensterkontakt `true` als *geschlossen* meldet,
**„Kontakt invertieren“** einschalten.

Der bisherige **einzelne Fensterkontakt** (`WindowID`) in der Raumkonfiguration
bleibt gültig. Weitere Fenster können über die Fensterliste hinzugefügt werden.
Dieselbe Variablen-ID wird innerhalb eines Raums nur einmal gezählt; ist sie
zusätzlich in der Fensterliste vorhanden, gilt deren Invertierungsoption.

- Sind alle Kontakte geschlossen, steht in der Kachel „alle geschlossen“.
- Ist bei zwei Fenstern eines offen, steht dort „1 von 2 offen“.
- Die Kennzahl **„Fenster offen“ zählt einzelne geöffnete Kontakte**, nicht
  nur Räume mit mindestens einem geöffneten Fenster.
- Ist ein Kontakt nicht lesbar, wird die Darstellung als unvollständig markiert;
  der Raum wird **nicht** fälschlich als vollständig geschlossen angezeigt.

### Betriebsarten und Farben

| Integer (Standard) | Modus | Farbe |
| --- | --- | --- |
| `0` | Automatik | Neutral (grau) |
| `1` | Komfort | Grün |
| `2` | Standby | Gelb |
| `3` | Nacht / Economy | Grau |
| `4` | Frost-/Hitzeschutz | Blau |

Diese vier Werte lassen sich im Formular einzeln verändern. Jeder Wert muss
einzigartig sein. Auch Stringwerte wie `Komfort`, `Eco` oder `Frostschutz`
werden erkannt. `0 = Automatik` wird zusätzlich erkannt, solange nicht eine
der vier frei konfigurierbaren Betriebsarten ausdrücklich den Wert `0` erhält.
Ein unbekannter Code wird als **„Unbekannt (Wert)“** angezeigt und als
Messwertproblem gemeldet.

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

### Plausibilitätsprüfung für Temperaturen

Im aufklappbaren Bereich **„Plausibilitätsprüfung für Raumtemperaturen“** ist
die Prüfung standardmäßig aktiviert. Die Grenzen sind zunächst **−20 °C**
und **+60 °C**. Beide Grenzwerte selbst sind erlaubt; nur Werte außerhalb
des Intervalls werden als unplausibel behandelt.

- Geprüft werden **Ist- und Solltemperatur**, nicht Taupunkt, Luftfeuchtigkeit
  oder andere Messwerte.
- Bei einem Ausreißer (z. B. `32767 °C` durch einen Sensorfehler) erscheint
  in der Kachel **„–“**, und die Raummonitor-Instanz erhält **Status 202**.
- Die Quellvariable in IP-Symcon bleibt dabei unverändert. Sobald der Sensor
  wieder einen gültigen Wert liefert, erscheint dieser automatisch.
- Die Grenzen können global für die Raummonitor-Instanz angepasst werden.
  **Untergrenze muss kleiner als Obergrenze** sein; andernfalls wird die
  Konfiguration als fehlerhaft gemeldet (Status 201).
- Für besondere Einsatzfälle lässt sich die Prüfung ausschalten. Dann werden
  numerische Temperaturwerte unabhängig von diesen Grenzen angezeigt.

## Beispielkonfiguration

Unter [docs/beispielkonfiguration.json](docs/beispielkonfiguration.json) gibt es ein kleines Beispiel.
Die IDs **12345 bis 12352 sind frei erfundene Platzhalter** und dürfen nicht
ungeprüft in einer vorhandenen Symcon-Installation eingesetzt werden.

## Anzeige / Kachel

Oberhalb stehen sechs Kennzahlen; wenn ein Raum den Automatikmodus verwendet,
erscheint eine zusätzliche Kennzahl „Automatik“. Darunter folgt die nach Gruppe und Raumname
sortierte Tabelle. **Standardmäßig ist die automatische Spaltenauswahl aktiv:**
Raumname und Isttemperatur sind immer sichtbar. Solltemperatur, Licht,
Betriebsart, Fenster und Ventil erscheinen nur, wenn mindestens ein Raum eine
passende Variable verwendet. Die Spalten gelten einheitlich für alle Etagen;
ein Raum ohne Zuordnung zeigt darin „–“.

Die Option **„Tabellenspalten automatisch nach verwendeten Variablen anzeigen“**
kann deaktiviert werden, um wieder alle sieben Grundspalten anzuzeigen.
Zusatzspalten für Feuchte, CO₂, Taupunkt und Heizen/Kühlen benötigen weiterhin
die separate Freigabe **„Optionale Klima-Spalten anzeigen“**. Mit aktivierter
Automatik erscheinen auch diese Zusatzspalten jeweils nur bei tatsächlicher
Zuordnung.

Nicht konfigurierte Werte erscheinen als „–“. Eine
Warnmeldung in der Kachel und der Instanzstatus zeigen fehlende oder ungültige
Messwerte an. Auf schmalen Kacheln ist die Tabelle horizontal scrollbar.

**Wichtig:** Eine zugeordnete Variable hält ihre Spalte sichtbar, selbst wenn
der aktuelle Messwert fehlt. Fehler werden also nicht durch automatisches
Ausblenden versteckt.

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
| 201 | Fehlerhafte Raum-/Licht-/Fensterliste, doppelte Betriebsartwerte oder ungültige Temperaturgrenzen |
| 202 | Konfigurierter Messwert fehlt, ist nicht lesbar oder die Ist-/Solltemperatur liegt außerhalb der Plausibilitätsgrenzen |

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
