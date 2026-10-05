<?php
/**
 * Typografie (Baukasten → Typografie).
 *
 * Box „Schriftschnitte & Maße“: je Repeater-Zeile eine Schrift (Laden: fonts.php) mit Schriftgröße und
 * Zeilenhöhe je Stufe sowie Laufweite je Gewicht. Ausgabe als Klassen:
 *
 *   .ff-{schrift}   setzt font-family und im eigenen Bereich die Variablen
 *                   --theme-font-size-{stufe}, --theme-line-height-{stufe}, --zpt-ls-{gewicht}
 *   .font-size-xl   (scss) liest die Variablen → Größe + Zeilenhöhe der aktuellen Schrift
 *   .fw-700, strong (scss) setzen --zpt-ls auf die Laufweite des Gewichts der aktuellen Schrift
 *
 * Die Rollen (Grundschrift → :root, .font-serif, .font-sans-serif, .font-style-deco, .font-mono)
 * bekommen die Werte der ihnen zugewiesenen Schrift.
 *
 * Einstellungen je HTML-Element (h1, p, ul …): eigene Seite Baukasten → Elemente (elements.php).
 *
 * Box „Schriftgrößen“: Skala der Stufen (Repeater zpt_type_scale: Name, Größe, Zeilenhöhe, Fließtext)
 * mit Vorlagen (ZPT_TYPE_PRESETS). Daraus: --theme-font-size-{stufe} / --theme-line-height-{stufe}
 * auf :root, Klassen .font-size-{stufe} / .font-size-{bp}-{stufe} und die Felder je Schrift.
 *
 * Leere Felder je Schrift: Werte der Skala.
 */

defined('ABSPATH') || exit;

// Stufen, Gewichte, Rollen: includes/config.php

// ----------------------------------------------------------------- SKALA

/**
 * Stufen der Schriftgrößen-Skala in Reihenfolge, Schlüssel = Feldname (copy_l):
 * ['copy_l' => ['name' => 'copy-l', 'size' => '20px', 'line_height' => '1.2em', 'base' => false], …].
 * Ungültig oder leer → gewählte Vorlage (Standard „12punkt“).
 * Rohwerte per get_option: wird in acf/include_fields und acf/load_field gebraucht (kein get_field()).
 */
function zpt_type_steps(bool $fresh = false): array {
    static $steps = null;
    if ($steps !== null && !$fresh) {
        return $steps;
    }

    $steps = [];
    $rows  = (int) get_option('options_zpt_type_scale', 0);
    for ($i = 0; $i < $rows; $i++) {
        $name = zpt_type_step_name((string) get_option("options_zpt_type_scale_{$i}_name", ''));
        $key  = str_replace('-', '_', $name);
        if ($name === '' || isset($steps[$key])) {
            continue;
        }
        $steps[$key] = [
            'name'        => $name,
            'size'        => zpt_css_value(get_option("options_zpt_type_scale_{$i}_size", '')) ?: '1rem',
            'line_height' => zpt_css_value(get_option("options_zpt_type_scale_{$i}_line_height", '')) ?: 'normal',
            'base'        => (bool) get_option("options_zpt_type_scale_{$i}_base", false),
        ];
    }

    // genau eine Fließtext-Stufe, sonst Vorlage
    if (count(array_filter($steps, fn($s) => $s['base'])) !== 1) {
        $preset = (string) get_option('options_zpt_type_preset', '12punkt');
        $steps  = zpt_type_preset_steps(isset(ZPT_TYPE_PRESETS[$preset]) ? $preset : '12punkt');
    }

    return $steps;
}

/**
 * Stufen einer Vorlage im Format von zpt_type_steps().
 */
function zpt_type_preset_steps(string $preset): array {
    $steps = [];
    foreach (ZPT_TYPE_PRESETS[$preset]['steps'] as [$name, $size, $line_height]) {
        $steps[str_replace('-', '_', $name)] = [
            'name'        => $name,
            'size'        => $size,
            'line_height' => $line_height,
            'base'        => $name === ZPT_TYPE_PRESETS[$preset]['base'],
        ];
    }
    return $steps;
}

/**
 * Name einer Stufe für Klassen/Variablen: klein, nur a–z, 0–9 und Bindestrich („Copy L“ → „copy-l“, „Groß“ → „gross“).
 */
function zpt_type_step_name(string $name): string {
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(remove_accents(trim($name)))), '-');
}

