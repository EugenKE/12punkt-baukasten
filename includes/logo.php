<?php
/**
 * Logo: Einstellungsseite Baukasten → Logo & Bilder (Slug zpt-logo) und Ausgabe. Box „Bilder aus dem Theme“: theme-files.php.
 *
 * Box „Logo“:
 * - Hauptlogo (zpt_logo) + Alternativtext (zpt_logo_alt, leer = Seitenname).
 *   Nicht gesetzt → Logo aus dem Customizer (custom_logo).
 * - Varianten (Repeater zpt_logo_variants: Name, Kürzel, Bild), z. B. Weiß, Negativ, Quer, Signet.
 *   Kürzel leer = aus dem Namen („Weiß“ → weiss).
 * Ausgabe: zpt_logo() / zpt_logo('weiss', ['class' => 'h-px-40 w-auto', 'inline' => true]),
 *          Shortcode [zpt_logo variant="weiss" class="…" inline="1"]. Unbekannte Variante → Hauptlogo.
 *
 * Box „Favicon“ – Quelle (zpt_favicon_source):
 * - Mediathek: Favicon-Bild (zpt_favicon, quadratisch, mind. 512 px) → beim Speichern erzeugt nach
 *   uploads/zpt-favicon/: favicon.ico (16/32/48), apple-touch-icon.png (180, Hintergrund zpt_favicon_background),
 *   icon-192.png, icon-512.png, site.webmanifest. Dazu SVG-Favicon (zpt_favicon_svg, optional).
 * - Ordner im Theme (zpt_favicon_folder, z. B. assets/favicons): fertige Dateien mit diesen Namen werden direkt
 *   verwendet, fehlende aus dem größten Rasterbild erzeugt (bevorzugt favicon.png); *.svg = SVG-Favicon.
 *   Geänderte Dateien im Theme werden beim nächsten Aufruf des Admin-Bereichs übernommen.
 * - Theme-Farbe (zpt_theme_color, <meta name="theme-color">).
 * Ersetzt das Website-Icon aus dem Customizer (site_icon), solange ein Favicon gesetzt ist – auch im Admin
 * und auf der Login-Seite; /favicon.ico leitet WordPress auf die erzeugte Datei um.
 *
 * SVG-Upload: Administratoren (manage_options) dürfen SVG in die Mediathek laden; die Datei wird beim
 * Hochladen bereinigt (zpt_sanitize_svg(): keine Skripte, Ereignisse, externen Verweise).
 * Abschalten: add_filter('zpt/svg_upload', '__return_false');
 *
 * Felder: acf-json/group_zpt_settings_logo.json, group_zpt_settings_favicon.json
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- LOGO

/**
 * Alle Logos: ['' => ['name' => 'Logo', 'id' => 12], 'weiss' => ['name' => 'Weiß', 'id' => 13], …].
 * Rohwerte per get_option (auch in acf/load_field nutzbar, kein get_field()).
 */
function zpt_logos(): array {
    static $logos = null;
    if ($logos !== null) {
        return $logos;
    }

    $logos = ['' => ['name' => 'Logo', 'id' => (int) get_option('options_zpt_logo') ?: (int) get_theme_mod('custom_logo')]];

    $count = (int) get_option('options_zpt_logo_variants');
    for ($i = 0; $i < $count; $i++) {
        $name = trim((string) get_option("options_zpt_logo_variants_{$i}_name"));
        $slug = zpt_logo_slug((string) get_option("options_zpt_logo_variants_{$i}_slug") ?: $name);
        $id   = (int) get_option("options_zpt_logo_variants_{$i}_image");
        if ($slug !== '' && $id && !isset($logos[$slug])) {
            $logos[$slug] = ['name' => $name ?: $slug, 'id' => $id];
        }
    }

    return $logos = apply_filters('zpt/logos', $logos);
}

/**
 * Kürzel einer Variante: „Weiß quer“ → weiss-quer.
 */
