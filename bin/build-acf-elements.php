<?php
/**
 * Erzeugt aus ZPT_ELEMENTS (includes/config.php):
 * - acf-json/group_zpt_settings_elements.json     (Baukasten → Elemente, Tabs je Gruppe, darin je Element)
 * - acf-json/group_zpt_settings_nav_elements.json (Baukasten → Navigation, Gruppe ZPT_NAV_ELEMENT_GROUP)
 * Nicht im ACF-Backend bearbeiten – Änderungen in config.php, dann:
 *
 *   npm run build:acf
 *
 * Feldnamen (= gespeicherte Werte, options_zpt_element_{element}_…):
 *   role, font_weight, options[], color, color_hover, background, list_style,
 *   size_{bp}, margin_{seite}_{bp}, padding_{seite}_{bp}
 */

if (PHP_SAPI !== 'cli') {
    exit;
}

define('ABSPATH', __DIR__);
require dirname(__DIR__) . '/includes/config.php';

// ----------------------------------------------------------------- FELD-BAUSTEINE

// Nur abweichende Einstellungen – den Rest ergänzt ACF (acf_validate_field), hält die Datei klein.
function zpt_build_field(string $key, string $name, string $label, string $type, array $extra = []): array {
    return array_merge(['key' => $key, 'label' => $label, 'name' => $name, 'type' => $type], $extra);
}

function zpt_build_select(string $key, string $name, string $label, array $choices, string $width, string $class = ''): array {
    return zpt_build_field($key, $name, $label, 'select', [
        'wrapper' => ['width' => $width, 'class' => $class, 'id' => ''],
        'choices' => $choices,
    ]);
}

function zpt_build_color(string $key, string $name, string $label): array {
    return zpt_build_field($key, $name, $label, 'text', [
        'wrapper'     => ['width' => '25', 'class' => 'zpt-color-field zpt-color-tokens', 'id' => ''],
        'placeholder' => 'wie umgebend',
    ]);
}

/**
 * Eine Matrix-Zeile: Gruppe (Tabellen-Layout) mit einem Feld je Breakpoint.
 * Leer ab SM = wie kleiner, d. h. nur XS ausgefüllt gilt für alle Breiten.
 * Darstellung: elements.php (CSS), admin-elements.js.
 */
function zpt_build_bp_row(string $key, string $name, string $label, ?array $choices, bool $head, int $min = 0, ?int $max = null, ?int $step = null): array {
    $sub = [];
    foreach (ZPT_ELEMENT_BREAKPOINTS as $bp => $bp_label) {
        $empty = $bp === 'xs' ? '–' : 'wie kleiner';
        // Auswahl (Stufen) oder Zahlenfeld in der 5px-Skala (Abstände)
        $sub[] = $choices !== null
            ? zpt_build_select("{$key}_{$bp}", $bp, $bp_label, ['' => $empty] + $choices, '16.66')
            : zpt_build_field("{$key}_{$bp}", $bp, $bp_label, 'number', [
                'wrapper'     => ['width' => '16.66', 'class' => '', 'id' => ''],
                'placeholder' => $empty,
                'min'         => $min,
                'max'         => $max ?? ZPT_SPACER_STEP * ZPT_SPACER_COUNT,
                'step'        => $step ?? ZPT_SPACER_STEP,
            ]);
    }
    return zpt_build_field($key, $name, $label, 'group', [
        'wrapper'    => ['width' => '', 'class' => 'zpt-bp-row' . ($head ? ' zpt-bp-head' : ''), 'id' => ''],
        'layout'     => 'table',
        'sub_fields' => $sub,
    ]);
}

// ----------------------------------------------------------------- AUSWAHLEN

$roles = ['' => 'wie umgebend'];
foreach (ZPT_FONT_ROLES as $role => $def) {
    $roles[$role] = $def['label'];
}

$weights = ['' => 'Standard'];
foreach (ZPT_FONT_WEIGHTS as $weight => $label) {
    $weights[(string) $weight] = $weight . ' ' . $label;
}

