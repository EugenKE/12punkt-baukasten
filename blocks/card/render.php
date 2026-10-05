<?php
/**
 * Block: zpt/card
 * Quelle „Beitrag / Seite“: Bild, Überschrift, Text und Link kommen aus dem Beitrag,
 * eigene Angaben in den Feldern gehen vor.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

// Quelle „Theme“: Pfad im Theme statt Attachment-ID (theme-files.php)
$image    = get_field('image_source') === 'theme' ? (string) get_field('theme_image') : (int) get_field('image');
$headline = (string) get_field('headline');
$text     = (string) get_field('text');
$link     = is_array(get_field('link')) ? get_field('link') : [];

if (get_field('source') === 'post' && ($source_post = (int) get_field('post'))) {
    $image    = $image ?: (int) get_post_thumbnail_id($source_post);
    $headline = $headline ?: get_the_title($source_post);
    $text     = $text ?: wpautop(esc_html(get_the_excerpt($source_post)));
    $link     = [
        'url'    => ($link['url'] ?? '') ?: get_permalink($source_post),
        'title'  => ($link['title'] ?? '') ?: __('Mehr erfahren', '12punkt-baukasten'),
        'target' => $link['target'] ?? '',
    ];
}

zpt_component('card', [
    'image'        => $image,
    'image_alt'    => get_field('image_source') === 'theme' ? (string) get_field('theme_image_alt') : '',
    'headline'     => $headline,
    'headline_tag' => get_field('headline_tag') ?: 'h3',
    'text'         => $text,
    'link'         => $link,
    'layout'       => get_field('layout') ?: 'vertical',
    'order'        => get_field('order') ?: 'image-first',
    'link_mode'    => get_field('link_mode') ?: 'button',
    'button_style' => get_field('button_style') ?: 'btn-primary',
    'ratio'        => (string) get_field('ratio'),
    'body_classes' => get_field('css_body') ?: '',
    'attrs'        => zpt_block_attrs($block, ['zpt-card', 'card', 'h-100', get_field('border') ? '' : 'border-0', get_field('css_classes')]),
    'is_preview'   => $is_preview,
]);
