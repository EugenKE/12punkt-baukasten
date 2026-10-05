<?php
/**
 * Styleguide: Seiten-Template zum Prüfen der Style-Einstellungen (Typografie, Farben, Abstände, Buttons).
 *
 * Seite mit Template „Styleguide“ anlegen und im Feld „Bereich“ wählen, was sie zeigt.
 * Die Beispiele nutzen die echten Klassen; ein kleines Skript zeigt daneben die im Browser
 * berechneten Werte an (Größe, Zeilenhöhe, Laufweite, Farbe, Abstand).
 *
 * Felder: acf-json/group_zpt_page_styleguide.json
 * Ausgabe: templates/tmpl-styleguide.php, templates/styleguide/*.php
 */

defined('ABSPATH') || exit;

const ZPT_STYLEGUIDE_SECTIONS = [
    'overview'   => 'Übersicht',
    'typography' => 'Typografie',
    'colors'     => 'Farben',
    'spacing'    => 'Abstände',
    'buttons'    => 'Buttons',
];

add_filter('zpt/page_templates', fn($templates) => $templates + ['tmpl-styleguide.php' => __('Styleguide', '12punkt-baukasten')]);

// Styleguide-Seiten nicht in Suchmaschinen
add_filter('wp_robots', function ($robots) {
    if (is_page() && get_page_template_slug() === 'tmpl-styleguide.php') {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
    }
    return $robots;
});

/**
 * Bereich der aktuellen Styleguide-Seite.
 */
function zpt_styleguide_section(): string {
    $section = (string) get_field('zpt_styleguide_section');
    return isset(ZPT_STYLEGUIDE_SECTIONS[$section]) ? $section : 'overview';
}

/**
 * Ausgabe-Platzhalter für einen gemessenen Wert.
 *
 * @param string $measure font | color | padding | margin | width | button
 */
function zpt_sg_out(string $measure): string {
    return '<code class="zpt-sg-out small text-body-secondary" data-sg-measure="' . esc_attr($measure) . '">…</code>';
}

/**
 * Alle Styleguide-Seiten: [bereich => Seiten-ID], in der Reihenfolge der Bereiche.
 */
function zpt_styleguide_pages(): array {
    $ids = get_posts([
        'post_type'      => 'page',
        'post_status'    => ['publish', 'private'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'tmpl-styleguide.php',
    ]);

    $pages = [];
    foreach ($ids as $id) {
        $section = (string) get_field('zpt_styleguide_section', $id) ?: 'overview';
        $pages[$section] ??= $id;
    }

    return array_intersect_key(array_replace(ZPT_STYLEGUIDE_SECTIONS, $pages), $pages);
}