// Stufen der Vorlage „12punkt“ – zur Laufzeit ersetzt durch die Skala (typography.php, acf/load_field)
// Stufen der Vorlage „12punkt“; zur Laufzeit ersetzt durch die aktuelle Skala (elements.php, acf/load_field)
$steps = [];
foreach (ZPT_TYPE_PRESETS['12punkt']['steps'] as [$step]) {
    $steps[str_replace('-', '_', $step)] = $step;
}

$lists = ['' => 'Standard'] + ZPT_LIST_STYLES;

/**
 * Felder in Tabs: Logo-Größe (nur navbar_brand), Schrift (Rolle, Gewicht, Liste, Optionen, Größe), Farben, Margin, Padding
 * (je 4 Zeilen oben/rechts/unten/links mit eigener Kopfzeile XS … XXL).
 * Leere Tabs fallen weg; bleibt nur einer, ohne Tabs.
 */
function zpt_build_element_tabs(string $key, array $top, array $rows): array {
    $tabs = ['logo' => ['Logo-Größe', [], []], 'font' => ['Schrift', [], []], 'colors' => ['Farben', [], []], 'margin' => ['Margin', [], []], 'padding' => ['Padding', [], []]];
    foreach ($top as $field) {
        $tab = $field['name'] === 'logo_size_mode' ? 'logo' : (in_array($field['name'], ['color', 'color_hover', 'background'], true) ? 'colors' : 'font');
        $tabs[$tab][1][] = $field;
    }
    foreach ($rows as $row) {
        $tabs[$row['name'] === 'size' ? 'font' : ($row['name'] === 'logo_size' ? 'logo' : strtok($row['name'], '_'))][2][] = $row;
    }
    $tabs = array_filter($tabs, fn($tab) => $tab[1] || $tab[2]);

    $sub = [];
    foreach ($tabs as $tab => [$label, $tab_top, $tab_rows]) {
        if (count($tabs) > 1) {
            $sub[] = zpt_build_field("{$key}_tab_{$tab}", '', $label, 'tab', ['placement' => 'top', 'endpoint' => 0]);
        }
        foreach ($tab_top as &$field) {
            $field['wrapper']['width'] = $tab === 'logo' ? '' : (string) floor(100 / max(3, count($tab_top)));
        }
        unset($field);
        if ($tab_rows) {
            $tab_rows[0]['wrapper']['class'] .= ' zpt-bp-head';
        }
        array_push($sub, ...$tab_top, ...$tab_rows);
    }
    return $sub;
}

/**
 * Akkordeon + Gruppe eines Elements (Felder je nach features), in Tabs Schrift / Farben / Margin / Padding.
 */
