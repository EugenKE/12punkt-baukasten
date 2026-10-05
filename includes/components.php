<?php
/**
 * Hilfsfunktionen und Feld-Auswahlen für die Komponenten Galerie, Akkordeon, Card, Icon, Video, Posts.
 *
 * Feldgruppen: acf-json/group_zpt_block_{name}.json, gemeinsames Raster: group_zpt_tpl_per_row.json
 * Markup:      templates/components/{name}.php (im Theme überschreibbar)
 */

defined('ABSPATH') || exit;

// Seitenverhältnisse (Bootstrap .ratio; 3x2 und 9x16 ergänzt in scss/components/_components.scss)
const ZPT_RATIOS = ['1x1', '4x3', '3x2', '16x9', '21x9', '9x16'];

// ----------------------------------------------------------------- RASTER

/**
 * Klassen für ein Raster aus „pro Reihe“ (bp_xs … bp_xxl) und „Abstand“:
 * ['row', 'row-cols-1', 'row-cols-md-3', 'g-20'].
 */
function zpt_per_row_classes($per_row, $gap = ''): array {
    $per_row = is_array($per_row) ? $per_row : [];
    $classes = ['row'];
    foreach (array_merge(['xs'], array_keys(zpt_breakpoint_min())) as $bp) {
        $n = (int) ($per_row["bp_{$bp}"] ?? 0);
        if ($n > 0) {
            $classes[] = $bp === 'xs' ? "row-cols-{$n}" : "row-cols-{$bp}-{$n}";
        }
    }
    if (count($classes) === 1) {
        $classes[] = 'row-cols-1';
    }
    if ((string) $gap !== '') {
        $classes[] = 'g-' . (int) $gap;
    }
    return $classes;
}

/**
 * Container-Klassen für ein festes Seitenverhältnis (Bild füllt die Fläche): 'ratio ratio-4x3 zpt-cover'.
 */
function zpt_ratio_classes(string $ratio): string {
    return in_array($ratio, ZPT_RATIOS, true) ? "ratio ratio-{$ratio} zpt-cover" : '';
}

// ----------------------------------------------------------------- VIDEO

/**
 * Einbettungs-Adresse für YouTube (youtube-nocookie) / Vimeo aus Adresse oder ID. Unbekannt → ''.
 */
function zpt_video_embed_url(string $source, string $value): string {
    $value = trim($value);
    if ($source === 'youtube') {
        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([\w-]{11})~', $value, $m)) {
            $value = $m[1];
        }
        return preg_match('/^[\w-]{11}$/', $value) ? "https://www.youtube-nocookie.com/embed/{$value}?autoplay=1&rel=0" : '';
    }
    if ($source === 'vimeo') {
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $value, $m)) {
            $value = $m[1];
        }
        return preg_match('/^\d+$/', $value) ? "https://player.vimeo.com/video/{$value}?autoplay=1&dnt=1" : '';
    }
    return '';
}

// ----------------------------------------------------------------- SVG

/**
 * SVG-Code bereinigen: nur SVG-Elemente/-Attribute, keine Skripte, keine on…-Attribute, keine javascript:-Links.
 */
function zpt_sanitize_svg(string $svg): string {
    if (!preg_match('~<svg[\s>].*</svg>~is', $svg, $m)) {
        return '';
    }
    $attrs = array_fill_keys([
        'class', 'id', 'style', 'fill', 'fill-rule', 'fill-opacity', 'clip-rule', 'clip-path', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity',
        'opacity', 'transform', 'd', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'width', 'height',
        'points', 'viewbox', 'xmlns', 'xmlns:xlink', 'version', 'preserveaspectratio', 'offset', 'stop-color', 'stop-opacity',
        'gradientunits', 'gradienttransform', 'href', 'xlink:href', 'mask', 'font-family', 'font-size', 'font-weight',
        'text-anchor', 'dominant-baseline', 'role', 'aria-hidden', 'aria-label', 'focusable',
    ], true);
    $elements = ['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'lineargradient',
        'radialgradient', 'stop', 'clippath', 'mask', 'use', 'symbol', 'title', 'desc', 'text', 'tspan'];

    // CSS aus <style> und style="" in Attribute (Illustrator: .st1{fill:#8aaa04}) – wp_kses ließe fill im style nicht stehen
    $svg = zpt_svg_css_to_attrs($m[0], array_keys($attrs));
    // script/style samt Inhalt entfernen (wp_kses ließe den Text stehen)
    $svg = preg_replace('~<(script|style|foreignobject)\b.*?</\1\s*>~is', '', $svg);
    $svg = wp_kses($svg, array_fill_keys($elements, $attrs), ['http', 'https', 'data']);
    // href nur auf interne Verweise (#id) – kein javascript:/externe Ressourcen
    $svg = preg_replace('~\s(?:xlink:)?href="(?!#)[^"]*"~i', '', $svg);
    // wp_kses schreibt Namen klein – als eigene Datei (XML) zählt die Schreibweise (viewBox, linearGradient …)
    foreach (['viewBox', 'preserveAspectRatio', 'gradientUnits', 'gradientTransform', 'linearGradient', 'radialGradient', 'clipPath'] as $name) {
        $svg = preg_replace('~(?<=[\s</])' . strtolower($name) . '(?=[\s=>/])~', $name, $svg);
    }
    return $svg;
}

