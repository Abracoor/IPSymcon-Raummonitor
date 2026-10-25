<?php

declare(strict_types=1);

require_once __DIR__ . '/../Raummonitor/RaummonitorModel.php';

function check(mixed $actual, mixed $expected, string $description): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, "FEHLER: $description\nErwartet: " . var_export($expected, true)
            . "\nErhalten: " . var_export($actual, true) . "\n");
        exit(1);
    }
    echo "OK: $description\n";
}

$values = [
    1 => 21.4, 2 => 22.0, 3 => 1, 4 => false, 5 => 35,
    6 => true, 7 => false, 8 => 23.0, 9 => 24.0, 10 => 4,
    11 => 0, 12 => 25, 13 => 18.5, 14 => 17.0, 15 => 3,
    16 => true, 17 => 127.5, 18 => 42.0, 19 => 850,
    20 => 12.5, 21 => true
];
$rooms = [
    [
        'Group' => 'EG', 'Room' => 'Wohnzimmer', 'ActualTempID' => 1,
        'SetpointTempID' => 2, 'OperationModeID' => 3, 'WindowID' => 4,
        'ValveID' => 5, 'DetailCategoryID' => 12353, 'HumidityID' => 18, 'CO2ID' => 19,
        'DewPointID' => 20, 'HeatingCoolingID' => 21
    ],
    [
        'Group' => 'EG', 'Room' => 'Bad', 'ActualTempID' => 8,
        'SetpointTempID' => 9, 'OperationModeID' => 10,
        'WindowID' => 11, 'ValveID' => 12
    ],
    [
        'Group' => 'OG', 'Room' => 'Büro', 'ActualTempID' => 13,
        'SetpointTempID' => 14, 'OperationModeID' => 15,
        'WindowID' => 16, 'WindowInverted' => true,
        'ValveID' => 17, 'ValveMax' => 255
    ]
];
$lights = [
    ['Group' => 'EG', 'Room' => 'Wohnzimmer', 'VariableID' => 6],
    ['Group' => 'EG', 'Room' => 'Wohnzimmer', 'VariableID' => 7]
];
$reads = 0;
$reader = static function (int $id) use (&$reads, &$values): ?array {
    $reads++;
    return array_key_exists($id, $values) ? ['value' => $values[$id]] : null;
};

$data = RaummonitorModel::build($rooms, $lights, ['CoolingWhenTrue' => true], $reader);
check($data['totalRooms'], 3, 'Drei Räume');
check($data['openWindows'], 0, 'Fensterstatus inklusive Invertierung');
check($data['modeCounts']['comfort'], 1, 'Komfort erkannt');
check($data['modeCounts']['frost'], 1, 'Frostschutz erkannt');
check($data['modeCounts']['night'], 1, 'Nacht erkannt');
check($data['columns']['setpoint'], true, 'Soll-Spalte sichtbar bei zugeordneter Variable');
check($data['columns']['lights'], true, 'Licht-Spalte sichtbar bei vorhandener Lichtliste');
check($data['columns']['mode'], true, 'Betriebsart-Spalte sichtbar bei zugeordneter Variable');
check($data['columns']['window'], true, 'Fenster-Spalte sichtbar bei vorhandenem Einzelkontakt');
check($data['columns']['valve'], true, 'Ventil-Spalte sichtbar bei zugeordneter Variable');
check($data['columns']['humidity'], false, 'Zusatzspalten ohne Freigabe verborgen');
check($data['groups'][0]['rooms'][1]['mode']['label'], 'Komfort', 'KNX DPT 20.102: 1 = Komfort');
check($data['groups'][0]['rooms'][0]['mode']['label'], 'Frostschutz', 'KNX DPT 20.102: 4 = Frostschutz');
check($data['groups'][0]['rooms'][0]['name'], 'Bad', 'Alphabetisch innerhalb EG');
check($data['groups'][0]['rooms'][1]['lights'], 1, 'Lichtanzahl aus zwei Bool-Variablen');
check($data['groups'][1]['rooms'][0]['valve'], 50.0, 'Ventil-Rohwert 0–255 umgerechnet');
check($data['groups'][0]['rooms'][1]['heatingCooling'], 'Kühlen', 'Heizen/Kühlen');
check($data['groups'][0]['rooms'][1]['detailCategoryID'], 12353, 'Optionale Detailkategorie wird übernommen');
check($data['groups'][0]['rooms'][0]['detailCategoryID'], 0, 'Alter Raum ohne Detailseite bleibt ohne Navigation');
check($data['missingIDs'], [], 'Alle Werte verfügbar');
check($reads, count(RaummonitorModel::configuredIDs($rooms, $lights)), 'Jede referenzierte Variable genau einmal gelesen');
$noTarget = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Ohne Detailseite', 'ActualTempID' => 8, 'DetailCategoryID' => 1]],
    [], [], $reader
);
check($noTarget['groups'][0]['rooms'][0]['detailCategoryID'], 0,
    'Symcon Kein(e) (ID 1) erzeugt keine Navigation');
