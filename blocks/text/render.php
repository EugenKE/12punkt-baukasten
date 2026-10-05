<?php
/**
 * Block: zpt/text
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

zpt_component('text', [
    'content'    => get_field('content_wysiwyg') ?: '',
    'attrs'      => zpt_block_attrs($block, ['section-text', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
