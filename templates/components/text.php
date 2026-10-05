<?php
/**
 * Komponente: Text
 * Override im Theme: zpt-baukasten/components/text.php
 *
 * @var array $args {
 *     @type string $content    Formatierter WYSIWYG-Inhalt.
 *     @type array  $attrs      ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

if (empty($args['content'])):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Text eingeben …</div>';
    endif;
    return;
endif;
?>

<div<?php echo zpt_html_attrs($args['attrs'] ?? []); ?>>
    <?php echo $args['content']; ?>
</div>
