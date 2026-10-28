<?php

declare(strict_types=1);

// Minimaler SDK-Doppelgänger für einen Integrationstest ohne Symcon-Server.
const KR_READY = 3;
const IPS_KERNELMESSAGE = 10100;
const VM_UPDATE = 10603;
const VM_DELETE = 10602;
const IS_INACTIVE = 104;
const IS_ACTIVE = 102;
const INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN = 2;

$GLOBALS['testValues'] = [10 => 21.5, 11 => 1, 12 => true, 13 => true];

function IPS_GetKernelRunlevel(): int { return KR_READY; }
function IPS_VariableExists(int $id): bool { return array_key_exists($id, $GLOBALS['testValues']); }
function GetValue(int $id): mixed { return $GLOBALS['testValues'][$id]; }

class IPSModuleStrict
{
    public array $properties = [];
    public array $messages = [];
    public array $references = [];
    public array $buffers = [];
    public array $updates = [];
    public int $status = 0;
    public int $visualizationType = 0;
    public array $timers = [];

    public function __construct() { $this->Create(); }
    public function Create(): void { }
    public function ApplyChanges(): void { }
    protected function RegisterPropertyString(string $key, string $default): void { $this->properties[$key] = $default; }
    protected function RegisterPropertyInteger(string $key, int $default): void { $this->properties[$key] = $default; }
    protected function RegisterPropertyFloat(string $key, float $default): void { $this->properties[$key] = $default; }
    protected function RegisterPropertyBoolean(string $key, bool $default): void { $this->properties[$key] = $default; }
    protected function ReadPropertyString(string $key): string { return $this->properties[$key]; }
    protected function ReadPropertyInteger(string $key): int { return $this->properties[$key]; }
    protected function ReadPropertyFloat(string $key): float { return $this->properties[$key]; }
    protected function ReadPropertyBoolean(string $key): bool { return $this->properties[$key]; }
    protected function RegisterTimer(string $name, int $interval, string $code): void { $this->timers[$name] = $interval; }
    protected function SetTimerInterval(string $name, int $interval): void { $this->timers[$name] = $interval; }
    protected function SetVisualizationType(int $type): void { $this->visualizationType = $type; }
    protected function GetMessageList(): array { return $this->messages; }
    protected function RegisterMessage(int $id, int $message): void { $this->messages[$id][] = $message; }
    protected function UnregisterMessage(int $id, int $message): void {
        $this->messages[$id] = array_values(array_filter($this->messages[$id], fn (int $item): bool => $item !== $message));
    }
    protected function GetReferenceList(): array { return array_keys($this->references); }
    protected function RegisterReference(int $id): void { $this->references[$id] = true; }
    protected function UnregisterReference(int $id): void { unset($this->references[$id]); }
    protected function GetBuffer(string $key): string { return $this->buffers[$key] ?? ''; }
    protected function SetBuffer(string $key, string $value): void { $this->buffers[$key] = $value; }
    protected function UpdateVisualizationValue(string $payload): void { $this->updates[] = $payload; }
    protected function SendDebug(string $title, string $message, int $format): void { }
    protected function SetStatus(int $code): void { $this->status = $code; }
}

require_once __DIR__ . '/../Raummonitor/module.php';

function verify(bool $condition, string $text): void
{
    if (!$condition) {
        fwrite(STDERR, "FEHLER: $text\n");
        exit(1);
    }
    echo "OK: $text\n";
}

$instance = new Raummonitor();
$instance->properties['Rooms'] = json_encode([
    [
        'Group' => 'EG', 'Room' => '<script>alert(1)</script>', 'ActualTempID' => 10,
        'OperationModeID' => 11, 'WindowID' => 12, 'DetailCategoryID' => 12353
    ]
]);
$instance->properties['Windows'] = json_encode([
    ['Group' => 'EG', 'Room' => '<script>alert(1)</script>',
        'Name' => 'Fenster 2', 'VariableID' => 13, 'Invert' => false]
]);
$instance->ApplyChanges();
verify($instance->status === 102, 'Instanzstatus ist aktiv');
verify($instance->GetSnapshot()['modeCounts']['comfort'] === 1, 'DPT 20.102: 1 = Komfort auch in Modulvoreinstellung');
verify($instance->GetSnapshot()['openWindows'] === 2, 'Fenster aus Einzelzuordnung und neuer Liste zusammengefasst');
verify($instance->GetSnapshot()['groups'][0]['rooms'][0]['detailCategoryID'] === 12353,
    'Optionale Detailkategorie erscheint im Modellsnapshot');
