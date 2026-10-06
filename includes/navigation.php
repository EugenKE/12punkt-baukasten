<?php
/**
 * Navigation-Komponente: Menü-Auswahl, Sprungmarken und Walker.
 * Blocks: blocks/navigation (Menü), blocks/anchors (Sprungmarken), Markup: templates/components/navigation.php
 *
 * Dazu die Navigationsleiste im Theme-Kopf: Einstellungen Baukasten → Navigation (Slug zpt-navigation,
 * Felder acf-json/group_zpt_settings_nav_*.json). Das Theme fragt sie über zpt_navbar() ab.
 */

defined('ABSPATH') || exit;

/**
 * Menü-ID aus dem Feldwert „menu:{ID}“ oder „location:{slug}“ (Menü-Position des Themes). 0 = keins.
 */
function zpt_nav_menu_id(string $value): int {
    if (str_starts_with($value, 'location:')) {
        return (int) (get_nav_menu_locations()[substr($value, 9)] ?? 0);
    }
    return str_starts_with($value, 'menu:') ? (int) substr($value, 5) : 0;
}

/**
 * Sprungmarken aus dem Repeater „anchors“ (Block zpt/anchors): [['label' => …, 'url' => '#id'], …] – unvollständige Zeilen fallen weg.
 */
function zpt_nav_anchors(array $rows): array {
    $items = [];
    foreach ($rows as $row) {
        $label  = trim((string) ($row['label'] ?? ''));
        $target = zpt_css_id($row['target'] ?? '');
        if ($label !== '' && $target) {
            $items[] = ['label' => $label, 'url' => '#' . $target];
        }
    }
    return $items;
}

// ----------------------------------------------------------------- NAVIGATIONSLEISTE (THEME-KOPF)

/**
 * Einstellungen der Navigationsleiste fürs Theme (header.php):
 * [
 *   'header_class'    => 'sticky-top zpt-sticky-header' | '', (am <header>)
 *   'navbar_class'    => 'navbar-expand-lg zpt-navbar', (an der <nav class="navbar …">; Umbruchpunkt)
 *   'container_class' => 'container' | 'container-fluid',
 *   'toggler_icon'    => HTML des Toggler-Inhalts (eigene Icons oder .navbar-toggler-icon),
 *   'logo_class'      => '' (Klassen am Logo; Größe: Baukasten → Navigation → Schrift, Farben, Größen → Logo / Name → Tab Logo, zpt_logo_css()),
 * ]
 * Rohwerte per get_option (kein get_field() – auch in acf/load_field nutzbar).
 */
function zpt_navbar(): array {
    $open  = zpt_icon((string) get_option('options_zpt_nav_toggler_open', ''));
    $close = zpt_icon((string) get_option('options_zpt_nav_toggler_close', ''));

    if ($open === '') {
        $open = '<span class="navbar-toggler-icon"></span>';
    }
    $toggler = $close === ''
        ? $open
        : '<span class="zpt-toggler-open">' . $open . '</span><span class="zpt-toggler-close">' . $close . '</span>';

    // Sprungmarken: Scrollspy für Anker-Links im Menü, Abstand unter dem sticky Header (assets/js/anchors.js)
    zpt_enqueue('anchors');

    // Logo so hoch wie die Navigation: misst die Menüpunkte (assets/js/navbar-logo.js)
    $logo_fit = zpt_logo_size()['mode'] === 'nav';
    if ($logo_fit) {
        zpt_enqueue('navbar-logo');
    }

    return apply_filters('zpt/navbar', [
        'header_class'    => get_option('options_zpt_nav_position') === 'sticky' ? 'sticky-top zpt-sticky-header' : '',
        'navbar_class'    => trim(zpt_navbar_expand_class() . ' zpt-navbar' . ($logo_fit ? ' zpt-logo-fit' : '') . zpt_logo_align_class()),
        'container_class' => get_option('options_zpt_nav_container') === 'fixed' ? 'container' : 'container-fluid',
        'toggler_icon'    => $toggler,
        'logo_class'      => '',
    ]);
}

