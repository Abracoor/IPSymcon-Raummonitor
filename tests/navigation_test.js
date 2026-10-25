// Prüft die optionale Raum-Navigation ohne laufenden Symcon-Server.
// Ausführung: node tests/navigation_test.js
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const html = fs.readFileSync(path.join(__dirname, '../Raummonitor/module.html'), 'utf8');
const script = html.split('<script>')[1]?.split('</script>')[0];
assert.ok(script, 'HTML enthält die Kachel-Logik');

const form = JSON.parse(fs.readFileSync(path.join(__dirname, '../Raummonitor/form.json'), 'utf8'));
const roomList = form.elements.find(element => element.name === 'Rooms');
const nav = roomList.form.find(element => element.caption === 'Navigation zur Detailseite (optional)');
const picker = nav.items.find(element => element.name === 'DetailCategoryID');
assert.equal(picker.value, 1, 'Kategorieauswahl beginnt mit Kein(e) statt dem Symcon-Wurzelobjekt');
assert.equal(roomList.columns.find(column => column.name === 'DetailCategoryID').add, 1,
    'Auch neue Raumzeilen übernehmen den inaktiven Wert 1');
assert.ok(nav.items.some(element => element.type === 'Label' && element.caption.includes('Kein(e)')),
    'Raumdialog erklärt den inaktiven Ausgangszustand');

const opened = [];
const context = vm.createContext({
    document: {
        createElement(tagName) {
            return {
                tagName,
                listeners: {},
                attributes: {},
                setAttribute(key, value) { this.attributes[key] = value; },
                addEventListener(name, fn) { this.listeners[name] = fn; }
            };
        }
    },
    openObject: categoryID => opened.push(categoryID)
});
vm.runInContext(script, context);

let room = vm.runInContext("rmRoomName({name: 'Bad'})", context);
assert.equal(room, 'Bad', 'Alte Konfiguration ohne Detailseite bleibt einfacher Text');

room = vm.runInContext("rmRoomName({name: 'Bad', detailCategoryID: 0})", context);
assert.equal(room, 'Bad', 'Zielkategorie 0 deaktiviert den Klick');

room = vm.runInContext("rmRoomName({name: 'Bad', detailCategoryID: 1})", context);
assert.equal(room, 'Bad', 'Symcon-Platzhalter 1 deaktiviert den Klick');

room = vm.runInContext("rmRoomName({name: 'Arbeitszimmer', detailCategoryID: 12353})", context);
assert.equal(room.tagName, 'button', 'Zielkategorie erzeugt einen zugänglichen Button');
assert.equal(room.type, 'button');
assert.equal(room.textContent, 'Arbeitszimmer');
assert.equal(room.attributes['aria-label'], 'Detailseite öffnen: Arbeitszimmer');
assert.equal(opened.length, 0, 'Eine Darstellung allein startet keine Navigation');
room.listeners.click();
assert.deepEqual(opened, [12353], 'Ein Klick nutzt openObject mit der konfigurierten Kategorie');

room = vm.runInContext("rmRoomName({name: '<script>test</script>', detailCategoryID: 12354})", context);
assert.equal(room.textContent, '<script>test</script>', 'Benutzertext bleibt textContent statt HTML');

room = vm.runInContext("rmRoomName({name: 'Ungültiges Ziel', detailCategoryID: '12353'})", context);
assert.equal(room, 'Ungültiges Ziel', 'Ungültige ID-Typen erzeugen keinen Button');

console.log('OK: Optionale Navigation nur mit gültiger Zielkategorie und per Klick.');