/**
 * Schlüssel der Fließtext-Stufe („copy“).
 */
function zpt_type_base(): string {
    foreach (zpt_type_steps() as $key => $step) {
        if ($step['base']) {
            return $key;
        }
    }
    return (string) array_key_first(zpt_type_steps());
}

/**
 * CSS-Name einer Stufe aus ihrem Schlüssel (copy_l → copy-l).
 */
function zpt_type_css(string $key): string {
    return str_replace('_', '-', $key);
}

// ----------------------------------------------------------------- WERTE

/**
 * Familienname für CSS: ohne Anführungszeichen und Zeichen, die aus der Deklaration ausbrechen.
 */
function zpt_css_font_name(string $family): string {
    return trim(str_replace(['"', "'"], '', zpt_css_value($family)));
}

/**
 * Rollen-Zuordnung: ['base' => 'Inter', 'serif' => 'Lora', …] (nur gesetzte).
 */
function zpt_font_roles(): array {
    $roles = [];
    foreach (array_keys(ZPT_FONT_ROLES) as $role) {
        $roles[$role] = zpt_css_font_name((string) get_option("options_zpt_font_families_{$role}", ''));
    }
    return array_filter($roles);
}

/**
 * Rollen mit geladener Schrift: ['base' => 'Inter', …] (Schlüssel aus zpt_loaded_fonts()).
 */
function zpt_css_role_fonts(): array {
    $loaded = array_keys(zpt_loaded_fonts());
    $fonts  = [];
    foreach (zpt_font_roles() as $role => $name) {
        foreach ($loaded as $family) {
            if (strcasecmp(zpt_css_font_name($family), $name) === 0) {
                $fonts[$role] = $family;
                break;
            }
        }
    }
    return $fonts;
}

/**
 * Größen und Zeilenhöhen der Skala („alle Schriften“): ['sizes' => [stufe => wert], 'line_heights' => […]].
 */
function zpt_global_type_values(): array {
    return [
        'sizes'        => array_map(fn($s) => $s['size'], zpt_type_steps()),
        'line_heights' => array_map(fn($s) => $s['line_height'], zpt_type_steps()),
    ];
}

/**
 * Eigene Werte einer geladenen Schrift (Repeater-Zeile):
 * ['sizes' => [stufe => wert], 'line_heights' => […], 'letter_spacing' => [gewicht => wert]].
 */
function zpt_font_type_values(array $font): array {
    static $rows = null;
    $rows ??= (array) (get_field('zpt_fonts', 'option') ?: []);
    $row    = $rows[$font['row']] ?? [];

    $ls = [];
    foreach ((array) ($row['letter_spacing'] ?? []) as $item) {
        if (!empty($item['weight'])) {
            $ls[(string) $item['weight']] = $item['value'] ?? '';
        }
    }

    return [
        'sizes'          => array_filter(array_map('zpt_css_value', (array) ($row['sizes'] ?? []))),
        'line_heights'   => array_filter(array_map('zpt_css_value', (array) ($row['line_heights'] ?? []))),
        'letter_spacing' => array_filter(array_map('zpt_css_value', $ls)),
    ];
}

// ----------------------------------------------------------------- CSS

/**
 * Klassen .ff-{schrift} + Rollen-Selektoren und die Werte der Grundschrift auf :root.
 */
function zpt_font_classes_css(): string {
    $fonts   = zpt_loaded_fonts();
    $roles   = zpt_font_roles();
    $css     = '';

    foreach ($fonts as $family => $font) {
        $name   = zpt_css_font_name($family);
        $own    = zpt_font_type_values($font);
        $roles_of_font = array_keys(array_filter($roles, fn($f) => strcasecmp($f, $name) === 0));
        $generic = 'sans-serif';
        foreach ($roles_of_font as $role) {
            $generic = $role === 'base' ? $generic : ZPT_FONT_ROLES[$role]['generic'];
        }

        // --- Grundschrift: eigene Werte auf :root (überschreiben die globalen)
        if (in_array('base', $roles_of_font, true)) {
            $css .= ':root{' . zpt_font_vars_css($own, [], false) . '}';
        }

        // --- Klasse + weitere Rollen: vollständiger Satz (sonst erben verschachtelte Schriften fremde Werte)
        $selectors = array_merge(
            ['.ff-' . $font['slug']],
            array_map(fn($r) => ZPT_FONT_ROLES[$r]['selector'], array_diff($roles_of_font, ['base']))
        );
        $selector = implode(',', $selectors);

        $css .= $selector . '{' . zpt_font_declarations($family, $generic) . '}';
        // ohne Spezifität: Größen-/Gewichtsklassen und Überschriften gewinnen immer
        $base  = zpt_type_css(zpt_type_base());
        $css  .= ':where(' . $selector . '){font-size:var(--theme-font-size-' . $base . ');line-height:var(--theme-line-height-' . $base . ');--zpt-ls:var(--zpt-ls-400, normal);}';
    }

    return $css;
}

