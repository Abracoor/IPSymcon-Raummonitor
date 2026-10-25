# Modulkonzept „Raummonitor“

## Zielbild

Eine Instanz des Raummonitors bündelt mehrere Räume. Jeder Raum benötigt nur
einen eindeutigen Namen. Alle Messwerte sind optional; auch ein Raum mit
ausschließlich Licht- oder Fensterstatus beziehungsweise einer Detailseite
ist möglich. Freie Gruppenbezeichnungen wie
„Erdgeschoss“ oder „Obergeschoss“ ordnen die Räume. Innerhalb jeder Gruppe wird
alphabetisch nach Raumname sortiert.

## Datenfluss

1. Die Instanz speichert drei JSON-Listen (`Rooms`, `Lights`, `Windows`) und die Einstellungen.
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
| `ActualTempID` | Nein | Aktuelle Temperatur in °C |
| `DetailCategoryID` | Nein | Kategorie für einen optionalen Klick auf den Raumnamen, Standard `1` („Kein(e)“), auch früherer Wert `0` inaktiv |
| `SetpointTempID` | Nein | Solltemperatur in °C |
| `OperationModeID` | Nein | KNX DPT 20.102 standardmäßig 1/2/3/4 (Komfort/Standby/Nacht/Schutz), konfigurierbare Zuordnung; 0=Automatik wird erkannt, sofern nicht bereits individuell belegt |
| `WindowID` | Nein | Bestehender Einzelkontakt als Bool/0/1; mit `WindowInverted` invertierbar |
| `ValveID` | Nein | Aktuelle Ventilstellung; `ValveMax` skaliert auf Prozent |
| `LightCountID` | Nein | Bereits vorhandene Anzahl eingeschalteter Lichter |
| `HumidityID` | Nein | Relative Feuchtigkeit in Prozent |
| `CO2ID` | Nein | CO₂ in ppm |
| `DewPointID` | Nein | Taupunkt in °C |
| `HeatingCoolingID` | Nein | Bool/0/1 für Heiz-/Kühlbetrieb |

`Lights` ist eine zweite Liste für die Zuordnung beliebig vieler Bool-Schalter
pro Raum. Dabei müssen `Group` und `Room` mit der Raumliste übereinstimmen.
Ist `LightCountID` gesetzt, hat dessen Wert Vorrang gegenüber den Lichtzeilen.

`Windows` ist eine dritte Liste für beliebig viele Fensterkontakte je Raum
(`Group`, `Room`, `Name`, `VariableID`, `Invert`). Der Raumname und die Gruppe
müssen exakt passen. Alle Kontakte werden zusammen mit einem eventuell bereits
konfigurierten `WindowID` ausgewertet. Eine doppelte Variablen-ID wird nur einmal
gezählt; die Fensterliste hat dann Vorrang bei der Invertierung. Pro Raum werden
Kontaktanzahl, Anzahl geöffneter Kontakte und der gemeinsame Status ermittelt.
Die globale Kennzahl `openWindows` zählt **Kontakte**, keine Räume.

## Automatische Tabellenspalten

Die Eigenschaft `AutoColumns` ist standardmäßig `true`. Die Modellberechnung
ermittelt je optionaler Spalte, ob in mindestens einer gültigen Raumzeile
eine passende Variablen-ID oder eine passende Licht-/Fensterlisten-Zuordnung
existiert. Das Ergebnis ist eine **globale** Spaltenmaske für alle Gruppen.

- Nur `Raum` erscheint immer.
- `Ist`, `Soll`, `Licht`, `Modus`, `Fenster` und `Ventil` nur bei Konfiguration.
- Klima-Zusatzspalten werden zusätzlich von `ShowExtraColumns` gesteuert:
  Auch bei aktivierter Automatik bleiben sie ohne Freigabe verborgen.
- Bei deaktiviertem `AutoColumns` werden alle sechs optionalen Basisspalten
  wie in den bisherigen Builds angezeigt (Zusatzspalten weiter optional).
- Eine konfigurierte, aktuell aber fehlende Variable hält die Spalte sichtbar,
  sodass ein Messwertfehler nicht unsichtbar wird.

