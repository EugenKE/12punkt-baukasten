<?php
/**
 * Block: zpt/accordion
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$items = [];
foreach ((array) (get_field('items') ?: []) as $row) {
    if (trim((string) ($row['title'] ?? '')) === '' && trim((string) ($row['text'] ?? '')) === '') {
        continue;
    }
    $items[] = ['title' => (string) ($row['title'] ?? ''), 'text' => (string) ($row['text'] ?? '')];
}

zpt_component('accordion', [
    'id'         => 'zpt-accordion-' . substr(md5($block['id'] ?? uniqid()), 0, 8),
    'items'      => $items,
    'open'       => get_field('open') ?: 'first',
    'multiple'   => (bool) get_field('multiple'),
    'title_tag'  => get_field('title_tag') ?: 'h3',
    'attrs'      => zpt_block_attrs($block, ['zpt-accordion', 'accordion', get_field('flush') ? 'accordion-flush' : '', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
