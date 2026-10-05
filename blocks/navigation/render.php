<?php
/**
 * Block: zpt/navigation
 * Feld „Menü“: „menu:{ID}“ (bestimmtes Menü) oder „location:{slug}“ (Menü-Position des Themes –
 * folgt der Zuordnung unter Design → Menüs). Sprungmarken auf der Seite: Block zpt/anchors.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

zpt_component('navigation', [
    'menu'       => zpt_nav_menu_id((string) get_field('menu')),
    'depth'      => (int) (get_field('depth') ?: 2),
    'direction'  => get_field('direction') ?: 'horizontal',
    'style'      => get_field('style') ?: 'nav',
    'align'      => (string) get_field('align'),
    'link_class' => (string) get_field('css_link'),
    'attrs'      => zpt_block_attrs($block, ['zpt-navigation', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
