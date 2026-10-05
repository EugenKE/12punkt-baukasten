<?php
/**
 * Einstellungen "Baukasten" (ACF Options Pages: Farben, Typografie, Elemente, Buttons, Icons, Logo, Navigation, Layout, Meta Infos;
 * dazu Import / Export: import-export.php) und deren Ausgabe:
 * - Farben, Typografie: → CSS-Variablen im Frontend und Editor
 *                       (Werte pro Schrift und .ff-*-Klassen: typography.php)
 * - Layout:             Breakpoints, Gutter, Container-Rand (breakpoints.php)
 * - Globale Meta Infos: <meta name="description|keywords"> (pro Seite überschreibbar)
 *
 * - Elemente: elements.php
 * - Buttons: buttons.php
 * - Icons: icons.php
 * - Navigation (Leiste im Theme-Kopf): navigation.php
 * - Logo, Favicon: logo.php
 *
 * Felder: acf-json/group_zpt_settings_style.json (Farben), group_zpt_settings_typo*.json,
 *         group_zpt_settings_meta.json, group_zpt_meta.json
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- OPTIONS PAGE

/**
 * Einstellungsseiten (ACF Options Pages): ['zpt-colors' => 'Farben', …].
 * Dazu kommt Import / Export (import-export.php) als eigene Admin-Seite.
 */
function zpt_settings_subpages(): array {
    return apply_filters('zpt/settings/subpages', [
        'zpt-colors'     => __('Farben', '12punkt-baukasten'),
        'zpt-typography' => __('Typografie', '12punkt-baukasten'),
        'zpt-elements'   => __('Elemente', '12punkt-baukasten'),
        'zpt-buttons'    => __('Buttons', '12punkt-baukasten'),
        'zpt-icons'      => __('Icons', '12punkt-baukasten'),
        'zpt-logo'       => __('Logo & Bilder', '12punkt-baukasten'),
        'zpt-navigation' => __('Navigation', '12punkt-baukasten'),
        'zpt-layout'     => __('Layout', '12punkt-baukasten'),
        'zpt-meta'       => __('Meta Infos', '12punkt-baukasten'),
    ]);
}

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page')) {
        return;
    }
    // Hauptmenü: leitet auf die erste Unterseite weiter
    acf_add_options_page([
        'page_title' => '12punkt Baukasten',
        'menu_title' => __('Baukasten', '12punkt-baukasten'),
        'menu_slug'  => 'zpt-settings',
        'capability' => 'manage_options',
        'icon_url'   => 'dashicons-layout',
        'position'   => 59,
        'redirect'   => true,
    ]);

    foreach (zpt_settings_subpages() as $slug => $title) {
        acf_add_options_sub_page([
            /* translators: %s: Name der Einstellungsseite */
            'page_title'  => sprintf(__('Baukasten – %s', '12punkt-baukasten'), $title),
            'menu_title'  => $title,
            'menu_slug'   => $slug,
            'parent_slug' => 'zpt-settings',
            'capability'  => 'manage_options',
        ]);
    }
});

// ----------------------------------------------------------------- COLORS

/**
 * Angelegte Systemfarben mit der Grundfarbe ihrer Palettenfarbe: ['primary' => '#0d6efd', …].
 * Nicht angelegte Bootstrap-Farben fehlen (es gilt --bs-*). Mit Shades: zpt_system_color_palette().
 */
function zpt_colors(): array {
    return array_map(fn($shades) => $shades[zpt_color_base()], zpt_system_color_palette());
}

/**
 * CSS-Variablen für die Farben. Setzt die Baukasten-Variablen (--theme-*) und die
 * Bootstrap-Variablen (--bs-*), damit auch .text-*, .bg-*, Links usw. greifen.
 * Dazu die Variablen der Farbpalette (--theme-blau-100 …).
 */
