<?php
/**
 * Komponente: Card (Bootstrap .card)
 * Override im Theme: zpt-baukasten/components/card.php
 *
 * @var array $args {
 *     @type int    $image        Attachment-ID (0 = ohne Bild).
 *     @type string $headline
 *     @type string $headline_tag h2 … h6, p.
 *     @type string $text         Formatierter Text.
 *     @type array  $link         ['url', 'title', 'target'].
 *     @type string $layout       vertical | horizontal-50 | horizontal-40 (nebeneinander ab MD).
 *     @type string $order        image-first | text-first.
 *     @type string $link_mode    button | card (ganze Card klickbar) | both.
 *     @type string $button_style Klasse wie im Button-Block (btn-primary, link-further, link …).
 *     @type string $ratio        '' oder 1x1, 4x3, 3x2, 16x9.
 *     @type string $body_classes Zusätzliche Klassen für .card-body.
 *     @type array  $attrs        ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview   true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$image    = (int) ($args['image'] ?? 0);
$headline = (string) ($args['headline'] ?? '');
$text     = (string) ($args['text'] ?? '');
$link     = $args['link'] ?? [];
$url      = (string) ($link['url'] ?? '');

if (!$image && $headline === '' && trim(wp_strip_all_tags($text)) === ''):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Card: Bild, Überschrift oder Text eingeben …</div>';
    endif;
    return;
endif;

$layout     = $args['layout'] ?? 'vertical';
$horizontal = $layout !== 'vertical';
$text_first = ($args['order'] ?? '') === 'text-first';
$mode       = $args['link_mode'] ?? 'button';
$tag        = in_array($args['headline_tag'] ?? '', ['h2', 'h3', 'h4', 'h5', 'h6', 'p'], true) ? $args['headline_tag'] : 'h3';
$target     = ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '';

// Button: Stil wie im Button-Block
$style  = (string) ($args['button_style'] ?? 'btn-primary');
$button = '';
if ($url !== '' && in_array($mode, ['button', 'both'], true)) {
    $class  = str_starts_with($style, 'btn-') ? "btn {$style}" : ($style === 'link' ? '' : $style);
    $button = '<a href="' . esc_url($url) . '"' . ($class ? ' class="' . esc_attr($class) . '"' : '') . $target . '>'
        . esc_html(($link['title'] ?? '') ?: 'Mehr erfahren') . '</a>';
}

ob_start();
if ($image) {
    zpt_component('image', [
        'image_id'          => $image,
        'container_classes' => zpt_ratio_classes((string) ($args['ratio'] ?? '')),
        'img_classes'       => 'img-fluid w-100' . ($horizontal ? ' h-100 object-fit-cover' : ''),
        'attrs'             => ['class' => ['mb-0', 'zpt-card-image', $horizontal ? 'h-100' : '']],
        'is_preview'        => !empty($args['is_preview']),
    ]);
}
$image_html = ob_get_clean();

ob_start(); ?>
<div<?php echo zpt_html_attrs(['class' => ['card-body', $args['body_classes'] ?? '']]); ?>>
    <?php if ($headline !== ''): ?>
        <<?php echo $tag; ?> class="card-title">
            <?php if ($url !== '' && in_array($mode, ['card', 'both'], true)): ?>
                <a href="<?php echo esc_url($url); ?>" class="stretched-link text-reset text-decoration-none"<?php echo $target; ?>><?php echo esc_html($headline); ?></a>
            <?php else: ?>
                <?php echo esc_html($headline); ?>
            <?php endif; ?>
        </<?php echo $tag; ?>>
    <?php endif; ?>
    <?php if (trim(wp_strip_all_tags($text)) !== ''): ?>
        <div class="card-text"><?php echo $text; ?></div>
    <?php endif; ?>
    <?php if ($button): ?>
        <div class="card-link-wrap"><?php echo $button; ?></div>
    <?php endif; ?>
</div>
<?php
$body_html = ob_get_clean();

$attrs = $args['attrs'] ?? [];
$attrs['class'] = zpt_class_list($attrs['class'] ?? [], 'zpt-card-' . $layout, $text_first ? 'zpt-card-text-first' : '');
?>

<div<?php echo zpt_html_attrs($attrs); ?>>
    <?php if ($horizontal && $image_html):
        $image_col = $layout === 'horizontal-40' ? 'col-md-5' : 'col-md-6';
        $text_col  = $layout === 'horizontal-40' ? 'col-md-7' : 'col-md-6';
        ?>
        <div class="row g-0 h-100">
            <div class="<?php echo esc_attr($image_col . ($text_first ? ' order-md-2' : '')); ?>"><?php echo $image_html; ?></div>
            <div class="<?php echo esc_attr($text_col); ?>"><?php echo $body_html; ?></div>
        </div>
    <?php elseif ($text_first): ?>
        <?php echo $body_html . $image_html; ?>
    <?php else: ?>
        <?php echo $image_html . $body_html; ?>
    <?php endif; ?>
</div><!-- .zpt-card -->
