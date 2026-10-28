# Raummonitor (Instanz)

Die Instanz stellt die Werte mehrerer Räume in einer HTML-SDK-Kachel der
Symcon-9.x-Kachelvisualisierung dar.

Die Konfiguration erfolgt über vier Listen: **Räume und Messwerte** (nur der
Raumname ist Pflicht), optional **Lichtvariablen je Raum**,
**Fensterkontakte je Raum** und **Präsenzmelder je Raum**. Die Isttemperatur
steht wie alle übrigen Messwerte im aufklappbaren Bereich des Raumdialogs.
Gruppennamen werden frei vergeben und können beispielsweise Etagen bezeichnen.
Die Kachel zeigt standardmäßig nur die Tabellenspalten, für die mindestens
ein Raum passende Variablen verwendet, auch die Isttemperatur ist optional.
Präsenz bedeutet dabei den gemeldeten Melderstatus, nicht zwingend eine
tatsächliche Raumbelegung.
Das lässt sich in der Instanz abschalten.

Die Bedeutung aller Einstellungen, die Konfiguration von KNX-Modi, Beispiele
und die GitHub-Installation sind in der [zentralen README](../README.md)
beschrieben.
