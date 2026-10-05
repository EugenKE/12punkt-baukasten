<?php
/**
 * Übersetzungen des Plugins (Quellsprache Deutsch, Textdomain 12punkt-baukasten).
 *
 *   npm run i18n:pot    languages/12punkt-baukasten.pot neu erzeugen und in die .po-Dateien einmischen (msgmerge)
 *   npm run i18n:build  .po → .mo (PHP, ACF, block.json) und .json (JS, wp_set_script_translations)
 *
 * Quellen der Texte:
 * - PHP/JS: __(), _x(), _n() … (xgettext)
 * - acf-json/*.json: Titel, Beschriftungen, Anleitungen, Auswahlen, Meldungen, Platzhalter, Button-Texte
 * - blocks/*\/block.json: Titel, Beschreibung, Stichwörter (Kontexte wie WordPress: block title/description/keyword)
 * - Labels aus Konstanten (includes/config.php, ZPT_BUTTON_SIZES), die zur Laufzeit per __($label) übersetzt werden
 *
 * Neue Sprache: languages/12punkt-baukasten-{locale}.po anlegen (Kopf wie bei en_US), dann i18n:pot und i18n:build.
 * Benötigt GNU gettext (xgettext, msgmerge, msgcat, msgfmt).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}

const ZPT_I18N_DOMAIN = '12punkt-baukasten';

$root = dirname(__DIR__);
$lang = $root . '/languages';
$pot  = $lang . '/' . ZPT_I18N_DOMAIN . '.pot';

$cmd = $argv[1] ?? '';
chdir($root);

if ($cmd === 'pot') {
    zpt_i18n_pot($root, $pot);
    foreach (glob($lang . '/' . ZPT_I18N_DOMAIN . '-*.po') as $po) {
        zpt_i18n_run(['msgmerge', '--quiet', '--update', '--backup=none', '--no-fuzzy-matching', '--width=120', $po, $pot]);
        echo 'aktualisiert: ' . basename($po) . "\n";
    }
} elseif ($cmd === 'build') {
    zpt_i18n_build($root, $lang);
} else {
    fwrite(STDERR, "Aufruf: php bin/build-i18n.php pot|build\n");
    exit(1);
}

// ----------------------------------------------------------------- POT

function zpt_i18n_pot(string $root, string $pot): void {
    $tmp = sys_get_temp_dir() . '/zpt-i18n-' . getmypid();
    @mkdir($tmp);

    $php = zpt_i18n_files(['12punkt-baukasten.php', 'includes', 'blocks', 'templates'], 'php');
    $js  = zpt_i18n_files(['assets/js'], 'js');

    $common = ['--from-code=UTF-8', '--add-comments=translators:', '--sort-by-file', '--no-wrap', '--package-name=12punkt Baukasten'];
    zpt_i18n_run(array_merge(['xgettext', '--language=PHP', '-o', "$tmp/php.pot",
        '--keyword=__', '--keyword=_e', '--keyword=_x:1,2c', '--keyword=_ex:1,2c', '--keyword=_n:1,2', '--keyword=_nx:1,2,4c',
        '--keyword=esc_html__', '--keyword=esc_html_e', '--keyword=esc_html_x:1,2c',
        '--keyword=esc_attr__', '--keyword=esc_attr_e', '--keyword=esc_attr_x:1,2c', '--keyword=_n_noop:1,2'], $common, $php));
    zpt_i18n_run(array_merge(['xgettext', '--language=JavaScript', '-o', "$tmp/js.pot",
        '--keyword=__', '--keyword=_x:1,2c', '--keyword=_n:1,2', '--keyword=_nx:1,2,4c'], $common, $js));

    // Texte aus JSON-Dateien und Konstanten
    $entries = [];
    foreach (glob($root . '/acf-json/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        zpt_i18n_acf($data, $entries, 'acf-json/' . basename($file));
    }
    foreach (glob($root . '/blocks/*/block.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        $ref  = 'blocks/' . basename(dirname($file)) . '/block.json';
        zpt_i18n_add($entries, $data['title'] ?? '', $ref, 'block title');
        zpt_i18n_add($entries, $data['description'] ?? '', $ref, 'block description');
        foreach ($data['keywords'] ?? [] as $keyword) {
            zpt_i18n_add($entries, $keyword, $ref, 'block keyword');
        }
    }
    foreach (zpt_i18n_constants($root) as [$text, $ref]) {
        zpt_i18n_add($entries, $text, $ref);
    }
    file_put_contents("$tmp/json.pot", zpt_i18n_pot_header() . zpt_i18n_pot_entries($entries));

    zpt_i18n_run(['msgcat', '--use-first', '--no-wrap', '--sort-by-file', '-o', "$tmp/all.pot", "$tmp/php.pot", "$tmp/js.pot", "$tmp/json.pot"]);
    // Kopf vereinheitlichen (xgettext setzt CHARSET-Platzhalter)
    $all = file_get_contents("$tmp/all.pot");
    $all = preg_replace('/\A.*?\n\n/s', zpt_i18n_pot_header(), $all, 1);
    file_put_contents($pot, $all);
    array_map('unlink', glob("$tmp/*"));
    rmdir($tmp);

    preg_match_all('/^msgid "(?!")/m', $all, $m);
    echo 'POT: ' . count($m[0]) . " Texte → languages/" . basename($pot) . "\n";
}

