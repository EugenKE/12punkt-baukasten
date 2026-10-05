<?php
/**
 * Seiten-Template "Sektionen-Seite": die im Feld "Sektionen" gewählten Sektionen nacheinander.
 * Überschreibbar durch tmpl-sections_page.php im Theme.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) {
    the_post();
    zpt_the_sections();
}

get_footer();
