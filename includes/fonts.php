<?php
/**
 * Schriften laden (Baukasten → Typografie → „Schriftquellen“ und „Schriftschnitte & Maße“).
 *
 * Jede Zeile im Repeater „Schriftschnitte & Maße“ (zpt_fonts) wählt eine Schrift aus einer gemeinsamen Liste:
 * - wp:{slug}     Familie aus der WP-Schriftbibliothek (wp_font_family / wp_font_face),
 *                 nur die unter Schriftquellen → WP-Schriften angehakten
 * - theme:{slug}  Familie aus dem Theme-Ordner (Schriftquellen → Theme-Ordner, z. B. fonts/).
 *                 Liegen dort CSS-Dateien, werden diese geladen (Familien aus deren @font-face).
 *                 Sonst werden @font-face-Regeln aus den Schriftdateien erzeugt:
 *                   fonts/Lato/Lato-BoldItalic.woff2   → Familie aus dem Unterordner
 *                   fonts/Lato-BoldItalic.woff2        → Familie aus dem Dateinamen (bis - oder _)
 *                 Gewicht/Stil aus dem Dateinamen (Bold, 700, Italic …).
 * - system:{slug} System-Standard (Schriftquellen → System-Standard): nichts zu laden, font-family ist der
 *                 eingegebene Stack (Arial, "Helvetica Neue", Helvetica, sans-serif), Name = erster Eintrag.
 *
 * Geladen werden nur die Schriften im Repeater.
 * Felder: acf-json/group_zpt_settings_typography.json, group_zpt_settings_typo_loading.json
 */

defined('ABSPATH') || exit;

const ZPT_FONT_FORMATS = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];

// System-Standard (Schriftquellen → System-Standard), solange nichts gespeichert ist
const ZPT_SYSTEM_FONT_DEFAULT = 'Arial, "Helvetica Neue", Helvetica, sans-serif';

/**
 * Ordner für eigene Schriften im Theme (Schriftquellen → Theme-Ordner).
 * Alle Lade-Einstellungen werden direkt aus den Optionen gelesen (nicht per get_field()), weil sie
 * auch in acf/load_field-Filtern gebraucht werden – get_field() würde dort Felder erneut laden.
 */
function zpt_font_theme_folder(): string {
    return zpt_sanitize_font_folder(get_option('options_zpt_font_loading_theme_folder', ''));
}

/**
 * Angebotene Familien der WP-Schriftbibliothek (Schriftquellen → WP-Schriften): [slug …].
 * Noch nie gespeichert → alle installierten.
 */
function zpt_font_wp_selected(): array {
    $saved = get_option('options_zpt_font_loading_wp_families', null);
    if ($saved === null || $saved === false) {
        return array_keys(zpt_wp_font_families());
    }
    return array_values(array_filter((array) $saved));
}

/**
 * Stack des System-Standards (Schriftquellen → System-Standard), '' = keiner. Noch nie gespeichert → Arial-Stack.
 */
function zpt_font_system_stack(): string {
    $saved = get_option('options_zpt_font_loading_system_stack', null);
    $stack = ($saved === null || $saved === false) ? ZPT_SYSTEM_FONT_DEFAULT : (string) $saved;
    return trim(str_replace([';', '{', '}', '<', '>'], '', $stack), " ,");
}

/**
 * Ordnerangabe relativ zum Theme: ohne "..", Backslashes und führende/abschließende Slashes.
 */
function zpt_sanitize_font_folder($folder): string {
    return trim(str_replace(['..', '\\'], '', (string) $folder), '/ ');
}

/**
 * Schlüssel der gewählten Schriften in Repeater-Reihenfolge: [Zeile => 'wp:inter', …].
 */
function zpt_selected_font_keys(): array {
    $keys = [];
    for ($i = 0, $rows = (int) get_option('options_zpt_fonts', 0); $i < $rows; $i++) {
        if ($key = (string) get_option("options_zpt_fonts_{$i}_font", '')) {
            $keys[$i] = $key;
        }
    }
    return $keys;
}

// ----------------------------------------------------------------- WP-SCHRIFTBIBLIOTHEK

/**
 * Installierte Schriftfamilien: [slug => Name].
 */
