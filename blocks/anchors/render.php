<?php
/**
 * Block: zpt/anchors (Sprungmarken)
 * Links auf CSS IDs dieser Seite (Repeater „anchors“). Markup: Navigation-Komponente
 * (templates/components/navigation.php), Scrollspy: assets/js/anchors.js.
 * Als Leiste oben: Row „Oben anheften“ + Hintergrundfarbe.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

zpt_component('navigation', [
    'anchors'    => zpt_nav_anchors(get_field('anchors') ?: []),
    'scrollspy'  => (bool) get_field('scrollspy'),
    'nowrap'     => (bool) get_field('nowrap'),
    'direction'  => get_field('direction') ?: 'horizontal',
    'style'      => get_field('style') ?: 'underline',
    'align'      => (string) get_field('align'),
    'link_class' => (string) get_field('css_link'),
    'attrs'      => zpt_block_attrs($block, ['zpt-navigation', 'zpt-anchors', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
