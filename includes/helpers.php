<?php
/**
 * Hilfsfunktionen für Blocks und Komponenten.
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- TEMPLATE LOADER

/**
 * Sucht ein Komponenten-Template. Reihenfolge:
 * 1. Child-Theme / Theme:  {theme}/zpt-baukasten/components/{name}.php
 * 2. Plugin:               templates/components/{name}.php
 */
function zpt_locate_component(string $name): string {
    $theme_file = locate_template('zpt-baukasten/components/' . $name . '.php');
    if ($theme_file) {
        return $theme_file;
    }
    $plugin_file = ZPT_PATH . 'templates/components/' . $name . '.php';
    return file_exists($plugin_file) ? $plugin_file : '';
}

/**
 * Rendert eine Komponente. Im Template steht $args zur Verfügung.
 */
function zpt_component(string $name, array $args = []): void {
    $file = zpt_locate_component($name);
    if (!$file) {
        if (current_user_can('edit_posts')) {
            /* translators: %s: Name der Komponente */
            echo '<div class="message message-error">' . esc_html(sprintf(__('Komponente "%s" nicht gefunden.', '12punkt-baukasten'), $name)) . '</div>';
        }
        return;
    }
    $args = apply_filters('zpt/component/args', $args, $name);
    $args = apply_filters('zpt/component/args/' . $name, $args);
    load_template($file, false, $args);
}

// ----------------------------------------------------------------- HTML ATTRIBUTES

/**
 * Normalisiert Klassen (Strings, Arrays, leere Werte) zu einem Array.
 */
function zpt_class_list(...$classes): array {
    $list = [];
    foreach ($classes as $class) {
        if (is_array($class)) {
            $list = array_merge($list, zpt_class_list(...$class));
        } elseif (is_string($class) && trim($class) !== '') {
            $list = array_merge($list, preg_split('/\s+/', trim($class)));
        }
    }
    return array_values(array_unique($list));
}

/**
 * Baut aus ['id' => '', 'class' => [], 'style' => []] einen Attribut-String.
 * style als assoziatives Array (property => value) oder Liste von Deklarationen.
 * Weitere Attribute (href, target, rel …) werden übernommen; leere Werte entfallen.
 */
function zpt_html_attrs(array $attrs): string {
    $out = '';

    if (!empty($attrs['id'])) {
        $out .= ' id="' . esc_attr($attrs['id']) . '"';
    }

    $classes = zpt_class_list($attrs['class'] ?? []);
    if ($classes) {
        $out .= ' class="' . esc_attr(implode(' ', $classes)) . '"';
    }

    $style = [];
    foreach ($attrs['style'] ?? [] as $prop => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $style[] = is_int($prop) ? rtrim($value, '; ') : $prop . ':' . $value;
    }
    if ($style) {
        $out .= ' style="' . esc_attr(implode(';', $style)) . '"';
    }

    foreach (array_diff_key($attrs, array_flip(['id', 'class', 'style'])) as $name => $value) {
        if ($value === '' || $value === null || $value === false) {
            continue;
        }
        $value = in_array($name, ['href', 'src'], true) ? esc_url($value) : esc_attr($value);
        $out  .= ' ' . sanitize_key($name) . '="' . $value . '"';
    }

    return $out;
}

// ----------------------------------------------------------------- BLOCK ATTRIBUTES

/**
 * Wrapper-Attribute eines Blocks: ID (Feld „CSS ID“, sonst HTML-Anker aus „Erweitert“),
 * "Zusätzliche CSS-Klassen" aus dem Editor und die Einstellungen (Schrift, Hintergrund).
 */
function zpt_block_attrs(array $block, array $classes = [], array $style = [], bool $with_properties = true): array {
    $attrs = [
        'id'    => zpt_css_id(get_field('css_id')) ?: ($block['anchor'] ?? ''),
        'class' => zpt_class_list($classes, $block['className'] ?? ''),
        'style' => $style,
    ];

    if ($with_properties) {
        $props = zpt_properties();
        $attrs['class'] = zpt_class_list($attrs['class'], $props['class']);
        // leere Einstellungen überschreiben keine gesetzten Werte (z. B. Hintergrund der Row)
        $attrs['style'] = array_merge($attrs['style'], array_filter($props['style'], fn($v) => $v !== '' && $v !== null));
    }

    return $attrs;
}

/**
 * ID aus einem Feld: ohne führendes „#“, Leerzeichen → „-“. Ungültig/leer → ''.
 */
function zpt_css_id($value): string {
    $id = preg_replace('/\s+/', '-', ltrim(trim(is_string($value) ? $value : ''), '#'));
    return preg_match('/^[A-Za-z][\w-]*$/', $id) ? $id : '';
}

/**
 * Liest die Felder aus "Template: Komponenten-Einstellungen" (Gruppen "font" und "colors").
 * Blocks ohne eine der Gruppen (Row: kein „colors“, Button: kein „font“) liefern dafür leere Werte.
 */
function zpt_properties(): array {
    $font   = zpt_font_attrs((array) (get_field('font') ?: []));
    $colors = (array) (get_field('colors') ?: []);

    return [
        'class' => $font['class'],
        'style' => [
            'background-color' => zpt_color_value($colors['background_color'] ?? ''),
        ] + $font['style'],
    ];
}