function zpt_colors_vars(): array {
    $vars = zpt_palette_vars();

    foreach (zpt_system_color_palette() as $name => $shades) {
        $hex      = $shades[zpt_color_base()];
        $rgb      = zpt_hex_to_rgb($hex);
        $hover    = zpt_shade($rgb, .3);
        $contrast = zpt_contrast_color($rgb);

        $vars["--theme-{$name}"]          = $hex;
        $vars["--theme-{$name}-hover"]    = zpt_rgb_to_hex($hover);
        $vars["--theme-{$name}-contrast"] = $contrast;
        // Bootstrap-Farben: auch Bootstraps Variablen, eigene Namen (accent) nur --theme-*
        if (in_array($name, array_merge(ZPT_BOOTSTRAP_THEME_COLORS, ZPT_BOOTSTRAP_BASIC_COLORS), true)) {
            $vars["--bs-{$name}"]     = $hex;
            $vars["--bs-{$name}-rgb"] = implode(', ', $rgb);
        }
        if (in_array($name, ZPT_BOOTSTRAP_THEME_COLORS, true)) {
            // wie Bootstraps _maps.scss: .bg-*-subtle, .border-*-subtle, .text-*-emphasis
            $vars["--bs-{$name}-bg-subtle"]     = zpt_rgb_to_hex(zpt_tint($rgb, .8));
            $vars["--bs-{$name}-border-subtle"] = zpt_rgb_to_hex(zpt_tint($rgb, .6));
            $vars["--bs-{$name}-text-emphasis"] = zpt_rgb_to_hex(zpt_shade($rgb, .6));
        }
        // Shades 100–900 (colors.php)
        foreach ($shades as $shade => $shade_hex) {
            $vars["--theme-{$name}-{$shade}"] = $shade_hex;
        }

        if ($name === 'primary') {
            $vars['--bs-link-color']           = $hex;
            $vars['--bs-link-color-rgb']       = implode(', ', $rgb);
            $vars['--bs-link-hover-color']     = zpt_rgb_to_hex($hover);
            $vars['--bs-link-hover-color-rgb'] = implode(', ', $hover);
        }
    }

    return $vars;
}

function zpt_hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return array_map('hexdec', str_split($hex, 2));
}

function zpt_rgb_to_hex(array $rgb): string {
    return sprintf('#%02x%02x%02x', ...$rgb);
}

/**
 * Abdunkeln wie Bootstraps shade-color(): Mischung mit Schwarz.
 */
function zpt_shade(array $rgb, float $weight): array {
    return array_map(fn($c) => (int) round($c * (1 - $weight)), $rgb);
}

/**
 * Aufhellen wie Bootstraps tint-color(): Mischung mit Weiß.
 */
function zpt_tint(array $rgb, float $weight): array {
    return array_map(fn($c) => (int) round($c + (255 - $c) * $weight), $rgb);
}

/**
 * Lesbare Textfarbe auf der Farbe – wie Bootstraps color-contrast() (WCAG, min. 4.5:1).
 */
function zpt_contrast_color(array $rgb): string {
    $luminance = function (array $rgb): float {
        $c = array_map(function ($v) {
            $v /= 255;
            return $v <= .03928 ? $v / 12.92 : (($v + .055) / 1.055) ** 2.4;
        }, $rgb);
        return .2126 * $c[0] + .7152 * $c[1] + .0722 * $c[2];
    };

    $l          = $luminance($rgb);
    $with_white = 1.05 / ($l + .05);
    $with_black = ($l + .05) / .05;

    return ($with_white >= 4.5 || $with_white >= $with_black) ? '#fff' : '#000';
}

// ----------------------------------------------------------------- TYPOGRAPHY

/**
 * CSS-Variablen für Schriftarten, -größen und Zeilenhöhen (nur gesetzte Werte).
 * Die Fallbacks stehen in scss/_config.scss ($default-font-*).
 */
function zpt_typography_vars(): array {
    $vars = [];

    // Rollen: Familienname mit generischer Ausweichschrift (Werte pro Schrift: typography.php)
    foreach (zpt_font_roles() as $role => $family) {
        $value = zpt_font_family_value($family, ZPT_FONT_ROLES[$role]['generic']);
        $var   = $role === 'base' ? '--theme-font-family' : '--theme-font-family-' . str_replace('_', '-', $role);

        $vars[$var] = $value;
        if ($role === 'base') {
            $vars['--bs-body-font-family'] = $value;
        } elseif ($role === 'mono') {
            $vars['--bs-font-monospace'] = $value;
        }
    }

    // Schriftgrößen-Skala (typography.php)
    return $vars + zpt_type_scale_vars();
}

/**
 * Freitext-Wert für eine CSS-Variable: getrimmt, ohne Zeichen, die aus der Deklaration ausbrechen.
 */
function zpt_css_value($value): string {
    $value = is_string($value) ? trim($value) : '';
    return preg_match('/[;{}<>\\\\]/', $value) ? '' : $value;
}

// ----------------------------------------------------------------- OUTPUT

/**
 * Alle Style-Einstellungen als :root{…}.
 */
function zpt_style_css(): string {
    $vars = apply_filters('zpt/style/vars', array_merge(zpt_colors_vars(), zpt_typography_vars()));

    $css = '';
    foreach ($vars as $var => $value) {
        $css .= $var . ':' . $value . ';';
    }

    return ($css ? ':root{' . $css . '}' : '') . zpt_grid_css() . zpt_type_scale_css() . zpt_element_css() . zpt_font_classes_css() . zpt_buttons_css() . zpt_navbar_css() . zpt_color_classes_css();
}

