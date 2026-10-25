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
        'OperationModeID' => 11, 'WindowID' => 12
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
verify(str_contains($html, 'overflow-x: auto'), 'Tabelle bleibt horizontal scrollbar');
verify(!str_contains($html, 'position: sticky'), 'Keine überlagernde fixe Raumspalte bei schmalen Kacheln');
verify(str_contains($html, '--rm-green: color-mix(in srgb, #207c4c 45%, var(--rm-text))'),
    'Komfort- und Fensterfarbe passen sich der Textfarbe des Symcon-Themes an');
verify(str_contains($html, '--rm-blue: color-mix(in srgb, #2466b0 45%, var(--rm-text))'),
    'Blaue Statusindikatoren verwenden die adaptive Farbpalette');

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

echo "\nAlle Modultests bestanden.\n";
