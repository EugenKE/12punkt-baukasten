<?php
/**
 * Komponente: Akkordeon (Bootstrap .accordion)
 * Override im Theme: zpt-baukasten/components/accordion.php
 *
 * @var array $args {
 *     @type string $id         Eindeutige ID.
 *     @type array  $items      Je Eintrag: title, text (formatiert).
 *     @type string $open       first | none | all – beim Laden offen.
 *     @type bool   $multiple   Mehrere gleichzeitig offen (sonst schließt das Öffnen die anderen).
 *     @type string $title_tag  h2 … h6, div.
 *     @type array  $attrs      ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$items = $args['items'] ?? [];
if (!$items):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Akkordeon: Einträge anlegen …</div>';
    endif;
    return;
endif;

$attrs = $args['attrs'] ?? [];
$attrs['id'] = $attrs['id'] ?: $args['id'];
$tag   = in_array($args['title_tag'] ?? '', ['h2', 'h3', 'h4', 'h5', 'h6', 'div'], true) ? $args['title_tag'] : 'h3';
$open  = $args['open'] ?? 'first';
?>

<div<?php echo zpt_html_attrs($attrs); ?>>
    <?php foreach ($items as $i => $item):
        $is_open  = $open === 'all' || ($open === 'first' && $i === 0);
        $item_id  = $attrs['id'] . '-' . $i;
        ?>
        <div class="accordion-item">
            <<?php echo $tag; ?> class="accordion-header" id="<?php echo esc_attr($item_id); ?>-header">
                <button class="accordion-button<?php echo $is_open ? '' : ' collapsed'; ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr($item_id); ?>"
                    aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html($item['title']); ?>
                </button>
            </<?php echo $tag; ?>>
            <div id="<?php echo esc_attr($item_id); ?>" class="accordion-collapse collapse<?php echo $is_open ? ' show' : ''; ?>"
                aria-labelledby="<?php echo esc_attr($item_id); ?>-header"
                <?php echo empty($args['multiple']) ? 'data-bs-parent="#' . esc_attr($attrs['id']) . '"' : ''; ?>>
                <div class="accordion-body">
                    <?php echo $item['text']; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div><!-- .zpt-accordion -->
