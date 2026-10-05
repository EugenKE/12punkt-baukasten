<?php
/**
 * Dateien aus dem Theme: Was zum Theme gehört und nicht redaktionell verwaltet wird (Favicons,
 * Illustrationen, Icons), kann statt aus der Mediathek aus einem Ordner im Theme kommen.
 * Child-Theme geht vor Eltern-Theme.
 *
 * Bilder aus dem Theme (Box „Bilder aus dem Theme“ auf Baukasten → Logo & Bilder):
 * - Ordner (zpt_theme_images_folder, z. B. assets/img), mit Unterordnern; png, jpg, gif, webp, avif, svg.
 * - Bild-, Card- und Row-Block: Quelle „Theme“ → Auswahl aus diesem Ordner. Gespeichert wird der Pfad
 *   relativ zum Theme (assets/img/illustrationen/haus.svg), damit ein geänderter Ordner nichts verschiebt.
 * - zpt_image_data() / zpt_image_html() nehmen Attachment-ID oder diesen Pfad.
 *
 * Felder: acf-json/group_zpt_settings_theme_images.json
 */

defined('ABSPATH') || exit;

/**
 * Bilddateien, die aus dem Theme gewählt werden können.
 */
const ZPT_THEME_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg'];

/**
 * Ordner im Theme bereinigen: relativ, ohne „..“, ohne Schrägstriche außen.
 */
function zpt_theme_folder(string $folder): string {
    $folder = trim(str_replace('\\', '/', $folder), "/ \t");
    return preg_match('~(^|/)\.\.(/|$)~', $folder) ? '' : $folder;
}

/**
 * Theme-Ordner, Child-Theme zuerst: [Pfad => URL].
 */
function zpt_theme_roots(): array {
    return array_unique([
        get_stylesheet_directory() => get_stylesheet_directory_uri(),
        get_template_directory()   => get_template_directory_uri(),
    ]);
}

/**
 * Datei im Theme (Child vor Eltern): ['path' => …, 'url' => …] oder [] wenn nicht vorhanden.
 */
function zpt_theme_file(string $rel): array {
    $rel = zpt_theme_folder($rel);
    if ($rel === '') {
        return [];
    }
    foreach (zpt_theme_roots() as $dir => $url) {
        if (is_file($dir . '/' . $rel)) {
            return ['path' => $dir . '/' . $rel, 'url' => $url . '/' . implode('/', array_map('rawurlencode', explode('/', $rel)))];
        }
    }
    return [];
}

/**
 * Dateien eines Ordners im Theme (Child + Eltern zusammengeführt, Child geht vor):
 * [Pfad relativ zum Theme => absoluter Pfad], natürlich sortiert.
 */
function zpt_theme_files(string $folder, array $extensions = [], bool $recursive = false): array {
    $folder = zpt_theme_folder($folder);
    if ($folder === '') {
        return [];
    }
    $files = [];
    foreach (array_reverse(array_keys(zpt_theme_roots())) as $dir) {
        if (!is_dir($dir . '/' . $folder)) {
            continue;
        }
        $iterator = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir . '/' . $folder, FilesystemIterator::SKIP_DOTS))
            : new FilesystemIterator($dir . '/' . $folder);
        foreach ($iterator as $file) {
            $name = $file->getFilename();
            if (!$file->isFile() || $name[0] === '.' || ($extensions && !in_array(strtolower($file->getExtension()), $extensions, true))) {
                continue;
            }
            $files[substr(str_replace('\\', '/', $file->getPathname()), strlen($dir) + 1)] = $file->getPathname();
        }
    }
    uksort($files, 'strnatcasecmp');
    return $files;
}

/**
 * Ist das ein Bild aus dem Theme (Pfad) statt einer Attachment-ID?
 */
function zpt_is_theme_image($image): bool {
    return is_string($image) && $image !== '' && !ctype_digit($image);
}

// ----------------------------------------------------------------- BILDER AUS DEM THEME

/**
 * Ordner der Theme-Bilder (Einstellung), '' = keiner.
 */
function zpt_theme_images_folder(): string {
    return zpt_theme_folder((string) get_option('options_zpt_theme_images_folder', ''));
}

/**
 * Wählbare Bilder: [Pfad relativ zum Theme => absoluter Pfad].
 */
function zpt_theme_images(): array {
    static $images = null;
    return $images ??= zpt_theme_files(zpt_theme_images_folder(), ZPT_THEME_IMAGE_EXTENSIONS, true);
}

/**
 * Breite/Höhe einer Bilddatei: [w, h] oder [0, 0].
 */
function zpt_image_file_size(string $file): array {
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg') {
        return zpt_svg_size($file);
    }
    $size = @getimagesize($file);
    return $size ? [(int) $size[0], (int) $size[1]] : [0, 0];
}

/**
 * Daten eines Theme-Bilds im Format von zpt_image_data() (ID 0); [] wenn die Datei fehlt.
 * Alternativtext: $alt, sonst aus dem Dateinamen („haus-am-see.svg“ → „Haus am see“).
 */