Die HTML-Kachel erhält dieselbe Spaltenmaske für sämtliche Etagen. Ihre
Mindestbreite richtet sich nach der Anzahl und Art der sichtbaren Spalten.

## Optionale Navigation

Eine Raumzeile kann zusätzlich `DetailCategoryID` enthalten. Nur eine gültige,
konfigurierte Kategorie-ID größer als `1` aktiviert in der HTML-Kachel einen anklickbaren
Raumnamen. Die Navigation nutzt `openObject(KategorieID)` aus dem offiziellen
Symcon HTML-SDK (seit 8.2) und bleibt innerhalb der aktuellen Visualisierung.
Ein nicht konfiguriertes Ziel (`1`, früher `0` oder fehlendes Feld) wird als normaler Text
gerendert. Der Raummonitor ändert durch diese Funktion keine KNX- oder
anderen Prozessvariablen; bestehende JSON-Raumlisten bleiben kompatibel.

## Farbkonzept

| Status | Kennzeichnung |
| --- | --- |
| Komfort | Grün |
| Standby | Gelb/Amber |
| Nacht | Grau |
| Frostschutz | Blau |
| Automatik | Grau (neutral) |
| Fenster offen | Blau |
| Fenster geschlossen | Grün |
| Unbekannt/nicht eingerichtet | Neutral oder Gedankenstrich |

Die Farben werden sparsam für Status-Chips und Kennzahlen eingesetzt. Die
Hintergründe beziehen ihre Grundfarbe aus dem Symcon-Theme; die Tabellen sind
horizontal scrollbar, falls nicht alle Spalten in eine schmale Kachel passen.

## Temperatur-Plausibilität

Die Moduloption `ValidateTemperatures` ist standardmäßig `true`.
`MinimumTemperature` (−20 °C) und `MaximumTemperature` (+60 °C) definieren
ein inklusive Grenzen gültiges Intervall für **Ist- und Solltemperaturen**.
Die Prüfung findet ausschließlich im Lesemodell nach dem Auslesen statt:
Unplausible Temperaturwerte werden in der Kachel als fehlend (`null` bzw. „–“)
behandelt; die vorhandene Quellvariable wird niemals überschrieben. Für eine
zugeordnete und unplausible Variable wird die ID in `missingIDs` aufgenommen,
was den Instanzstatus `202` auslöst. Sobald wieder ein plausibler Wert
gelesen wird, verschwindet die Warnung von selbst.

Ungültige Grenzen (Untergrenze größer oder gleich Obergrenze) ergeben bei
aktiver Prüfung einen Konfigurationsfehler `201`. Die Prüfung kann für
Sonderfälle deaktiviert werden. Die Grenzwerte gelten einheitlich für alle
Räume einer Instanz. Andere Messwerttypen werden davon nicht beeinflusst.

## Betriebs- und Fehlerverhalten

- `IS_ACTIVE (102)`: konfigurierte Räume ohne Daten- oder Konfigurationsfehler
  (auch reine Navigationsräume ohne Messwert sind zulässig).
- `IS_INACTIVE (104)`: keine Räume vorhanden.
- `201`: fehlerhafte Konfiguration, doppelte Namen, nicht eindeutiges Modusmapping
  oder ungültige Temperaturgrenzen bei aktiver Prüfung.
- `202`: mindestens ein konfigurierter Wert fehlt, hat ein inkompatibles Format
  oder verletzt die konfigurierten Ist-/Solltemperaturgrenzen.
- Unbekannte Integer-Betriebsarten (z. B. `99`) erhalten den Status
  „Unbekannt (99)“ und führen ebenfalls zu `202`. Der gültige Modus `0=Automatik`
  bleibt ohne Fehlermeldung und wird bei Bedarf zusätzlich zusammengefasst.
- Nicht zugeordnete optionale Felder werden als „–“ dargestellt, nicht als `0`.
- Nicht vorhandene Werte werden niemals als „Fenster geschlossen“ interpretiert.
- Ein nicht lesbarer Fensterkontakt wird markiert. Bei ausschließlich geschlossenen
  bekannten Kontakten bleibt der Raumstatus dann unbekannt; erkannte offene
  Kontakte werden trotzdem gezählt und angezeigt.
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