/**
 * font-family + vollständiger Variablen-Satz einer geladenen Schrift (für Klassen, Rollen, Elemente).
 */
function zpt_font_declarations(string $family, string $generic): string {
    $font = zpt_loaded_fonts()[$family] ?? null;
    if (!$font) {
        return '';
    }
    return 'font-family:' . zpt_font_family_value($family, $generic) . ';'
        . zpt_font_vars_css(zpt_font_type_values($font), zpt_global_type_values(), true);
}

/**
 * Variablen-Deklarationen einer Schrift. $complete = alle Stufen/Gewichte setzen (Fallback: Skala, normal).
 */
function zpt_font_vars_css(array $own, array $global, bool $complete): string {
    $css = '';

    foreach (zpt_type_steps() as $step => $defaults) {
        $var = zpt_type_css($step);
        foreach (['sizes' => ['--theme-font-size-', 'size'], 'line_heights' => ['--theme-line-height-', 'line_height']] as $key => [$prefix, $default_key]) {
            $value = $own[$key][$step] ?? ($complete ? ($global[$key][$step] ?? $defaults[$default_key]) : null);
            if ($value !== null) {
                $css .= $prefix . $var . ':' . $value . ';';
            }
        }
    }

    foreach (array_keys(ZPT_FONT_WEIGHTS) as $weight) {
        $value = $own['letter_spacing'][$weight] ?? ($complete ? 'normal' : null);
        if ($value !== null) {
            $css .= '--zpt-ls-' . $weight . ':' . $value . ';';
        }
    }

    return $css;
}

/**
 * Variablen der Skala auf :root: ['--theme-font-size-copy' => '16px', '--theme-line-height-copy' => '1.2em', …].
 */
function zpt_type_scale_vars(): array {
    $vars = [];
    foreach (zpt_type_steps() as $key => $step) {
        $vars['--theme-font-size-' . zpt_type_css($key)]   = $step['size'];
        $vars['--theme-line-height-' . zpt_type_css($key)] = $step['line_height'];
    }
    return $vars;
}

/**
 * Fließtext (body) in der Fließtext-Stufe und Klassen je Stufe:
 * .font-size-{stufe} und responsiv .font-size-{bp}-{stufe} (Größe + Zeilenhöhe der aktuellen Schrift).
 */
function zpt_type_scale_css(): string {
    $base = zpt_type_css(zpt_type_base());
    $css  = 'body{font-size:var(--theme-font-size-' . $base . ');line-height:var(--theme-line-height-' . $base . ');}';

    foreach (['' => 0] + zpt_breakpoint_min() as $bp => $min_width) {
        $rules = '';
        foreach (array_keys(zpt_type_steps()) as $key) {
            $var   = zpt_type_css($key);
            $class = '.font-size-' . ($bp ? $bp . '-' : '') . $var;
            $rules .= $class . ',' . $class . '>p{font-size:var(--theme-font-size-' . $var . ');line-height:var(--theme-line-height-' . $var . ');}';
        }
        $css .= $min_width ? '@media (min-width:' . $min_width . 'px){' . $rules . '}' : $rules;
    }

    return $css;
}

