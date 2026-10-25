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