function zpt_build_element(string $el, array $def): array {
    global $roles, $weights, $steps, $lists;
    $out = [];
    $f   = $def['features'];
    $key = "field_zpt_element_{$el}";
    $sub = [];

    // --- Logo-Größe (Logo in der Navigationsleiste): Art + Matrix in px, die bei „Höhe der Navigation“ ausgeblendet ist
    if (in_array('logo', $f, true)) {
        $sub[] = zpt_build_field("{$key}_logo_size_mode", 'logo_size_mode', 'Logo', 'button_group', [
            'instructions'  => 'Feste Höhe oder Breite: die andere Seite ergibt sich aus dem Bild. Höhe der Navigation: so hoch wie die Menüpunkte (inkl. Padding), mobil wie der Menü-Button – das Logo wächst und schrumpft mit der Navigation. Feste Größe: alles leer = 40 px, ab LG 50 px.',
            'choices'       => ['height' => 'Feste Höhe', 'width' => 'Feste Breite', 'nav' => 'Höhe der Navigation'],
            'default_value' => 'height',
            'return_format' => 'value',
            'allow_null'    => 0,
            'layout'        => 'horizontal',
        ]);
    }

    // --- Zeile oben: Schrift, Farbe, Liste
    if (in_array('type', $f, true)) {
        $sub[] = zpt_build_select("{$key}_role", 'role', 'Schrift', $roles, '25');
        $sub[] = zpt_build_select("{$key}_font_weight", 'font_weight', 'Gewicht', $weights, '25');
    }
    if (in_array('list', $f, true)) {
        $sub[] = zpt_build_select("{$key}_list_style", 'list_style', 'Aufzählungszeichen', $lists, '25');
    }
    if (in_array('color', $f, true)) {
        $sub[] = zpt_build_color("{$key}_color", 'color', $el === 'hr' ? 'Linienfarbe' : 'Farbe');
    }
    if (in_array('link', $f, true)) {
        $sub[] = zpt_build_color("{$key}_color_hover", 'color_hover', 'Farbe Hover');
    }
    if (in_array('background', $f, true)) {
        $sub[] = zpt_build_color("{$key}_background", 'background', 'Hintergrund');
    }
    $options = [];
    if (in_array('type', $f, true)) {
        $options['uppercase'] = ZPT_ELEMENT_OPTIONS['uppercase'];
    }
    if (in_array('link', $f, true)) {
        $options['no_underline'] = ZPT_ELEMENT_OPTIONS['no_underline'];
    }
    if ($options) {
        $sub[] = zpt_build_field("{$key}_options", 'options', 'Optionen', 'checkbox', [
            'wrapper'       => ['width' => '25', 'class' => '', 'id' => ''],
            'choices'       => $options,
            'default_value' => [],
            'return_format' => 'value',
            'allow_custom'  => 0,
            'save_custom'   => 0,
            'layout'        => 'vertical',
            'toggle'        => 0,
        ]);
    }

    // --- Matrix: Größe, Margin, Padding je Breakpoint (Kopfzeile mit XS … XXL setzt zpt_build_element_tabs())
    $rows = [];
    if (in_array('logo', $f, true)) {
        // Höhe bzw. Breite in px; alles leer = ZPT_LOGO_SIZE_DEFAULT (40, ab LG 50)
        $rows[] = zpt_build_bp_row("{$key}_logo_size", 'logo_size', 'Höhe / Breite (px)', null, false, 1, 1000, 1)
            + ['conditional_logic' => [[['field' => "{$key}_logo_size_mode", 'operator' => '!=', 'value' => 'nav']]]];
    }
    if (in_array('size', $f, true)) {
        $rows[] = zpt_build_bp_row("{$key}_size", 'size', 'Größe', $steps, false);
    }
    // Margin auch negativ (wie .mt-n20)
    $max = ZPT_SPACER_STEP * ZPT_SPACER_COUNT;
    foreach (['margin' => -$max, 'padding' => 0] as $prop => $min) {
        if (!in_array($prop, $f, true)) {
            continue;
        }
        foreach (ZPT_ELEMENT_SIDES as $side => [, $side_label]) {
            // „Margin“/„Padding“ steht am Tab – Zeile nur mit der Seite
            $rows[] = zpt_build_bp_row("{$key}_{$prop}_{$side}", "{$prop}_{$side}", ucfirst($side_label), null, false, $min);
        }
    }

    $sub = zpt_build_element_tabs($key, $sub, $rows);

    $out[] = zpt_build_field("{$key}_accordion", '', $def['label'], 'accordion', [
        'wrapper'      => ['width' => '', 'class' => 'zpt-element-accordion', 'id' => ''],
        'open'         => 0,
        'multi_expand' => 0,
        'endpoint'     => 0,
    ]);
    $out[] = zpt_build_field($key, "zpt_element_{$el}", '', 'group', [
        'wrapper'    => ['width' => '', 'class' => 'zpt-element', 'id' => ''],
        'layout'     => 'block',
        'sub_fields' => $sub,
    ]);

    return $out;
}

// ----------------------------------------------------------------- FELDER

