<?php
/**
 * Komponente: Headline
 * Override im Theme: zpt-baukasten/components/headline.php
 *
 * @var array $args {
 *     @type string $text  Text der Überschrift.
 *     @type string $tag   h1 … h6.
 *     @type array  $attrs ['id', 'class', 'style'] für zpt_html_attrs().
 * }
 */

defined('ABSPATH') || exit;

$tag = in_array($args['tag'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $args['tag'] : 'h2';
?>

<<?php echo $tag; ?><?php echo zpt_html_attrs($args['attrs'] ?? []); ?>>
    <?php echo esc_html($args['text'] ?? ''); ?>
</<?php echo $tag; ?>>