$rootTarget = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Wurzel', 'ActualTempID' => 8, 'DetailCategoryID' => 0]],
    [], [], $reader
);
check($rootTarget['groups'][0]['rooms'][0]['detailCategoryID'], 0,
    'Frühere Wurzel-Auswahl (ID 0) bleibt inaktiv');

// Konfigurierbare Temperatur-Plausibilität inkl. Grenzwerten und Rückkehr.
$values[1] = 32767;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['groups'][0]['rooms'][1]['actual'], null, 'Unplausible Isttemperatur wird nicht angezeigt');
check(in_array(1, $data['missingIDs'], true), true, 'Unplausible Isttemperatur wird diagnostiziert');
check($data['columns']['setpoint'], true, 'Plausibilitätsfehler blendet keine konfigurierte Spalte aus');

$data = RaummonitorModel::build($rooms, $lights, ['ValidateTemperatures' => false], $reader);
check($data['groups'][0]['rooms'][1]['actual'], 32767.0, 'Abgeschaltete Plausibilitätsprüfung lässt Rohwerte zu');
$values[1] = 21.4;

$values[2] = -20;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['groups'][0]['rooms'][1]['setpoint'], -20.0, 'Untergrenze ist eingeschlossen');
$values[2] = 60;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['groups'][0]['rooms'][1]['setpoint'], 60.0, 'Obergrenze ist eingeschlossen');
$values[2] = 60.1;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['groups'][0]['rooms'][1]['setpoint'], null, 'Sollwert oberhalb der Obergrenze wird verworfen');
$values[2] = 22.0;

$data = RaummonitorModel::build($rooms, $lights,
    ['MinimumTemperature' => 22.0, 'MaximumTemperature' => 25.0], $reader);
check($data['groups'][0]['rooms'][1]['actual'], null, 'Individuelle Temperaturgrenzen werden verwendet');

$data = RaummonitorModel::build($rooms, $lights,
    ['MinimumTemperature' => 70.0, 'MaximumTemperature' => 60.0], $reader);
check(count($data['configurationErrors']) > 0, true, 'Vertauschte Temperaturgrenzen werden als Konfigurationsfehler gemeldet');

// QA: Der KNX-Modus 0 ist gültig, unbekannte Codes dagegen nicht.
$values[3] = 0;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['modeCounts']['auto'], 1, 'KNX DPT 20.102: 0 = Automatik');
check($data['groups'][0]['rooms'][1]['mode']['label'], 'Automatik', 'Automatik als lesbarer Status');
check($data['missingIDs'], [], 'Gültiger Automatikmodus erzeugt keinen Datenfehler');

$data = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Benutzerdefiniert', 'ActualTempID' => 1, 'OperationModeID' => 3]],
    [], ['ComfortValue' => 0, 'StandbyValue' => 1, 'NightValue' => 2, 'FrostValue' => 3], $reader
);
check($data['modeCounts']['comfort'], 1, 'Benutzerdefinierte Belegung von 0 hat Vorrang');
check($data['modeCounts']['auto'], 0, 'Belegter Wert 0 wird nicht als Automatik doppelt gezählt');

$values[3] = 99;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['modeCounts']['unknown'], 1, 'Unbekannter Integerstatus erkannt');
check($data['groups'][0]['rooms'][1]['mode']['label'], 'Unbekannt (99)', 'Unbekannter Code verständlich angezeigt');
check(in_array(3, $data['missingIDs'], true), true, 'Ungültige Betriebsart löst Messwertfehler aus');
$values[3] = 1;

