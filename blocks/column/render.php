<?php
/**
 * Block: zpt/column
 * Bootstrap-Spalte (.zpt-col col-*) in einer Row, darin beliebig viele Komponenten.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

$col_attrs = zpt_block_attrs($block, [
    'zpt-col',
    zpt_col_width_classes(get_field('col_widths')),
    get_field('no_padding') ? 'px-0' : '',
    get_field('col_classes'),
]);
?>

<div<?php echo zpt_html_attrs($col_attrs); ?>>
    <InnerBlocks allowedBlocks="<?php echo esc_attr(wp_json_encode(zpt_column_allowed_blocks())); ?>" />
</div><!-- .zpt-col -->