// ----------------------------------------------------------------- ZWISCHENSPEICHER
// zpt_style_css() kostet ~10 ms je Aufruf (Frontend + Editor) → als Option zwischengespeichert.
// Neu berechnet, wenn sich eine Einstellung (options_*), die WP-Schriftbibliothek oder der
// Fingerabdruck (Plugin-Code, Theme, Schriften-Ordner im Theme) ändert.
// Abschalten: add_filter('zpt/style/cache', '__return_false');

function zpt_style_css_cached(): string {
    if (!apply_filters('zpt/style/cache', true)) {
        return zpt_style_css();
    }

    $key   = zpt_style_css_fingerprint();
    $cache = get_option('zpt_style_css_cache');
    if (is_array($cache) && ($cache['key'] ?? '') === $key) {
        return (string) $cache['css'];
    }

    $css = zpt_style_css();
    update_option('zpt_style_css_cache', ['key' => $key, 'css' => $css], true);
    return $css;
}

/**
 * Änderungszeiten von Plugin-Code und Feldgruppen, aktives Theme, Schriften-Ordner im Theme.
 */
function zpt_style_css_fingerprint(): string {
    // neueste Änderung zählt (Reihenfolge von glob() hängt von der Locale ab: CLI ≠ Webserver)
    $files = array_merge(glob(ZPT_PATH . 'includes/*.php') ?: [], glob(ZPT_PATH . 'acf-json/*.json') ?: []);
    $parts = [ZPT_VERSION, get_stylesheet(), count($files), max(array_map('filemtime', $files) ?: [0])];
    if ($folder = zpt_font_theme_folder()) {
        $parts[] = (int) @filemtime(get_stylesheet_directory() . '/' . $folder);
    }
    return md5(implode('|', $parts));
}

/**
 * Zwischenspeicher verwerfen – einmal am Ende der Anfrage (ACF speichert hunderte Optionen einzeln).
 */
function zpt_style_css_flush(): void {
    static $queued = false;
    if (!$queued) {
        $queued = true;
        add_action('shutdown', fn() => delete_option('zpt_style_css_cache'));
    }
}

// Einstellungen der Baukasten-Seiten (ACF speichert als options_*; auch Import, CLI)
foreach (['added_option', 'updated_option', 'deleted_option'] as $zpt_hook) {
    add_action($zpt_hook, function ($option) {
        if (str_starts_with($option, 'options_')) {
            zpt_style_css_flush();
        }
    });
}
unset($zpt_hook);

// WP-Schriftbibliothek (Schriften installiert/entfernt)
add_action('save_post', function ($post_id, $post) {
    if (in_array($post->post_type, ['wp_font_family', 'wp_font_face'], true)) {
        zpt_style_css_flush();
    }
}, 10, 2);
add_action('deleted_post', function ($post_id, $post) {
    if ($post && in_array($post->post_type, ['wp_font_family', 'wp_font_face'], true)) {
        zpt_style_css_flush();
    }
}, 10, 2);

// --- Frontend: direkt nach zpt.css, vor dem Theme-CSS
add_action('wp_enqueue_scripts', function () {
    if ($css = zpt_style_css_cached()) {
        wp_add_inline_style(wp_style_is('zpt-baukasten', 'enqueued') ? 'zpt-baukasten' : 'theme_style', $css);
    }
}, 20);

// --- Editor: hinter zpt.css (das als erster Editor-Style eingefügt wird)
add_filter('block_editor_settings_all', function ($settings) {
    if ($css = zpt_style_css_cached()) {
        array_splice($settings['styles'], 1, 0, [[
            'css'            => $css,
            '__unstableType' => 'theme',
            'isGlobalStyles' => false,
        ]]);
    }
    return $settings;
}, 20);

// ----------------------------------------------------------------- META
add_action('wp_head', function () {
    $post_id = is_singular() ? get_queried_object_id() : 0;

    $description = ($post_id ? get_field('meta_description', $post_id) : '')
        ?: get_field('meta_description', 'option')
        ?: get_bloginfo('description');

    $keywords = ($post_id ? get_field('meta_keywords', $post_id) : '')
        ?: get_field('meta_keywords', 'option');

    if ($description) {
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
    }
    if ($keywords) {
        echo '<meta name="keywords" content="' . esc_attr(wp_strip_all_tags($keywords)) . '">' . "\n";
    }
}, 1);