/**
 * Umbruchpunkt: navbar-expand-{bp}, „always“ = navbar-expand (nie Button), „never“ = '' (immer Button).
 */
function zpt_navbar_expand_class(): string {
    $expand = (string) get_option('options_zpt_nav_expand', 'lg') ?: 'lg';
    if ($expand === 'always') {
        return 'navbar-expand';
    }
    if ($expand === 'never') {
        return '';
    }
    return isset(zpt_breakpoint_min()[$expand]) ? 'navbar-expand-' . $expand : 'navbar-expand-lg';
}

// Auswahl Umbruchpunkt: Breakpoints aus Baukasten → Layout (mit Mindestbreite)
add_filter('acf/load_field/key=field_zpt_nav_expand', function ($field) {
    $field['choices'] = ['never' => __('nie – immer Button (☰)', '12punkt-baukasten')];
    foreach (zpt_breakpoint_min() as $bp => $min) {
        /* translators: 1: Breakpoint-Kürzel (z. B. LG), 2: Mindestbreite in Pixeln */
        $field['choices'][$bp] = sprintf(__('%1$s (ab %2$dpx)', '12punkt-baukasten'), strtoupper($bp), $min);
    }
    $field['choices']['always'] = __('immer ausgeklappt – kein Button', '12punkt-baukasten');
    return $field;
});

/**
 * CSS der Navigationsleiste (Teil von zpt_style_css()): Größe des Toggler-Icons.
 * Hintergrund, Schrift, Farben, Abstände: Elemente der Gruppe „navbar“ (elements.php).
 */
function zpt_navbar_css(): string {
    $css = '';
    if ($size = zpt_css_value(get_option('options_zpt_nav_toggler_size', ''))) {
        $css .= '--bs-navbar-toggler-font-size:' . $size . ';';
    }
    return $css ? '.zpt-navbar{' . $css . '}' : '';
}

/**
 * Klassen der Ausrichtung je Breakpoint: ['xs' => 'center', 'md' => 'start', 'lg' => 'fill'] →
 * justify-content-center justify-content-md-start zpt-nav-fill-lg. Leere Werte erben vom kleineren Breakpoint;
 * nach „volle Breite“ hebt zpt-nav-nofill-{bp} sie wieder auf (scss/baukasten/_base.scss).
 */
function zpt_nav_align_classes(array $aligns): array {
    $classes = [];
    $fill    = false;
    foreach (array_merge(['xs'], array_keys(ZPT_BOOTSTRAP_BREAKPOINTS)) as $bp) {
        $value = (string) ($aligns[$bp] ?? '');
        if ($value === '' || !in_array($value, ['start', 'center', 'end', 'between', 'fill'], true)) {
            continue;
        }
        $infix = $bp === 'xs' ? '' : '-' . $bp;
        if ($value === 'fill') {
            $classes[] = 'zpt-nav-fill' . $infix;
        } else {
            if ($fill) {
                $classes[] = 'zpt-nav-nofill' . $infix;
            }
            $classes[] = 'justify-content' . $infix . '-' . $value;
        }
        $fill = $value === 'fill';
    }
    return $classes;
}

/**
 * Ausrichtung je Breakpoint (Gruppe „aligns“, bp_xs … bp_xxl). Blocks vor 0.4.4 haben nur „align“
 * (ein Wert) – gilt als XS, bis der Block neu gespeichert wird.
 */
function zpt_navigation_block_aligns(array $block): array {
    $aligns = [];
    foreach ((array) (get_field('aligns') ?: []) as $key => $value) {
        if ($value !== '' && $value !== null) {
            $aligns[substr($key, 3)] = (string) $value;
        }
    }
    $legacy = $block['data']['align'] ?? '';
    if (!$aligns && is_string($legacy) && $legacy !== '') {
        $aligns['xs'] = $legacy;
    }
    return $aligns;
}

