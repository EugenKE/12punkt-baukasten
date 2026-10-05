<?php
/**
 * Komponente: Icon (aus den Icon-Sets oder SVG-Code). Größe über font-size, SVG ist 1em groß.
 * Override im Theme: zpt-baukasten/components/icon.php
 *
 * @var array $args {
 *     @type string $source     set | svg
 *     @type string $icon       Kürzel aus der Icon-Auswahl (bi:arrow-right, fa-solid:house …) → zpt_icon().
 *     @type string $svg        Bereinigter SVG-Code.
 *     @type array  $link       ['url', 'title', 'target'] – optional.
 *     @type string $label      Beschriftung für Screenreader.
 *     @type array  $attrs      ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$icon = '';
if (($args['source'] ?? '') === 'svg') {
    $icon = $args['svg'] ?? '';
} elseif (!empty($args['icon'])) {
    $icon = zpt_icon($args['icon']);
}

if ($icon === ''):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">' . (empty($args['icon']) || ($args['source'] ?? '') === 'svg'
            ? 'Icon: auswählen oder SVG eingeben …'
            : 'Icon „' . esc_html($args['icon']) . '“: Icon-Set ist unter Baukasten → Icons nicht geladen.') . '</div>';
    endif;
    return;
endif;

$label = trim((string) ($args['label'] ?? ''));
$url   = (string) ($args['link']['url'] ?? '');
$attrs = $args['attrs'] ?? [];
?>

<div<?php echo zpt_html_attrs($attrs); ?>>
    <?php if ($url !== ''): ?>
        <a href="<?php echo esc_url($url); ?>" class="zpt-icon-link"
            <?php echo ($args['link']['target'] ?? '') === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>
            <?php echo $label !== '' ? 'aria-label="' . esc_attr($label) . '"' : ''; ?>>
            <span aria-hidden="true"><?php echo $icon; ?></span>
        </a>
    <?php else: ?>
        <span<?php echo $label !== '' ? ' role="img" aria-label="' . esc_attr($label) . '"' : ' aria-hidden="true"'; ?>><?php echo $icon; ?></span>
    <?php endif; ?>
</div><!-- .zpt-icon -->
