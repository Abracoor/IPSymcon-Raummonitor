// Test der Symcon-Farbschema-Erkennung ohne Browser oder produktive Instanz.
// Ausführung: node tests/theme_test.js
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const html = fs.readFileSync(path.join(__dirname, '../Raummonitor/module.html'), 'utf8');
const script = html.split('<script>')[1]?.split('</script>')[0];
assert.ok(script, 'Kachel enthält ausführbares JavaScript');

const state = new Set();
const root = {
    classList: {
        toggle(name, enabled) {
            if (enabled) state.add(name);
            else state.delete(name);
        }
    }
};
let textColor = 'rgb(41, 51, 66)';
const context = vm.createContext({
    document: {getElementById: id => id === 'rm-root' ? root : null},
    getComputedStyle: () => ({color: textColor})
});
vm.runInContext(script, context);

// Die CSS-Grundfarben gelten ohne rm-dark direkt für das helle Design.
assert.match(html, /--rm-green:\s*#207c4c;/);
assert.match(html, /\.rm-root\.rm-dark\s*\{/);
assert.match(html, /--rm-green:\s*color-mix\(in srgb, #207c4c 45%, var\(--rm-text\)\)/);

vm.runInContext('rmSyncTheme()', context);
assert.ok(!state.has('rm-dark'), 'Dunkle Textfarbe aktiviert die helle Palette');

textColor = 'rgb(241, 241, 241)';
vm.runInContext('rmSyncTheme()', context);
assert.ok(state.has('rm-dark'), 'Helle Textfarbe aktiviert die dunkle Palette');

textColor = 'rgb(41, 51, 66)';
vm.runInContext('rmSyncTheme()', context);
assert.ok(!state.has('rm-dark'), 'Zurückwechseln auf helles Design funktioniert');

console.log('OK: Getrennte Farbpaletten und Symcon-Theme-Erkennung funktionieren.');
