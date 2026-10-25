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
        'ValveID' => 5, 'HumidityID' => 18, 'CO2ID' => 19,
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
check($data['groups'][0]['rooms'][1]['mode']['label'], 'Komfort', 'KNX DPT 20.102: 1 = Komfort');
check($data['groups'][0]['rooms'][0]['mode']['label'], 'Frostschutz', 'KNX DPT 20.102: 4 = Frostschutz');
check($data['groups'][0]['rooms'][0]['name'], 'Bad', 'Alphabetisch innerhalb EG');
check($data['groups'][0]['rooms'][1]['lights'], 1, 'Lichtanzahl aus zwei Bool-Variablen');
check($data['groups'][1]['rooms'][0]['valve'], 50.0, 'Ventil-Rohwert 0–255 umgerechnet');
check($data['groups'][0]['rooms'][1]['heatingCooling'], 'Kühlen', 'Heizen/Kühlen');
check($data['missingIDs'], [], 'Alle Werte verfügbar');
check($reads, count(RaummonitorModel::configuredIDs($rooms, $lights)), 'Jede referenzierte Variable genau einmal gelesen');

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
check(count($data['configurationErrors']) > 0, true, 'Fehlende Pflichtzuordnung erkannt');

$data = RaummonitorModel::build($rooms, [], ['GroupRooms' => false], $reader);
check(count($data['groups']), 1, 'Ohne Gruppen eine Gesamttabelle');

echo "\nAlle Modelltests bestanden.\n";
