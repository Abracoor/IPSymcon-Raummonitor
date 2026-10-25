// Prüft die dynamische Spaltenwahl ohne laufenden Symcon-Server.
// Ausführung: node tests/columns_test.js
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const html = fs.readFileSync(path.join(__dirname, '../Raummonitor/module.html'), 'utf8');
const script = html.split('<script>')[1]?.split('</script>')[0];
assert.ok(script, 'Kachel enthält JavaScript');

const form = JSON.parse(fs.readFileSync(path.join(__dirname, '../Raummonitor/form.json'), 'utf8'));
const rooms = form.elements.find(element => element.name === 'Rooms');
assert.ok(rooms, 'Konfigurator enthält die Raumliste');
assert.ok(!rooms.columns.find(column => column.name === 'ActualTempID').caption.includes('*'),
    'Isttemperatur ist in der Tabellenübersicht nicht als Pflichtfeld markiert');
assert.ok(!rooms.form.find(element => element.name === 'ActualTempID').caption.includes('*'),
    'Isttemperatur ist im Raumdialog nicht als Pflichtfeld markiert');

const context = vm.createContext({});
vm.runInContext(script, context);

function columnNames(columns, showExtras = false) {
    context.testData = {columns, showExtras};
    const result = vm.runInContext('rmVisibleColumns(testData).map(column => column.key)', context);
    return Array.from(result);
}

assert.deepEqual(columnNames({actual: false}), ['name'],
    'Ohne Messwerte bleibt allein die Raumspalte');
assert.deepEqual(columnNames({actual: false, lights: true, window: true}),
    ['name', 'lights', 'window'],
    'Licht und Fenster sind ohne Isttemperatur möglich');
assert.deepEqual(columnNames({actual: false, setpoint: true}), ['name', 'setpoint'],
    'Solltemperatur kann ohne Isttemperatur angezeigt werden');
assert.deepEqual(columnNames({actual: true, lights: true}), ['name', 'actual', 'lights'],
    'Isttemperatur bleibt sichtbar, sobald sie irgendwo zugeordnet ist');
assert.deepEqual(columnNames({actual: false, humidity: true}, true), ['name', 'humidity'],
    'Freigegebene Klimaspalten funktionieren ohne Isttemperatur');
assert.deepEqual(columnNames(null).slice(0, 2), ['name', 'actual'],
    'Die alte Payload ohne Spaltenmaske bleibt darstellbar');

console.log('OK: Die Ist-Spalte ist wie andere Messwerte optional.');
