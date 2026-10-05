<?php
/**
 * Komponente: Posts (Kacheln, Liste oder Akkordeon)
 * Override im Theme: zpt-baukasten/components/posts.php
 *
 * @var array $args {
 *     @type string $id           Eindeutige ID (Akkordeon).
 *     @type int[]  $posts        Beitrags-IDs in Reihenfolge.
 *     @type string $layout       tiles | list | accordion
 *     @type bool   $show_image   Beitragsbild.
 *     @type bool   $show_date    Datum.
 *     @type bool   $show_excerpt Auszug.
 *     @type string $more         Link-Text; leer = ganze Kachel/Zeile klickbar.
 *     @type string $title_tag    h2 … h6.
 *     @type string $ratio        Bild-Seitenverhältnis ('' = wie das Bild).
 *     @type array  $grid_classes Raster-Klassen (Kacheln).
 *     @type string $item_classes Zusätzliche Klassen je Element.
 *     @type array  $attrs        ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview   true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$posts = $args['posts'] ?? [];
if (!$posts):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Posts: keine Beiträge gefunden …</div>';
    endif;
    return;
endif;

$layout = $args['layout'] ?? 'tiles';
$tag    = in_array($args['title_tag'] ?? '', ['h2', 'h3', 'h4', 'h5', 'h6'], true) ? $args['title_tag'] : 'h3';
$more   = trim((string) ($args['more'] ?? ''));
$attrs  = $args['attrs'] ?? [];
$attrs['id'] = $attrs['id'] ?: $args['id'];

$image = function (int $post) use ($args) {
    $thumb = (int) get_post_thumbnail_id($post);
    if (!$thumb || empty($args['show_image'])) {
        return;
    }
    zpt_component('image', [
        'image_id'          => $thumb,
        'container_classes' => zpt_ratio_classes((string) ($args['ratio'] ?? '')),
        'size'              => 'medium_large',
        'attrs'             => ['class' => ['mb-0', 'zpt-post-image']],
        'is_preview'        => !empty($args['is_preview']),
    ]);
};
$date = function (int $post) use ($args) {
    if (!empty($args['show_date'])) {
        echo '<time class="zpt-post-date" datetime="' . esc_attr(get_the_date('c', $post)) . '">' . esc_html(get_the_date('', $post)) . '</time>';
    }
};
$excerpt = function (int $post) use ($args) {
    if (!empty($args['show_excerpt']) && ($text = get_the_excerpt($post))) {
        echo '<div class="zpt-post-excerpt">' . wpautop(esc_html($text)) . '</div>';
    }
};
$title = function (int $post, bool $stretched) use ($tag) {
    echo "<{$tag} class=\"zpt-post-title\">";
    if ($stretched) {
        echo '<a href="' . esc_url(get_permalink($post)) . '" class="stretched-link text-reset text-decoration-none">' . esc_html(get_the_title($post)) . '</a>';
    } else {
        echo esc_html(get_the_title($post));
    }
    echo "</{$tag}>";
};
$more_link = function (int $post) use ($more) {
    if ($more !== '') {
        echo '<a href="' . esc_url(get_permalink($post)) . '" class="link-further zpt-post-more">' . esc_html($more) . '</a>';
    }
};
?>

<div<?php echo zpt_html_attrs($attrs); ?>>

    <?php if ($layout === 'accordion'): ?>
        <div class="accordion" id="<?php echo esc_attr($attrs['id']); ?>-accordion">
            <?php foreach ($posts as $i => $post): $item_id = $attrs['id'] . '-' . $i; ?>
                <div<?php echo zpt_html_attrs(['class' => ['accordion-item', $args['item_classes'] ?? '']]); ?>>
                    <<?php echo $tag; ?> class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#<?php echo esc_attr($item_id); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr($item_id); ?>">
                            <?php echo esc_html(get_the_title($post)); ?>
                        </button>
                    </<?php echo $tag; ?>>
                    <div id="<?php echo esc_attr($item_id); ?>" class="accordion-collapse collapse" data-bs-parent="#<?php echo esc_attr($attrs['id']); ?>-accordion">
                        <div class="accordion-body">
                            <?php $date($post); $excerpt($post); ?>
                            <a href="<?php echo esc_url(get_permalink($post)); ?>" class="link-further zpt-post-more"><?php echo esc_html($more ?: 'Weiterlesen'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($layout === 'list'): ?>
        <ul class="list-unstyled zpt-posts-list">
            <?php foreach ($posts as $post): ?>
                <li<?php echo zpt_html_attrs(['class' => ['zpt-post', 'position-relative', $args['item_classes'] ?? '']]); ?>>
                    <?php $date($post); $title($post, $more === ''); $excerpt($post); $more_link($post); ?>
                </li>
            <?php endforeach; ?>
        </ul>

    <?php else: ?>
        <div<?php echo zpt_html_attrs(['class' => $args['grid_classes'] ?? ['row']]); ?>>
            <?php foreach ($posts as $post): ?>
                <div class="col">
                    <article<?php echo zpt_html_attrs(['class' => ['zpt-post', 'card', 'h-100', $args['item_classes'] ?? '']]); ?>>
                        <?php $image($post); ?>
                        <div class="card-body">
                            <?php $date($post); $title($post, $more === ''); $excerpt($post); $more_link($post); ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div><!-- .zpt-posts -->