$fields = [[
    'key'       => 'field_zpt_elements_intro',
    'label'     => '',
    'name'      => '',
    'type'      => 'message',
    'message'   => 'Einstellungen je HTML-Element. Leer = wie umgebend bzw. wie der nächstkleinere Breakpoint (XS = alle Breiten, SM … XXL = ab diesem Breakpoint, siehe Baukasten → Breakpoints). Abstände in px (5er-Schritte, Margin auch negativ). Klassen am Element (<code>.mt-20</code>, <code>.font-size-xl</code>, <code>.fw-700</code> …) haben Vorrang.',
    'new_lines' => '',
    'esc_html'  => 0,
]];

foreach (ZPT_ELEMENT_GROUPS as $group => $group_label) {
    $fields[] = zpt_build_field("field_zpt_elements_tab_{$group}", '', $group_label, 'tab', [
        'placement' => 'top',
        'endpoint'  => 0,
    ]);

    foreach (ZPT_ELEMENTS as $el => $def) {
        if ($def['group'] === $group) {
            array_push($fields, ...zpt_build_element($el, $def));
        }
    }

    // Akkordeon beenden, sonst landet der nächste Tab im letzten Akkordeon
    $fields[] = zpt_build_field("field_zpt_elements_end_{$group}", '', '', 'accordion', ['endpoint' => 1]);
}

// ----------------------------------------------------------------- SCHREIBEN

$group = [
    'key'                   => 'group_zpt_settings_elements',
    'title'                 => 'Baukasten: Elemente',
    'display_title'         => 'Elemente',
    'fields'                => $fields,
    'location'              => [[['param' => 'options_page', 'operator' => '==', 'value' => 'zpt-elements']]],
    'menu_order'            => 0,
    'position'              => 'normal',
    'style'                 => 'default',
    'label_placement'       => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen'        => '',
    'active'                => true,
    'description'           => 'Erzeugt von bin/build-acf-elements.php aus ZPT_ELEMENTS – nicht im Backend bearbeiten.',
    'show_in_rest'          => 0,
    'modified'              => time(),
];

$file = dirname(__DIR__) . '/acf-json/group_zpt_settings_elements.json';
// kompakt (generiert, ~650 Felder): eingerückt wäre die Datei dreimal so groß
file_put_contents($file, json_encode($group, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo 'geschrieben: ' . basename($file) . ' (' . count($fields) . " Felder oben)\n";

// --- Navigation: Elemente der Gruppe ZPT_NAV_ELEMENT_GROUP als eigene Box auf Baukasten → Navigation
$nav_fields = [[
    'key'       => 'field_zpt_nav_elements_intro',
    'label'     => '',
    'name'      => '',
    'type'      => 'message',
    'message'   => 'Schrift, Farben und Größen der Navigationsleiste. Leer = wie umgebend bzw. wie Bootstrap; Größe und Abstände je Breakpoint wie unter Baukasten → Elemente (leer = wie der nächstkleinere).',
    'new_lines' => '',
    'esc_html'  => 0,
]];
foreach (ZPT_ELEMENTS as $el => $def) {
    if ($def['group'] === ZPT_NAV_ELEMENT_GROUP) {
        array_push($nav_fields, ...zpt_build_element($el, $def));
    }
}
$nav_fields[] = zpt_build_field('field_zpt_nav_elements_end', '', '', 'accordion', ['endpoint' => 1]);

$nav_group = array_merge($group, [
    'key'           => 'group_zpt_settings_nav_elements',
    'title'         => 'Baukasten: Navigation – Schrift, Farben, Größen',
    'display_title' => 'Schrift, Farben, Größen',
    'fields'        => $nav_fields,
    'location'      => [[['param' => 'options_page', 'operator' => '==', 'value' => 'zpt-navigation']]],
    'menu_order'    => 2,
]);
$file = dirname(__DIR__) . '/acf-json/group_zpt_settings_nav_elements.json';
file_put_contents($file, json_encode($nav_group, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo 'geschrieben: ' . basename($file) . ' (' . count($nav_fields) . " Felder oben)\n";
