<?php
/**
 * Breakpoints (Baukasten → Layout): Mindestbreite und Container-Breite je Breakpoint.
 *
 * Media Queries lassen sich nicht über CSS-Variablen steuern. Weichen die Werte von Bootstrap ab,
 * wird aus assets/css/zpt.css einmalig eine angepasste Kopie in uploads/zpt-baukasten/ erzeugt
 * (min-width/max-width der Media Queries und Container-Breiten ersetzt) und statt zpt.css geladen.
 * Ändert sich zpt.css oder eine Einstellung, entsteht eine neue Kopie (Hash im Dateinamen).
 *
 * Grid (zweite Box auf derselben Seite „Layout“): Gutter und Container-Rand je Breakpoint
 * → --zpt-grid-gutter / --zpt-container-padding (scss/_config.scss baut $grid-gutter-width und
 * $container-padding-x darauf auf, also .row, .grid und .container).
 *
 * Felder: acf-json/group_zpt_settings_breakpoints.json, group_zpt_settings_grid.json
 */

defined('ABSPATH') || exit;

// Bootstrap 5.3: $grid-breakpoints und $container-max-widths
const ZPT_BOOTSTRAP_BREAKPOINTS = [
    'sm'  => ['min' => 576,  'container' => 540],
    'md'  => ['min' => 768,  'container' => 720],
    'lg'  => ['min' => 992,  'container' => 960],
    'xl'  => ['min' => 1200, 'container' => 1140],
    'xxl' => ['min' => 1400, 'container' => 1320],
];

/**
 * Breakpoints aus den Einstellungen: ['sm' => ['min' => 576, 'container' => 540], …].
 * Direkt aus den Optionen gelesen (wird früh und in Feld-Filtern gebraucht).
 * Ungültige Angaben (nicht aufsteigend) → Bootstrap-Werte.
 */
function zpt_breakpoints(): array {
    static $breakpoints = null;
    if ($breakpoints !== null) {
        return $breakpoints;
    }

    $breakpoints = [];
    foreach (ZPT_BOOTSTRAP_BREAKPOINTS as $bp => $defaults) {
        foreach ($defaults as $key => $default) {
            $value = (int) get_option("options_zpt_breakpoints_{$bp}_{$key}", 0);
            $breakpoints[$bp][$key] = $value > 0 ? $value : $default;
        }
    }

    if (!zpt_breakpoints_ascending($breakpoints)) {
        $breakpoints = ZPT_BOOTSTRAP_BREAKPOINTS;
    }

    return $breakpoints;
}

/**
 * Mindestbreiten: ['sm' => 576, 'md' => 768, …].
 */
function zpt_breakpoint_min(): array {
    return array_map(fn($bp) => $bp['min'], zpt_breakpoints());
}

function zpt_breakpoints_ascending(array $breakpoints): bool {
    $mins = array_column($breakpoints, 'min');
    for ($i = 1; $i < count($mins); $i++) {
        if ($mins[$i] <= $mins[$i - 1]) {
            return false;
        }
    }
    return true;
}

// Standard-Gutter, entspricht dem Fallback in scss/_config.scss
const ZPT_GRID_GUTTER = 20;

// ----------------------------------------------------------------- GRID

/**
 * Grid-Werte je Breakpoint in px: ['gutter' => ['xs' => 20, 'md' => 30], 'container_padding' => [...]].
 * Nur gesetzte Werte (0 ist erlaubt); leere erben im CSS vom kleineren Breakpoint.
 */
function zpt_grid(): array {
    $grid = [];
    foreach (['gutter' => 'zpt_grid_gutter', 'container_padding' => 'zpt_container_padding'] as $key => $option) {
        $grid[$key] = [];
        foreach (array_merge(['xs'], array_keys(ZPT_BOOTSTRAP_BREAKPOINTS)) as $bp) {
            $value = get_option("options_{$option}_{$bp}", '');
            if (is_numeric($value) && $value >= 0) {
                $grid[$key][$bp] = (int) $value;
            }
        }
    }
    return $grid;
}

/**
 * CSS für Gutter und Container-Rand: :root{--zpt-grid-gutter:…} und je Breakpoint per Media Query.
 * XS-Gutter = Standard wird nicht ausgegeben (Fallback in zpt.css).
 */
function zpt_grid_css(): string {
    $vars = ['gutter' => '--zpt-grid-gutter', 'container_padding' => '--zpt-container-padding'];
    $min  = ['xs' => 0] + zpt_breakpoint_min();
    $grid = zpt_grid();
    if (($grid['gutter']['xs'] ?? null) === ZPT_GRID_GUTTER) {
        unset($grid['gutter']['xs']);
    }

    $css = '';
    foreach ($min as $bp => $min_width) {
        $decl = '';
        foreach ($vars as $key => $var) {
            if (isset($grid[$key][$bp])) {
                $decl .= $var . ':' . $grid[$key][$bp] . 'px;';
            }
        }
        if ($decl) {
            $css .= $min_width ? "@media (min-width: {$min_width}px){:root{{$decl}}}" : ":root{{$decl}}";
        }
    }
    return $css;
}

// ----------------------------------------------------------------- CSS-DATEI

/**
 * Zu ladende Baukasten-CSS: ['path' => …, 'url' => …, 'version' => …].
 * Standard-Breakpoints → assets/css/zpt.css, sonst die angepasste Kopie in uploads/.
 */
