<?php
/**
 * Komponente: Navigation (WordPress-Menü oder Sprungmarken auf der Seite)
 * Override im Theme: zpt-baukasten/components/navigation.php
 *
 * @var array $args {
 *     @type int    $menu       ID des Menüs (0 = keins).
 *     @type array|null $anchors Sprungmarken [['label', 'url' => '#id'], …] statt Menü (null = Menü; Block zpt/anchors).
 *     @type bool   $scrollspy  Sprungmarken: Link zum sichtbaren Abschnitt aktiv setzen (assets/js/anchors.js).
 *     @type bool   $nowrap     Sprungmarken horizontal: nicht umbrechen, seitlich wischen.
 *     @type int    $depth      1 = nur oberste Ebene, 2 = mit Untermenüs.
 *     @type string $direction  horizontal (Untermenüs als Dropdown) | vertical (Untermenüs eingerückt).
 *     @type string $style      nav | pills | underline | list
 *     @type string|array $align '' | center | end | between | fill (nur horizontal), oder je Breakpoint
 *                            ['xs' => 'center', 'md' => 'start', …] (start = links; zpt_nav_align_classes()).
 *     @type string $link_class Zusätzliche Klassen an jedem Link.
 *     @type array  $attrs      ['id', 'class', 'style'] für zpt_html_attrs() (<nav>).
 *     @type bool   $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$anchors = $args['anchors'] ?? null;
$menu    = $anchors === null && $args['menu'] ? wp_get_nav_menu_object($args['menu']) : false;
if ($anchors === null ? (!$menu || !$menu->count) : !$anchors):
    if (!empty($args['is_preview'])):
        $hint = $anchors !== null ? 'Sprungmarken: Links eintragen …' : 'Navigation: ' . ($menu ? 'Das Menü ist leer.' : 'Menü wählen …');
        echo '<div class="zpt-placeholder">' . $hint . '</div>';
    endif;
    return;
endif;

$vertical = ($args['direction'] ?? '') === 'vertical';
$style    = $args['style'] ?? 'nav';
$aligns   = $vertical ? [] : (is_array($args['align'] ?? '') ? $args['align'] : ['xs' => (string) ($args['align'] ?? '')]);
$align    = (string) ($aligns['xs'] ?? '');
$nowrap   = $anchors !== null && !$vertical && !empty($args['nowrap']);

$ul_classes = zpt_class_list(
    $style === 'list' ? 'list-unstyled' : 'nav',
    ['pills' => 'nav-pills', 'underline' => 'nav-underline'][$style] ?? '',
    $vertical ? 'flex-column' : '',
    // nicht umbrechen (Sprungmarken): nur ein Wert, Sonderfall unten
    $nowrap ? ($align === 'fill' ? 'nav-fill' : ($align ? 'justify-content-' . $align : '')) : zpt_nav_align_classes($aligns),
    $nowrap ? 'flex-nowrap text-nowrap' : ''
);
// Nicht umbrechen: mittig/rechts per Auto-Margin statt justify-content – sonst ist bei Überlauf der Anfang abgeschnitten
if ($nowrap && in_array($align, ['center', 'end'], true)) {
    $ul_classes = array_diff($ul_classes, ['justify-content-' . $align]);
    $ul_classes[] = $align === 'center' ? 'mx-auto' : 'ms-auto';
} elseif ($nowrap && $align) {
    $ul_classes[] = 'flex-grow-1';
}

$attrs = $args['attrs'] ?? [];
$attrs['class']      = zpt_class_list($attrs['class'] ?? [], 'zpt-navigation-' . ($vertical ? 'vertical' : 'horizontal'), $nowrap ? 'zpt-navigation-nowrap d-flex overflow-x-auto' : '');
$attrs['aria-label'] = $anchors !== null ? __('Inhalt der Seite', '12punkt-baukasten') : $menu->name;
if ($anchors !== null && !empty($args['scrollspy'])) {
    $attrs['data-zpt-scrollspy'] = 'true';
    zpt_enqueue('anchors');
}
$link_class = zpt_class_list($style === 'list' ? '' : 'nav-link', $args['link_class'] ?? '');
?>

<nav<?php echo zpt_html_attrs($attrs); ?>>
    <?php if ($anchors !== null): ?>
        <ul class="<?php echo esc_attr(implode(' ', $ul_classes)); ?>">
            <?php foreach ($anchors as $item): ?>
                <li<?php if ($style !== 'list'): ?> class="nav-item"<?php endif; ?>>
                    <a class="<?php echo esc_attr(implode(' ', $link_class)); ?>" href="<?php echo esc_attr($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: wp_nav_menu([
        'menu'        => $menu->term_id,
        'container'   => false,
        'items_wrap'  => '<ul class="%2$s">%3$s</ul>',
        'menu_class'  => implode(' ', $ul_classes),
        'depth'       => max(1, (int) ($args['depth'] ?? 2)),
        'fallback_cb' => false,
        'walker'      => new ZPT_Nav_Walker([
            'dropdown'   => !$vertical,
            'plain'      => $style === 'list',
            'link_class' => $args['link_class'] ?? '',
            'preview'    => !empty($args['is_preview']),
        ]),
    ]); endif; ?>
</nav><!-- .zpt-navigation -->
