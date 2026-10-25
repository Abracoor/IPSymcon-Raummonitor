<?php

declare(strict_types=1);

require_once __DIR__ . '/RaummonitorModel.php';

/**
 * Raummonitor: schreibgeschütztes Dashboard für Raum- und Heizungsdaten.
 *
 * Ab IP-Symcon 9.0. Die HTML-SDK-Kachel erhält ihre Initialdaten über
 * GetVisualizationTile und alle weiteren Änderungen über VM_UPDATE.
 */
class Raummonitor extends IPSModuleStrict
{
    private const STATUS_CONFIGURATION_ERROR = 201;
    private const STATUS_MISSING_DATA = 202;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterPropertyString('Rooms', '[]');
        $this->RegisterPropertyString('Lights', '[]');
        $this->RegisterPropertyInteger('ComfortValue', 0);
        $this->RegisterPropertyInteger('StandbyValue', 1);
        $this->RegisterPropertyInteger('NightValue', 2);
        $this->RegisterPropertyInteger('FrostValue', 3);
        $this->RegisterPropertyBoolean('CoolingWhenTrue', true);
        $this->RegisterPropertyBoolean('GroupRooms', true);
        $this->RegisterPropertyBoolean('ShowExtraColumns', false);
        $this->RegisterPropertyInteger('RefreshSeconds', 0);

        // Optionaler Sicherheitsabgleich; regulär ist die Kachel ereignisgesteuert.
        $this->RegisterTimer('Refresh', 0, 'RMON_Update($_IPS["TARGET"]);');

        // Der Vollbildtyp wurde erst in 9.1 eingeführt. 9.0 nutzt HTML (1).
        $this->SetVisualizationType(
            defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN')
                ? INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN
                : 1
        );
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        // Beim Bearbeiten der Konfiguration alte Abonnements und Referenzen lösen.
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if (in_array($message, [VM_UPDATE, VM_DELETE, IPS_KERNELMESSAGE], true)) {
                    $this->UnregisterMessage((int) $senderID, $message);
                }
            }
        }
        foreach ($this->GetReferenceList() as $id) {
            $this->UnregisterReference($id);
        }

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
        $seconds = max(0, min(3600, $this->ReadPropertyInteger('RefreshSeconds')));
        $this->SetTimerInterval('Refresh', $seconds * 1000);

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        [$rooms, $lights] = $this->configurationLists();
        foreach (RaummonitorModel::configuredIDs($rooms, $lights) as $id) {
            if (!IPS_VariableExists($id)) {
                continue;
            }
            $this->RegisterReference($id);
            $this->RegisterMessage($id, VM_UPDATE);
            $this->RegisterMessage($id, VM_DELETE);
        }

        $this->Update();
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($Message === IPS_KERNELMESSAGE && ($Data[0] ?? null) === KR_READY) {
            $this->ApplyChanges();
            return;
        }

        if ($Message === VM_UPDATE || $Message === VM_DELETE) {
            $this->Update();
        }
    }

    /**
     * Lesefunktion für Skripte: RMON_GetSnapshot($id).
     * Gibt dieselbe strukturierte, schreibgeschützte Datensicht wie die Kachel zurück.
     */
    public function GetSnapshot(): array
    {
        [$rooms, $lights, $validJSON] = $this->configurationLists();
        $options = $this->options();

        $snapshot = RaummonitorModel::build(
            $rooms,
            $lights,
            $options,
            static function (int $variableID): ?array {
                if ($variableID <= 0 || !IPS_VariableExists($variableID)) {
                    return null;
                }
                try {
                    return ['value' => GetValue($variableID)];
                } catch (Throwable $exception) {
                    return null;
                }
            }
        );
        if (!$validJSON) {
            $snapshot['configurationErrors'][] = 'Ungültiges JSON in der Raum- oder Lichtliste';
        }
        return $snapshot;
    }

    /**
     * Öffentliche Funktion RMON_Update($id) für Formular, Timer oder Skripte.
     * Meldet nur bei tatsächlicher Datenänderung eine neue Darstellung.
     */
    public function Update(): void
    {
        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $state = $this->GetSnapshot();
        if ($state['configurationErrors'] !== []) {
            $this->SetStatus(self::STATUS_CONFIGURATION_ERROR);
        } elseif ($state['missingIDs'] !== []) {
            $this->SetStatus(self::STATUS_MISSING_DATA);
        } elseif ($state['totalRooms'] === 0) {
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }

        $diagnostics = json_encode([$state['configurationErrors'], $state['missingIDs']]);
        if ($this->GetBuffer('DiagnosticsHash') !== md5((string) $diagnostics)) {
            $this->SetBuffer('DiagnosticsHash', md5((string) $diagnostics));
            if ($state['configurationErrors'] !== [] || $state['missingIDs'] !== []) {
                $this->SendDebug('Konfiguration/Variablen', (string) $diagnostics, 0);
            }
        }

        $json = $this->encode($this->tilePayload($state));
        $hash = hash('sha256', $json);
        if ($hash !== $this->GetBuffer('TileHash')) {
            $this->SetBuffer('TileHash', $hash);
            $this->UpdateVisualizationValue($json);
        }
    }

    public function GetVisualizationTile(): string
    {
        $html = file_get_contents(__DIR__ . '/module.html');
        if ($html === false) {
            $this->SendDebug(__FUNCTION__, 'module.html konnte nicht gelesen werden', 0);
            return '';
        }

        // JSON_HEX_TAG verhindert, dass Raumnamen Script-/HTML-Tags einschleusen.
        $json = $this->encode($this->tilePayload($this->GetSnapshot()));
        return $html . '<script>handleMessage(' . $json . ');</script>';
    }

    /** @return array{0: array, 1: array, 2: bool} */
    private function configurationLists(): array
    {
        $rooms = json_decode($this->ReadPropertyString('Rooms'), true);
        $lights = json_decode($this->ReadPropertyString('Lights'), true);
        $valid = is_array($rooms) && array_is_list($rooms)
            && is_array($lights) && array_is_list($lights);
        return [is_array($rooms) ? $rooms : [], is_array($lights) ? $lights : [], $valid];
    }

    private function options(): array
    {
        return [
            'ComfortValue' => $this->ReadPropertyInteger('ComfortValue'),
            'StandbyValue' => $this->ReadPropertyInteger('StandbyValue'),
            'NightValue' => $this->ReadPropertyInteger('NightValue'),
            'FrostValue' => $this->ReadPropertyInteger('FrostValue'),
            'CoolingWhenTrue' => $this->ReadPropertyBoolean('CoolingWhenTrue'),
            'GroupRooms' => $this->ReadPropertyBoolean('GroupRooms'),
            'ShowExtraColumns' => $this->ReadPropertyBoolean('ShowExtraColumns')
        ];
    }

    private function tilePayload(array $state): array
    {
        // Diagnosedaten bleiben in der Kachel knapp; Detail-IDs nur für das Modul-Debug.
        return [
            'totalRooms' => $state['totalRooms'],
            'openWindows' => $state['openWindows'],
            'modeCounts' => $state['modeCounts'],
            'groups' => $state['groups'],
            'showGroups' => $state['showGroups'],
            'showExtras' => $state['showExtras'],
            'issueCount' => count($state['configurationErrors']) + count($state['missingIDs'])
        ];
    }

    private function encode(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS |
            JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }
}
