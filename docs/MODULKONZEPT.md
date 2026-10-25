# Modulkonzept „Raummonitor“

## Zielbild

Eine Instanz des Raummonitors bündelt mehrere Räume. Jeder Raum hat mindestens
einen Namen und einen Isttemperatur-Datenpunkt. Freie Gruppenbezeichnungen wie
„Erdgeschoss“ oder „Obergeschoss“ ordnen die Räume. Innerhalb jeder Gruppe wird
alphabetisch nach Raumname sortiert.

## Datenfluss

1. Die Instanz speichert zwei JSON-Listen (`Rooms`, `Lights`) und die Einstellungen.
   Die Raumliste zeigt nur drei Übersichts-Spalten; ein eigenes Zeilenformular
   enthält die optionalen Zuordnungen. Die JSON-Struktur bleibt unverändert.
2. `ApplyChanges()` registriert Referenzen und `VM_UPDATE`-/`VM_DELETE`-Nachrichten
   für die verwendeten Variablen-IDs.
3. `RaummonitorModel` liest alle Werte einmal pro Aktualisierung, bildet
   Raumzeilen, Gruppen und Kennzahlen und kennzeichnet fehlende Werte.
4. `GetVisualizationTile()` rendert die erste Kacheldarstellung. Bei Änderungen
   liefert `UpdateVisualizationValue()` nur neue JSON-Daten an `handleMessage()`.
5. Die Kachel bleibt vollständig lesend. Es gibt keine Schreibbefehle auf KNX,
   keine Aktorensteuerung und keine Cloud-Dienste.

## Zuordnungsmodell

| Feld | Pflicht | Bedeutung |
| --- | --- | --- |
| `Group` | Nein | Freie Gruppenbezeichnung (Standard „Ohne Gruppe“) |
| `Room` | Ja | Sichtbarer Raumname; in der Gruppe eindeutig |
| `ActualTempID` | Ja | Aktuelle Temperatur in °C |
| `SetpointTempID` | Nein | Solltemperatur in °C |
| `OperationModeID` | Nein | KNX DPT 20.102 standardmäßig 1/2/3/4 (Komfort/Standby/Nacht/Schutz), konfigurierbare Zuordnung; 0=Auto wird bislang nicht gesondert dargestellt |
| `WindowID` | Nein | Bool/0/1 als Fensterstatus; mit `WindowInverted` invertierbar |
| `ValveID` | Nein | Aktuelle Ventilstellung; `ValveMax` skaliert auf Prozent |
| `LightCountID` | Nein | Bereits vorhandene Anzahl eingeschalteter Lichter |
| `HumidityID` | Nein | Relative Feuchtigkeit in Prozent |
| `CO2ID` | Nein | CO₂ in ppm |
| `DewPointID` | Nein | Taupunkt in °C |
| `HeatingCoolingID` | Nein | Bool/0/1 für Heiz-/Kühlbetrieb |

`Lights` ist eine zweite Liste für die Zuordnung beliebig vieler Bool-Schalter
pro Raum. Dabei müssen `Group` und `Room` mit der Raumliste übereinstimmen.
Ist `LightCountID` gesetzt, hat dessen Wert Vorrang gegenüber den Lichtzeilen.

## Farbkonzept

| Status | Kennzeichnung |
| --- | --- |
| Komfort | Grün |
| Standby | Gelb/Amber |
| Nacht | Grau |
| Frostschutz | Blau |
| Fenster offen | Blau |
| Fenster geschlossen | Grün |
| Unbekannt/nicht eingerichtet | Neutral oder Gedankenstrich |

Die Farben werden sparsam für Status-Chips und Kennzahlen eingesetzt. Die
Hintergründe beziehen ihre Grundfarbe aus dem Symcon-Theme; die Tabellen sind
horizontal scrollbar, falls nicht alle Spalten in eine schmale Kachel passen.

## Betriebs- und Fehlerverhalten

- `IS_ACTIVE (102)`: konfigurierte Räume mit gültigen Messwerten.
- `IS_INACTIVE (104)`: keine Räume vorhanden.
- `201`: fehlerhafte Konfiguration, doppelte Namen, nicht eindeutiges Modusmapping.
- `202`: mindestens ein konfigurierter Wert fehlt oder hat ein inkompatibles Format.
- Nicht zugeordnete optionale Felder werden als „–“ dargestellt, nicht als `0`.
- Nicht vorhandene Werte werden niemals als „Fenster geschlossen“ interpretiert.
- Ein partiell fehlender Lichtstatus wird mit `*` markiert.
- Optionaler Timer; standardmäßig Updates ausschließlich durch Variablennachrichten.

## Abgrenzung zum Zustandszähler

Inspiration ist Schnittchers Projekt
[Zustandszaehler](https://github.com/Schnittcher/Zustandszaehler)
(Gruppenliste, eigenständige HTML-SDK-Kachel, Ereignisaktualisierung). Der
Raummonitor ist ein eigenständiger Neuentwurf mit neuer Modul-/Bibliotheks-ID,
eigenem Datenmodell und eigener Darstellung; es wurden keine Quelldateien aus
dem Referenzprojekt übernommen.

## Spätere Erweiterungen

1. **Raumerkennung** unter einer ausgewählten IP-Symcon-Kategorie: konfigurierbare
   Suchnamen und Profile, Vorschau und explizite Übernahme statt stiller Änderungen.
2. Heiz-/Kühlbedarf, Ist-Soll-Abweichung, Schwellenwerte und Verlaufsindikatoren.
3. Aktualitätskontrolle anhand `VariableUpdated` mit konfigurierbaren Grenzwerten.
4. Zusätzliche Zustandsprofile je Raum (Fenster gekippt, HVAC-Regler, Heizsperre).
5. Filter für Geschosse und getrennte kompakte Ansichten.

Die automatische Erkennung ist absichtlich **nicht** Bestandteil von Version 1.0;
die zuverlässige manuelle Zuordnung ist die Grundlage.
