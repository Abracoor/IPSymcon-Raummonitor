# Changelog

## 1.0.16 – Alle Messwerte optional, nur Raumname erforderlich (25.10.2026)

- Die Isttemperatur ist kein Pflichtfeld mehr. Licht-, Fenster-, Klima- oder
  reine Navigationsräume funktionieren ohne Temperaturvariable.
- Die Spalte „Ist“ folgt nun derselben automatischen Spaltenlogik wie die
  übrigen Messwerte und wird ohne Zuordnung ausgeblendet.
- Eine bewusst nicht zugeordnete Isttemperatur erzeugt keinen Fehlerstatus;
  fehlende oder ungültige Werte einer konfigurierten Variablen bleiben Fehler.
- Die manuelle Vollansicht zeigt weiterhin alle Basisspalten. Vorhandene
  Konfigurationen, KNX-Datenpunkte und Detailseiten bleiben unverändert.
- Konfigurator, Dokumentation und automatisierte Tests angepasst.

## 1.0.15 – Leere Detailseiten-Auswahl korrigiert (25.10.2026)

- Symcon verwendet Objekt-ID `1` für „Kein(e)“; ID `0` bezeichnet das
  Wurzelobjekt „IP Symcon“. In Build 14 wurde dies im Dialog verwechselt.
- Neue Raumzeilen und das Auswahlfeld erhalten daher jetzt Wert `1`.
- Bestehende Werte `0` und `1` gelten beide als inaktiv; echte
  Detailkategorien behalten ihre ID und funktionieren unverändert.
- Formularhinweis und README erklären das Leeren der Auswahl über das X.

## 1.0.14 – Eindeutiger Ausgangswert für optionale Detailseite (25.10.2026)

- Auswahlfeld für die Raum-Detailseite explizit mit dem Wert `0` initialisiert.
  Dadurch ist der inaktive Zustand eindeutig festgelegt, statt den
  Standardwert des Kategorie-Auswahlfelds zu verwenden.
- Der Erklärungstext benennt den inaktiven Zustand (Wert 0) ausdrücklich.
- Bereits gespeicherte Zielkategorien und das Klickverhalten bleiben erhalten.

## 1.0.13 – Klick auf Raumnamen zur Detailseite (25.10.2026)

- Pro Raum kann optional eine Zielkategorie für eine vorhandene Detailseite
  in der Kachelvisualisierung hinterlegt werden.
- Nur konfigurierte Raumnamen werden als anklickbare Buttons angezeigt;
  der Seitenwechsel erfolgt über die offizielle HTML-SDK-Funktion `openObject()`.
- Räume ohne Detailseite bleiben unverändert. Keine Änderung an KNX-Daten,
  Messwertberechnung oder bestehenden Konfigurationen.
- Modell-, Modul- und JavaScript-Tests für die optionale Navigation ergänzt.

## 1.0.12 – Getrennte Farbpaletten für helles und dunkles Design (25.10.2026)

- Im hellen Symcon-Design wieder die kräftigen Originalfarben aus Build 10.
- Im dunklen Design bleiben die aufgehellten, kontrastreicheren Farben aus
  Build 11 erhalten.
- Erkennung über die tatsächlich berechnete Textfarbe der Kachel, nicht nur
  über die Betriebssystem-Einstellung zum Farbschema.
- Keine Änderungen an Messwerten, Funktionen oder Konfiguration.

## 1.0.11 – Besserer Farbkontrast im dunklen Design (25.10.2026)

- Grüne, gelbe, blaue und graue Statusfarben werden aus der jeweiligen
  Grundfarbe und der Textfarbe des aktiven Symcon-Themes gemischt.
- Komfort- und Fensterindikatoren, Kennzahlen sowie Betriebsarten bleiben
  so im hellen Design kräftig und sind im dunklen Design heller lesbar.
- Keine Änderungen an Daten, Funktionen oder gespeicherten Einstellungen.

## 1.0.10 – Überlagerung in schmalen Kacheln behoben (25.10.2026)

- Die Raumspalte bleibt beim horizontalen Scrollen nicht mehr fixiert.
  Dadurch können Messwerte bei schmalen Kacheln nicht mehr unter dem
  teilweise transparenten Hintergrund der Raumbezeichnungen durchscheinen.
- Die Tabelle wird als Ganzes seitlich verschoben. Darstellung, Werte und
  gespeicherte Konfiguration bleiben ansonsten unverändert.

## 1.0.9 – Beschriftung der zyklischen Aktualisierung verbessert (25.10.2026)

- Feldbeschriftung auf „Zyklische Aktualisierung (s)“ gekürzt, damit sie
  in der IP-Symcon-Konfiguration vollständig sichtbar ist.
- Den Hinweis „0 = nur bei Variablenänderungen“ in eine separate Zeile verlegt.
  Keine Änderung an der Timerfunktion oder an gespeicherten Einstellungen.

## 1.0.8 – Konfigurierbare Temperatur-Plausibilitätsprüfung (25.10.2026)

- Automatische Prüfung der Ist- und Solltemperaturen mit Standardgrenzen
  −20 bis +60 °C (Grenzen eingeschlossen); beide Werte sind konfigurierbar.
