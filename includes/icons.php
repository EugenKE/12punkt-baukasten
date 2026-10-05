<?php
/**
 * Icons: Einstellungsseite Baukasten → Icons (Slug zpt-icons) und Ausgabe.
 *
 * Icon-Sets (je eine Postbox, einzeln zuschaltbar):
 * - Bootstrap Icons  assets/vendor/bootstrap-icons/   Standard: an
 * - Font Awesome     assets/vendor/fontawesome/       Free: solid, regular, brands; oder eigene CSS-URL (Pro/Kit)
 * - Material Icons   assets/vendor/material-icons/    filled, outlined, round, sharp, two-tone (Ligaturen)
 * - Eigenes SVG-Set  Ordner im Theme (*.svg) und/oder SVG-Code im Repeater, Farbe optional = Textfarbe
 * Iconfonts und Namenslisten (assets/icons/lists/*.json) kommen aus node_modules: npm run build:icons.
 *
 * Ein Icon wird als Kürzel gespeichert: „{familie}:{name}“, z. B. bi:arrow-right, fa-solid:house,
 * fa-brands:github, mi-outlined:home, svg:logo. Ohne „:“ = Bootstrap Icon (alte Werte).
 * Ausgabe: zpt_icon('fa-solid:house').
 *
 * Icon-Auswahl: ACF-Select mit Wrapper-Klasse „zpt-icon-picker“ (Icon-Block, Buttons) – Suche per AJAX
 * über alle aktiven Sets, Vorschau in der Liste (assets/js/admin-icons.js).
 *
 * Felder: acf-json/group_zpt_settings_icons_*.json
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- FAMILIEN

const ZPT_MATERIAL_STYLES = [
    'filled'   => 'Filled',
    'outlined' => 'Outlined',
    'round'    => 'Round',
    'sharp'    => 'Sharp',
    'two-tone' => 'Two Tone',
];

/**
 * Alle Icon-Familien: Kürzel => [set, style, label, class (sprintf mit Name), ligature (Name als Text)].
 */
function zpt_icon_families(): array {
    $families = [
        'bi' => ['set' => 'bootstrap', 'style' => '', 'label' => 'Bootstrap', 'class' => 'bi bi-%s'],
    ];
    foreach (['solid' => 'Solid', 'regular' => 'Regular', 'brands' => 'Brands'] as $style => $label) {
        $families["fa-{$style}"] = ['set' => 'fontawesome', 'style' => $style, 'label' => "Font Awesome {$label}", 'class' => "fa-{$style} fa-%s"];
    }
    foreach (ZPT_MATERIAL_STYLES as $style => $label) {
        $class = $style === 'filled' ? 'material-icons' : "material-icons-{$style}";
        $key   = $style === 'filled' ? 'mi' : "mi-{$style}";
        $families[$key] = ['set' => 'material', 'style' => $style, 'label' => "Material {$label}", 'class' => "{$class} zpt-mi", 'ligature' => true];
    }
    $families['svg'] = ['set' => 'svg', 'style' => '', 'label' => __('Eigenes Set', '12punkt-baukasten'), 'class' => 'zpt-svg-icon'];

    return $families;
}

/**
 * Einstellungen der Seite Icons (mit Standardwerten, solange nie gespeichert).
 * get_option statt get_field: wird auch in acf/load_field-Filtern gebraucht.
 */
function zpt_icon_settings(): array {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $opt = fn($name, $default) => get_option("options_zpt_icons_{$name}", $default);

    return $settings = [
        'bootstrap'          => (bool) $opt('bootstrap', true),
        'fontawesome'        => (bool) $opt('fontawesome', false),
        'fontawesome_styles' => (array) ($opt('fontawesome_styles', ['solid', 'brands']) ?: []),
        'fontawesome_css'    => esc_url_raw((string) $opt('fontawesome_css', '')),
        'material'           => (bool) $opt('material', false),
        'material_styles'    => (array) ($opt('material_styles', ['filled']) ?: []),
        'svg'                => (bool) $opt('svg', false),
        'svg_folder'         => zpt_icon_svg_folder((string) $opt('svg_folder', '')),
        'svg_current_color'  => (bool) $opt('svg_current_color', true),
    ];
}

