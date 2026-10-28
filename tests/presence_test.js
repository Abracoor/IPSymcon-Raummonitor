// Testet Präsenzdarstellung und neue Formularkonfiguration ohne Symcon-Server.
// Ausführung: node tests/presence_test.js
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const form = JSON.parse(read('Raummonitor/form.json'));
const roomList = form.elements.find(element => element.name === 'Rooms');
const roomValues = roomList.form.find(element => element.caption.includes('Heizung, Fenster'));
assert.ok(roomValues, 'Raumdialog besitzt einen Bereich für optionale Raumwerte');
assert.ok(roomValues.items.some(element => element.name === 'ActualTempID'),
    'Isttemperatur steht bei den übrigen optionalen Messwerten');
assert.ok(roomValues.items.some(element => element.name === 'PresenceID'),
    'Ein Präsenzmelder kann direkt pro Raum konfiguriert werden');
assert.ok(roomValues.items.some(element => element.name === 'PresenceInverted'),
    'Die direkte Präsenzvariable kann invertiert werden');
assert.equal(roomList.columns.find(element => element.name === 'PresenceID').add, 0,
    'Neue Räume erhalten standardmäßig keine Präsenzzuordnung');
const presences = form.elements.find(element => element.name === 'Presences');
assert.ok(presences, 'Separate Liste für zusätzliche Präsenzmelder vorhanden');
for (const name of ['Group', 'Room', 'VariableID', 'Invert']) {
    assert.ok(presences.columns.some(element => element.name === name), `${name} in Präsenzliste vorhanden`);
}

const html = read('Raummonitor/module.html');
const script = html.split('<script>')[1]?.split('</script>')[0];
assert.ok(script, 'HTML enthält ausführbare Kachellogik');
const context = vm.createContext({
    document: {
        createElement(tagName) {
            return {tagName, dataset: {}, textContent: '', title: ''};
        }
    }
});
vm.runInContext(script, context);

function pill(room) {
    context.testRoom = room;
    return vm.runInContext('rmPresence(testRoom)', context);
}

let result = pill({presenceCount: 0});
assert.equal(result.textContent, '–', 'Nicht zugeordneter Präsenzmelder liefert Gedankenstrich');

result = pill({presenceCount: 2, activePresenceCount: 1, presenceIncomplete: false});
assert.equal(result.textContent, 'erkannt', 'Ein aktiver Melder genügt für Präsenz');
assert.equal(result.dataset.state, 'presence-on', 'Präsenz wird grün markiert');
assert.equal(result.title, '1 von 2 Meldern melden Präsenz', 'Anzahl der aktiven Melder als Hinweis');

result = pill({presenceCount: 2, activePresenceCount: 0, presenceIncomplete: false});
assert.equal(result.textContent, 'keine erkannt', 'Zwei negative Melder bedeuten keine erkannte Präsenz');
assert.equal(result.dataset.state, 'presence-off', 'Keine Präsenz erhält neutralen Zustand');

result = pill({presenceCount: 2, activePresenceCount: 0, presenceIncomplete: true});
assert.equal(result.textContent, 'unbekannt', 'Fehlender Melder darf nicht als keine Präsenz erscheinen');

result = pill({presenceCount: 2, activePresenceCount: 1, presenceIncomplete: true});
assert.equal(result.textContent, 'erkannt *', 'Teilweise fehlende Präsenzwerte werden markiert');
assert.match(result.title, /Melderstatus fehlt/, 'Tooltip kennzeichnet unvollständige Meldungen');

context.testData = {columns: {presence: true}};
const columns = Array.from(vm.runInContext('rmVisibleColumns(testData).map(column => column.key)', context));
assert.deepEqual(columns, ['name', 'presence'], 'Nur die Präsenzspalte erscheint ohne andere Raumwerte');
assert.match(html, /presenceRoomsConfigured/, 'Präsenzkennzahl erscheint nur bei vorhandener Zuordnung');
assert.match(html, /roomsWithPresence/, 'Die Anzahl präsenzmeldender Räume wird angezeigt');

console.log('OK: Präsenzformular, Mehrfachmelder und Kachelanzeige geprüft.');
