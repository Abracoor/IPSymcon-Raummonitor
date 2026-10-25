<?php

declare(strict_types=1);

/**
 * Reine, unabhängig von IP-Symcon testbare Datenauswertung für den Raummonitor.
 *
 * $read(int $variableID) liefert null für fehlende Variablen oder ein Array
 * ['value' => gemischter Variablenwert]. Die Auflösung der Symcon-IDs übernimmt
 * die Modulklasse. Damit sind KNX, Modbus und normale Symcon-Variablen gleichwertig.
 */
final class RaummonitorModel
{
    private const ROOM_IDS = [
        'ActualTempID', 'SetpointTempID', 'OperationModeID', 'WindowID',
        'ValveID', 'LightCountID', 'HumidityID', 'CO2ID', 'DewPointID',
        'HeatingCoolingID'
    ];

    public static function configuredIDs(array $rooms, array $lights, array $windows = []): array
    {
        $ids = [];
        foreach ($rooms as $room) {
            if (!is_array($room)) {
                continue;
            }
            foreach (self::ROOM_IDS as $field) {
                $id = (int) ($room[$field] ?? 0);
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }
        foreach ($lights as $light) {
            if (is_array($light) && (int) ($light['VariableID'] ?? 0) > 0) {
                $ids[(int) $light['VariableID']] = true;
            }
        }
        foreach ($windows as $window) {
            if (is_array($window) && (int) ($window['VariableID'] ?? 0) > 0) {
                $ids[(int) $window['VariableID']] = true;
            }
        }
        return array_keys($ids);
    }

    public static function build(array $rooms, array $lights, array $options, callable $read, array $windows = []): array
    {
        $configErrors = [];
        $missing = [];
        $groups = [];
        $seenRooms = [];
        $count = ['comfort' => 0, 'standby' => 0, 'night' => 0, 'frost' => 0, 'unknown' => 0];
        $openWindows = 0;
        $cache = [];
        // Spalten gelten global für alle Gruppen. Ihre Sichtbarkeit hängt
        // von der Zuordnung einer ID ab, nicht von ihrem aktuellen Messwert.
        $columns = array_fill_keys([
            'setpoint', 'lights', 'mode', 'window', 'valve',
            'humidity', 'co2', 'dewPoint', 'heatingCooling'
        ], false);

        // Jede Variable wird pro Aktualisierung nur einmal aus Symcon gelesen.
        $valueOf = static function (int $id) use ($read, &$cache, &$missing): mixed {
            if ($id <= 0) {
                return null;
            }
            if (!array_key_exists($id, $cache)) {
                $result = $read($id);
                $cache[$id] = is_array($result) && array_key_exists('value', $result)
                    ? $result['value'] : null;
            }
            if ($cache[$id] === null) {
                $missing[$id] = true;
            }
            return $cache[$id];
        };

        $lightsByRoom = [];
        foreach ($lights as $index => $light) {
            if (!is_array($light)) {
                $configErrors[] = 'Ungültiger Eintrag in der Lichtliste: ' . ($index + 1);
                continue;
            }
            $group = self::groupName($light['Group'] ?? '');
            $name = trim((string) ($light['Room'] ?? ''));
            $id = (int) ($light['VariableID'] ?? 0);
            if ($name === '' || $id <= 0) {
                $configErrors[] = 'Lichtzeile ' . ($index + 1) . ': Raum oder Variablen-ID fehlt';
                continue;
            }
            $key = self::roomKey($group, $name);
            if (isset($lightsByRoom[$key][$id])) {
                $configErrors[] = 'Lichtvariable ' . $id . ' im Raum ' . $name . ' doppelt';
            }
            $lightsByRoom[$key][$id] = (bool) ($light['Invert'] ?? false);
        }

        // Einzelkontakte aus der separaten Fensterliste. Die bisherige
        // WindowID je Raum wird später zusätzlich berücksichtigt.
        $windowsByRoom = [];
        foreach ($windows as $index => $entry) {
            if (!is_array($entry)) {
                $configErrors[] = 'Ungültiger Eintrag in der Fensterliste: ' . ($index + 1);
                continue;
            }
            $group = self::groupName($entry['Group'] ?? '');
            $name = trim((string) ($entry['Room'] ?? ''));
            $id = (int) ($entry['VariableID'] ?? 0);
            if ($name === '' || $id <= 0) {
                $configErrors[] = 'Fensterzeile ' . ($index + 1) . ': Raum oder Variablen-ID fehlt';
                continue;
            }
            $key = self::roomKey($group, $name);
            if (isset($windowsByRoom[$key][$id])) {
                $configErrors[] = 'Fensterkontakt ' . $id . ' im Raum ' . $name . ' doppelt';
                continue;
            }
            $windowsByRoom[$key][$id] = (bool) ($entry['Invert'] ?? false);
        }

        $mapping = [
            (int) ($options['ComfortValue'] ?? 1) => ['kind' => 'comfort', 'label' => 'Komfort'],
            (int) ($options['StandbyValue'] ?? 2) => ['kind' => 'standby', 'label' => 'Standby'],
            (int) ($options['NightValue'] ?? 3) => ['kind' => 'night', 'label' => 'Nacht'],
            (int) ($options['FrostValue'] ?? 4) => ['kind' => 'frost', 'label' => 'Frostschutz']
        ];
        if (count($mapping) !== 4) {
            $configErrors[] = 'Die vier Betriebsarten müssen unterschiedliche Integerwerte besitzen';
        }

        foreach ($rooms as $index => $room) {
            if (!is_array($room)) {
                $configErrors[] = 'Ungültige Raumzeile ' . ($index + 1);
                continue;
            }
            $group = self::groupName($room['Group'] ?? '');
            $name = trim((string) ($room['Room'] ?? ''));
            if ($name === '') {
                $configErrors[] = 'Raumzeile ' . ($index + 1) . ': Raumname fehlt';
                continue;
            }
            $key = self::roomKey($group, $name);
            if (isset($seenRooms[$key])) {
                $configErrors[] = 'Raum ' . $group . ' / ' . $name . ' ist doppelt';
                continue;
            }
            $seenRooms[$key] = true;
            $tempID = (int) ($room['ActualTempID'] ?? 0);
            if ($tempID <= 0) {
                $configErrors[] = 'Raum ' . $name . ': Isttemperatur fehlt';
            }

            $actual = self::number($valueOf($tempID));
            $setpoint = self::number($valueOf((int) ($room['SetpointTempID'] ?? 0)));
            $humidity = self::number($valueOf((int) ($room['HumidityID'] ?? 0)));
            $co2 = self::number($valueOf((int) ($room['CO2ID'] ?? 0)));
            $dewPoint = self::number($valueOf((int) ($room['DewPointID'] ?? 0)));

            foreach (['ActualTempID' => $actual, 'SetpointTempID' => $setpoint,
                'HumidityID' => $humidity, 'CO2ID' => $co2, 'DewPointID' => $dewPoint] as $field => $number) {
                $id = (int) ($room[$field] ?? 0);
                if ($id > 0 && $number === null) {
                    $missing[$id] = true;
                }
            }

            $modeID = (int) ($room['OperationModeID'] ?? 0);
            $mode = null;
            if ($modeID > 0) {
                $raw = $valueOf($modeID);
                $mode = self::mode($raw, $mapping);
                if ($mode === null) {
                    $mode = ['kind' => 'unknown', 'label' => 'Unbekannt'];
                    $missing[$modeID] = true;
                }
                $count[$mode['kind']]++;
            }

            $roomWindows = $windowsByRoom[$key] ?? [];
            $windowID = (int) ($room['WindowID'] ?? 0);
            // Eine doppelt eingetragene ID nur einmal zählen. Die neue
            // Fensterliste hat bei einer Doppelbelegung Vorrang.
            if ($windowID > 0 && !array_key_exists($windowID, $roomWindows)) {
                $roomWindows[$windowID] = (bool) ($room['WindowInverted'] ?? false);
            }
            $windowCount = count($roomWindows);
            if ($windowCount > 0) {
                $columns['window'] = true;
            }
            $openWindowCount = 0;
            $windowIncomplete = false;
            foreach ($roomWindows as $id => $invert) {
                $state = self::switchState($valueOf((int) $id));
                if ($state === null) {
                    $missing[$id] = true;
                    $windowIncomplete = true;
                    continue;
                }
                if ($invert ? !$state : $state) {
                    $openWindowCount++;
                }
            }
            $openWindows += $openWindowCount;
            // Fehlende Werte niemals als sicher geschlossen ausgeben.
            $window = $windowCount === 0 || ($windowIncomplete && $openWindowCount === 0)
                ? null : $openWindowCount > 0;

            $valveID = (int) ($room['ValveID'] ?? 0);
            $valve = null;
            if ($valveID > 0) {
                $raw = self::number($valueOf($valveID));
                $max = (float) ($room['ValveMax'] ?? 100);
                if ($max <= 0 || !is_finite($max)) {
                    $configErrors[] = 'Raum ' . $name . ': ungültiger Ventil-Endwert';
                } elseif ($raw !== null && $raw >= 0 && $raw <= $max) {
                    $valve = round($raw / $max * 100, 1);
                } else {
                    $missing[$valveID] = true;
                }
            }

            $lightCount = null;
            $lightIncomplete = false;
            $lightCountID = (int) ($room['LightCountID'] ?? 0);
            if ($lightCountID > 0) {
                $raw = self::number($valueOf($lightCountID));
                if ($raw !== null && $raw >= 0 && floor($raw) === $raw) {
                    $lightCount = (int) $raw;
                } else {
                    $missing[$lightCountID] = true;
                }
            } elseif (isset($lightsByRoom[$key])) {
                $lightCount = 0;
                $known = 0;
                foreach ($lightsByRoom[$key] as $id => $invert) {
                    $state = self::switchState($valueOf((int) $id));
                    if ($state === null) {
                        $missing[$id] = true;
                        $lightIncomplete = true;
                        continue;
                    }
                    $known++;
                    if ($invert ? !$state : $state) {
                        $lightCount++;
                    }
                }
                if ($known === 0) {
                    $lightCount = null;
                }
            }

            $heatCoolID = (int) ($room['HeatingCoolingID'] ?? 0);
            $heatCool = null;
            if ($heatCoolID > 0) {
                $state = self::switchState($valueOf($heatCoolID));
                if ($state === null) {
                    $missing[$heatCoolID] = true;
                } else {
                    $isCooling = $state === (bool) ($options['CoolingWhenTrue'] ?? true);
                    $heatCool = $isCooling ? 'Kühlen' : 'Heizen';
                }
            }

            foreach ([
                'setpoint' => 'SetpointTempID',
                'mode' => 'OperationModeID',
                'valve' => 'ValveID',
                'humidity' => 'HumidityID',
                'co2' => 'CO2ID',
                'dewPoint' => 'DewPointID',
                'heatingCooling' => 'HeatingCoolingID'
            ] as $column => $field) {
                if ((int) ($room[$field] ?? 0) > 0) {
                    $columns[$column] = true;
                }
            }
            if ($lightCountID > 0 || isset($lightsByRoom[$key])) {
                $columns['lights'] = true;
            }

            $groups[$group][] = [
                'name' => $name,
                'group' => $group,
                'actual' => $actual,
                'setpoint' => $setpoint,
                'lights' => $lightCount,
                'lightsIncomplete' => $lightIncomplete,
                'mode' => $mode,
                'window' => $window,
                'windowCount' => $windowCount,
                'openWindowCount' => $openWindowCount,
                'windowIncomplete' => $windowIncomplete,
                'valve' => $valve,
                'humidity' => $humidity,
                'co2' => $co2,
                'dewPoint' => $dewPoint,
                'heatingCooling' => $heatCool
            ];
        }

        foreach ($lightsByRoom as $key => $_) {
            if (!isset($seenRooms[$key])) {
                $configErrors[] = 'Lichtzuordnung ohne passenden Raum: ' . str_replace("\0", ' / ', $key);
            }
        }
        foreach ($windowsByRoom as $key => $_) {
            if (!isset($seenRooms[$key])) {
                $configErrors[] = 'Fensterzuordnung ohne passenden Raum: ' . str_replace("\0", ' / ', $key);
            }
        }

        // Natürliche alphabetische Sortierung innerhalb der Gruppen (Bad vor Wohnzimmer).
        uksort($groups, 'strnatcasecmp');
        foreach ($groups as &$entries) {
            usort($entries, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        }
        unset($entries);

        $grouped = [];
        $totalRooms = 0;
        foreach ($groups as $name => $entries) {
            $totalRooms += count($entries);
            $grouped[] = ['name' => $name, 'rooms' => $entries];
        }
        if (!(bool) ($options['GroupRooms'] ?? true)) {
            $all = [];
            foreach ($grouped as $group) {
                array_push($all, ...$group['rooms']);
            }
            usort($all, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
            $grouped = [['name' => '', 'rooms' => $all]];
        }

        // Manuell vollständig: Raum und Ist sind ohnehin immer sichtbar,
        // weitere Basisspalten werden eingeblendet. Extras erfordern auch
        // weiterhin die separate Freigabe durch ShowExtraColumns.
        $extras = ['humidity', 'co2', 'dewPoint', 'heatingCooling'];
        if (!(bool) ($options['AutoColumns'] ?? true)) {
            foreach (array_keys($columns) as $column) {
                $columns[$column] = true;
            }
        }
        if (!(bool) ($options['ShowExtraColumns'] ?? false)) {
            foreach ($extras as $column) {
                $columns[$column] = false;
            }
        }

        return [
            'totalRooms' => $totalRooms,
            'openWindows' => $openWindows,
            'modeCounts' => $count,
            'groups' => $grouped,
            'showGroups' => (bool) ($options['GroupRooms'] ?? true),
            'showExtras' => (bool) ($options['ShowExtraColumns'] ?? false),
            'columns' => $columns,
            'configurationErrors' => $configErrors,
            'missingIDs' => array_map('intval', array_keys($missing))
        ];
    }

    private static function groupName(mixed $name): string
    {
        $name = trim((string) $name);
        return $name === '' ? 'Ohne Gruppe' : $name;
    }

    private static function roomKey(string $group, string $room): string
    {
        return $group . "\0" . $room;
    }

    private static function number(mixed $raw): ?float
    {
        if (is_bool($raw) || $raw === null || !(is_int($raw) || is_float($raw) || is_string($raw))) {
            return null;
        }
        $normalized = is_string($raw) ? str_replace(',', '.', trim($raw)) : $raw;
        if (!is_numeric($normalized)) {
            return null;
        }
        $value = (float) $normalized;
        return is_finite($value) ? $value : null;
    }

    private static function mode(mixed $raw, array $mapping): ?array
    {
        if ($raw === null || is_bool($raw)) {
            return null;
        }
        $number = self::number($raw);
        if ($number !== null && floor($number) === $number) {
            return $mapping[(int) $number] ?? ['kind' => 'unknown', 'label' => (string) (int) $number];
        }
        if (!is_string($raw)) {
            return null;
        }
        return match (strtolower(trim($raw))) {
            'komfort', 'comfort' => ['kind' => 'comfort', 'label' => 'Komfort'],
            'standby' => ['kind' => 'standby', 'label' => 'Standby'],
            'nacht', 'night', 'eco', 'economy' => ['kind' => 'night', 'label' => 'Nacht'],
            'frostschutz', 'frost', 'building protection' => ['kind' => 'frost', 'label' => 'Frostschutz'],
            default => null
        };
    }

    private static function switchState(mixed $raw): ?bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw) || is_float($raw)) {
            return is_finite((float) $raw) ? $raw > 0 : null;
        }
        if (!is_string($raw)) {
            return null;
        }
        $value = strtolower(trim($raw));
        if (in_array($value, ['offen', 'open', 'an', 'on', 'ja', 'true', 'gekippt'], true)) {
            return true;
        }
        if (in_array($value, ['geschlossen', 'closed', 'aus', 'off', 'nein', 'false', ''], true)) {
            return false;
        }
        $number = self::number($value);
        return $number === null ? null : $number > 0;
    }
}
