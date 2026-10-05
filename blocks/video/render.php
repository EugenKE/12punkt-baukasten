<?php
/**
 * Block: zpt/video
 * YouTube (youtube-nocookie) / Vimeo werden erst nach Klick geladen (assets/js/video.js),
 * bis dahin sind nur Vorschaubild und Hinweis zu sehen – keine Verbindung zum Anbieter.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$source = get_field('source') ?: 'youtube';
$embed  = $source === 'file' ? '' : zpt_video_embed_url($source, (string) get_field('url'));
$file   = $source === 'file' ? (string) wp_get_attachment_url((int) get_field('file')) : '';

$poster = (int) get_field('poster');
$notice = (bool) (get_field('notice') ?? true);

if ($embed) {
    zpt_enqueue('video');
}

zpt_component('video', [
    'source'     => $source,
    'embed'      => $embed,
    'file'       => $file,
    'title'      => (string) get_field('title'),
    'poster'     => $poster,
    // Ohne Hinweis und ohne eigenes Vorschaubild: Vorschaubild des Anbieters (lokal gespeichert)
    'poster_url' => $embed && !$notice && !$poster ? zpt_video_thumbnail_url($embed) : '',
    'ratio'      => in_array(get_field('ratio'), ZPT_RATIOS, true) ? get_field('ratio') : '16x9',
    'controls'   => (bool) get_field('controls'),
    'autoplay'   => (bool) get_field('autoplay'),
    'notice'     => $notice,
    'attrs'      => zpt_block_attrs($block, ['zpt-video', get_field('css_classes')]),
    'is_preview' => $is_preview,
]);