function zpt_logo_slug(string $name): string {
    return sanitize_title(str_replace(['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'], ['ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue'], trim($name)));
}

/**
 * Attachment-ID eines Logos; unbekannte Variante → Hauptlogo, kein Logo → 0.
 */
function zpt_logo_id(string $variant = ''): int {
    $logos = zpt_logos();
    return (int) ($logos[$variant]['id'] ?? $logos['']['id']);
}

/**
 * Logo als <img> (oder bei SVG mit inline = true als <svg>); '' ohne Logo.
 * $args: class, alt (Standard: Alternativtext der Einstellungen bzw. Seitenname), inline, size.
 */
function zpt_logo(string $variant = '', array $args = []): string {
    $id = zpt_logo_id($variant);
    if (!$id) {
        return '';
    }

    $args = wp_parse_args($args, [
        'class'  => '',
        'alt'    => trim((string) get_option('options_zpt_logo_alt')) ?: get_bloginfo('name'),
        'inline' => false,
        'size'   => 'full',
    ]);
    $variant = isset(zpt_logos()[$variant]) ? $variant : '';
    $class   = trim('zpt-logo ' . ($variant !== '' ? 'zpt-logo-' . sanitize_html_class($variant) . ' ' : '') . $args['class']);

    if ($args['inline'] && get_post_mime_type($id) === 'image/svg+xml') {
        $svg = zpt_sanitize_svg((string) @file_get_contents(get_attached_file($id)));
        if ($svg) {
            // eigene class/role/aria-* nur am <svg> selbst ersetzen
            return preg_replace_callback('~^<svg\b[^>]*>~i', fn($tag) => '<svg class="' . esc_attr($class) . '" role="img" aria-label="' . esc_attr($args['alt']) . '"'
                . substr(preg_replace('~\s(?:class|role|aria-label|aria-hidden)="[^"]*"~i', '', $tag[0]), 4), $svg, 1);
        }
    }

    return wp_get_attachment_image($id, $args['size'], false, ['class' => $class, 'alt' => $args['alt'], 'loading' => false]);
}

// ----------------------------------------------------------------- LOGO: GRÖSSE IN DER NAVIGATIONSLEISTE
// Baukasten → Navigation → Schrift, Farben, Größen → Logo / Name → Tab „Logo“ (Element navbar_brand,
// Feature logo, ZPT_ELEMENTS): feste Höhe oder Breite je Breakpoint (andere Seite auto) oder „Höhe der Navigation“:
// assets/js/navbar-logo.js misst Menüpunkte (mobil den Menü-Button) und setzt --zpt-logo-height.
// Reines CSS geht dafür nicht: die Breite des Logo-Links stünde fest, bevor die gestreckte Höhe bekannt ist.

// Standard ohne Einstellung – wie zuvor die Klassen im Theme (h-px-40 h-px-lg-50 w-auto)
const ZPT_LOGO_SIZE_DEFAULT = ['xs' => 40, 'lg' => 50];

/**
 * Größe des Logos in der Navigationsleiste: ['mode' => 'height'|'width'|'nav', 'sizes' => ['xs' => 40, 'lg' => 50]].
 * Rohwerte per get_option; nur gesetzte Breakpoints, leere erben im CSS vom kleineren.
 */
function zpt_logo_size(): array {
    $mode = (string) zpt_element_value('navbar_brand', 'logo_size_mode');
    $mode = in_array($mode, ['height', 'width', 'nav'], true) ? $mode : 'height';

    $sizes = [];
    foreach (zpt_element_row('navbar_brand', 'logo_size') as $bp => $value) {
        if (is_numeric($value) && $value > 0) {
            $sizes[$bp] = (int) $value;
        }
    }
    if (!$sizes && $mode === 'height') {
        $sizes = ZPT_LOGO_SIZE_DEFAULT;
    }

    return ['mode' => $mode, 'sizes' => $sizes];
}