function zpt_theme_image_data(string $rel, string $alt = ''): array {
    $file = zpt_theme_file($rel);
    if (!$file) {
        return [];
    }
    [$width, $height] = zpt_image_file_size($file['path']);
    $title = ucfirst(trim(preg_replace('~[-_\s]+~', ' ', pathinfo($rel, PATHINFO_FILENAME))));

    return [
        'ID'      => 0,
        'url'     => $file['url'] . '?v=' . (int) @filemtime($file['path']),
        'width'   => $width,
        'height'  => $height,
        'format'  => $width > $height ? 'landscape' : ($width < $height ? 'portrait' : 'square'),
        'title'   => $title,
        'caption' => '',
        'alt'     => $alt !== '' ? $alt : $title,
    ];
}

/**
 * <img> für Attachment-ID oder Theme-Bild. $attrs wie bei wp_get_attachment_image().
 */
function zpt_image_html($image, string $size = 'large', array $attrs = []): string {
    if (!zpt_is_theme_image($image)) {
        return wp_get_attachment_image((int) $image, $size, false, $attrs);
    }
    $data = zpt_theme_image_data($image, (string) ($attrs['alt'] ?? ''));
    if (!$data) {
        return '';
    }
    unset($attrs['sizes'], $attrs['srcset']);
    return '<img' . zpt_html_attrs(array_merge([
        'src'      => $data['url'],
        'width'    => $data['width'] ?: null,
        'height'   => $data['height'] ?: null,
        'alt'      => $data['alt'],
        'loading'  => 'lazy',
        'decoding' => 'async',
    ], $attrs)) . '>';
}

// --- Auswahlfelder der Blocks („Quelle: Theme“): Klasse zpt-theme-image am Feld
add_filter('acf/load_field/type=select', function ($field) {
    if (!str_contains((string) ($field['wrapper']['class'] ?? ''), 'zpt-theme-image') || is_admin() && ($GLOBALS['typenow'] ?? '') === 'acf-field-group') {
        return $field;
    }
    $folder  = zpt_theme_images_folder();
    $choices = [];
    foreach (array_keys(zpt_theme_images()) as $rel) {
        $choices[$rel] = substr($rel, strlen($folder) + 1);
    }
    $field['choices'] = $choices;
    if (!$choices) {
        $field['instructions'] = $folder === ''
            ? __('Noch kein Ordner eingestellt: Baukasten → Logo & Bilder → Bilder aus dem Theme.', '12punkt-baukasten')
            /* translators: %s: Ordner im Theme */
            : sprintf(__('Keine Bilder in „%s“ gefunden.', '12punkt-baukasten'), $folder);
    }
    return $field;
});

// --- Auswahl mit Vorschaubild (select2), im Block-Editor
add_action('acf/input/admin_enqueue_scripts', function () {
    $urls = [];
    foreach (array_keys(zpt_theme_images()) as $rel) {
        $urls[$rel] = zpt_theme_file($rel)['url'] ?? '';
    }
    wp_add_inline_script('acf-input', 'window.zptThemeImages = ' . wp_json_encode($urls) . ';' . <<<'JS'
(function () {
    if (!window.acf) return;
    const item = (state) => {
        const url = state.id && window.zptThemeImages[state.id];
        if (!url) return state.text;
        const el = document.createElement('span');
        el.className = 'zpt-theme-image-option';
        el.innerHTML = '<img alt="" loading="lazy"><span></span>';
        el.firstChild.src = url;
        el.lastChild.textContent = state.text;
        return el;
    };
    acf.add_filter('select2_args', function (args, $select, settings, field) {
        if (field && field.$el && field.$el.hasClass('zpt-theme-image')) {
            args.templateResult = item;
            args.templateSelection = item;
        }
        return args;
    });
})();
JS);
    wp_add_inline_style('acf-input', '
        .zpt-theme-image-option {display: inline-flex; align-items: center; gap: 8px; vertical-align: middle;}
        .zpt-theme-image-option img {width: 32px; height: 32px; object-fit: contain; flex: none;
            background: repeating-conic-gradient(#e5e5e5 0 25%, #fff 0 50%) 0 0 / 8px 8px;}
    ');
});

// --- Einstellung: Ordner bereinigen, gefundene Bilder anzeigen
add_filter('acf/update_value/key=field_zpt_theme_images_folder', fn($value) => zpt_theme_folder((string) $value));
add_filter('acf/prepare_field/key=field_zpt_theme_images_folder', function ($field) {
    $field['prepend'] = wp_get_theme()->get_stylesheet() . '/';
    $folder = zpt_theme_folder((string) $field['value']);
    if ($folder !== '') {
        $count = count(zpt_theme_files($folder, ZPT_THEME_IMAGE_EXTENSIONS, true));
        /* translators: %d: Anzahl gefundener Bilder */
        $field['instructions'] .= $count ? ' – ' . sprintf(_n('gefunden: %d Bild.', 'gefunden: %d Bilder.', $count, '12punkt-baukasten'), $count) : ' – <strong>' . __('keine Bilder gefunden.', '12punkt-baukasten') . '</strong>';
    }
    return $field;
});
