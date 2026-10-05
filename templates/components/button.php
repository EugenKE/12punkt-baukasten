<?php
/**
 * Komponente: Button
 * Override im Theme: zpt-baukasten/components/button.php
 *
 * @var array $args {
 *     @type array $buttons    Je Button: url, title, target, style (btn-primary, btn-outline-primary,
 *                             btn-link, link-further, link), size (Größen-Klassen, z. B.
 *                             ['btn-sm', 'btn-md-lg']: zpt_button_size_classes()), icon
 *                             (Kürzel bi:arrow-right … → zpt_icon()), icon_position (before|after), classes.
 *     @type string $align     Ausrichtung: '' (links), center, end, between.
 *     @type array $attrs      ['id', 'class', 'style'] für zpt_html_attrs() (Wrapper).
 *     @type bool  $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

if (empty($args['buttons'])):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Button: Link eingeben …</div>';
    endif;
    return;
endif;

// Ausrichtung der Buttons im Flex-Wrapper (center, end, between; leer = links)
$align = in_array($args['align'] ?? '', ['center', 'end', 'between'], true) ? 'justify-content-' . $args['align'] : '';
$attrs = $args['attrs'] ?? [];
$attrs['class'] = zpt_class_list($attrs['class'] ?? [], 'd-flex flex-wrap align-items-center gap-10', $align);
?>

<div<?php echo zpt_html_attrs($attrs); ?>>
    <?php foreach ($args['buttons'] as $button):
        $style = (string) ($button['style'] ?? 'btn-primary');
        $is_btn = str_starts_with($style, 'btn-');
        $link_attrs = [
            'href'  => $button['url'],
            'class' => zpt_class_list(
                $is_btn ? ['btn', $style, $button['size'] ?? []] : ($style === 'link' ? '' : $style),
                $button['classes'] ?? ''
            ),
        ];
        if (($button['target'] ?? '') === '_blank') {
            $link_attrs['target'] = '_blank';
            $link_attrs['rel']    = 'noopener';
        }
        $icon = zpt_icon((string) ($button['icon'] ?? ''));
        $after = ($button['icon_position'] ?? 'before') === 'after';
        ?>
        <a<?php echo zpt_html_attrs($link_attrs); ?>><?php
            echo $after ? '' : ($icon ? $icon . ' ' : '');
            echo esc_html($button['title']);
            echo $after && $icon ? ' ' . $icon : '';
        ?></a>
    <?php endforeach; ?>
</div>