/**
 * Ausrichtung des Logos in der Leiste als Klasse (mit Leerzeichen davor): ' zpt-logo-align-top' / '-baseline',
 * mittig = '' (Bootstrap-Standard). CSS: scss/components/_components.scss.
 */
function zpt_logo_align_class(): string {
    $align = (string) zpt_element_value('navbar_brand', 'logo_align');
    return in_array($align, ['top', 'baseline'], true) ? ' zpt-logo-align-' . $align : '';
}

/**
 * CSS der Logo-Größe (Teil von zpt_style_css()).
 */
function zpt_logo_css(): string {
    $sel  = '.zpt-navbar .navbar-brand > :is(img, svg)';
    $size = zpt_logo_size();

    if ($size['mode'] === 'nav') {
        // Fallback bis zur ersten Messung: Standardhöhe
        return "{$sel}{height:var(--zpt-logo-height," . ZPT_LOGO_SIZE_DEFAULT['xs'] . "px);width:auto;max-width:none}";
    }

    [$prop, $auto] = $size['mode'] === 'width' ? ['width', 'height'] : ['height', 'width'];
    $min = ['xs' => 0] + zpt_breakpoint_min();
    $css = '';
    foreach ($size['sizes'] as $bp => $px) {
        $rule = "{$sel}{{$prop}:{$px}px;{$auto}:auto;max-width:none}";
        $css .= !empty($min[$bp]) ? "@media (min-width: {$min[$bp]}px){{$rule}}" : $rule;
    }
    return $css;
}

add_shortcode('zpt_logo', function ($atts) {
    $atts = shortcode_atts(['variant' => '', 'class' => '', 'alt' => '', 'inline' => ''], $atts, 'zpt_logo');
    $args = ['class' => $atts['class'], 'inline' => filter_var($atts['inline'], FILTER_VALIDATE_BOOLEAN)];
    if ($atts['alt'] !== '') {
        $args['alt'] = $atts['alt'];
    }
    return zpt_logo(sanitize_key($atts['variant']), $args);
});

// --- Kürzel der Varianten prüfen (eindeutig, nicht leer)
add_filter('acf/validate_value/key=field_zpt_logo_variants_name', function ($valid, $value, $field, $input) {
    if ($valid !== true) {
        return $valid;
    }
    $rows = $_POST['acf']['field_zpt_logo_variants'] ?? [];
    if (!preg_match('~\[([^\]]+)\]\[field_zpt_logo_variants_name\]$~', $input, $m) || !isset($rows[$m[1]])) {
        return $valid;
    }
    $slug_of = fn($row) => zpt_logo_slug((string) wp_unslash($row['field_zpt_logo_variants_slug'] ?? '') ?: (string) wp_unslash($row['field_zpt_logo_variants_name'] ?? ''));

    $slug = $slug_of($rows[$m[1]]);
    if ($slug === '') {
        return __('Name bzw. Kürzel ergibt kein gültiges Kürzel.', '12punkt-baukasten');
    }
    $count = count(array_filter(is_array($rows) ? $rows : [], fn($row) => $slug_of($row) === $slug));
    /* translators: %s: Kürzel der Logo-Variante */
    return $count > 1 ? sprintf(__('Das Kürzel „%s“ ist mehrfach vergeben.', '12punkt-baukasten'), $slug) : true;
}, 10, 4);

