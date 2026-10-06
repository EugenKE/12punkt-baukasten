<?php
/**
 * Sektionen und Seiten-Templates.
 *
 * - CPT "section": wiederverwendbare Inhaltsbereiche, gebaut mit zpt/*-Blocks.
 *   Direkt aufgerufen (/section/{slug}/): Redaktion sieht die Sektion einzeln (Vorschau, noindex),
 *   Besucher werden auf die Seite umgeleitet, die sie verwendet (sonst Startseite).
 * - Seiten-Template "Block-Seite":     Inhalt der Seite (zpt/row-Blocks).
 * - Seiten-Template "Sektionen-Seite": gewählte Sektionen nacheinander (Feld "sections").
 *
 * Die Templates liegen im Plugin (templates/) und nutzen get_header()/get_footer() des Themes.
 * Seiten mit „Sektionen-Seite“ öffnen im klassischen Editor (Titel, WYSIWYG, Feld „Sektionen“).
 * Wechsel im Block-Editor → nach dem Speichern neu geladen.
 * Ein Theme kann sie überschreiben, indem es eine Datei mit gleichem Namen mitbringt.
 *
 * Felder: acf-json/group_zpt_page_sections.json
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- CPT SEKTION
add_action('init', function () {
    register_post_type('section', [
        'labels' => [
            'name'               => __('Sektionen', '12punkt-baukasten'),
            'singular_name'      => __('Sektion', '12punkt-baukasten'),
            'add_new'            => __('Sektion hinzufügen', '12punkt-baukasten'),
            'add_new_item'       => __('Neue Sektion', '12punkt-baukasten'),
            'edit_item'          => __('Sektion bearbeiten', '12punkt-baukasten'),
            'new_item'           => __('Neue Sektion', '12punkt-baukasten'),
            'all_items'          => __('Sektionen', '12punkt-baukasten'),
            'view_item'          => __('Sektion ansehen', '12punkt-baukasten'),
            'search_items'       => __('Sektionen durchsuchen', '12punkt-baukasten'),
            'not_found'          => __('Keine Sektion gefunden', '12punkt-baukasten'),
            'not_found_in_trash' => __('Keine Sektion im Papierkorb', '12punkt-baukasten'),
            'menu_name'          => __('Sektionen', '12punkt-baukasten'),
        ],
        // nur Bausteine für Seiten: eigene URL nur als Vorschau für die Redaktion (siehe DIREKTAUFRUF)
        'public'              => false,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'exclude_from_search' => true,
        'menu_icon'           => 'dashicons-align-wide',
        'menu_position'       => 21,
        'supports'            => ['title', 'editor', 'thumbnail', 'revisions'],
    ]);
});

// --- Admin-Liste: Bild und ID
add_filter('manage_section_posts_columns', function ($columns) {
    return [
        'cb'          => $columns['cb'],
        'zpt_image'   => __('Bild', '12punkt-baukasten'),
        'title'       => $columns['title'],
        'zpt_post_id' => 'ID',
        'date'        => $columns['date'],
    ];
});

add_action('manage_section_posts_custom_column', function ($column, $post_id) {
    if ($column === 'zpt_image') {
        echo get_the_post_thumbnail($post_id, [60, 60]);
    } elseif ($column === 'zpt_post_id') {
        echo (int) $post_id;
    }
}, 10, 2);

// feste Breite für Bild und ID (sonst teilt WordPress die Breite gleichmäßig auf)
add_action('admin_head-edit.php', function () {
    if (get_current_screen()?->post_type !== 'section') {
        return;
    }
    echo '<style>
        .wp-list-table .column-zpt_image {width: 60px;}
        .wp-list-table .column-zpt_image img {display: block; width: 60px; height: 60px; object-fit: cover;}
        .wp-list-table .column-zpt_post_id {width: 60px;}
    </style>';
});

// ----------------------------------------------------------------- SEITEN-TEMPLATES
// Schlüssel = Wert in _wp_page_template (bestehende Seiten behalten ihr Template)
function zpt_page_templates(): array {
    return apply_filters('zpt/page_templates', [
        'tmpl-blocks.php'        => __('Block-Seite', '12punkt-baukasten'),
        'tmpl-sections_page.php' => __('Sektionen-Seite', '12punkt-baukasten'),
    ]);
}

add_filter('theme_page_templates', fn($templates) => array_merge($templates, zpt_page_templates()));

add_filter('template_include', function ($template) {
    if (!is_singular('page')) {
        return $template;
    }

    $slug = get_page_template_slug();
    if (!isset(zpt_page_templates()[$slug])) {
        return $template;
    }

    return locate_template($slug) ?: ZPT_PATH . 'templates/' . $slug;
});

// --- „Sektionen-Seite“ im klassischen Editor (Titel, WYSIWYG, Feld „Sektionen“) – Gutenberg bleibt sonst aktiv
add_filter('use_block_editor_for_post', function ($use, $post) {
    return ($post && $post->post_type === 'page' && get_page_template_slug($post) === 'tmpl-sections_page.php') ? false : $use;
}, 10, 2);

// Box „Sektionen“ immer sichtbar – nicht über „Ansicht anpassen“ ausblendbar (gespeichert je Benutzer)
add_filter('hidden_meta_boxes', function ($hidden, $screen) {
    return ($screen && $screen->post_type === 'page') ? array_values(array_diff($hidden, ['acf-group_zpt_page_sections'])) : $hidden;
}, 10, 2);

// … und immer an derselben Stelle (unter dem Editor, vor Revisionen): aus gespeicherten Box-Anordnungen nehmen
add_filter('get_user_option_meta-box-order_page', function ($order) {
    if (!is_array($order)) {
        return $order;
    }
    foreach ($order as $context => $boxes) {
        $order[$context] = implode(',', array_diff(explode(',', (string) $boxes), ['acf-group_zpt_page_sections']));
    }
    return $order;
});

// Im Block-Editor auf „Sektionen-Seite“ gewechselt → nach dem Speichern im klassischen Editor neu laden
add_action('enqueue_block_editor_assets', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->post_type !== 'page') {
        return;
    }
    wp_enqueue_script('zpt-editor-sections-page', ZPT_URL . 'assets/js/editor-sections-page.js', ['wp-data', 'wp-dom-ready', 'wp-editor', 'wp-edit-post'], filemtime(ZPT_PATH . 'assets/js/editor-sections-page.js'), true);
});

// ----------------------------------------------------------------- DIREKTAUFRUF
// Redaktion (darf die Sektion bearbeiten): Sektion einzeln mit Header/Footer – auch „Vorschau“ im Editor.
// Alle anderen: 302 auf die Verwendung (zpt_section_target_url()), da sich die Zuordnung ändern kann.

/**
 * Ziel für Besucher: erste veröffentlichte Seite, die die Sektion im Feld "sections" hat (+ Anker),
 * bei der Footer-Sektion die Startseite mit #footer, sonst die Startseite.
 */