function zpt_wp_font_families(): array {
    $families = get_posts([
        'post_type'      => 'wp_font_family',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    return wp_list_pluck($families, 'post_title', 'post_name');
}

/**
 * Schnitte der gewählten Familien als Font-Face-Liste (siehe zpt_font_face_css()).
 */
function zpt_wp_font_faces(array $slugs): array {
    $faces = [];

    foreach ($slugs as $slug) {
        $family = get_page_by_path($slug, OBJECT, 'wp_font_family');
        if (!$family) {
            continue;
        }

        $children = get_posts([
            'post_type'      => 'wp_font_face',
            'post_status'    => 'publish',
            'post_parent'    => $family->ID,
            'posts_per_page' => -1,
        ]);

        foreach ($children as $child) {
            $data = json_decode($child->post_content, true) ?: [];
            $src  = [];
            foreach ((array) ($data['src'] ?? []) as $url) {
                $ext       = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                $src[$url] = ZPT_FONT_FORMATS[$ext] ?? '';
            }
            if (!$src || empty($data['fontFamily'])) {
                continue;
            }
            $faces[] = [
                // WP speichert Namen mit Leerzeichen teils in Anführungszeichen: "B612 Mono"
                'family' => trim(explode(',', $data['fontFamily'])[0], " '\""),
                'weight' => $data['fontWeight'] ?? '400',
                'style'  => $data['fontStyle'] ?? 'normal',
                'src'    => $src,
            ];
        }
    }

    return $faces;
}

// ----------------------------------------------------------------- THEME-ORDNER

/**
 * Inhalt des Theme-Ordners: ['css' => [Dateipfad …], 'faces' => [Font-Face …]].
 * CSS-Dateien haben Vorrang – dann werden keine Regeln erzeugt.
 */
function zpt_theme_fonts(string $folder): array {
    $result = ['css' => [], 'faces' => []];
    $dir    = get_stylesheet_directory() . '/' . $folder;

    if ($folder === '' || !is_dir($dir)) {
        return $result;
    }

    $result['css'] = glob($dir . '/*.css') ?: [];
    if ($result['css']) {
        return $result;
    }

    $ext   = implode(',', array_keys(ZPT_FONT_FORMATS));
    $files = array_merge(
        glob($dir . '/*.{' . $ext . '}', GLOB_BRACE) ?: [],
        glob($dir . '/*/*.{' . $ext . '}', GLOB_BRACE) ?: []
    );

    $faces = [];
    foreach ($files as $file) {
        $rel  = substr($file, strlen($dir) + 1);
        $name = pathinfo($file, PATHINFO_FILENAME);
        $sub  = dirname($rel);
        $key  = $sub . '/' . $name;

        if (!isset($faces[$key])) {
            $faces[$key] = zpt_parse_font_filename($name) + [
                'family' => $sub !== '.' ? $sub : preg_split('/[-_ ]/', $name)[0],
                'src'    => [],
            ];
        }
        $url = get_stylesheet_directory_uri() . '/' . implode('/', array_map('rawurlencode', explode('/', $folder . '/' . $rel)));
        $faces[$key]['src'][$url] = ZPT_FONT_FORMATS[strtolower(pathinfo($file, PATHINFO_EXTENSION))];
    }

    // woff2 vor woff vor ttf/otf
    $order = array_flip(array_values(ZPT_FONT_FORMATS));
    foreach ($faces as &$face) {
        uasort($face['src'], fn($a, $b) => $order[$a] <=> $order[$b]);
    }
    unset($face);

    $result['faces'] = array_values($faces);
    return $result;
}

/**
 * Gewicht und Stil aus einem Dateinamen: "Lato-BoldItalic" → 700 italic, "inter-v12-latin-600" → 600.
 * Variable Fonts ("Variable", "[wght]", "VF") bekommen den Bereich 100 900.
 */
function zpt_parse_font_filename(string $name): array {
    $n     = strtolower($name);
    $style = preg_match('/italic|oblique/', $n) ? 'italic' : 'normal';

    if (preg_match('/variable|\[wght|[-_]vf\b/', $n)) {
        return ['weight' => '100 900', 'style' => $style];
    }
    if (preg_match('/(?<![0-9])([1-9]00)(?![0-9])/', $n, $m)) {
        return ['weight' => $m[1], 'style' => $style];
    }

    // längere Begriffe zuerst ("extrabold" vor "bold")
    $weights = [
        'extralight' => 200, 'ultralight' => 200, 'extrabold' => 800, 'ultrabold' => 800,
        'semibold'   => 600, 'demibold'   => 600, 'hairline'  => 100, 'thin'      => 100,
        'light'      => 300, 'medium'     => 500, 'black'     => 900, 'heavy'     => 900,
        'bold'       => 700,
    ];
    foreach ($weights as $word => $weight) {
        if (str_contains(str_replace(['-', '_', ' '], '', $n), $word)) {
            return ['weight' => (string) $weight, 'style' => $style];
        }
    }

    return ['weight' => '400', 'style' => $style];
}

// ----------------------------------------------------------------- AUSGABE

/**
 * @font-face-Regeln für eine Font-Face-Liste
 * ([['family' => 'Lato', 'weight' => '700', 'style' => 'italic', 'src' => [url => format]] …]).
 */
function zpt_font_face_css(array $faces): string {
    $css = '';

    foreach ($faces as $face) {
        $src = [];
        foreach ($face['src'] as $url => $format) {
            $src[] = "url('" . esc_url_raw($url) . "')" . ($format ? " format('{$format}')" : '');
        }
        $css .= sprintf(
            "@font-face{font-family:'%s';font-style:%s;font-weight:%s;font-display:swap;src:%s;}",
            str_replace(["'", '\\'], '', $face['family']),
            $face['style'] === 'italic' ? 'italic' : 'normal',
            preg_replace('/[^0-9 ]/', '', (string) $face['weight']) ?: '400',
            implode(',', $src)
        );
    }

    return $css;
}

/**
 * Alle verfügbaren Schriften (WP-Schriftbibliothek + System-Standard + Theme-Ordner):
 * ['wp:inter' => ['family' => 'Inter', 'label' => 'Inter (WP-Schriftbibliothek)',
 *                 'faces' => [Font-Face …], 'css' => '' | Dateipfad, 'stack' => '' | font-family], …]
 * Bei 'css' stehen die Faces nur für die Gewichte drin, geladen wird die CSS-Datei.
 * Bei 'stack' (System-Standard) wird nichts geladen, die Faces liefern nur die Gewichte.
 */
function zpt_available_fonts(): array {
    static $fonts = null;
    if ($fonts !== null) {
        return $fonts;
    }
    $fonts = [];

    // --- WP-Schriftbibliothek
    foreach (array_intersect_key(zpt_wp_font_families(), array_flip(zpt_font_wp_selected())) as $slug => $title) {
        $faces = zpt_wp_font_faces([$slug]);
        $family = $faces[0]['family'] ?? trim($title, " '\"");
        $fonts['wp:' . $slug] = [
            'family' => $family,
            /* translators: %s: Name der Schriftfamilie */
            'label'  => sprintf(__('%s (WP-Schriftbibliothek)', '12punkt-baukasten'), $family),
            'faces'  => $faces,
            'css'    => '',
            'stack'  => '',
        ];
    }

    // --- System-Standard
    if ($stack = zpt_font_system_stack()) {
        $family = trim(explode(',', $stack)[0], " '\"");
        $fonts['system:' . sanitize_title($family)] = [
            'family' => $family,
            /* translators: %s: Name der Schriftfamilie */
            'label'  => sprintf(__('%s (System-Standard)', '12punkt-baukasten'), $family),
            'faces'  => array_map(fn($w) => ['family' => $family, 'weight' => $w, 'style' => 'normal', 'src' => []], ['400', '700']),
            'css'    => '',
            'stack'  => $stack,
        ];
    }

    // --- Theme-Ordner
    $folder = zpt_font_theme_folder();
    $theme  = zpt_theme_fonts($folder);
    $faces  = $theme['faces'];
    $css_of = [];

    foreach ($theme['css'] as $file) {
        foreach (zpt_css_file_faces($file) as $face) {
            $faces[]                          = $face;
            $css_of[strtolower($face['family'])] = $file;
        }
    }

    $names = []; // CSS unterscheidet bei Familiennamen nicht nach Groß-/Kleinschreibung
    foreach ($faces as $face) {
        $family = $names[strtolower($face['family'])] ??= $face['family'];
        $key    = 'theme:' . sanitize_title($family);
        $fonts[$key] ??= [
            'family' => $family,
            /* translators: 1: Name der Schriftfamilie, 2: Ordner im Theme */
            'label'  => sprintf(__('%1$s (Theme: %2$s/)', '12punkt-baukasten'), $family, $folder),
            'faces'  => [],
            'css'    => $css_of[strtolower($family)] ?? '',
            'stack'  => '',
        ];
        $fonts[$key]['faces'][] = $face;
    }

    return $fonts;
}

/**
 * Familie und Gewicht der @font-face-Regeln einer CSS-Datei.
 */
function zpt_css_file_faces(string $file): array {
    $faces = [];
    preg_match_all('/@font-face\s*{([^}]*)}/i', (string) file_get_contents($file), $blocks);

    foreach ($blocks[1] as $block) {
        if (!preg_match('/font-family\s*:\s*([^;]+)/i', $block, $family)) {
            continue;
        }
        preg_match('/font-weight\s*:\s*([^;]+)/i', $block, $weight);
        $faces[] = [
            'family' => trim($family[1], " \t\n\r'\""),
            'weight' => trim($weight[1] ?? '400'),
            'style'  => 'normal',
            'src'    => [],
        ];
    }

    return $faces;
}

/**
 * Geladene (= im Repeater gewählte) Schriften in Repeater-Reihenfolge:
 * ['Inter' => ['key' => 'wp:inter', 'row' => 0, 'slug' => 'inter', 'weights' => ['400', '600'], 'source' => […]], …]
 * Variable Fonts (Gewicht "100 900") bekommen alle Gewichte im Bereich.
 */
function zpt_loaded_fonts(): array {
    static $fonts = null;
    if ($fonts !== null) {
        return $fonts;
    }
    $fonts     = [];
    $available = zpt_available_fonts();

    foreach (zpt_selected_font_keys() as $row => $key) {
        if (!isset($available[$key]) || isset($fonts[$available[$key]['family']])) {
            continue;
        }
        $font    = $available[$key];
        $weights = [];
        foreach ($font['faces'] as $face) {
            $weights = array_merge($weights, zpt_font_weight_list((string) $face['weight']));
        }
        $weights = array_values(array_unique($weights));
        sort($weights, SORT_NUMERIC);

        $fonts[$font['family']] = [
            'key'     => $key,
            'row'     => $row,
            'slug'    => sanitize_title($font['family']),
            'weights' => $weights ?: ['400'],
            'source'  => $font,
        ];
    }

    return $fonts;
}

/**
 * Was geladen werden soll: ['css' => [Dateipfad …], 'faces' => [Font-Face …]].
 */
function zpt_fonts(): array {
    $fonts = ['css' => [], 'faces' => []];

    foreach (zpt_loaded_fonts() as $font) {
        if ($font['source']['stack']) {
            continue; // System-Standard: nichts zu laden
        }
        if ($font['source']['css']) {
            $fonts['css'][] = $font['source']['css'];
        } else {
            $fonts['faces'] = array_merge($fonts['faces'], $font['source']['faces']);
        }
    }
    $fonts['css'] = array_values(array_unique($fonts['css']));

    return $fonts;
}

/**
 * Gewichte aus einer font-weight-Angabe: "700" → [700], "100 900" → [100, 200, … 900], "bold" → [700].
 */
function zpt_font_weight_list(string $weight): array {
    $weight = strtolower(trim($weight));
    $named  = ['normal' => '400', 'bold' => '700'];
    $parts  = array_map(fn($w) => $named[$w] ?? $w, preg_split('/\s+/', $weight));
    $parts  = array_filter($parts, fn($w) => preg_match('/^[1-9]00$/', $w));

    if (count($parts) === 2) {
        return array_map('strval', range((int) min($parts), (int) max($parts), 100));
    }
    return $parts ? [reset($parts)] : ['400'];
}

/**
 * Wert für font-family: Stack der System-Schrift bzw. 'Name', generische Ausweichschrift.
 */
function zpt_font_family_value(string $family, string $generic = 'sans-serif'): string {
    $name = zpt_css_font_name($family);
    foreach (zpt_loaded_fonts() as $loaded => $font) {
        if ($font['source']['stack'] && strcasecmp($loaded, $name) === 0) {
            return $font['source']['stack'];
        }
    }
    return "'{$name}', {$generic}";
}

/**
 * Namen aller geladenen Schriftfamilien (für die Auswahl unter „Schriftarten“).
 */
function zpt_loaded_font_families(): array {
    return array_keys(zpt_loaded_fonts());
}

/**
 * URL zu einer Datei im (Child-)Theme.
 */
function zpt_theme_file_url(string $file): string {
    return get_stylesheet_directory_uri() . substr($file, strlen(get_stylesheet_directory()));
}

// --- Frontend: CSS-Dateien aus dem Theme-Ordner; @font-face inline vor den Style-Variablen
add_action('wp_enqueue_scripts', function () {
    foreach (zpt_fonts()['css'] as $i => $file) {
        wp_enqueue_style('zpt-fonts-' . $i, zpt_theme_file_url($file), [], (string) filemtime($file));
    }
    if ($css = zpt_font_face_css(zpt_fonts()['faces'])) {
        wp_add_inline_style(wp_style_is('zpt-baukasten', 'enqueued') ? 'zpt-baukasten' : 'theme_style', $css);
    }
}, 15);

// --- Editor
add_filter('block_editor_settings_all', function ($settings) {
    $styles = [];

    foreach (zpt_fonts()['css'] as $file) {
        $styles[] = ['css' => file_get_contents($file), 'baseURL' => dirname(zpt_theme_file_url($file)) . '/'];
    }
    if ($css = zpt_font_face_css(zpt_fonts()['faces'])) {
        $styles[] = ['css' => $css];
    }

    foreach ($styles as $style) {
        $settings['styles'][] = $style + ['__unstableType' => 'theme', 'isGlobalStyles' => false];
    }

    return $settings;
});

// ----------------------------------------------------------------- FELDER

// Repeater „Schriftschnitte & Maße“: Auswahl aus allen verfügbaren Schriften
add_filter('acf/load_field/key=field_zpt_fonts_font', function ($field) {
    $field['choices'] = array_map(fn($font) => $font['label'], zpt_available_fonts());
    if (!$field['choices']) {
        $field['instructions'] = __('Keine Schriften gefunden: unter „Schriftquellen“ → „WP-Schriften“ Schriften anhaken bzw. installieren (Design → Schriftarten), einen System-Standard eintragen oder einen Theme-Ordner angeben.', '12punkt-baukasten');
    }
    return $field;
});

// WP-Schriftbibliothek: installierte Familien als Checkboxen (Standard: alle angehakt)
add_filter('acf/load_field/key=field_zpt_font_loading_wp_families', function ($field) {
    $field['choices']       = zpt_wp_font_families();
    $field['default_value'] = array_keys($field['choices']);
    if (!$field['choices']) {
        $field['instructions'] = __('Noch keine Schriften installiert (Design → Schriftarten).', '12punkt-baukasten');
    }
    return $field;
});

// Theme-Ordner: Theme-Pfad voranstellen, gefundene Schriften anzeigen
add_filter('acf/load_field/key=field_zpt_font_theme_folder', function ($field) {
    $field['prepend'] = 'themes/' . get_stylesheet() . '/';

    $folder = zpt_font_theme_folder();
    if (is_admin() && $folder !== '') {
        $fonts = zpt_theme_fonts($folder);
        if ($fonts['css']) {
            $found = 'CSS: ' . implode(', ', array_map('basename', $fonts['css']));
        } elseif ($fonts['faces']) {
            $families = [];
            foreach ($fonts['faces'] as $face) {
                $families[$face['family']][] = $face['weight'] . ($face['style'] === 'italic' ? ' italic' : '');
            }
            $found = implode(' · ', array_map(fn($f, $w) => $f . ' (' . implode(', ', $w) . ')', array_keys($families), $families));
        } else {
            $found = __('nichts – Ordner fehlt oder ist leer.', '12punkt-baukasten');
        }
        $field['instructions'] .= '<br><strong>' . esc_html__('Gefunden:', '12punkt-baukasten') . '</strong> ' . esc_html($found);
    }

    return $field;
});

// Zuordnung (Grundschrift, Sans-Serif …): Auswahl aus den geladenen Schriften
foreach (['base', 'sans_serif', 'serif', 'deco', 'mono'] as $zpt_font_role) {
    add_filter('acf/load_field/key=field_zpt_settings_font_family_' . $zpt_font_role, function ($field) use ($zpt_font_role) {
        $families = zpt_loaded_font_families();
        $field['choices'] = array_combine($families, $families) ?: [];

        // gespeicherte Schrift, die nicht (mehr) geladen wird, nicht stillschweigend verlieren
        $saved = (string) get_option('options_zpt_font_families_' . $zpt_font_role, '');
        if ($saved !== '' && !isset($field['choices'][$saved])) {
            /* translators: %s: Name der Schriftfamilie */
            $field['choices'][$saved] = sprintf(__('%s (nicht geladen)', '12punkt-baukasten'), $saved);
        }

        return $field;
    });
}
unset($zpt_font_role);