// Auswahl: Menü-Positionen des Themes, dann alle Menüs
add_filter('acf/load_field/key=field_zpt_navigation_menu', function ($field) {
    $field['choices'] = ['' => __('– Menü wählen –', '12punkt-baukasten')];
    $menus     = wp_get_nav_menus();
    $assigned  = get_nav_menu_locations();
    foreach (get_registered_nav_menus() as $slug => $label) {
        $menu = !empty($assigned[$slug]) ? wp_get_nav_menu_object($assigned[$slug]) : null;
        $field['choices']["location:{$slug}"] = $menu
            /* translators: 1: Menü-Position des Themes, 2: Name des zugeordneten Menüs */
            ? sprintf(__('Position: %1$s (%2$s)', '12punkt-baukasten'), $label, $menu->name)
            /* translators: %s: Menü-Position des Themes */
            : sprintf(__('Position: %s (kein Menü zugeordnet)', '12punkt-baukasten'), $label);
    }
    foreach ($menus as $menu) {
        /* translators: %s: Name des Menüs */
        $field['choices']["menu:{$menu->term_id}"] = sprintf(__('Menü: %s', '12punkt-baukasten'), $menu->name);
    }
    return $field;
});

/**
 * Walker für .nav (Bootstrap): li.nav-item > a.nav-link.
 * - dropdown:   Untermenü als Bootstrap-Dropdown (horizontal), sonst eingerückte Liste (vertikal)
 * - plain:      schlichte Liste ohne nav-Klassen
 * - link_class: zusätzliche Klassen an jedem Link
 * - preview:    im Editor kein Dropdown-Toggle (Klick soll den Block auswählen)
 */
class ZPT_Nav_Walker extends Walker_Nav_Menu {

    private array $opts;

    public function __construct(array $opts = []) {
        $this->opts = $opts + ['dropdown' => true, 'plain' => false, 'link_class' => '', 'preview' => false];
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        $class = $this->opts['dropdown'] ? 'dropdown-menu' : ($this->opts['plain'] ? 'list-unstyled ps-20' : 'nav flex-column ps-20');
        $output .= '<ul class="' . esc_attr($class) . '">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</ul>';
    }

    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        $item      = $data_object;
        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $has_child = in_array('menu-item-has-children', $classes, true) && (!isset($args->depth) || (int) $args->depth !== 1);
        $dropdown  = $this->opts['dropdown'] && $has_child && $depth === 0;
        $in_menu   = $this->opts['dropdown'] && $depth > 0;
        // Sprungmarken („/#kontakt“) hält WordPress für die aktuelle Seite – aktiv setzt sie anchors.js (Scrollspy)
        $active    = ($item->current || in_array('current-menu-ancestor', $classes, true)) && !str_contains((string) $item->url, '#');
        $plain     = $this->opts['plain'];

        // --- <li>
        $li = array_merge($classes, (!$plain && !$in_menu) ? ['nav-item'] : [], $dropdown ? ['dropdown'] : []);
        $li = apply_filters('nav_menu_css_class', array_filter($li), $item, $args, $depth);
        $output .= '<li class="' . esc_attr(implode(' ', $li)) . '">';

        // --- <a>
        $atts = [
            'href'   => $item->url ?: '',
            'title'  => $item->attr_title ?: '',
            'target' => $item->target ?: '',
            'rel'    => $item->xfn ?: '',
            'class'  => implode(' ', zpt_class_list($in_menu ? 'dropdown-item' : ($plain ? '' : 'nav-link'), $active ? 'active' : '', $this->opts['link_class'])),
        ];
        if ($item->current && !str_contains((string) $item->url, '#')) {
            $atts['aria-current'] = 'page';
        }
        if ($dropdown) {
            $atts['class'] .= ' dropdown-toggle';
            $atts['role']   = 'button';
            $atts['aria-expanded'] = 'false';
            if (!$this->opts['preview']) {
                $atts['data-bs-toggle'] = 'dropdown';
            }
        }
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $html = '';
        foreach ($atts as $name => $value) {
            if ($value !== '' && $value !== null) {
                $html .= ' ' . $name . '="' . ($name === 'href' ? esc_url($value) : esc_attr($value)) . '"';
            }
        }
        $title   = apply_filters('nav_menu_item_title', apply_filters('the_title', $item->title, $item->ID), $item, $args, $depth);
        $output .= '<a' . $html . '>' . $title . '</a>';
    }

    public function end_el(&$output, $data_object, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}
