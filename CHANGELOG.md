# Changelog

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
