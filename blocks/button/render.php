<?php
/**
 * Block: zpt/button
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$buttons = [];
foreach ((array) (get_field('buttons') ?: []) as $i => $row) {
    $link = is_array($row['link'] ?? null) ? $row['link'] : [];
    // Ohne Link: im Frontend weglassen, im Editor als Hinweis zeigen (sonst wirkt die neue Zeile verschwunden)
    if (empty($link['url'])) {
        if (!$is_preview) {
            continue;
        }
        /* translators: %d: Nummer des Buttons */
        $link = ['url' => '#', 'title' => sprintf(__('Button %d: Link fehlt', '12punkt-baukasten'), $i + 1), 'target' => ''];
    }
    $buttons[] = [
        'url'           => $link['url'],
        'title'         => $link['title'] ?: $link['url'],
        'target'        => $link['target'] ?? '',
        'style'         => $row['style'] ?? 'btn-primary',
        'size'          => zpt_button_size_classes($row['sizes'] ?? []),
        'icon'          => $row['icon'] ?? '',
        'icon_position' => $row['icon_position'] ?? 'before',
        'classes'       => $row['css_classes'] ?? '',
    ];
}

zpt_component('button', [
    'buttons'    => $buttons,
    'align'      => get_field('align') ?: '',
    'attrs'      => zpt_block_attrs($block, ['zpt-buttons', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