// Mehrere Kontakte im selben Raum: bisheriger Einzelkontakt + zusätzliche Liste.
$values[22] = true;
$windows = [
    ['Group' => 'EG', 'Room' => 'Wohnzimmer', 'Name' => 'Fenster rechts',
        'VariableID' => 22, 'Invert' => false]
];
$data = RaummonitorModel::build($rooms, $lights, [], $reader, $windows);
check($data['openWindows'], 1, 'Ein geöffnetes Fenster zählt als ein Kontakt');
check($data['groups'][0]['rooms'][1]['windowCount'], 2, 'Alter Einzelkontakt und Fensterliste zusammengefasst');
check($data['groups'][0]['rooms'][1]['openWindowCount'], 1, 'Ein von zwei Fenstern geöffnet');
check($data['groups'][0]['rooms'][1]['window'], true, 'Ein geöffnetes Fenster macht den Raumstatus offen');
check($data['columns']['window'], true, 'Zusätzliche Fensterlisten aktivieren globale Fensterspalte');
check(in_array(22, RaummonitorModel::configuredIDs($rooms, $lights, $windows), true), true,
    'Zusätzlicher Fensterkontakt wird für Updates abonniert');

$values[4] = true;
$data = RaummonitorModel::build($rooms, $lights, [], $reader, $windows);
check($data['openWindows'], 2, 'Zwei geöffnete Fenster werden einzeln gezählt');

$values[4] = false;
$values[22] = false;
$data = RaummonitorModel::build($rooms, $lights, [], $reader, $windows);
check($data['groups'][0]['rooms'][1]['window'], false, 'Alle Kontakte geschlossen = Raum geschlossen');

unset($values[22]);
$data = RaummonitorModel::build($rooms, $lights, [], $reader, $windows);
check($data['groups'][0]['rooms'][1]['window'], null, 'Unbekannter Kontakt niemals als geschlossen angezeigt');
check($data['groups'][0]['rooms'][1]['windowIncomplete'], true, 'Unvollständige Kontaktliste markiert');
check($data['missingIDs'], [22], 'Fehlender Fensterkontakt wird gemeldet');

$windows[] = ['Group' => 'EG', 'Room' => 'Wohnzimmer', 'VariableID' => 4, 'Invert' => false];
$data = RaummonitorModel::build($rooms, $lights, [], $reader, $windows);
check($data['groups'][0]['rooms'][1]['windowCount'], 2, 'Dieselbe ID aus Einzelkontakt und Liste wird nicht doppelt gezählt');
$values[22] = true;
array_pop($windows);

$values[3] = 2;
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['modeCounts']['standby'], 1, 'KNX DPT 20.102: 2 = Standby');
$values[3] = 1;

unset($values[7]);
$data = RaummonitorModel::build($rooms, $lights, [], $reader);
check($data['groups'][0]['rooms'][1]['lightsIncomplete'], true, 'Teilfehler Lichtstatus wird markiert');
check($data['missingIDs'], [7], 'Fehlende ID wird gemeldet');

$data = RaummonitorModel::build(
    [['Group' => 'OG', 'Room' => 'Test', 'ActualTempID' => 1, 'OperationModeID' => 3]],
    [], ['ComfortValue' => 5, 'StandbyValue' => 6, 'NightValue' => 7, 'FrostValue' => 1],
    $reader
);
check($data['modeCounts']['frost'], 1, 'Integerzuordnung frei konfigurierbar');

$data = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Ohne Ist', 'ActualTempID' => 0]],
    [], [], $reader
);
check($data['configurationErrors'], [], 'Isttemperatur ist keine Pflichtzuordnung mehr');
check($data['missingIDs'], [], 'Nicht zugeordnete Temperatur erzeugt keinen Datenfehler');
check($data['totalRooms'], 1, 'Auch Raum ohne jeden Messwert bleibt sichtbar');
check($data['columns']['actual'], false, 'Nicht genutzte Ist-Spalte automatisch ausgeblendet');

