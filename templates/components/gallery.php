<?php
/**
 * Komponente: Galerie
 * Override im Theme: zpt-baukasten/components/gallery.php
 *
 * @var array $args {
 *     @type string $id             Eindeutige ID (Karussell-Steuerung).
 *     @type int[]  $images         Attachment-IDs.
 *     @type string $layout         tiles | masonry | carousel
 *     @type array  $grid_classes   Raster-Klassen (Kacheln/Masonry): ['row', 'row-cols-2', 'row-cols-md-4', 'g-20'].
 *     @type string $item_classes   Zusätzliche Klassen je Bild-Element.
 *     @type bool   $controls       Karussell: Pfeile.
 *     @type bool   $indicators     Karussell: Punkte.
 *     @type float  $interval       Karussell: Sekunden bis zum nächsten Bild (0 = nur per Klick).
 *     @type bool   $fade           Karussell: überblenden.
 *     @type bool   $photoswipe
 *     @type string $caption_source caption_none | caption_media | caption_media_wysiwyg
 *     @type string $image_size     Bildgröße (large …).
 *     @type string $ratio          '' oder 1x1, 4x3, 3x2, 16x9, 21x9 (Bild zugeschnitten).
 *     @type string $message        HTML der Message über der Galerie (zpt_message_html()), '' = keine.
 *     @type array  $attrs          ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview     true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$images = $args['images'] ?? [];
if (!$images):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Galerie: Bilder auswählen …</div>';
    endif;
    return;
endif;

$layout = $args['layout'] ?? 'tiles';
$attrs  = $args['attrs'] ?? [];
$attrs['id'] = $attrs['id'] ?: $args['id'];

// Bild über die Bild-Komponente (Photoswipe, Bildunterschrift, Seitenverhältnis)
$render_image = function (int $image_id, array $extra = []) use ($args) {
    zpt_component('image', array_merge([
        'image_id'          => $image_id,
        'photoswipe'        => !empty($args['photoswipe']),
        'caption_source'    => $args['caption_source'] ?? 'caption_none',
        'container_classes' => zpt_ratio_classes((string) ($args['ratio'] ?? '')),
        'size'              => $args['image_size'] ?? 'large',
        'attrs'             => ['class' => ['mb-0']],
        'is_preview'        => !empty($args['is_preview']),
    ], $extra));
};
?>

<?php if (!empty($args['message'])): ?><div class="zpt-has-message"><?php endif; ?>

<?php if ($layout === 'carousel'):
    $interval = (float) ($args['interval'] ?? 0);
    $attrs['class'] = zpt_class_list($attrs['class'] ?? [], 'carousel', 'slide', !empty($args['fade']) ? 'carousel-fade' : '');
    // Im Editor kein Autoplay (der Block soll beim Bearbeiten still stehen)
    if ($interval > 0 && empty($args['is_preview'])) {
        $attrs['data-bs-ride']      = 'carousel';
        $attrs['data-bs-interval'] = (string) round($interval * 1000);
    } else {
        $attrs['data-bs-interval'] = 'false';
    }
    ?>
    <div<?php echo zpt_html_attrs($attrs); ?>>

        <?php if (!empty($args['indicators']) && count($images) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($images as $i => $image_id): ?>
                    <button type="button" data-bs-target="#<?php echo esc_attr($attrs['id']); ?>" data-bs-slide-to="<?php echo (int) $i; ?>"
                        <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Bild <?php echo (int) $i + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="carousel-inner">
            <?php foreach ($images as $i => $image_id): ?>
                <div<?php echo zpt_html_attrs(['class' => ['carousel-item', $i === 0 ? 'active' : '', $args['item_classes'] ?? '']]); ?>>
                    <?php $render_image($image_id); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($args['controls']) && count($images) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo esc_attr($attrs['id']); ?>" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Zurück</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#<?php echo esc_attr($attrs['id']); ?>" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Weiter</span>
            </button>
        <?php endif; ?>

    </div><!-- .carousel -->

<?php else:
    $grid = ['class' => zpt_class_list($args['grid_classes'] ?? ['row'], $layout === 'masonry' ? 'row-masonry' : '')];
    if ($layout === 'masonry') {
        $grid['data-msnry-selector'] = '.zpt-gallery-item';
    }
    ?>
    <div<?php echo zpt_html_attrs($attrs); ?>>
        <div<?php echo zpt_html_attrs($grid); ?>>
            <?php if ($layout === 'masonry'): ?>
                <div class="masonry-grid-sizer col"></div>
            <?php endif; ?>
            <?php foreach ($images as $image_id): ?>
                <div<?php echo zpt_html_attrs(['class' => ['col', 'zpt-gallery-item', $args['item_classes'] ?? '']]); ?>>
                    <?php $render_image($image_id); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div><!-- .zpt-gallery -->
<?php endif; ?>

<?php if (!empty($args['message'])): ?><?php echo $args['message']; ?></div><!-- .zpt-has-message --><?php endif; ?>