verify($instance->properties['AutoColumns'] === true, 'Spaltenautomatik ist standardmäßig aktiviert');
verify($instance->properties['ValidateTemperatures'] === true, 'Temperaturprüfung standardmäßig aktiviert');
verify($instance->properties['MinimumTemperature'] === -20.0, 'Temperatur-Untergrenze standardmäßig minus 20 Grad');
verify($instance->properties['MaximumTemperature'] === 60.0, 'Temperatur-Obergrenze standardmäßig plus 60 Grad');
verify($instance->GetSnapshot()['columns']['lights'] === false, 'Unbenutzte Lichtspalte wird automatisch verborgen');
verify($instance->GetSnapshot()['columns']['window'] === true, 'Benutzte Fensterspalte bleibt sichtbar');
verify($instance->visualizationType === 2, 'HTML-Vollbildtyp verwendet');
verify(count($instance->references) === 4, 'Vier referenzierte Variablen inklusive zusätzlichem Fenster');
verify(count($instance->updates) === 1, 'Erste Kachel-Aktualisierung verschickt');

$html = $instance->GetVisualizationTile();
verify(str_contains($html, 'function handleMessage('), 'HTML-SDK Nachrichtenfunktion vorhanden');
verify(!str_contains($html, '<script>alert(1)</script>'), 'Raumname kann kein Script einschleusen');
verify(str_contains($html, '\\u003Cscript\\u003E'), 'HTML-Tag wird im JSON geschützt');
verify(str_contains($html, 'openObject(categoryID)'), 'Offizielle HTML-SDK-Navigation in der Kachel enthalten');
verify(str_contains($html, '"detailCategoryID":12353'), 'Detailkategorie in der Kachel-Payload vorhanden');
verify(str_contains($html, 'overflow-x: auto'), 'Tabelle bleibt horizontal scrollbar');
verify(!str_contains($html, 'position: sticky'), 'Keine überlagernde fixe Raumspalte bei schmalen Kacheln');
verify(str_contains($html, '--rm-green: #207c4c'),
    'Kräftige Originalfarbe für Komfort und Fenster im hellen Design');
verify(str_contains($html, '.rm-root.rm-dark'),
    'Eigene Farbpalette für das dunkle Symcon-Design');
verify(str_contains($html, '--rm-blue: color-mix(in srgb, #2466b0 45%, var(--rm-text))'),
    'Blaue Statusindikatoren sind im dunklen Design heller');
verify(str_contains($html, "root.classList.toggle('rm-dark', rmIsLightText(getComputedStyle(root).color))"),
    'Aktuelles Symcon-Theme wird anhand der tatsächlichen Textfarbe erkannt');

$instance->Update();
verify(count($instance->updates) === 1, 'Unveränderter Zustand wird nicht erneut gesendet');

$GLOBALS['testValues'][12] = false;
$instance->MessageSink(time(), 12, VM_UPDATE, []);
verify(count($instance->updates) === 2, 'Variablenupdate aktualisiert die Kachel');

$GLOBALS['testValues'][11] = 99;
$instance->MessageSink(time(), 11, VM_UPDATE, []);
verify($instance->status === 202, 'Unbekannte KNX-Betriebsart führt zu Fehlerstatus');
verify($instance->GetSnapshot()['modeCounts']['unknown'] === 1, 'Ungültiger Modus erscheint nicht als Komfort');

$GLOBALS['testValues'][11] = 0;
$instance->MessageSink(time(), 11, VM_UPDATE, []);
verify($instance->status === 102, 'KNX-Automatik ist gültig und hebt den Fehlerstatus auf');
verify($instance->GetSnapshot()['modeCounts']['auto'] === 1, 'Automatik-Modus in der Instanzauswertung erkannt');