// Räume mit reinem Licht-/Fensterstatus oder nur einem Navigationsziel.
$otherRooms = [
    ['Group' => 'EG', 'Room' => 'Licht', 'ActualTempID' => 0],
    ['Group' => 'EG', 'Room' => 'Fenster', 'WindowID' => 16],
    ['Group' => 'EG', 'Room' => 'Details', 'DetailCategoryID' => 12353]
];
$otherLights = [['Group' => 'EG', 'Room' => 'Licht', 'VariableID' => 6]];
$data = RaummonitorModel::build($otherRooms, $otherLights, [], $reader);
check($data['totalRooms'], 3, 'Licht-, Fenster- und Navigationsräume ohne Isttemperatur');
check($data['configurationErrors'], [], 'Keine Pflichtfeldfehler bei alternativen Räumen');
check($data['missingIDs'], [], 'Andere Räume erzeugen keine Isttemperatur-Messfehler');
check($data['columns']['actual'], false, 'Ist-Spalte bleibt ohne Zuordnung verborgen');
check($data['columns']['lights'], true, 'Lichtliste macht Lichtspalte ohne Isttemperatur sichtbar');
check($data['columns']['window'], true, 'Fensterkontakt macht Fensterspalte ohne Isttemperatur sichtbar');
check($data['groups'][0]['rooms'][0]['detailCategoryID'], 12353,
    'Reiner Navigationsraum behält sein Ziel');

$data = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Nur Soll', 'SetpointTempID' => 9]], [], [], $reader
);
check($data['columns']['actual'], false, 'Solltemperatur allein benötigt keine Ist-Spalte');
check($data['columns']['setpoint'], true, 'Solltemperatur allein aktiviert ihre eigene Spalte');
check($data['configurationErrors'], [], 'Nur Solltemperatur ist eine gültige Raumkonfiguration');

$data = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Sensor fehlt', 'ActualTempID' => 999]], [], [], $reader
);
check($data['columns']['actual'], true, 'Konfigurierte, nicht lesbare Temperatur bleibt als Spalte sichtbar');
check($data['missingIDs'], [999], 'Konfigurierte, nicht lesbare Temperatur bleibt Fehler');
check($data['configurationErrors'], [], 'Fehlende Sensordaten sind kein Pflichtfeldfehler');

$data = RaummonitorModel::build(
    [['Group' => 'EG', 'Room' => 'Ohne Ist']], [], ['AutoColumns' => false], $reader
);
check($data['columns']['actual'], true, 'Manuelle Vollansicht zeigt auch ohne Zuordnung Ist');

$data = RaummonitorModel::build($rooms, [], ['GroupRooms' => false], $reader);
check(count($data['groups']), 1, 'Ohne Gruppen eine Gesamttabelle');

$onlyActual = [['Group' => 'EG', 'Room' => 'Minimal', 'ActualTempID' => 1]];
$data = RaummonitorModel::build($onlyActual, [], [], $reader);
check(array_filter($data['columns']), ['actual' => true],
    'Nur Isttemperatur zugeordnet: alle anderen optionalen Spalten ausgeblendet');

$data = RaummonitorModel::build($onlyActual, [], ['AutoColumns' => false], $reader);
check($data['columns']['lights'], true, 'Manuelle Vollansicht zeigt Lichtspalte ohne Verknüpfung');
check($data['columns']['window'], true, 'Manuelle Vollansicht zeigt Fensterspalte ohne Verknüpfung');
check($data['columns']['humidity'], false, 'Klima-Zusatzspalten bleiben trotz Vollansicht optional');

$data = RaummonitorModel::build($onlyActual, [],
    ['AutoColumns' => false, 'ShowExtraColumns' => true], $reader);
check($data['columns']['co2'], true, 'Vollansicht mit Klima-Freigabe zeigt CO2 auch ohne Zuordnung');

$data = RaummonitorModel::build($onlyActual, [], ['ShowExtraColumns' => true], $reader);
check($data['columns']['co2'], false, 'Automatik blendet unbenutzte Klima-Spalten aus');

$withExtras = [['Group' => 'OG', 'Room' => 'Klima', 'ActualTempID' => 1,
    'SetpointTempID' => 999, 'HumidityID' => 18]];
$data = RaummonitorModel::build($withExtras, [], ['ShowExtraColumns' => true], $reader);
check($data['columns']['setpoint'], true, 'Fehlender Soll-Messwert blendet konfigurierte Spalte nicht aus');
check($data['columns']['humidity'], true, 'Konfigurierte Klimaspalte wird bei Freigabe sichtbar');
check($data['columns']['co2'], false, 'Unbenutzte Klimaspalte bleibt verborgen');

$data = RaummonitorModel::build($onlyActual, [], [], $reader,
    [['Group' => 'EG', 'Room' => 'Minimal', 'VariableID' => 22]]);
check($data['columns']['window'], true, 'Fensterliste allein aktiviert Spalte');

echo "\nAlle Modelltests bestanden.\n";