/**
 * Einfache Klassen-Regeln aus <style> (.st1{…}, .a,.b{…}) und style-Attribute als Präsentations-Attribute
 * an die Elemente schreiben (fill, stroke …). Nur Eigenschaften aus $allowed; CSS geht vor vorhandene Attribute.
 */
function zpt_svg_css_to_attrs(string $svg, array $allowed): string {
    $rules = [];
    if (preg_match_all('~<style\b[^>]*>(.*?)</style\s*>~is', $svg, $m)) {
        $css = preg_replace('~/\*.*?\*/|<!\[CDATA\[|\]\]>~s', '', implode("\n", $m[1]));
        preg_match_all('~([^{}]+)\{([^{}]*)\}~', $css, $blocks, PREG_SET_ORDER);
        foreach ($blocks as [, $selectors, $decl]) {
            foreach (explode(',', $selectors) as $selector) {
                if (preg_match('~^\s*\.([\w-]+)\s*$~', $selector, $c)) {
                    $rules[$c[1]] = ($rules[$c[1]] ?? '') . ';' . $decl;
                }
            }
        }
    }

    return preg_replace_callback('~<([a-z][\w:-]*)(\s[^>]*?)(/?)>~i', function ($tag) use ($rules, $allowed) {
        $attrs = $tag[2];
        $css   = '';
        if ($rules && preg_match('~\sclass="([^"]*)"~i', $attrs, $c)) {
            foreach (preg_split('~\s+~', trim($c[1])) as $class) {
                $css .= ';' . ($rules[$class] ?? '');
            }
        }
        if (preg_match('~\sstyle="([^"]*)"~i', $attrs, $st)) {
            $css .= ';' . $st[1];
            $attrs = str_replace($st[0], '', $attrs);
        }
        foreach (explode(';', $css) as $declaration) {
            [$prop, $value] = array_map('trim', array_pad(explode(':', $declaration, 2), 2, ''));
            $prop = strtolower($prop);
            if ($prop === '' || $value === '' || !in_array($prop, $allowed, true) || preg_match('~[<>"]~', $value)) {
                continue;
            }
            $attrs = preg_replace('~\s' . preg_quote($prop, '~') . '="[^"]*"~i', '', $attrs) . ' ' . $prop . '="' . $value . '"';
        }
        return '<' . $tag[1] . $attrs . $tag[3] . '>';
    }, $svg);
}

// ----------------------------------------------------------------- FELD-AUSWAHLEN

// Card: Button-Stil wie im Button-Block (Varianten bzw. Farben, Link-Stile)
add_filter('acf/load_field/key=field_zpt_card_button_style', function ($field) {
    $field['choices'] = zpt_button_styles();
    return $field;
});

// Posts: öffentliche Inhaltstypen
add_filter('acf/load_field/key=field_zpt_posts_post_type', function ($field) {
    $field['choices'] = [];
    foreach (get_post_types(['public' => true], 'objects') as $type) {
        if ($type->name !== 'attachment') {
            $field['choices'][$type->name] = $type->labels->name;
        }
    }
    return $field;
});

// Message: Schriftgröße je Breakpoint – Stufen der Schriftgrößen-Skala (Typografie)
add_filter('acf/load_field/type=select', function ($field) {
    if (!preg_match('/^field_zpt_msg_font_size_bp_([a-z]+)$/', $field['key'] ?? '', $m)) {
        return $field;
    }
    $field['choices'] = ['' => $m[1] === 'xs' ? __('Standard', '12punkt-baukasten') : __('wie kleiner', '12punkt-baukasten')];
    foreach (zpt_type_steps() as $key => $step) {
        $field['choices'][$key] = $step['name'];
    }
    return $field;
});