// ----------------------------------------------------------------- LAUFWEITEN-ZEILEN
// Nach dem Speichern: je Schrift eine Laufweiten-Zeile für jedes geladene Gewicht anlegen
// (bestehende Werte bleiben, Reihenfolge nach Gewicht).
add_action('acf/save_post', function ($post_id) {
    if ($post_id !== 'options' || !have_rows('zpt_fonts', 'option')) {
        return;
    }

    $available = zpt_available_fonts();
    $rows      = (array) get_field('zpt_fonts', 'option');
    $changed   = false;

    foreach ($rows as &$row) {
        $font = $available[$row['font'] ?? ''] ?? null;
        if (!$font) {
            continue;
        }

        $weights = [];
        foreach ($font['faces'] as $face) {
            $weights = array_merge($weights, zpt_font_weight_list((string) $face['weight']));
        }

        $ls = [];
        foreach ((array) ($row['letter_spacing'] ?? []) as $item) {
            if (!empty($item['weight'])) {
                $ls[(string) $item['weight']] = (string) ($item['value'] ?? '');
            }
        }
        $before = $ls;
        foreach (array_unique($weights) as $weight) {
            $ls[$weight] ??= '';
        }
        ksort($ls, SORT_NUMERIC);

        if ($ls !== $before || array_keys($ls) !== array_keys($before)) {
            $row['letter_spacing'] = array_map(fn($w, $v) => ['weight' => (string) $w, 'value' => $v], array_keys($ls), $ls);
            $changed = true;
        }
    }
    unset($row);

    if ($changed) {
        update_field('zpt_fonts', $rows, 'option');
    }
}, 20);

// ----------------------------------------------------------------- FELDER AUS DER SKALA
// Größe/Zeilenhöhe je Schrift: ein Feld je Stufe, Platzhalter = Wert der Skala.
add_action('acf/include_fields', function () {
    $steps = zpt_type_steps();
    $width = (string) floor(100 / max(1, count($steps)));
    $order = 0;

    foreach ($steps as $key => $step) {
        foreach (['size' => ['field_zpt_fonts_sizes', 'size'], 'line_height' => ['field_zpt_fonts_line_heights', 'line_height']] as $type => [$parent, $value]) {
            acf_add_local_field([
                'key'           => "field_zpt_fonts_{$type}_{$key}",
                'label'         => $step['name'],
                'name'          => $key,
                'type'          => 'text',
                'parent'        => $parent,
                'menu_order'    => $order,
                'wrapper'       => ['width' => $width, 'class' => '', 'id' => ''],
                'placeholder'   => $step[$value],
                'default_value' => '',
            ]);
        }
        $order++;
    }
}, 20);

// --- Skala prüfen: gültige, eindeutige Namen, genau eine Fließtext-Stufe
add_filter('acf/validate_value/key=field_zpt_type_scale', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $names = [];
    $base  = 0;
    foreach (is_array($value) ? $value : [] as $row) {
        $name = zpt_type_step_name((string) wp_unslash($row['field_zpt_type_scale_name'] ?? ''));
        if ($name === '') {
            return __('Jede Stufe braucht einen Namen (a–z, 0–9, Bindestrich).', '12punkt-baukasten');
        }
        if (isset($names[$name])) {
            /* translators: %s: Name der Stufe */
            return sprintf(__('Der Name „%s“ ist mehrfach vergeben.', '12punkt-baukasten'), $name);
        }
        $names[$name] = true;
        $base += empty($row['field_zpt_type_scale_base']) ? 0 : 1;
    }
    return $base === 1 ? true : __('Genau eine Stufe muss „Fließtext“ sein.', '12punkt-baukasten');
}, 10, 2);

// --- Namen beim Speichern vereinheitlichen („Copy L“ → „copy-l“)
add_filter('acf/update_value/key=field_zpt_type_scale_name', fn($value) => zpt_type_step_name((string) $value));

// Auswahl „Größe“ (Block-Felder, Elemente): Stufen der Skala
add_filter('acf/load_field/key=field_zpt_props_font_size', function ($field) {
    $field['choices'] = ['' => __('Standard', '12punkt-baukasten')];
    foreach (zpt_type_steps() as $key => $step) {
        $field['choices']['font-size-' . zpt_type_css($key)] = $step['name'];
    }
    return $field;
});

add_filter('acf/load_field/type=select', function ($field) {
    if (!preg_match('/^field_zpt_element_.+_size_[a-z]+$/', $field['key'] ?? '')) {
        return $field;
    }
    $empty = $field['choices'][''] ?? '';
    $field['choices'] = ['' => $empty];
    foreach (zpt_type_steps() as $key => $step) {
        $field['choices'][$key] = $step['name'];
    }
    return $field;
});