// --- Admin: Kürzel-Feld zeigt das Kürzel aus dem Namen als Platzhalter; Bildvorschau auf Karomuster
add_action('acf/input/admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if (!$screen || !str_ends_with($screen->id, '_zpt-logo')) {
        return;
    }
    wp_add_inline_script('acf-input', <<<'JS'
(function ($) {
    const slug = (s) => s.trim().toLowerCase()
        .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    $(document).on('input', '[data-key="field_zpt_logo_variants_name"] input', function () {
        $(this).closest('.acf-row').find('[data-key="field_zpt_logo_variants_slug"] input').attr('placeholder', slug(this.value));
    });
    $(function () {
        $('[data-key="field_zpt_logo_variants_name"] input').trigger('input');
    });
})(jQuery);
JS);
    wp_add_inline_style('acf-input', '
        .acf-field[data-name="zpt_logo"] .image-wrap img, .acf-field-repeater[data-name="zpt_logo_variants"] .image-wrap img,
        .acf-field[data-name="zpt_favicon"] .image-wrap img, .acf-field[data-name="zpt_favicon_svg"] .image-wrap img {
            max-height: 120px !important; width: auto !important; padding: 10px;
            background: repeating-conic-gradient(#e5e5e5 0 25%, #fff 0 50%) 0 0 / 16px 16px;}
        .zpt-favicon-preview {display: flex; flex-wrap: wrap; align-items: flex-end; gap: 20px;}
        .zpt-favicon-preview figure {margin: 0; text-align: center; font-size: 12px; color: #646970;}
        .zpt-favicon-preview img {display: block; margin: 0 auto 4px; background: repeating-conic-gradient(#e5e5e5 0 25%, #fff 0 50%) 0 0 / 16px 16px;}
    ');
});

// ----------------------------------------------------------------- FAVICON

/**
 * Ordner der erzeugten Favicons: ['dir' => …/uploads/zpt-favicon, 'url' => …].
 */
function zpt_favicon_dir(): array {
    $uploads = wp_upload_dir(null, false);
    return ['dir' => $uploads['basedir'] . '/zpt-favicon', 'url' => set_url_scheme($uploads['baseurl'] . '/zpt-favicon')];
}

/**
 * Einstellungen der Box Favicon (Rohwerte, Farben als Hex).
 */
function zpt_favicon_settings(): array {
    return [
        'source'     => get_option('options_zpt_favicon_source') === 'theme' ? 'theme' : 'media',
        'folder'     => zpt_theme_folder((string) get_option('options_zpt_favicon_folder')),
        'image'      => (int) get_option('options_zpt_favicon'),
        'svg'        => (int) get_option('options_zpt_favicon_svg'),
        'background' => zpt_favicon_color((string) get_option('options_zpt_favicon_background')) ?: '#ffffff',
        'theme'      => zpt_favicon_color((string) get_option('options_zpt_theme_color')),
    ];
}

/**
 * Wert eines Farbfelds → Hexwert (Baukasten-Farbe, Farbname, Hex); ungültig → ''.
 */
function zpt_favicon_color(string $value): string {
    $value = strtolower(trim($value));
    if (isset(zpt_color_tokens()[$value])) {
        return zpt_color_tokens()[$value];
    }
    $rgb = zpt_color_to_rgb($value);
    return $rgb ? zpt_rgb_to_hex($rgb) : '';
}

/**
 * Stand der erzeugten Dateien (Option zpt_favicon): ['key' => Fingerabdruck, 'files' => [Name => URL], 'v' => Version].
 */
function zpt_favicon_state(): array {
    $state = get_option('zpt_favicon');
    return is_array($state) ? $state : [];
}

/**
 * Dateien im Favicon-Ordner des Themes, nach Rolle: ['source' => Pfad, 'favicon.ico' => Pfad, …].
 * Fertige Dateien (favicon.ico, favicon.svg, apple-touch-icon.png, icon-192.png, icon-512.png,
 * site.webmanifest) werden direkt verwendet; fehlende werden aus dem größten Rasterbild erzeugt
 * (bevorzugt favicon.png). Ein einzelnes favicon.png (≥ 512 px) reicht also.
 */
function zpt_favicon_theme_files(string $folder): array {
    $files = [];
    $best  = 0;
    foreach (zpt_theme_files($folder, ['ico', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'webmanifest', 'json']) as $rel => $path) {
        $name = strtolower(basename($rel));
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($name, ['favicon.ico', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png'], true)) {
            $files[$name] = $path;
        } elseif (in_array($name, ['site.webmanifest', 'manifest.webmanifest', 'manifest.json'], true)) {
            $files['manifest'] ??= $path;
        } elseif ($ext === 'svg' && ($name === 'favicon.svg' || !isset($files['favicon.svg']))) {
            $files['favicon.svg'] = $path;
        }
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $width = zpt_image_file_size($path)[0] + ($name === 'favicon.png' ? 100000 : 0);
            if ($width > $best) {
                [$best, $files['source']] = [$width, $path];
            }
        }
    }
    return $files;
}

/**
 * URL einer Datei im Theme (absoluter Pfad → URL).
 */
function zpt_favicon_theme_url(string $path): string {
    foreach (zpt_theme_roots() as $dir => $url) {
        if (str_starts_with($path, $dir . '/')) {
            return $url . '/' . implode('/', array_map('rawurlencode', explode('/', substr($path, strlen($dir) + 1))));
        }
    }
    return '';
}

/**
 * Favicon-Dateien erzeugen, wenn sich Quelle, Bild, Theme-Dateien, Hintergrund, Theme-Farbe oder Seitenname geändert haben.
 */
function zpt_favicon_generate(bool $force = false): void {
    $settings = zpt_favicon_settings();
    $theme    = $settings['source'] === 'theme' && $settings['folder'] !== '' ? zpt_favicon_theme_files($settings['folder']) : [];
    $file     = $settings['source'] === 'theme' ? ($theme['source'] ?? '') : ($settings['image'] ? (string) get_attached_file($settings['image']) : '');

    $fingerprint = [$settings['source'], $file, $file ? (int) @filemtime($file) : 0, $settings['background'], $settings['theme'], get_bloginfo('name')];
    if ($settings['source'] === 'theme') {
        foreach ($theme as $path) {
            $fingerprint[] = $path . ':' . (int) @filemtime($path);
        }
    } else {
        $fingerprint[] = $settings['svg'];
    }
    $key   = md5(implode('|', $fingerprint));
    $state = zpt_favicon_state();
    if (!$force && ($state['key'] ?? '') === $key) {
        return;
    }

    $dir = zpt_favicon_dir();
    foreach (glob($dir['dir'] . '/*') ?: [] as $old) {
        @unlink($old);
    }

    // fertige Dateien aus dem Theme
    $files = [];
    foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'manifest'] as $name) {
        if (isset($theme[$name])) {
            $files[$name] = zpt_favicon_theme_url($theme[$name]);
        }
    }
    if ($settings['source'] === 'media' && $settings['svg'] && ($svg = wp_get_attachment_url($settings['svg']))) {
        $files['favicon.svg'] = $svg;
    }

    // fehlende erzeugen
    $src = ($file && is_readable($file) && function_exists('imagecreatefromstring')) ? @imagecreatefromstring((string) file_get_contents($file)) : false;
    if ($src && wp_mkdir_p($dir['dir'])) {
        $create = [
            'favicon.ico'          => fn() => zpt_favicon_ico([16 => zpt_favicon_png($src, 16), 32 => zpt_favicon_png($src, 32), 48 => zpt_favicon_png($src, 48)]),
            'apple-touch-icon.png' => fn() => zpt_favicon_png($src, 180, $settings['background']),
            'icon-192.png'         => fn() => zpt_favicon_png($src, 192),
            'icon-512.png'         => fn() => zpt_favicon_png($src, 512),
        ];
        foreach ($create as $name => $data) {
            if (!isset($files[$name]) && ($data = $data()) !== '' && file_put_contents($dir['dir'] . '/' . $name, $data)) {
                $files[$name] = $dir['url'] . '/' . $name;
            }
        }
    }

    if (!isset($files['manifest']) && (isset($files['icon-192.png']) || isset($files['icon-512.png']))) {
        $manifest = [
            'name'             => get_bloginfo('name'),
            'short_name'       => get_bloginfo('name'),
            'icons'            => array_values(array_filter([
                isset($files['icon-192.png']) ? ['src' => $files['icon-192.png'], 'sizes' => '192x192', 'type' => 'image/png'] : null,
                isset($files['icon-512.png']) ? ['src' => $files['icon-512.png'], 'sizes' => '512x512', 'type' => 'image/png'] : null,
            ])),
            'theme_color'      => $settings['theme'] ?: null,
            'background_color' => $settings['background'],
            'display'          => 'browser',
        ];
        if (wp_mkdir_p($dir['dir']) && file_put_contents($dir['dir'] . '/site.webmanifest', wp_json_encode(array_filter($manifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))) {
            $files['manifest'] = $dir['url'] . '/site.webmanifest';
        }
    }

    update_option('zpt_favicon', ['key' => $key, 'files' => $files, 'v' => substr($key, 0, 8)], true);
}

/**
 * Quadratisches PNG aus dem Bild (nicht quadratisch → mittig zugeschnitten), optional auf Hintergrundfarbe.
 */
function zpt_favicon_png($src, int $size, string $background = ''): string {
    $w    = imagesx($src);
    $h    = imagesy($src);
    $side = min($w, $h);

    $dst = imagecreatetruecolor($size, $size);
    if ($background && ($rgb = zpt_color_to_rgb($background))) {
        imagefill($dst, 0, 0, imagecolorallocate($dst, ...$rgb));
    } else {
        imagealphablending($dst, false);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $size, $size, $side, $side);
    imagesavealpha($dst, true);

    ob_start();
    imagepng($dst, null, 9);
    imagedestroy($dst);
    return (string) ob_get_clean();
}

/**
 * ICO-Datei aus PNGs: [16 => PNG-Daten, 32 => …] (PNG im ICO-Container, alle aktuellen Browser).
 */
function zpt_favicon_ico(array $pngs): string {
    $pngs   = array_filter($pngs);
    $header = pack('vvv', 0, 1, count($pngs));
    $dir    = '';
    $data   = '';
    $offset = 6 + 16 * count($pngs);
    foreach ($pngs as $size => $png) {
        $dir    .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($png), $offset);
        $data   .= $png;
        $offset += strlen($png);
    }
    return $header . $dir . $data;
}

/**
 * URL einer erzeugten Datei ('' wenn nicht vorhanden).
 */
function zpt_favicon_url(string $name): string {
    $state = zpt_favicon_state();
    $file  = (string) ($state['files'][$name] ?? '');
    if ($file === '') {
        return '';
    }
    // ältere Stände speichern nur den Dateinamen im Upload-Ordner
    return (str_contains($file, '/') ? $file : zpt_favicon_dir()['url'] . '/' . $file) . '?v=' . $state['v'];
}

/**
 * Ist ein eigenes Favicon (Bild oder SVG) gesetzt?
 */
function zpt_has_favicon(): bool {
    return zpt_favicon_url('favicon.ico') !== '' || zpt_favicon_url('favicon.svg') !== '';
}

/**
 * <link>/<meta>-Tags für den <head>.
 */
function zpt_favicon_tags(): string {
    $tags = [];
    if ($ico = zpt_favicon_url('favicon.ico')) {
        $tags[] = '<link rel="icon" href="' . esc_url($ico) . '" sizes="any">';
    }
    if ($svg = zpt_favicon_url('favicon.svg')) {
        $tags[] = '<link rel="icon" href="' . esc_url($svg) . '" type="image/svg+xml">';
    }
    if ($apple = zpt_favicon_url('apple-touch-icon.png')) {
        $tags[] = '<link rel="apple-touch-icon" href="' . esc_url($apple) . '">';
    }
    if ($manifest = zpt_favicon_url('manifest')) {
        $tags[] = '<link rel="manifest" href="' . esc_url($manifest) . '">';
    }
    if ($theme = zpt_favicon_settings()['theme']) {
        $tags[] = '<meta name="theme-color" content="' . esc_attr($theme) . '">';
    }
    return $tags ? implode("\n", $tags) . "\n" : '';
}

// --- Dateien neu erzeugen, sobald sich etwas geändert hat (prüft nur den Fingerabdruck): nach dem
// Speichern der Seite (Weiterleitung), nach Import, neuem Seitennamen, neuer Bilddatei
add_action('admin_init', fn() => zpt_favicon_generate());

// --- Ausgabe: ersetzt das Website-Icon von WordPress, solange ein eigenes Favicon gesetzt ist
add_action('init', function () {
    foreach (['wp_head', 'admin_head', 'login_head'] as $hook) {
        if (zpt_has_favicon()) {
            remove_action($hook, 'wp_site_icon', 99);
        }
        add_action($hook, function () {
            echo zpt_favicon_tags();
        }, 99);
    }
});

// /favicon.ico (do_favicon()) und andere Abfragen des Website-Icons → erzeugte Dateien
add_filter('get_site_icon_url', function ($url, $size) {
    if ($size <= 48 && ($ico = zpt_favicon_url('favicon.ico'))) {
        return $ico;
    }
    return zpt_favicon_url($size <= 192 ? 'icon-192.png' : 'icon-512.png') ?: $url;
}, 10, 2);

// --- Ordner im Theme: bereinigen, Theme-Name davor, gefundene Dateien anzeigen
add_filter('acf/update_value/key=field_zpt_favicon_folder', fn($value) => zpt_theme_folder((string) $value));
add_filter('acf/prepare_field/key=field_zpt_favicon_folder', function ($field) {
    $field['prepend'] = wp_get_theme()->get_stylesheet() . '/';
    $folder = zpt_theme_folder((string) $field['value']);
    if ($folder !== '') {
        $found  = zpt_favicon_theme_files($folder);
        $source = $found['source'] ?? '';
        $files  = array_map('basename', array_diff_key($found, ['source' => 1]));
        $field['instructions'] .= '<br>' . ($files || $source
            /* translators: 1: gefundene Dateien, 2: Bild, aus dem fehlende Dateien erzeugt werden */
            ? sprintf(__('Gefunden: %1$s. Fehlende Dateien werden erzeugt aus: %2$s', '12punkt-baukasten'), esc_html(implode(', ', $files) ?: '–'), esc_html($source ? basename($source) : '–'))
            : '<strong>' . __('Keine passenden Dateien gefunden.', '12punkt-baukasten') . '</strong>');
    }
    return $field;
});

// --- Vorschau der erzeugten Dateien in der Box
add_filter('acf/load_field/key=field_zpt_favicon_preview', function ($field) {
    $items = [];
    foreach (['favicon.ico' => [32, 'favicon.ico'], 'favicon.svg' => [32, 'favicon.svg'], 'apple-touch-icon.png' => [90, 'Apple 180 × 180'], 'icon-192.png' => [96, '192 × 192'], 'icon-512.png' => [128, '512 × 512']] as $name => [$px, $label]) {
        if ($url = zpt_favicon_url($name)) {
            $items[] = '<figure><img src="' . esc_url($url) . '" width="' . $px . '" height="' . $px . '" alt="">' . esc_html($label) . '</figure>';
        }
    }
    $field['message'] = $items
        ? '<div class="zpt-favicon-preview">' . implode('', $items) . '</div>'
        : __('Noch keine Dateien – Favicon-Bild bzw. Ordner wählen und speichern.', '12punkt-baukasten');
    return $field;
});

// ----------------------------------------------------------------- SVG-UPLOAD

function zpt_svg_upload_allowed(): bool {
    return apply_filters('zpt/svg_upload', true) && current_user_can('manage_options');
}

add_filter('upload_mimes', function ($mimes) {
    if (zpt_svg_upload_allowed()) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
});

// WordPress erkennt SVG nicht als Bild → Typ anhand der Endung bestätigen
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename) {
    if (zpt_svg_upload_allowed() && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'svg') {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 3);

// Beim Hochladen bereinigen (Formular-Upload und Sideload/REST)
$zpt_svg_prefilter = function ($file) {
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'svg' || !zpt_svg_upload_allowed()) {
        return $file;
    }
    $svg = zpt_sanitize_svg((string) file_get_contents($file['tmp_name']));
    if ($svg === '') {
        $file['error'] = __('Die Datei enthält kein gültiges SVG.', '12punkt-baukasten');
    } else {
        file_put_contents($file['tmp_name'], $svg);
    }
    return $file;
};
add_filter('wp_handle_upload_prefilter', $zpt_svg_prefilter);
add_filter('wp_handle_sideload_prefilter', $zpt_svg_prefilter);
unset($zpt_svg_prefilter);

/**
 * Breite/Höhe eines SVG aus width/height bzw. viewBox: [w, h] oder [0, 0].
 */
function zpt_svg_size(string $file): array {
    $svg = (string) @file_get_contents($file, false, null, 0, 4096);
    if (!preg_match('~<svg\b[^>]*>~is', $svg, $tag)) {
        return [0, 0];
    }
    $attr = fn($name) => preg_match('~\s' . $name . '="([^"]*)"~i', $tag[0], $m) ? $m[1] : '';
    $w    = (float) $attr('width');
    $h    = (float) $attr('height');
    if ((!$w || !$h || str_contains($attr('width') . $attr('height'), '%')) && ($box = preg_split('~[\s,]+~', trim($attr('viewBox')))) && count($box) === 4) {
        [$w, $h] = [(float) $box[2], (float) $box[3]];
    }
    return [(int) round($w), (int) round($h)];
}

// Maße in den Metadaten (Mediathek, ACF-Vorschau, width/height am <img>)
add_filter('wp_generate_attachment_metadata', function ($meta, $id) {
    if (get_post_mime_type($id) === 'image/svg+xml') {
        [$w, $h] = zpt_svg_size((string) get_attached_file($id));
        $meta    = array_merge((array) $meta, ['width' => $w, 'height' => $h, 'file' => _wp_relative_upload_path((string) get_attached_file($id))]);
    }
    return $meta;
}, 10, 2);

// wp_get_attachment_image() & Co. liefern bei SVG sonst nichts
add_filter('wp_get_attachment_image_src', function ($image, $id) {
    if (!$image && get_post_mime_type($id) === 'image/svg+xml') {
        $meta = wp_get_attachment_metadata($id) ?: [];
        if (empty($meta['width'])) {
            [$meta['width'], $meta['height']] = zpt_svg_size((string) get_attached_file($id));
        }
        return [wp_get_attachment_url($id), (int) $meta['width'], (int) $meta['height'], false];
    }
    return $image;
}, 10, 2);

// Mediathek: SVG als Bild mit Vorschau
add_filter('wp_prepare_attachment_for_js', function ($response, $attachment) {
    if (($response['mime'] ?? '') === 'image/svg+xml') {
        $src = wp_get_attachment_image_src($attachment->ID, 'full');
        $full = ['url' => $response['url'], 'width' => $src[1] ?? 0, 'height' => $src[2] ?? 0, 'orientation' => ($src[2] ?? 0) > ($src[1] ?? 0) ? 'portrait' : 'landscape'];
        $response['image'] = ['src' => $response['url'], 'width' => $full['width'], 'height' => $full['height']];
        $response['sizes'] = ['full' => $full, 'medium' => $full, 'thumbnail' => $full];
        $response['width'] = $full['width'];
        $response['height'] = $full['height'];
    }
    return $response;
}, 10, 2);