function zpt_css_file(): array {
    static $file = null;
    if ($file !== null) {
        return $file;
    }

    $source = ZPT_PATH . 'assets/css/zpt.css';
    $file   = [
        'path'    => $source,
        'url'     => ZPT_URL . 'assets/css/zpt.css',
        'version' => file_exists($source) ? (string) filemtime($source) : ZPT_VERSION,
    ];

    if (zpt_breakpoints() === ZPT_BOOTSTRAP_BREAKPOINTS || !file_exists($source)) {
        return $file;
    }

    $uploads = wp_upload_dir();
    $dir     = trailingslashit($uploads['basedir']) . 'zpt-baukasten';
    $hash    = substr(md5(serialize(zpt_breakpoints()) . filemtime($source)), 0, 10);
    $name    = 'zpt-' . $hash . '.css';
    $path    = $dir . '/' . $name;

    if (!file_exists($path)) {
        if (!wp_mkdir_p($dir)) {
            return $file;
        }
        // alte Kopien entfernen
        foreach (glob($dir . '/zpt-*.css') ?: [] as $old) {
            wp_delete_file($old);
        }
        if (file_put_contents($path, zpt_css_with_breakpoints((string) file_get_contents($source))) === false) {
            return $file;
        }
    }

    return $file = [
        'path'    => $path,
        'url'     => trailingslashit($uploads['baseurl']) . 'zpt-baukasten/' . $name,
        'version' => $hash,
    ];
}

/**
 * Ersetzt in kompiliertem Bootstrap-CSS die Breakpoints und Container-Breiten.
 * Relative url() (Icon-Fonts) werden absolut, weil die Kopie in uploads/ liegt.
 */
function zpt_css_with_breakpoints(string $css, ?array $breakpoints = null): string {
    $breakpoints ??= zpt_breakpoints();
    $min_map     = [];
    $container   = [];
    foreach (ZPT_BOOTSTRAP_BREAKPOINTS as $bp => $defaults) {
        $min_map[$defaults['min']]   = $breakpoints[$bp]['min'];
        $container[$defaults['min']] = $breakpoints[$bp]['container'];
    }

    // Container-Breiten: @media(min-width: 576px){.container-sm,.container{max-width:540px}}
    $css = preg_replace_callback(
        '/(@media\s*\(min-width:\s*)(\d+)(px\)\s*\{\s*(?:\.container[\w-]*\s*,\s*)*\.container\s*\{\s*max-width:\s*)\d+(px)/',
        fn($m) => isset($container[$m[2]]) ? $m[1] . $m[2] . $m[3] . $container[$m[2]] . $m[4] : $m[0],
        $css
    );

    // min-width: 576px → neuer Wert; max-width: 575.98px → neuer Wert − 0.02
    $css = preg_replace_callback(
        '/(min|max)-width:\s*(\d+(?:\.98)?)px/',
        function ($m) use ($min_map) {
            $value = (float) $m[2];
            $key   = $m[1] === 'max' ? (int) round($value + .02) : (int) $value;
            if (!isset($min_map[$key]) || ($m[1] === 'max' && !str_ends_with($m[2], '.98'))) {
                return $m[0];
            }
            return $m[1] . '-width: ' . ($m[1] === 'max' ? ($min_map[$key] - .02) : $min_map[$key]) . 'px';
        },
        $css
    );

    // relative URLs → absolut zur Plugin-CSS
    return preg_replace_callback(
        '/url\(\s*(["\']?)(?!data:|https?:|\/|#)([^"\')]+)\1\s*\)/',
        fn($m) => 'url(' . $m[1] . ZPT_URL . 'assets/css/' . $m[2] . $m[1] . ')',
        $css
    );
}

// ----------------------------------------------------------------- FELDER

// Bootstrap-Wert als Standard und Platzhalter
foreach (ZPT_BOOTSTRAP_BREAKPOINTS as $zpt_bp => $zpt_defaults) {
    foreach ($zpt_defaults as $zpt_key => $zpt_default) {
        add_filter("acf/load_field/key=field_zpt_bp_{$zpt_bp}_{$zpt_key}", function ($field) use ($zpt_default) {
            $field['default_value'] = $zpt_default;
            $field['placeholder']   = $zpt_default;
            return $field;
        });
    }
}
unset($zpt_bp, $zpt_defaults, $zpt_key, $zpt_default);

// Grid-Felder: Mindestbreite des Breakpoints als Hinweis
foreach (['grid_gutter', 'container_padding'] as $zpt_prefix) {
    add_filter("acf/load_field/key=field_zpt_{$zpt_prefix}_xs", function ($field) {
        $field['instructions'] = __('alle Breiten', '12punkt-baukasten');
        return $field;
    });
    foreach (array_keys(ZPT_BOOTSTRAP_BREAKPOINTS) as $zpt_bp) {
        add_filter("acf/load_field/key=field_zpt_{$zpt_prefix}_{$zpt_bp}", function ($field) use ($zpt_bp) {
            /* translators: %s: Mindestbreite des Breakpoints in px */
            $field['instructions'] = sprintf(__('ab %spx', '12punkt-baukasten'), zpt_breakpoint_min()[$zpt_bp]);
            return $field;
        });
    }
}
unset($zpt_prefix, $zpt_bp);

// Mindestbreiten müssen aufsteigen
add_filter('acf/validate_value/key=field_zpt_breakpoints', function ($valid, $value) {
    if ($valid !== true || !is_array($value)) {
        return $valid;
    }

    $breakpoints = [];
    foreach (ZPT_BOOTSTRAP_BREAKPOINTS as $bp => $defaults) {
        $min = (int) ($value["field_zpt_bp_{$bp}"]["field_zpt_bp_{$bp}_min"] ?? 0);
        $breakpoints[$bp]['min'] = $min > 0 ? $min : $defaults['min'];
    }

    return zpt_breakpoints_ascending($breakpoints)
        ? true
        : __('Die Breakpoints müssen von SM bis XXL aufsteigen.', '12punkt-baukasten');
}, 10, 2);