/**
 * Klassen und Stil aus einer Schrift-Gruppe (Felder aus „font“: Schriftrolle, Schriftgröße, Gewicht,
 * Textausrichtung, Schriftfarbe) – für den Block selbst und z. B. die Message.
 */
function zpt_font_attrs(array $font): array {
    $align = $font['text_align'] ?? '';
    return [
        'class' => [
            ($align && $align !== 'none') ? $align : '',
            zpt_font_role_class((string) ($font['zpt_font_role'] ?? '')),
            $font['zpt_font_size_class'] ?? '',
            $font['zpt_font_weight_class'] ?? '',
        ],
        'style' => ['color' => zpt_color_value($font['color'] ?? '')],
    ];
}

// ----------------------------------------------------------------- MESSAGE

/**
 * Message über Bild/Galerie (Gruppe „message“ aus „ZPT Template: Message“) als HTML, '' ohne Text.
 * Position und Breite je Breakpoint → .zpt-msg-{bp}-{position}, .zpt-msg-w-{bp}-{breite}
 * (CSS: scss/components/_message.scss), Innenabstand → .p-{n}, Abstand zum Rand → --zpt-msg-offset.
 * Schrift (Gruppe „font“): Rolle, Gewicht, Farbe; Größe und Ausrichtung je Breakpoint
 * → .font-size-{bp}-{stufe}, .text-{bp}-{start|center|end}.
 */
function zpt_message_html($message, string $classes = ''): string {
    $message = is_array($message) ? $message : [];
    $text    = (string) ($message['text'] ?? '');
    if (trim(wp_strip_all_tags($text)) === '') {
        return '';
    }

    $font  = (array) ($message['font'] ?? []);
    $steps = zpt_type_steps();
    $class = ['zpt-message', zpt_font_role_class((string) ($font['zpt_font_role'] ?? '')), $font['zpt_font_weight_class'] ?? ''];
    foreach (array_merge(['xs'], array_keys(zpt_breakpoint_min())) as $bp) {
        $infix = $bp === 'xs' ? '' : "{$bp}-";
        if ($pos = sanitize_html_class((string) ($message['position']["bp_{$bp}"] ?? ''))) {
            $class[] = "zpt-msg-{$infix}{$pos}";
        }
        if ($width = sanitize_html_class((string) ($message['width']["bp_{$bp}"] ?? ''))) {
            $class[] = "zpt-msg-w-{$infix}{$width}";
        }
        $size = (string) ($font['size']["bp_{$bp}"] ?? '');
        if (isset($steps[$size])) {
            $class[] = "font-size-{$infix}" . zpt_type_css($size);
        }
        $align = (string) ($font['align']["bp_{$bp}"] ?? '');
        if (in_array($align, ['start', 'center', 'end'], true)) {
            $class[] = "text-{$infix}{$align}";
        }
    }
    $padding = (string) ($message['padding'] ?? '');
    $class[] = 'p-' . ($padding === '' ? 20 : (int) $padding);

    $style = [
        'color'            => zpt_color_value($font['color'] ?? ''),
        'background-color' => zpt_color_value($message['background'] ?? ''),
    ];
    if (($offset = (string) ($message['offset'] ?? '')) !== '') {
        $style['--zpt-msg-offset'] = (int) $offset . 'px';
    }

    return '<div' . zpt_html_attrs(['class' => zpt_class_list($class, $classes), 'style' => $style]) . '>'
        . $text . '</div>';
}

/**
 * Bootstrap-Spaltenklassen aus dem Feld "col_widths" (bp_xs … bp_xxl) des Spalten-Blocks.
 */
function zpt_col_width_classes($widths): array {
    $widths  = is_array($widths) ? $widths : [];
    $classes = [];

    foreach (['bp_xs', 'bp_sm', 'bp_md', 'bp_lg', 'bp_xl', 'bp_xxl'] as $bp) {
        $value = $widths[$bp] ?? '';
        if ($value && $value !== 'none') {
            $classes[] = $value;
        }
    }

    // Neu eingefügte Blocks haben noch keine gespeicherten Daten
    return $classes ?: ['col-12'];
}

// ----------------------------------------------------------------- IMAGES

/**
 * Bilddaten für Komponenten (Nachfolger von get_image_details() aus dem Theme).
 */
function zpt_image_data(int $image_id): array {
    $meta = wp_get_attachment_metadata($image_id) ?: [];
    $full = wp_get_attachment_image_src($image_id, 'full');

    $width  = (int) ($meta['width'] ?? ($full[1] ?? 0));
    $height = (int) ($meta['height'] ?? ($full[2] ?? 0));

    $format = 'square';
    if ($width > $height) {
        $format = 'landscape';
    } elseif ($width < $height) {
        $format = 'portrait';
    }

    $title   = get_the_title($image_id);
    $caption = wp_get_attachment_caption($image_id) ?: $title;
    $alt     = get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $caption;

    return [
        'ID'      => $image_id,
        'url'     => $full[0] ?? '',
        'width'   => $width,
        'height'  => $height,
        'format'  => $format,
        'title'   => $title,
        'caption' => $caption,
        'alt'     => $alt,
    ];
}
