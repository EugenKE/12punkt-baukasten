<?php
/**
 * CSS und JS des Baukastens.
 *
 * Frontend:  zpt.css (Bootstrap 5.3 + Baukasten) und Bootstrap JS werden immer geladen.
 *            Photoswipe, Masonry, Video- und Sprungmarken-Skript nur, wenn ein Block sie braucht (zpt_enqueue()).
 * Editor:    zpt.css wird als Editor-Style übergeben (WordPress scoped es auf den Inhaltsbereich).
 *
 * Theme mit eigenem Sass-Build (importiert scss/zpt.scss selbst):
 *   add_filter('zpt/load_css', '__return_false');
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- REGISTER
add_action('init', function () {
    $ver = fn($file) => file_exists(ZPT_PATH . $file) ? (string) filemtime(ZPT_PATH . $file) : ZPT_VERSION;

    // --- Baukasten
    // zpt.css – bei eigenen Breakpoints die angepasste Kopie (breakpoints.php)
    $css = zpt_css_file();
    wp_register_style('zpt-baukasten', $css['url'], [], $css['version']);
    wp_register_script('zpt-bootstrap', ZPT_URL . 'assets/vendor/bootstrap.bundle.min.js', [], '5.3.8', true);

    // --- Photoswipe
    wp_register_style('zpt-photoswipe', ZPT_URL . 'assets/vendor/photoswipe/photoswipe.css', [], '5.3.6');
    wp_register_script('zpt-photoswipe-lib', ZPT_URL . 'assets/vendor/photoswipe/photoswipe.umd.min.js', [], '5.3.6', true);
    wp_register_script('zpt-photoswipe-lightbox', ZPT_URL . 'assets/vendor/photoswipe/photoswipe-lightbox.umd.min.js', [], '5.3.6', true);
    wp_register_script('zpt-photoswipe', ZPT_URL . 'assets/js/photoswipe-init.js', ['zpt-photoswipe-lib', 'zpt-photoswipe-lightbox'], $ver('assets/js/photoswipe-init.js'), true);
    wp_localize_script('zpt-photoswipe', 'zptPhotoswipe', ['iconUrl' => ZPT_URL . 'assets/icons/photoswipe/']);

    // --- Masonry
    wp_register_script('zpt-masonry-lib', ZPT_URL . 'assets/vendor/masonry.pkgd.min.js', [], '4.2.2', true);
    wp_register_script('zpt-masonry', ZPT_URL . 'assets/js/masonry-init.js', ['zpt-masonry-lib'], $ver('assets/js/masonry-init.js'), true);

    // --- Video (YouTube/Vimeo erst nach Klick laden)
    wp_register_script('zpt-video', ZPT_URL . 'assets/js/video.js', [], $ver('assets/js/video.js'), true);

    // --- Sprungmarken (Navigation: aktiven Abschnitt markieren; angeheftete Rows: Abstand der Sprungziele)
    wp_register_script('zpt-anchors', ZPT_URL . 'assets/js/anchors.js', [], $ver('assets/js/anchors.js'), true);
    wp_register_script('zpt-navbar-logo', ZPT_URL . 'assets/js/navbar-logo.js', [], $ver('assets/js/navbar-logo.js'), true);
});

// ----------------------------------------------------------------- FRONTEND
add_action('wp_enqueue_scripts', function () {
    if (apply_filters('zpt/load_css', true)) {
        wp_enqueue_style('zpt-baukasten');
    }
    if (apply_filters('zpt/load_bootstrap_js', true)) {
        wp_enqueue_script('zpt-bootstrap');
    }
}, 5);

/**
 * Lädt eine Bibliothek des Baukastens bei Bedarf (aus render.php der Blocks).
 * Im Editor wird nichts geladen.
 */
function zpt_enqueue(string $lib): void {
    if (is_admin() || wp_is_json_request()) {
        return;
    }
    switch ($lib) {
        case 'photoswipe':
            wp_enqueue_style('zpt-photoswipe');
            wp_enqueue_script('zpt-photoswipe');
            break;
        case 'masonry':
            wp_enqueue_script('zpt-masonry');
            break;
        case 'video':
            wp_enqueue_script('zpt-video');
            break;
        case 'anchors':
            wp_enqueue_script('zpt-anchors');
            break;
        case 'navbar-logo':
            wp_enqueue_script('zpt-navbar-logo');
            break;
    }
}

// ----------------------------------------------------------------- EDITOR
// zpt.css als Editor-Style: WordPress beschränkt es auf .editor-styles-wrapper,
// damit Bootstrap die Admin-Oberfläche nicht verändert. Vor den Theme-Styles,
// damit Theme-Anpassungen gewinnen.
add_filter('block_editor_settings_all', function ($settings) {
    $file = zpt_css_file()['path'];
    if (!apply_filters('zpt/load_css', true) || !file_exists($file)) {
        return $settings;
    }

    array_unshift($settings['styles'], [
        'css'            => file_get_contents($file),
        'baseURL'        => ZPT_URL . 'assets/css/',
        '__unstableType' => 'theme',
        'isGlobalStyles' => false,
    ]);

    return $settings;
});