- Unplausible Temperaturen erscheinen als „–“ und erzeugen Instanzstatus 202,
  ohne die Quellvariablen zu verändern. Gültige Werte stellen Status und
  Kachelanzeige automatisch wieder her.
- Plausibilitätsprüfung abschaltbar; ungültige Grenzkonfiguration führt
  bei aktiver Prüfung zu Status 201.
- Modell- und Modulprüfungen um Sensorausreißer, Grenzwerte und Fehlerbehebung
  ergänzt.

## 1.0.7 – Automatikbetrieb und Fehlererkennung für HVAC (25.10.2026)

- KNX DPT 20.102: gültigen Wert `0` als „Automatik“ darstellen; die
  zusätzliche Kennzahl erscheint nur, wenn mindestens ein Raum so betrieben wird.
- Individuelle Betriebsart-Zuordnungen mit Wert `0` behalten Vorrang.
- Unbekannte numerische Betriebsarten wie `99` werden mit Rohwert
  gekennzeichnet und lösen eine Diagnose-/Instanzwarnung (202) aus.
- Modell- und Integrationstests für Auto, ungültige Codes und individuelle
  Mappings ergänzt.

## 1.0.6 – Beschriftung im Raumdialog verbessert (25.10.2026)

- Das Feld für den einzelnen Fensterkontakt heißt im Bearbeitungsdialog
  kürzer „Fensterkontakt“, damit die Beschriftung vollständig sichtbar bleibt.
- Der Hinweis auf die zusätzliche Fensterliste steht nun in einer eigenen
  Zeile. Keine Änderung an gespeicherten Zuordnungen oder Funktionen.

## 1.0.5 – Dynamische Tabellenspalten (25.10.2026)

- Tabellenspalten werden standardmäßig anhand der konfigurierten Variablen
  automatisch ein- und ausgeblendet; Raumname und Isttemperatur bleiben sichtbar.
- Ein neuer Schalter `AutoColumns` ermöglicht weiterhin die vollständige Ansicht.
- Alle Etagen verwenden dieselbe Spaltenauswahl; fehlende Messwerte lassen
  konfigurierte Spalten sichtbar.
- Zusatzspalten für Klimadaten behalten die separate `ShowExtraColumns`-Freigabe.
- Die Tabellenbreite passt sich der Anzahl sichtbarer Spalten an.
- Modell- und Modultests um die neue Spaltenlogik ergänzt.

## 1.0.4 – Mehrere Fensterkontakte je Raum (25.10.2026)

- Neue kompakte Konfigurationsliste „Fensterkontakte je Raum“ mit Variablen-ID,
  frei wählbarer Bezeichnung und individueller Invertierung.
- Bisheriger Einzelkontakt bleibt kompatibel und kann durch weitere Kontakte
  ergänzt werden; doppelte IDs werden pro Raum nicht doppelt gezählt.
- Kachel zeigt „1 von 2 offen“ oder „alle geschlossen“; Gesamtkennzahl zählt
  einzelne geöffnete Kontakte. Fehlende Statuswerte bleiben erkennbar.
- Tests und Beispielkonfiguration für mehrere Fenster ergänzt.

## 1.0.3 – KNX-Betriebsarten korrigiert (25.10.2026)

- Standardzuordnung gemäß KNX DPT 20.102 korrigiert: 1=Komfort, 2=Standby,
  3=Nacht/Economy, 4=Frost-/Hitzeschutz; 0=Automatik ist nicht gesondert
  implementiert.
- Formulardokumentation, README und Tests angepasst.
- Bereits gespeicherte Modul-Einstellungen bleiben absichtlich unverändert;
  bei vorhandenen Instanzen die vier Werte einmalig manuell anpassen.

## 1.0.2 – Modul-Repository-Struktur korrigiert (25.10.2026)

- Beispielkonfiguration von `examples/` nach `docs/` verschoben.
- Der zusätzliche Ordner wurde von IP-Symcon als Modul ohne `module.json`
  interpretiert und verhinderte die Aktualisierung des Repositories.

## 1.0.1 – Konfigurationsdialog verbessert (25.10.2026)

- Die Raumliste zeigt nur noch Gruppe, Raumname und Isttemperatur.
- Alle optionalen Zuordnungen erscheinen im Bearbeitungsdialog in
  aufklappbaren Abschnitten. Keine Änderung am Datenmodell oder an Raum-IDs.
- Der Hinzufügen-Button ist auch ohne Browser-Zoom auf schmalen Fenstern erreichbar.

## 1.0.0 – Entwurf für den ersten Praxistest (25.10.2026)

- Eigenständiges Symcon-Modul `Raummonitor`.
- HTML-SDK-Kachel mit responsiver Tabelle und sechs Kennzahlen.
- Manuelle Raumauswahl und optionale Lichtlisten; variable Integer-Moduszuordnung.
- KNX-unabhängige Datenverarbeitung sowie Modul- und Modelltests.
- Autodiscovery bewusst für eine spätere Version zurückgestellt.
