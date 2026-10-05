<?php
/**
 * Einzelansicht einer Sektion – nur für die Redaktion (Besucher werden umgeleitet, siehe includes/sections.php).
 * Überschreibbar durch single-section.php im Theme.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) {
    the_post();
    zpt_the_section(get_post());
}

get_footer();