function zpt_section_target_url(WP_Post $section): string {
    $pages = get_posts([
        'post_type'      => 'any',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'fields'         => 'ids',
        // ACF speichert die Beziehung serialisiert: a:1:{i:0;s:3:"573";}
        'meta_query'     => [['key' => 'sections', 'value' => '"' . $section->ID . '"', 'compare' => 'LIKE']],
    ]);

    if ($pages) {
        $url = get_permalink($pages[0]) . '#section-' . $section->post_name;
    } elseif (($footer = zpt_footer_section()) && $footer->ID === $section->ID) {
        $url = home_url('/#footer');
    } else {
        $url = home_url('/');
    }

    return apply_filters('zpt/section/target_url', $url, $section);
}

add_action('template_redirect', function () {
    if (!is_singular('section')) {
        return;
    }
    $section = get_queried_object();
    if (current_user_can('edit_post', $section->ID)) {
        return;
    }
    wp_safe_redirect(zpt_section_target_url($section), 302, 'Sektion');
    exit;
});

add_filter('template_include', function ($template) {
    return is_singular('section') ? (locate_template('single-section.php') ?: ZPT_PATH . 'templates/single-section.php') : $template;
}, 20);

// nicht in Suchmaschinen und Sitemap
add_filter('wp_robots', fn($robots) => is_singular('section') ? wp_robots_no_robots($robots) : $robots);
add_filter('wp_sitemaps_post_types', function ($types) {
    unset($types['section']);
    return $types;
});

// ----------------------------------------------------------------- AUSGABE

/**
 * Gibt eine Sektion aus: <section id="…" class="zpt-section …"> + Block-Inhalt.
 * Während der Ausgabe ist die Sektion der aktuelle Post (für die Blocks, z. B. Container „wie Seite“).
 */