// ----------------------------------------------------------------- ADMIN: SCHRIFTEN ALS AKKORDEON
add_action('acf/input/admin_enqueue_scripts', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !str_ends_with($screen->id, 'zpt-typography')) {
        return;
    }

    wp_enqueue_script('zpt-admin-typography', ZPT_URL . 'assets/js/admin-typography.js', ['acf-input'], filemtime(ZPT_PATH . 'assets/js/admin-typography.js'), true);
    wp_add_inline_script('zpt-admin-typography', 'var zptTypePresets = ' . wp_json_encode(ZPT_TYPE_PRESETS) . ';', 'before');

    $repeater = '.acf-field[data-key="field_zpt_fonts"] > .acf-input > .acf-repeater > table > tbody';
    wp_add_inline_style('acf-input', "
        {$repeater} > .acf-row > .acf-row-handle .acf-icon.-collapse {display: block;}
        {$repeater} > .acf-row.-collapsed > .acf-fields {cursor: pointer; background: #f6f7f7;}
        {$repeater} > .acf-row.-collapsed > .acf-fields:hover {background: #f0f0f1;}
        {$repeater} > .acf-row:not(.-collapsed) > .acf-fields {box-shadow: inset 3px 0 0 #2271b1;}
        .zpt-type-scale .acf-row.-base > td.acf-field {background: #f0f6fc;}
    ");
});

// ----------------------------------------------------------------- BLOCK-EDITOR: SCHRIFT / GRÖSSE / GEWICHT
// Felder aus „Template: Komponenten-Einstellungen“ (geklont in die Komponenten-Blocks)

// Schriftrolle: nur Rollen mit zugeordneter, geladener Schrift („Serif (Lora)“). Gespeichert wird
// der Rollen-Schlüssel (serif, mono …), die Klasse entsteht bei der Ausgabe (zpt_font_role_class()).
add_filter('acf/load_field/name=zpt_font_role', function ($field) {
    $field['choices'] = ['' => __('Standard (wie umgebend)', '12punkt-baukasten')];
    foreach (zpt_css_role_fonts() as $role => $family) {
        $field['choices'][$role] = __(ZPT_FONT_ROLES[$role]['label'], '12punkt-baukasten') . ' (' . $family . ')';
    }
    return $field;
});

/**
 * CSS-Klasse einer Schriftrolle: serif → font-serif, mono → font-mono …,
 * Grundschrift → Klasse der Grundschrift (ff-inter), damit sie auch in einer Serif-Spalte greift.
 */
function zpt_font_role_class(string $role): string {
    if ($role === '' || !isset(ZPT_FONT_ROLES[$role])) {
        return '';
    }
    if ($role === 'base') {
        $family = zpt_css_role_fonts()['base'] ?? '';
        return $family ? 'ff-' . (zpt_loaded_fonts()[$family]['slug'] ?? '') : '';
    }
    return ltrim(ZPT_FONT_ROLES[$role]['selector'], '.');
}

add_filter('acf/load_field/name=zpt_font_weight_class', function ($field) {
    $weights = [];
    foreach (zpt_loaded_fonts() as $family => $font) {
        foreach ($font['weights'] as $weight) {
            $weights[$weight][] = $family;
        }
    }
    ksort($weights);

    // nur Gewichte der gewählten Schrift sichtbar: assets/js/editor-font-weights.js
    $field['choices'] = ['' => __('Standard', '12punkt-baukasten')];
    foreach (array_keys($weights) as $weight) {
        $field['choices']['fw-' . $weight] = $weight . ' ' . (ZPT_FONT_WEIGHTS[$weight] ?? '');
    }
    return $field;
});

/**
 * Gewichte je Schriftrolle für den Editor: ['' => Grundschrift|null, 'serif' => ['400', …], …].
 * '' (Standard) = Gewichte der Grundschrift; null = keine Einschränkung (keine Grundschrift gewählt).
 */
function zpt_editor_font_weights(): array {
    $map    = ['' => null];
    $loaded = zpt_loaded_fonts();

    foreach (zpt_css_role_fonts() as $role => $family) {
        $map[$role] = $loaded[$family]['weights'] ?? null;
    }
    if (isset($map['base'])) {
        $map[''] = $map['base'];
    }
    return $map;
}

add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script('zpt-editor-font-weights', ZPT_URL . 'assets/js/editor-font-weights.js', ['jquery'], filemtime(ZPT_PATH . 'assets/js/editor-font-weights.js'), true);
    wp_add_inline_script('zpt-editor-font-weights', 'var zptFontWeights = ' . wp_json_encode(zpt_editor_font_weights()) . ';', 'before');
});
