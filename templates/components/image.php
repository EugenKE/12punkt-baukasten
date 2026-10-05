<?php
/**
 * Komponente: Bild
 * Nachfolger von components/atom_image.php aus dem Theme.
 * Override im Theme: zpt-baukasten/components/image.php
 *
 * @var array $args {
 *     @type int    $image_id
 *     @type bool   $photoswipe
 *     @type string $figure_classes
 *     @type string $container_classes
 *     @type string $img_classes
 *     @type string $caption_source    caption_none | caption_media | caption_media_wysiwyg | caption_local
 *     @type string $caption_local     Formatierter WYSIWYG-Inhalt (nur bei caption_local).
 *     @type string $caption_classes
 *     @type string $size              Bildgröße für src/srcset (Standard: large).
 *     @type string $sizes             sizes-Attribut (Standard: bis zur Originalbreite volle Viewport-Breite).
 *     @type string $message           HTML der Message über dem Bild (zpt_message_html()), '' = keine.
 *     @type array  $attrs             ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview        true im Editor.
 * }
 */

defined('ABSPATH') || exit;

if (empty($args['image_id'])):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Bild auswählen …</div>';
    endif;
    return;
endif;

$image = zpt_image_data($args['image_id']);

// Im Editor kein Photoswipe-Link, sonst öffnet ein Klick das Bild statt den Block auszuwählen
$photoswipe = !empty($args['photoswipe']) && empty($args['is_preview']);

// --------------------- caption
$caption_source = ($args['caption_source'] ?? '') ?: 'caption_none';
$caption        = '';
if ($caption_source === 'caption_local'):
    $caption = $args['caption_local'] ?? '';
elseif ($caption_source === 'caption_media_wysiwyg' && function_exists('get_field')):
    $caption = apply_filters('the_content', get_field('caption_wysiwyg', $image['ID']) ?: '');
elseif ($caption_source === 'caption_media'):
    $caption = esc_html($image['caption']);
endif;

// --------------------- attributes
$figure_attrs = $args['attrs'] ?? [];
$figure_attrs['class'] = zpt_class_list(
    $figure_attrs['class'] ?? [],
    'figure-image',
    $args['figure_classes'] ?? '',
    $caption_source !== 'caption_none' ? 'has-caption' : ''
);

$img_html = wp_get_attachment_image($image['ID'], $args['size'] ?? 'large', false, [
    'class' => implode(' ', zpt_class_list(
        !$photoswipe ? 'preload' : '',
        ($args['img_classes'] ?? '') ?: 'img-fluid w-100',
        'format-' . $image['format']
    )),
    'alt'   => $image['alt'],
    'title' => $image['title'],
    // WP setzt sizes auf die Breite von $size – bei Hochformaten ist „large“ (max. 1024×1024) z. B. nur
    // 396px breit, der Browser nähme dann nie eine größere Datei aus dem srcset → unscharf.
    // Darum an der Originalbreite ausrichten, der Browser wählt passend zu Viewport und Pixeldichte.
    'sizes' => ($args['sizes'] ?? '') ?: sprintf('(max-width: %1$dpx) 100vw, %1$dpx', (int) $image['width']),
]);
?>

<?php if (!empty($args['message'])): ?><div class="zpt-has-message"><?php endif; ?>
<figure<?php echo zpt_html_attrs($figure_attrs); ?>>

    <?php if (!$photoswipe): ?>
        <div<?php echo zpt_html_attrs(['class' => ['image-container', $args['container_classes'] ?? '']]); ?>>
            <?php echo $img_html; ?>
        </div>
    <?php else: ?>
        <a href="<?php echo esc_url($image['url']); ?>"
            <?php echo zpt_html_attrs(['class' => ['section-image', 'image-container', 'add-to-photoswipe', $args['container_classes'] ?? '']]); ?>
            data-pswp-width="<?php echo esc_attr($image['width']); ?>"
            data-pswp-height="<?php echo esc_attr($image['height']); ?>"
            target="_blank">
            <?php echo $img_html; ?>
            <?php if (!empty($image['caption'])): ?>
                <div class="caption-for-photoswipe"><?php echo esc_html($image['caption']); ?></div>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <?php if ($caption_source !== 'caption_none'): ?>
        <figcaption<?php echo zpt_html_attrs(['class' => ['image-caption', $args['caption_classes'] ?? '']]); ?>>
            <?php echo $caption; ?>
        </figcaption>
    <?php endif; ?>

</figure>
<?php if (!empty($args['message'])): ?><?php echo $args['message']; ?></div><!-- .zpt-has-message --><?php endif; ?>