/**
 * Aktive Familien (Set eingeschaltet, Stil gewählt): ['bi' => [...], 'fa-solid' => [...], …].
 */
function zpt_icon_active_families(): array {
    $settings = zpt_icon_settings();
    return array_filter(zpt_icon_families(), function ($family) use ($settings) {
        $set = $family['set'];
        if (empty($settings[$set])) {
            return false;
        }
        return $family['style'] === '' || in_array($family['style'], $settings["{$set}_styles"] ?? [], true);
    });
}

// ----------------------------------------------------------------- AUSGABE

/**
 * Kürzel zerlegen: 'fa-solid:house' → ['fa-solid', 'house']; ohne „:“ = Bootstrap Icon.
 */
function zpt_icon_parse(string $token): array {
    $token = trim($token);
    [$family, $name] = str_contains($token, ':') ? explode(':', $token, 2) : ['bi', $token];
    return [sanitize_key($family), sanitize_html_class($name)];
}

/**
 * HTML eines Icons (dekorativ, aria-hidden) – leer, wenn das Icon bzw. sein Set nicht aktiv ist.
 *
 * @param string $token   Kürzel wie 'bi:arrow-right'.
 * @param array  $classes Zusätzliche Klassen.
 */
function zpt_icon(string $token, array $classes = []): string {
    [$family, $name] = zpt_icon_parse($token);
    $families = zpt_icon_active_families();
    if ($name === '' || !isset($families[$family])) {
        return '';
    }
    $def   = $families[$family];
    $class = trim(sprintf($def['class'], $name) . ' ' . implode(' ', array_map('sanitize_html_class', $classes)));

    if ($family === 'svg') {
        $svg = zpt_icon_svg_set()[$name] ?? '';
        return $svg === '' ? '' : preg_replace('~^<svg\b~i', '<svg class="' . esc_attr($class) . '" aria-hidden="true" focusable="false"', $svg, 1);
    }
    if (!empty($def['ligature'])) {
        return '<span class="' . esc_attr($class) . '" aria-hidden="true">' . esc_html($name) . '</span>';
    }
    return '<i class="' . esc_attr($class) . '" aria-hidden="true"></i>';
}

// ----------------------------------------------------------------- EIGENES SVG-SET

/**
 * Ordner im Theme bereinigen: relativ, ohne „..“, ohne Schrägstriche außen.
 */
function zpt_icon_svg_folder(string $folder): string {
    $folder = trim(str_replace('\\', '/', $folder), "/ \t");
    return preg_match('~(^|/)\.\.(/|$)~', $folder) ? '' : $folder;
}

/**
 * Icons des eigenen Sets: ['logo' => '<svg …>…</svg>', …], bereinigt, ohne Breite/Höhe.
 * Aus dem Theme-Ordner (Child-Theme vor Eltern-Theme) und dem Repeater (geht bei gleichem Namen vor).
 */
function zpt_icon_svg_set(): array {
    static $icons = null;
    if ($icons !== null) {
        return $icons;
    }
    $icons    = [];
    $settings = zpt_icon_settings();
    if (!$settings['svg']) {
        return $icons;
    }

    $raw = [];
    if ($settings['svg_folder'] !== '') {
        foreach (array_unique([get_template_directory(), get_stylesheet_directory()]) as $dir) {
            foreach (glob($dir . '/' . $settings['svg_folder'] . '/*.svg') ?: [] as $file) {
                $raw[zpt_icon_svg_name(basename($file, '.svg'))] = (string) file_get_contents($file);
            }
        }
    }
    $rows = (int) get_option('options_zpt_icons_svg_icons', 0);
    for ($i = 0; $i < $rows; $i++) {
        $name = zpt_icon_svg_name((string) get_option("options_zpt_icons_svg_icons_{$i}_name", ''));
        $code = (string) get_option("options_zpt_icons_svg_icons_{$i}_svg", '');
        if ($name !== '' && $code !== '') {
            $raw[$name] = $code;
        }
    }

    ksort($raw, SORT_NATURAL);
    foreach ($raw as $name => $code) {
        if ($name !== '' && ($svg = zpt_icon_prepare_svg($code, $settings['svg_current_color'])) !== '') {
            $icons[$name] = $svg;
        }
    }
    return $icons;
}