function zpt_i18n_files(array $paths, string $ext): array {
    $files = [];
    foreach ($paths as $path) {
        if (is_file($path)) {
            $files[] = $path;
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() === $ext) {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
    }
    sort($files);
    return $files;
}

// Feldgruppe/Feld rekursiv: dieselben Schlüssel, die ACF übersetzt (+ placeholder/prepend/append, siehe includes/i18n.php)
function zpt_i18n_acf(array $data, array &$entries, string $ref): void {
    foreach (['title', 'label', 'instructions', 'message', 'button_label', 'ui_on_text', 'ui_off_text', 'placeholder', 'prepend', 'append'] as $key) {
        if (isset($data[$key]) && is_string($data[$key])) {
            zpt_i18n_add($entries, $data[$key], $ref);
        }
    }
    if (isset($data['choices']) && is_array($data['choices'])) {
        foreach ($data['choices'] as $choice) {
            if (is_string($choice)) {
                zpt_i18n_add($entries, $choice, $ref);
            }
        }
    }
    foreach (['fields', 'sub_fields', 'layouts'] as $key) {
        foreach ($data[$key] ?? [] as $child) {
            if (is_array($child)) {
                zpt_i18n_acf($child, $entries, $ref);
            }
        }
    }
}

// Labels aus Konstanten, die zur Laufzeit mit __($label) übersetzt werden
function zpt_i18n_constants(string $root): array {
    define('ABSPATH', $root . '/');
    require $root . '/includes/config.php';

    $out = [];
    $ref = 'includes/config.php';
    foreach (ZPT_FONT_ROLES as $role) {
        $out[] = [$role['label'], $ref];
    }
    foreach ([ZPT_ELEMENT_GROUPS, ZPT_ELEMENT_OPTIONS, ZPT_LIST_STYLES] as $list) {
        foreach ($list as $label) {
            $out[] = [$label, $ref];
        }
    }
    foreach (ZPT_ELEMENT_SIDES as [, $label]) {
        $out[] = [$label, $ref];
    }
    foreach (ZPT_ELEMENTS as $element) {
        $out[] = [$element['label'], $ref];
    }

    // ZPT_BUTTON_SIZES steht in buttons.php (mit WordPress-Aufrufen) → nur den Block auslesen
    $src = file_get_contents($root . '/includes/buttons.php');
    if (preg_match('/const ZPT_BUTTON_SIZES = \[(.*?)\n\];/s', $src, $m)) {
        preg_match_all("/'label' => '([^']+)'/", $m[1], $labels);
        foreach ($labels[1] as $label) {
            $out[] = [$label, 'includes/buttons.php'];
        }
    }
    return $out;
}

function zpt_i18n_add(array &$entries, string $text, string $ref, string $context = ''): void {
    if (trim($text) === '' || !preg_match('/\p{L}/u', $text)) {
        return; // leer oder ohne Buchstaben (z. B. „–“, Zahlen)
    }
    $key = $context . "\4" . $text;
    $entries[$key] ??= ['context' => $context, 'text' => $text, 'refs' => []];
    $entries[$key]['refs'][$ref] = true;
}

function zpt_i18n_pot_header(): string {
    return "msgid \"\"\nmsgstr \"\"\n"
        . "\"Project-Id-Version: 12punkt Baukasten\\n\"\n"
        . "\"MIME-Version: 1.0\\n\"\n"
        . "\"Content-Type: text/plain; charset=UTF-8\\n\"\n"
        . "\"Content-Transfer-Encoding: 8bit\\n\"\n"
        . "\"Language: de_DE\\n\"\n"
        . "\"X-Domain: " . ZPT_I18N_DOMAIN . "\\n\"\n\n";
}

function zpt_i18n_pot_entries(array $entries): string {
    $out = '';
    foreach ($entries as $e) {
        $out .= '#: ' . implode(' ', array_keys($e['refs'])) . "\n";
        if ($e['context'] !== '') {
            $out .= 'msgctxt ' . zpt_i18n_quote($e['context']) . "\n";
        }
        $out .= 'msgid ' . zpt_i18n_quote($e['text']) . "\nmsgstr \"\"\n\n";
    }
    return $out;
}

function zpt_i18n_quote(string $s): string {
    return '"' . strtr($s, ['\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\t" => '\\t', "\r" => '']) . '"';
}

// ----------------------------------------------------------------- BUILD

function zpt_i18n_build(string $root, string $lang): void {
    foreach (glob($lang . '/' . ZPT_I18N_DOMAIN . '-*.po') as $po) {
        $locale = substr(basename($po, '.po'), strlen(ZPT_I18N_DOMAIN) + 1);
        $mo     = substr($po, 0, -3) . '.mo';
        zpt_i18n_run(['msgfmt', '--check-format', '-o', $mo, $po]);

        // JS: je Skript eine JSON-Datei mit den Texten, die in diesem Skript vorkommen
        [$header, $items] = zpt_i18n_parse_po($po);
        $done = 0;
        foreach (glob($lang . '/' . ZPT_I18N_DOMAIN . "-$locale-*.json") as $old) {
            unlink($old);
        }
        $by_file = [];
        foreach ($items as $item) {
            foreach ($item['refs'] as $ref) {
                if (str_starts_with($ref, 'assets/js/') && $item['msgstr'] && implode('', $item['msgstr']) !== '' && !$item['fuzzy']) {
                    $key = ($item['msgctxt'] !== null ? $item['msgctxt'] . "\4" : '') . $item['msgid'];
                    $by_file[$ref][$key] = $item['msgstr'];
                }
            }
        }
        foreach ($by_file as $file => $messages) {
            $messages = ['' => ['domain' => 'messages', 'lang' => $locale, 'plural-forms' => $header['Plural-Forms'] ?? 'nplurals=2; plural=(n != 1);']] + $messages;
            $json = ['translation-revision-date' => $header['PO-Revision-Date'] ?? '', 'generator' => 'zpt build-i18n', 'domain' => 'messages',
                'locale_data' => ['messages' => $messages]];
            // Dateiname wie load_script_textdomain(): md5 des Pfads relativ zum Plugin-Ordner
            file_put_contents($lang . '/' . ZPT_I18N_DOMAIN . "-$locale-" . md5($file) . '.json',
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $done++;
        }

        $total = count($items);
        $open  = count(array_filter($items, fn($i) => $i['fuzzy'] || implode('', $i['msgstr']) === ''));
        echo "$locale: " . ($total - $open) . "/$total übersetzt" . ($open ? " ($open offen)" : '') . ", $done JS-Dateien\n";
    }
}

/** Minimaler PO-Parser: [Kopf-Felder, Einträge mit msgctxt/msgid/msgstr[]/refs/fuzzy] */
function zpt_i18n_parse_po(string $file): array {
    $items  = [];
    $header = [];
    $blocks = preg_split('/\n\s*\n/', str_replace("\r", '', file_get_contents($file)));
    foreach ($blocks as $block) {
        $item = ['msgctxt' => null, 'msgid' => null, 'msgstr' => [], 'refs' => [], 'fuzzy' => false, 'obsolete' => false];
        $cur  = null;
        foreach (explode("\n", trim($block)) as $line) {
            if (str_starts_with($line, '#~')) {
                $item['obsolete'] = true;
                continue;
            }
            if (str_starts_with($line, '#:')) {
                foreach (preg_split('/\s+/', trim(substr($line, 2))) as $ref) {
                    $item['refs'][] = preg_replace('/:\d+$/', '', $ref);
                }
                continue;
            }
            if (str_starts_with($line, '#,')) {
                $item['fuzzy'] = $item['fuzzy'] || str_contains($line, 'fuzzy');
                continue;
            }
            if (str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^(msgctxt|msgid_plural|msgid|msgstr(?:\[(\d+)\])?)\s+(".*")$/', $line, $m)) {
                $cur = $m[1] === 'msgstr' ? 'msgstr[0]' : $m[1];
                $val = zpt_i18n_unquote($m[3]);
            } elseif (preg_match('/^(".*")$/', $line, $m) && $cur) {
                $val = zpt_i18n_unquote($m[1]);
            } else {
                continue;
            }
            if (str_starts_with($cur, 'msgstr[')) {
                $n = (int) substr($cur, 7, -1);
                $item['msgstr'][$n] = ($item['msgstr'][$n] ?? '') . $val;
            } else {
                $item[$cur] = ($item[$cur] ?? '') . $val;
            }
        }
        if ($item['obsolete'] || $item['msgid'] === null) {
            continue;
        }
        if ($item['msgid'] === '') {
            foreach (explode("\n", $item['msgstr'][0] ?? '') as $h) {
                if (str_contains($h, ':')) {
                    [$k, $v] = explode(':', $h, 2);
                    $header[trim($k)] = trim($v);
                }
            }
            continue;
        }
        ksort($item['msgstr']);
        $items[] = $item;
    }
    return [$header, $items];
}

function zpt_i18n_unquote(string $s): string {
    return stripcslashes(substr($s, 1, -1));
}

function zpt_i18n_run(array $cmd): void {
    $line = implode(' ', array_map('escapeshellarg', $cmd));
    exec($line . ' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, implode("\n", $out) . "\n");
        exit($code);
    }
    if ($out) {
        echo implode("\n", $out) . "\n";
    }
}
