<?php
/**
 * Block: zpt/posts
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

if (get_field('source') === 'manual') {
    $posts = array_values(array_filter(array_map('intval', (array) (get_field('posts') ?: [])),
        fn($id) => get_post_status($id) === 'publish'));
} else {
    $orderby = get_field('orderby') ?: 'date';
    $query   = [
        'post_type'           => get_field('post_type') ?: 'post',
        'posts_per_page'      => max(1, (int) (get_field('number') ?: 3)),
        'post_status'         => 'publish',
        'orderby'             => $orderby,
        'order'               => in_array($orderby, ['title', 'menu_order'], true) ? 'ASC' : 'DESC',
        'post__not_in'        => [(int) $post_id],
        'ignore_sticky_posts' => true,
        'fields'              => 'ids',
        'no_found_rows'       => true,
    ];
    $categories = array_filter(array_map('intval', (array) (get_field('category') ?: [])));
    if ($categories && $query['post_type'] === 'post') {
        $query['category__in'] = $categories;
    }
    $posts = get_posts(apply_filters('zpt/posts/query', $query, $block));
}

zpt_component('posts', [
    'id'           => 'zpt-posts-' . substr(md5($block['id'] ?? uniqid()), 0, 8),
    'posts'        => $posts,
    'layout'       => get_field('layout') ?: 'tiles',
    'show_image'   => (bool) get_field('show_image'),
    'show_date'    => (bool) get_field('show_date'),
    'show_excerpt' => (bool) get_field('show_excerpt'),
    'more'         => (string) get_field('more'),
    'title_tag'    => get_field('title_tag') ?: 'h3',
    'ratio'        => (string) get_field('ratio'),
    'grid_classes' => zpt_per_row_classes(get_field('per_row'), get_field('gap')),
    'item_classes' => get_field('css_item') ?: '',
    'attrs'        => zpt_block_attrs($block, ['zpt-posts', 'zpt-posts-' . (get_field('layout') ?: 'tiles'), get_field('css_classes')]),
    'is_preview'   => $is_preview,
]);