// Auch ein Raum mit ausschließlich einer Detailseite muss ohne Temperatur
// vollständig aktiv bleiben und keine Variablen-Referenzen benötigen.
$navigationOnly = new Raummonitor();
$navigationOnly->properties['Rooms'] = json_encode([
    ['Group' => 'EG', 'Room' => 'Navigation', 'DetailCategoryID' => 12353]
]);
$navigationOnly->ApplyChanges();
verify($navigationOnly->status === IS_ACTIVE, 'Navigationsraum ohne Isttemperatur ist aktiv');
verify($navigationOnly->GetSnapshot()['columns']['actual'] === false,
    'Instanz blendet ungenutzte Ist-Spalte aus');
verify($navigationOnly->GetSnapshot()['configurationErrors'] === [],
    'Reiner Navigationsraum hat keinen Konfigurationsfehler');
verify($navigationOnly->GetSnapshot()['missingIDs'] === [],
    'Reiner Navigationsraum hat keinen fehlenden Sensorwert');
verify(count($navigationOnly->references) === 0,
    'Reiner Navigationsraum registriert keine Sensorreferenzen');

$GLOBALS['testValues'][10] = 32767;
$instance->MessageSink(time(), 10, VM_UPDATE, []);
verify($instance->status === 202, 'Unplausible Temperatur führt zu Fehlerstatus');
verify($instance->GetSnapshot()['groups'][0]['rooms'][0]['actual'] === null,
    'Unplausible Temperatur wird nicht in der Kachel ausgegeben');

$GLOBALS['testValues'][10] = 21.5;
$instance->MessageSink(time(), 10, VM_UPDATE, []);
verify($instance->status === 102, 'Gültige Temperatur hebt den Fehlerstatus wieder auf');

unset($GLOBALS['testValues'][10]);
$instance->MessageSink(time(), 10, VM_DELETE, []);
verify($instance->status === 202, 'Fehlender Istwert führt zu Fehlerstatus');

// Neue Präsenzkonfiguration arbeitet mit einzelnen und mehreren Meldern,
// ohne eine Isttemperatur oder bestehende Instanzen zu verlangen.
$GLOBALS['testValues'][14] = false;
$GLOBALS['testValues'][15] = true;
$presenceInstance = new Raummonitor();
$presenceInstance->properties['Rooms'] = json_encode([
    ['Group' => 'EG', 'Room' => 'Schulungsraum', 'PresenceID' => 14]
]);
$presenceInstance->properties['Presences'] = json_encode([
    ['Group' => 'EG', 'Room' => 'Schulungsraum', 'Name' => 'Hinten', 'VariableID' => 15]
]);
$presenceInstance->ApplyChanges();
verify($presenceInstance->status === 102, 'Präsenzraum ohne Temperatur ist aktiv');
verify(count($presenceInstance->references) === 2, 'Beide Präsenz-IDs werden referenziert');
verify($presenceInstance->GetSnapshot()['columns']['presence'] === true,
    'Präsenzspalte sichtbar, obwohl keine Temperatur konfiguriert ist');
verify($presenceInstance->GetSnapshot()['columns']['actual'] === false,
    'Istspalte bei reinem Präsenzraum ausgeblendet');
verify($presenceInstance->GetSnapshot()['roomsWithPresence'] === 1, 'Ein Raum mit erkannter Präsenz');
verify(str_contains($presenceInstance->GetVisualizationTile(), '"roomsWithPresence":1'),
    'Präsenzkennzahl wird an die HTML-Kachel übergeben');

$GLOBALS['testValues'][15] = false;
$presenceInstance->MessageSink(time(), 15, VM_UPDATE, []);
verify($presenceInstance->GetSnapshot()['roomsWithPresence'] === 0,
    'Update des Zusatzmelders aktualisiert die Raumkennzahl');
verify(count($presenceInstance->updates) === 2,
    'Präsenzwechsel erzeugt automatisch ein Kachelupdate');

unset($GLOBALS['testValues'][15]);
$presenceInstance->MessageSink(time(), 15, VM_DELETE, []);
verify($presenceInstance->status === 202, 'Nicht verfügbarer Präsenzmelder erzeugt Diagnose 202');
verify($presenceInstance->GetSnapshot()['groups'][0]['rooms'][0]['presence'] === null,
    'Fehlender Präsenzzustand wird nicht als frei dargestellt');

$GLOBALS['testValues'][15] = true;
$presenceInstance->MessageSink(time(), 15, VM_UPDATE, []);
verify($presenceInstance->status === 102, 'Wieder gültiger Präsenzwert hebt Fehlerstatus auf');

echo "\nAlle Modultests bestanden.\n";
