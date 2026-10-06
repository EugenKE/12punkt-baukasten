<?php
/**
 * Block: zpt/row
 * Container + .row, darin Spalten-Blocks (zpt/column) mit den Komponenten.
 *
 * @var array $block      Block-Einstellungen und Attribute.
 * @var bool  $is_preview true im Editor.
 * @var int   $post_id    ID des aktuellen Beitrags.
 */

defined('ABSPATH') || exit;

// --------------------- wrapper
// Schrift (Rolle, Größe, Gewicht, Ausrichtung, Farbe) kommt über zpt_block_attrs() aus dem Bereich „Schrift“
// Hintergrund: Farbe, Bild (Quelle „Theme“: Bild aus dem Theme, theme-files.php), Wiederholen, Bildgröße
$image_bg = get_field('image_bg_source') === 'theme'
    ? zpt_image_data((string) get_field('theme_image_bg') ?: 0)
    : get_field('image_bg');
$style = zpt_background_style(
    (string) get_field('wrapper_background_color'),
    is_array($image_bg) ? $image_bg : null,
    (string) get_field('image_bg_repeat'),
    get_field('image_bg_size')
);

if ($inline_style = get_field('wrapper_inline_style')) {
    $style[] = $inline_style;
}

// Oben anheften (nicht im Editor): .position-sticky, weil .zpt-row-block position:relative hat;
// anchors.js hält Sprungziele unterhalb der Row (scroll-padding-top)
$sticky = get_field('sticky') && !$is_preview;
if ($sticky) {
    zpt_enqueue('anchors');
}

$wrapper_attrs = zpt_block_attrs($block, ['zpt-row-block', $sticky ? 'sticky-top position-sticky zpt-row-sticky' : '', get_field('wrapper_classes')], $style);

// --------------------- container
// "wie Seite" → Einstellung der Seite (Seitenleiste „Seite“ → Baukasten)
$container_type = get_field('container_type') ?: 'inherit';
if ($container_type === 'inherit') {
    $container_type = get_field('zpt_container', $post_id) ?: 'container-fluid';
}
$container_attrs = [
    'class' => [$container_type === 'container' ? 'container' : 'container-fluid', get_field('container_classes')],
];

// --------------------- row
$row_masonry = (bool) get_field('row_masonry');
if ($row_masonry) {
    zpt_enqueue('masonry');
}
$row_attrs   = [
    'id'    => zpt_css_id(get_field('row_id')),
    'class' => ['zpt-row', get_field('row_display') ?: 'row', get_field('row_classes'), $row_masonry ? 'row-masonry' : ''],
];

// --------------------- background video
$video     = get_field('video_bg') ?: [];
$video_url = $video['video_mp4']['url'] ?? '';
?>

<div<?php echo zpt_html_attrs($wrapper_attrs); ?>>

    <?php if ($video_url): ?>
        <video playsinline loop autoplay muted
            <?php if (!empty($video['video_poster']['url'])): ?>poster="<?php echo esc_url($video['video_poster']['url']); ?>"<?php endif; ?>
            class="<?php echo esc_attr(implode(' ', zpt_class_list('section-video-bg', $video['css_classes'] ?? ''))); ?>">
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
        </video>
    <?php endif; ?>

    <div<?php echo zpt_html_attrs($container_attrs); ?>>
        <div<?php echo zpt_html_attrs($row_attrs); ?>>

            <?php if ($row_masonry): ?>
                <div class="masonry-grid-sizer col-1"></div>
            <?php endif; ?>

            <InnerBlocks
                allowedBlocks="<?php echo esc_attr(wp_json_encode(zpt_row_allowed_blocks())); ?>"
                template="<?php echo esc_attr(wp_json_encode([['zpt/column']])); ?>"
            />

        </div><!-- .row -->
    </div><!-- .container -->

</div><!-- .zpt-row-block -->
