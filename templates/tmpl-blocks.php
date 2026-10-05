<?php
/**
 * Seiten-Template "Block-Seite": Inhalt der Seite (zpt/row-Blocks bringen Container und .row mit).
 * Überschreibbar durch tmpl-blocks.php im Theme.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) {
    the_post();
    the_content();
}

get_footer();
