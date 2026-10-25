# Changelog

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