/**
 * Icon-Name aus Datei-/Feldnamen: Kleinbuchstaben, Ziffern, Bindestrich („Icon Kamera.svg“ → icon-kamera).
 */
function zpt_icon_svg_name(string $name): string {
    return trim(preg_replace('~[^a-z0-9]+~', '-', strtolower(remove_accents($name))), '-');
}

/**
 * SVG fürs Set: bereinigen, Breite/Höhe/ID der Wurzel entfernen (Größe kommt aus CSS: 1em),
 * Farben optional auf currentColor (Textfarbe).
 */
function zpt_icon_prepare_svg(string $code, bool $current_color): string {
    $svg = zpt_sanitize_svg($code);
    if ($svg === '' || !preg_match('~^<svg\b[^>]*>~i', $svg, $m)) {
        return '';
    }
    $root = preg_replace('~\s(width|height|id|class|x|y)="[^"]*"~i', '', $m[0]);

    if ($current_color) {
        $color = '~\s(fill|stroke)="(?!none|transparent|currentColor|url\()[^"]*"~i';
        $svg   = preg_replace($color, ' $1="currentColor"', substr($svg, strlen($m[0])));
        $root  = preg_replace($color, ' $1="currentColor"', $root);
        if (!preg_match('~\sfill="~i', $root)) {
            $root = substr($root, 0, -1) . ' fill="currentColor">';
        }
        return $root . $svg;
    }
    return $root . substr($svg, strlen($m[0]));
}

// ----------------------------------------------------------------- LADEN

/**
 * CSS der aktiven Iconfonts einreihen (Frontend, Editor-Iframe, Admin-Seiten mit ACF-Feldern).
 */
function zpt_icons_enqueue_styles(): void {
    $settings = zpt_icon_settings();
    $ver      = fn($file) => file_exists(ZPT_PATH . $file) ? (string) filemtime(ZPT_PATH . $file) : ZPT_VERSION;

    if ($settings['bootstrap']) {
        $file = 'assets/vendor/bootstrap-icons/bootstrap-icons.min.css';
        wp_enqueue_style('zpt-icons-bootstrap', ZPT_URL . $file, [], $ver($file));
    }

    if ($settings['fontawesome'] && $settings['fontawesome_styles']) {
        if ($settings['fontawesome_css'] !== '') {
            wp_enqueue_style('zpt-icons-fontawesome', $settings['fontawesome_css'], [], null);
        } else {
            $file = 'assets/vendor/fontawesome/css/fontawesome.min.css';
            wp_enqueue_style('zpt-icons-fontawesome', ZPT_URL . $file, [], $ver($file));
            foreach ($settings['fontawesome_styles'] as $style) {
                $file = 'assets/vendor/fontawesome/css/' . sanitize_key($style) . '.min.css';
                if (file_exists(ZPT_PATH . $file)) {
                    wp_enqueue_style("zpt-icons-fontawesome-{$style}", ZPT_URL . $file, ['zpt-icons-fontawesome'], $ver($file));
                }
            }
        }
    }

    if ($settings['material']) {
        foreach ($settings['material_styles'] as $style) {
            $file = 'assets/vendor/material-icons/' . sanitize_key($style) . '.css';
            if (file_exists(ZPT_PATH . $file)) {
                wp_enqueue_style("zpt-icons-material-{$style}", ZPT_URL . $file, [], $ver($file));
            }
        }
    }
}

add_action('wp_enqueue_scripts', 'zpt_icons_enqueue_styles', 6);
// Editor: landet im Iframe des Inhalts und im Admin-Dokument (Vorschau in der Icon-Auswahl)
add_action('enqueue_block_assets', function () {
    if (is_admin()) {
        zpt_icons_enqueue_styles();
    }
});

