<?php
/**
 * Plugin Name:       12punkt Baukasten
 * Description:       Layout-Baukasten (Rows, Komponenten, Sektionen) als ACF-Blocks für Gutenberg, mit Style-Einstellungen und Seiten-Templates. Benötigt ACF Pro.
 * Version:           0.4.5
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            12punkt
 * Text Domain:       12punkt-baukasten
 * Domain Path:       /languages
 * Update URI:        https://github.com/EugenKE/12punkt-baukasten
 */

defined('ABSPATH') || exit;

define('ZPT_VERSION', '0.4.5');
define('ZPT_PATH', plugin_dir_path(__FILE__));
define('ZPT_URL', plugin_dir_url(__FILE__));

require_once ZPT_PATH . 'includes/i18n.php';
require_once ZPT_PATH . 'includes/updater.php';

// ----------------------------------------------------------------- ACF PRO CHECK
add_action('plugins_loaded', function () {
    if (!function_exists('acf_register_block_type')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>12punkt Baukasten:</strong> ' . esc_html__('ACF Pro muss installiert und aktiviert sein.', '12punkt-baukasten') . '</p></div>';
        });
        return;
    }

    require_once ZPT_PATH . 'includes/config.php';
    require_once ZPT_PATH . 'includes/helpers.php';
    require_once ZPT_PATH . 'includes/breakpoints.php';
    require_once ZPT_PATH . 'includes/assets.php';
    require_once ZPT_PATH . 'includes/settings.php';
    require_once ZPT_PATH . 'includes/import-export.php';
    require_once ZPT_PATH . 'includes/colors.php';
    require_once ZPT_PATH . 'includes/fonts.php';
    require_once ZPT_PATH . 'includes/typography.php';
    require_once ZPT_PATH . 'includes/elements.php';
    require_once ZPT_PATH . 'includes/buttons.php';
    require_once ZPT_PATH . 'includes/theme-files.php';
    require_once ZPT_PATH . 'includes/icons.php';
    require_once ZPT_PATH . 'includes/logo.php';
    require_once ZPT_PATH . 'includes/components.php';
    require_once ZPT_PATH . 'includes/navigation.php';
    require_once ZPT_PATH . 'includes/sections.php';
    require_once ZPT_PATH . 'includes/styleguide.php';
    require_once ZPT_PATH . 'includes/acf-json.php';
    require_once ZPT_PATH . 'includes/blocks.php';
    require_once ZPT_PATH . 'includes/editor.php';
});