function zpt_the_section(WP_Post|int $section): void {
    global $post;

    $section = get_post($section);
    if (!$section || $section->post_type !== 'section') {
        return;
    }
    // Entwürfe nur in der Vorschau der Sektion selbst
    if ($section->post_status !== 'publish' && !(is_preview() && get_queried_object_id() === $section->ID)) {
        return;
    }

    $page = $post;
    $post = $section;
    setup_postdata($post);

    // Hintergrund (Feldgruppe „Hintergrund“, acf-json/group_zpt_section_bg.json)
    $image_bg = get_field('zpt_bg_image_source', $section->ID) === 'theme'
        ? zpt_image_data((string) get_field('zpt_bg_theme_image', $section->ID) ?: 0)
        : get_field('zpt_bg_image', $section->ID);

    $attrs = [
        'id'    => 'section-' . $section->post_name,
        'class' => ['zpt-section', 'zpt-section-' . $section->ID],
        'style' => zpt_background_style(
            (string) get_field('zpt_bg_color', $section->ID),
            is_array($image_bg) ? $image_bg : null,
            (string) get_field('zpt_bg_repeat', $section->ID),
            get_field('zpt_bg_size', $section->ID)
        ),
    ];
    echo '<section' . zpt_html_attrs(apply_filters('zpt/section/attrs', $attrs, $section)) . '>';
    echo apply_filters('the_content', $section->post_content);
    echo '</section>';

    $post = $page;
    if ($post) {
        setup_postdata($post);
    }
}

/**
 * Gibt die im Feld "sections" gewählten Sektionen einer Seite aus.
 */
function zpt_the_sections(?int $page_id = null): void {
    foreach ((array) (get_field('sections', $page_id ?: get_the_ID()) ?: []) as $section) {
        zpt_the_section($section);
    }
}

// ----------------------------------------------------------------- FOOTER
// Bringt das Theme keinen eigenen Footer mit, gibt das Plugin eine Sektion als Footer aus:
// die Sektion mit Haken „Als Footer verwenden“ (Feld zpt_footer), sonst die mit Slug „footer“.
// Eigener Footer im Theme: add_theme_support('zpt-theme-footer');
// Eigene Position im Theme: zpt_the_footer() in footer.php aufrufen (dann keine zweite Ausgabe).

/**
 * Die Footer-Sektion oder null.
 */
function zpt_footer_section(): ?WP_Post {
    $ids = get_posts([
        'post_type'      => 'section',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'modified',
        'fields'         => 'ids',
        'meta_query'     => [['key' => 'zpt_footer', 'value' => '1']],
    ]);

    $section = $ids ? get_post($ids[0]) : get_page_by_path('footer', OBJECT, 'section');
    if ($section && $section->post_status !== 'publish') {
        $section = null;
    }

    return apply_filters('zpt/footer/section', $section);
}

/**
 * Gibt den Footer aus: <footer id="footer" class="zpt-footer"> + Sektion. Nur einmal je Seitenaufruf.
 */
function zpt_the_footer(): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $section = zpt_footer_section();
    // nicht doppelt, wenn die Footer-Sektion selbst angesehen wird
    if (!$section || is_singular('section') && get_queried_object_id() === $section->ID) {
        return;
    }

    echo '<footer id="footer" class="zpt-footer">';
    zpt_the_section($section);
    echo '</footer>';
}

// vor den Footer-Skripten (wp_print_footer_scripts: Priorität 20)
add_action('wp_footer', function () {
    if (current_theme_supports('zpt-theme-footer') || !apply_filters('zpt/footer/enabled', true)) {
        return;
    }
    zpt_the_footer();
}, 0);

// --- nur eine Footer-Sektion: beim Setzen des Hakens die anderen zurücksetzen
add_action('acf/save_post', function ($post_id) {
    if (!is_numeric($post_id) || get_post_type($post_id) !== 'section' || !get_post_meta($post_id, 'zpt_footer', true)) {
        return;
    }
    $others = get_posts([
        'post_type'      => 'section',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post__not_in'   => [(int) $post_id],
        'meta_query'     => [['key' => 'zpt_footer', 'value' => '1']],
    ]);
    foreach ($others as $id) {
        update_field('zpt_footer', 0, $id);
    }
}, 20);

// --- Admin-Liste: Footer-Sektion markieren
add_filter('display_post_states', function ($states, $post) {
    static $footer_id = null;
    if ($post->post_type !== 'section') {
        return $states;
    }
    $footer_id ??= zpt_footer_section()?->ID ?? 0;
    if ($footer_id === $post->ID) {
        $states['zpt_footer'] = __('Footer', '12punkt-baukasten');
    }
    return $states;
}, 10, 2);
