/**
 * Iconfonts aus node_modules nach assets/vendor/ kopieren und Namenslisten für die Icon-Auswahl
 * erzeugen (assets/icons/lists/*.json). Aufruf: npm run build:icons
 *
 * - Bootstrap Icons → assets/vendor/bootstrap-icons/   (bootstrap-icons.min.css + fonts/)
 * - Font Awesome    → assets/vendor/fontawesome/       (css/ + webfonts/, nur Free: solid, regular, brands)
 * - Material Icons  → assets/vendor/material-icons/    (filled, outlined, round, sharp, two-tone)
 *
 * Schriften nur als woff2 (die CSS nennt auch woff – reicht allen aktuellen Browsern).
 */

const fs   = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const nm   = (...p) => path.join(root, 'node_modules', ...p);
const out  = (...p) => path.join(root, 'assets', ...p);

function copy(from, to) {
    fs.mkdirSync(path.dirname(to), { recursive: true });
    fs.copyFileSync(from, to);
}

function writeJson(file, data) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, JSON.stringify(data));
    console.log(path.relative(root, file), Object.keys(data).length, 'Icons');
}

// ----------------------------------------------------------------- Bootstrap Icons
fs.rmSync(out('vendor/bootstrap-icons'), { recursive: true, force: true });
copy(nm('bootstrap-icons/font/bootstrap-icons.min.css'), out('vendor/bootstrap-icons/bootstrap-icons.min.css'));
copy(nm('bootstrap-icons/font/fonts/bootstrap-icons.woff2'), out('vendor/bootstrap-icons/fonts/bootstrap-icons.woff2'));
// Liste: {name: ''}
writeJson(out('icons/lists/bootstrap.json'),
    Object.fromEntries(Object.keys(require(nm('bootstrap-icons/font/bootstrap-icons.json'))).map((n) => [n, ''])));

// ----------------------------------------------------------------- Font Awesome (Free)
const fa = nm('@fortawesome/fontawesome-free');
fs.rmSync(out('vendor/fontawesome'), { recursive: true, force: true });
for (const f of ['fontawesome', 'solid', 'regular', 'brands']) {
    copy(path.join(fa, 'css', f + '.min.css'), out('vendor/fontawesome/css', f + '.min.css'));
}
for (const f of ['fa-solid-900.woff2', 'fa-regular-400.woff2', 'fa-brands-400.woff2']) {
    copy(path.join(fa, 'webfonts', f), out('vendor/fontawesome/webfonts', f));
}
// Liste: {name: 'solid regular|Suchbegriffe'}
const faMeta = require(path.join(fa, 'metadata/icon-families.json'));
const faList = {};
for (const [name, icon] of Object.entries(faMeta)) {
    const styles = (icon.familyStylesByLicense?.free || [])
        .filter((s) => s.family === 'classic')
        .map((s) => s.style);
    if (styles.length) {
        const terms = [icon.label, ...(icon.search?.terms || [])].join(' ').toLowerCase();
        faList[name] = styles.join(' ') + '|' + terms;
    }
}
writeJson(out('icons/lists/fontawesome.json'), faList);

// ----------------------------------------------------------------- Material Icons
const mi = nm('material-icons/iconfont');
fs.rmSync(out('vendor/material-icons'), { recursive: true, force: true });
for (const f of ['filled', 'outlined', 'round', 'sharp', 'two-tone']) {
    copy(path.join(mi, f + '.css'), out('vendor/material-icons', f + '.css'));
    const font = f === 'filled' ? 'material-icons' : 'material-icons-' + f;
    copy(path.join(mi, font + '.woff2'), out('vendor/material-icons', font + '.woff2'));
}
writeJson(out('icons/lists/material.json'),
    Object.fromEntries(Object.keys(require(nm('material-icons/_data/versions.json'))).map((n) => [n, ''])));

console.log('fertig');