// Admin mit ACF-Feldern (Editor, Einstellungsseiten): Fonts, Icon-Auswahl
add_action('acf/input/admin_enqueue_scripts', function () {
    zpt_icons_enqueue_styles();

    $file = 'assets/js/admin-icons.js';
    wp_enqueue_script('zpt-admin-icons', ZPT_URL . $file, ['acf-input'], (string) filemtime(ZPT_PATH . $file), true);
    wp_localize_script('zpt-admin-icons', 'zptIcons', [
        'families' => array_map(fn($f) => ['class' => $f['class'], 'ligature' => !empty($f['ligature'])], zpt_icon_active_families()),
        'svg'      => zpt_icon_svg_set(),
    ]);
    wp_add_inline_style('acf-input', '
        .zpt-icon-option {display: inline-flex; align-items: center; gap: 8px;}
        .zpt-icon-option > :first-child {font-size: 20px; line-height: 1; width: 1em; flex: none; text-align: center;}
        .zpt-icon-option svg {width: 1em; height: 1em; vertical-align: middle;}
        .zpt-icon-option small {opacity: .6;}
        .zpt-icon-preview {display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin: 0;}
        .zpt-icon-preview figure {margin: 0; padding: 10px 4px 6px; text-align: center; border: 1px solid #dcdcde; border-radius: 4px; background: #fff;}
        .zpt-icon-preview figure > :first-child {font-size: 32px; display: block; margin: 0 auto 6px;}
        .zpt-icon-preview svg {width: 1em; height: 1em;}
        .zpt-icon-preview figcaption {font-size: 11px; word-break: break-all; color: #50575e;}
    ');
});

// ----------------------------------------------------------------- ICON-AUSWAHL (ACF)

function zpt_is_icon_picker($field): bool {
    return is_array($field) && str_contains(' ' . ($field['wrapper']['class'] ?? '') . ' ', ' zpt-icon-picker ');
}

// Auswahl mit Suche, Einträge per AJAX
add_filter('acf/load_field/type=select', function ($field) {
    if (zpt_is_icon_picker($field)) {
        $field['ui']         = 1;
        $field['ajax']       = 1;
        $field['allow_null'] = 1;
        $field['multiple']   = 0;
        $field['choices']    = [];
        $field['placeholder'] = $field['placeholder'] ?: __('Icon suchen …', '12punkt-baukasten');
    }
    return $field;
});

// Alte Werte ohne Familie (Bootstrap-Name) → bi:name
add_filter('acf/load_value/type=select', function ($value, $post_id, $field) {
    if (zpt_is_icon_picker($field) && is_string($value) && $value !== '' && !str_contains($value, ':')) {
        $value = 'bi:' . $value;
    }
    return $value;
}, 10, 3);

// Gespeicherten Wert als Auswahl anbieten (sonst zeigt Select2 ihn nicht)
add_filter('acf/prepare_field/type=select', function ($field) {
    if (zpt_is_icon_picker($field) && is_string($field['value']) && $field['value'] !== '') {
        $field['choices'] = [$field['value'] => zpt_icon_label($field['value'])];
    }
    return $field;
});

/**
 * Beschriftung in der Auswahl: „arrow-right · Bootstrap“.
 */
function zpt_icon_label(string $token): string {
    [$family, $name] = zpt_icon_parse($token);
    $label = zpt_icon_families()[$family]['label'] ?? $family;
    return $name . ' · ' . $label . (isset(zpt_icon_active_families()[$family]) ? '' : ' ' . __('(nicht geladen)', '12punkt-baukasten'));
}

/**
 * Namenslisten der Iconfonts: ['bootstrap' => ['arrow-right' => '', …], 'fontawesome' => ['house' => 'solid regular|house home …'], …].
 */
function zpt_icon_list(string $set): array {
    static $lists = [];
    if (!isset($lists[$set])) {
        $file         = ZPT_PATH . "assets/icons/lists/{$set}.json";
        $lists[$set]  = file_exists($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    }
    return $lists[$set];
}

// Suche: alle aktiven Familien – genauer Name, dann Namensanfang, dann der Rest; 60 je Seite
add_filter('acf/fields/select/query', function ($response, $options) {
    if (!zpt_is_icon_picker(acf_get_field($options['field_key'] ?? ''))) {
        return $response;
    }
    $search = strtolower(trim(wp_unslash((string) ($options['s'] ?? ''))));
    $words  = array_filter(preg_split('~\s+~', $search));
    $exact  = [];
    $first  = [];
    $other  = [];

    foreach (zpt_icon_active_families() as $key => $family) {
        if ($family['set'] === 'svg') {
            $list = array_fill_keys(array_keys(zpt_icon_svg_set()), '');
        } else {
            $list = zpt_icon_list($family['set']);
        }
        foreach ($list as $name => $meta) {
            $name = (string) $name;
            // Font Awesome: „stile|suchbegriffe“
            $terms = '';
            if ($family['set'] === 'fontawesome') {
                [$styles, $terms] = array_pad(explode('|', (string) $meta, 2), 2, '');
                if (!in_array($family['style'], explode(' ', $styles), true)) {
                    continue;
                }
            }
            $haystack = str_replace('_', '-', $name) . ' ' . $terms;
            $match    = true;
            foreach ($words as $word) {
                if (!str_contains($haystack, str_replace('_', '-', $word))) {
                    $match = false;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
            $item  = ['id' => "{$key}:{$name}", 'text' => "{$name} · {$family['label']}"];
            $plain = str_replace('_', '-', $name);
            $start = $words ? str_replace('_', '-', reset($words)) : null;
            if ($start !== null && $plain === str_replace(['_', ' '], '-', $search)) {
                $exact[] = $item;
            } elseif ($start !== null && str_starts_with($plain, $start)) {
                $first[] = $item;
            } else {
                $other[] = $item;
            }
        }
    }

    $results = array_merge($exact, $first, $other);
    $page    = max(1, (int) ($options['paged'] ?? 1));
    $per     = 60;

    return [
        'results' => array_slice($results, ($page - 1) * $per, $per),
        'more'    => count($results) > $page * $per,
        'limit'   => $per,
    ];
}, 10, 2);

// ----------------------------------------------------------------- EINSTELLUNGSSEITE

// Vorschau des eigenen Sets (Feld „Vorschau“)
add_action('acf/render_field/key=field_zpt_icons_svg_preview', function () {
    $icons = zpt_icon_svg_set();
    if (!zpt_icon_settings()['svg']) {
        echo '<p class="description">' . esc_html__('Set ist ausgeschaltet.', '12punkt-baukasten') . '</p>';
        return;
    }
    if (!$icons) {
        echo '<p class="description">' . esc_html__('Noch keine Icons – Ordner angeben oder unten SVG-Code einfügen und speichern.', '12punkt-baukasten') . '</p>';
        return;
    }
    /* translators: %d: Anzahl der Icons */
    echo '<p class="description">' . esc_html(sprintf(_n('%d Icon, Stand beim letzten Speichern:', '%d Icons, Stand beim letzten Speichern:', count($icons), '12punkt-baukasten'), count($icons))) . '</p><div class="zpt-icon-preview">';
    foreach (array_keys($icons) as $name) {
        echo '<figure>' . zpt_icon("svg:{$name}") . '<figcaption>' . esc_html($name) . '</figcaption></figure>';
    }
    echo '</div>';
});

// Theme-Ordner: vorhandene SVG-Dateien anzeigen
add_filter('acf/prepare_field/key=field_zpt_icons_svg_folder', function ($field) {
    $theme = wp_get_theme();
    $field['prepend'] = $theme->get_stylesheet() . '/';
    $folder = zpt_icon_svg_folder((string) $field['value']);
    if ($folder !== '') {
        $count = 0;
        foreach (array_unique([get_template_directory(), get_stylesheet_directory()]) as $dir) {
            $count += count(glob($dir . '/' . $folder . '/*.svg') ?: []);
        }
        /* translators: %d: Anzahl gefundener SVG-Dateien */
        $field['instructions'] .= $count ? ' – ' . sprintf(_n('gefunden: %d Datei.', 'gefunden: %d Dateien.', $count, '12punkt-baukasten'), $count) : ' – <strong>' . __('keine SVG-Dateien gefunden.', '12punkt-baukasten') . '</strong>';
    }
    return $field;
});

// Icon-Name im Repeater bereinigen
add_filter('acf/update_value/key=field_zpt_icons_svg_icon_name', fn($value) => zpt_icon_svg_name((string) $value));
add_filter('acf/update_value/key=field_zpt_icons_svg_folder', fn($value) => zpt_icon_svg_folder((string) $value));
