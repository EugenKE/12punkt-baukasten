<?php
/**
 * Farben: Shade-Skala, Farbpalette, Systemfarben, Farbfelder.
 *
 * Einstellungen → Farben:
 * - Tab „Einstellungen“: Shade-Skala (Repeater zpt_shade_scale: Name, Mischen, Anteil) mit Vorlagen
 *   Bootstrap (100–900), Tailwind (50–950), Simple (light, base, dark). Genau ein Shade ist die Grundfarbe.
 * - Tab „Farbpalette“: Farbpalette (Repeater zpt_palette): Name („ecos-blau“) + Grundfarbe, übrige Shades
 *   berechnet oder überschrieben. Die Shade-Felder der Palette kommen aus der Skala (acf/include_fields).
 * - Tab „WordPress“ (Repeater zpt_wp_colors): welche Farben (mit Shade) in den Farbauswahlen und der
 *   WordPress-Farbpalette erscheinen; leer = alle.
 * - Tab „Systemfarben“ (Repeater zpt_system_colors): Name (primary, accent …) + Farbe (Auswahl Farbe + Shading
 *   aus der Palette oder eigene); Palettenfarbe → deren Shades, sonst berechnet. Nicht angelegte Bootstrap-Farben
 *   behalten Bootstraps Wert.
 *
 * Ein Farbfeld (Textfeld mit Wrapper-Klasse „zpt-color-field“) nimmt auf:
 * - Baukasten-Farbe, optional mit Shade: „primary“, „primary-300“, „ecos-blau-dark“ (nur Props-Felder)
 * - HTML-Farbname: „tomato“, „white“, „transparent“
 * - Hexwert: „#f60“, „#ff6600“, „#ff660080“
 *
 * Berechnung: aufhellen = mit Weiß mischen, abdunkeln = mit Schwarz mischen (Anteil in %).
 * CSS-Variablen: --theme-primary, --theme-primary-{shade} (settings.php), --theme-ecos-blau-{shade} (zpt_palette_vars()),
 * Klassen: .text-/.bg-/.border-{farbe}-{shade} … (zpt_color_classes_css()).
 */

defined('ABSPATH') || exit;

// Vorlagen der Shade-Skala: [Name, Mischen (tint|base|shade), Anteil in %]
// Anteile an die Farbreihen im Figma-Styleguide angepasst (Grundfarbe = Stufe 500).
const ZPT_SHADE_PRESETS = [
    'bootstrap' => [
        'label' => 'Bootstrap (100–900)',
        'scale' => [
            ['100', 'tint', 65], ['200', 'tint', 47], ['300', 'tint', 29], ['400', 'tint', 16],
            ['500', 'base', 0],
            ['600', 'shade', 15], ['700', 'shade', 31], ['800', 'shade', 49], ['900', 'shade', 67],
        ],
    ],
    'tailwind' => [
        'label' => 'Tailwind (50–950)',
        'scale' => [
            ['50', 'tint', 82], ['100', 'tint', 65], ['200', 'tint', 47], ['300', 'tint', 29], ['400', 'tint', 16],
            ['500', 'base', 0],
            ['600', 'shade', 15], ['700', 'shade', 31], ['800', 'shade', 49], ['900', 'shade', 67], ['950', 'shade', 79],
        ],
    ],
    'simple' => [
        'label' => 'Simple (light, base, dark)',
        'scale' => [
            ['light', 'tint', 40],
            ['base', 'base', 0],
            ['dark', 'shade', 30],
        ],
    ],
];

// Bootstrap-Farben: als Systemfarbe angelegt, setzen sie auch --bs-{name}; nicht angelegt gilt Bootstraps Wert
// (SCSS: --theme-{name} = var(--bs-{name})). Mit Komponentenklassen (.btn-*, .text-bg-*, .alert-*):
const ZPT_BOOTSTRAP_THEME_COLORS = ['primary', 'secondary', 'tertiary', 'quaternary', 'success', 'info', 'warning', 'danger', 'light', 'dark'];
// … und ohne (nur .text-*, .bg-* usw.):
const ZPT_BOOTSTRAP_BASIC_COLORS = ['black', 'white', 'gray'];

// Namen, die keine eigene Farbe tragen darf (Bootstrap-Klassen/-Schlüsselwörter)
const ZPT_COLOR_RESERVED = ['body', 'transparent', 'currentcolor', 'emphasis', 'subtle', 'reset'];

// CSS-Farbnamen (CSS Color Module Level 4)
const ZPT_COLOR_NAMES = [
    'aliceblue' => 'f0f8ff', 'antiquewhite' => 'faebd7', 'aqua' => '00ffff', 'aquamarine' => '7fffd4',
    'azure' => 'f0ffff', 'beige' => 'f5f5dc', 'bisque' => 'ffe4c4', 'black' => '000000',
    'blanchedalmond' => 'ffebcd', 'blue' => '0000ff', 'blueviolet' => '8a2be2', 'brown' => 'a52a2a',
    'burlywood' => 'deb887', 'cadetblue' => '5f9ea0', 'chartreuse' => '7fff00', 'chocolate' => 'd2691e',
    'coral' => 'ff7f50', 'cornflowerblue' => '6495ed', 'cornsilk' => 'fff8dc', 'crimson' => 'dc143c',
    'cyan' => '00ffff', 'darkblue' => '00008b', 'darkcyan' => '008b8b', 'darkgoldenrod' => 'b8860b',
    'darkgray' => 'a9a9a9', 'darkgreen' => '006400', 'darkgrey' => 'a9a9a9', 'darkkhaki' => 'bdb76b',
    'darkmagenta' => '8b008b', 'darkolivegreen' => '556b2f', 'darkorange' => 'ff8c00', 'darkorchid' => '9932cc',
    'darkred' => '8b0000', 'darksalmon' => 'e9967a', 'darkseagreen' => '8fbc8f', 'darkslateblue' => '483d8b',
    'darkslategray' => '2f4f4f', 'darkslategrey' => '2f4f4f', 'darkturquoise' => '00ced1', 'darkviolet' => '9400d3',
    'deeppink' => 'ff1493', 'deepskyblue' => '00bfff', 'dimgray' => '696969', 'dimgrey' => '696969',
    'dodgerblue' => '1e90ff', 'firebrick' => 'b22222', 'floralwhite' => 'fffaf0', 'forestgreen' => '228b22',
    'fuchsia' => 'ff00ff', 'gainsboro' => 'dcdcdc', 'ghostwhite' => 'f8f8ff', 'gold' => 'ffd700',
    'goldenrod' => 'daa520', 'gray' => '808080', 'green' => '008000', 'greenyellow' => 'adff2f',
    'grey' => '808080', 'honeydew' => 'f0fff0', 'hotpink' => 'ff69b4', 'indianred' => 'cd5c5c',
    'indigo' => '4b0082', 'ivory' => 'fffff0', 'khaki' => 'f0e68c', 'lavender' => 'e6e6fa',
    'lavenderblush' => 'fff0f5', 'lawngreen' => '7cfc00', 'lemonchiffon' => 'fffacd', 'lightblue' => 'add8e6',
    'lightcoral' => 'f08080', 'lightcyan' => 'e0ffff', 'lightgoldenrodyellow' => 'fafad2', 'lightgray' => 'd3d3d3',
    'lightgreen' => '90ee90', 'lightgrey' => 'd3d3d3', 'lightpink' => 'ffb6c1', 'lightsalmon' => 'ffa07a',
    'lightseagreen' => '20b2aa', 'lightskyblue' => '87cefa', 'lightslategray' => '778899', 'lightslategrey' => '778899',
    'lightsteelblue' => 'b0c4de', 'lightyellow' => 'ffffe0', 'lime' => '00ff00', 'limegreen' => '32cd32',
    'linen' => 'faf0e6', 'magenta' => 'ff00ff', 'maroon' => '800000', 'mediumaquamarine' => '66cdaa',
    'mediumblue' => '0000cd', 'mediumorchid' => 'ba55d3', 'mediumpurple' => '9370db', 'mediumseagreen' => '3cb371',
    'mediumslateblue' => '7b68ee', 'mediumspringgreen' => '00fa9a', 'mediumturquoise' => '48d1cc', 'mediumvioletred' => 'c71585',
    'midnightblue' => '191970', 'mintcream' => 'f5fffa', 'mistyrose' => 'ffe4e1', 'moccasin' => 'ffe4b5',
    'navajowhite' => 'ffdead', 'navy' => '000080', 'oldlace' => 'fdf5e6', 'olive' => '808000',
    'olivedrab' => '6b8e23', 'orange' => 'ffa500', 'orangered' => 'ff4500', 'orchid' => 'da70d6',
    'palegoldenrod' => 'eee8aa', 'palegreen' => '98fb98', 'paleturquoise' => 'afeeee', 'palevioletred' => 'db7093',
    'papayawhip' => 'ffefd5', 'peachpuff' => 'ffdab9', 'peru' => 'cd853f', 'pink' => 'ffc0cb',
    'plum' => 'dda0dd', 'powderblue' => 'b0e0e6', 'purple' => '800080', 'rebeccapurple' => '663399',
    'red' => 'ff0000', 'rosybrown' => 'bc8f8f', 'royalblue' => '4169e1', 'saddlebrown' => '8b4513',
    'salmon' => 'fa8072', 'sandybrown' => 'f4a460', 'seagreen' => '2e8b57', 'seashell' => 'fff5ee',
    'sienna' => 'a0522d', 'silver' => 'c0c0c0', 'skyblue' => '87ceeb', 'slateblue' => '6a5acd',
    'slategray' => '708090', 'slategrey' => '708090', 'snow' => 'fffafa', 'springgreen' => '00ff7f',
    'steelblue' => '4682b4', 'tan' => 'd2b48c', 'teal' => '008080', 'thistle' => 'd8bfd8',
    'tomato' => 'ff6347', 'turquoise' => '40e0d0', 'violet' => 'ee82ee', 'wheat' => 'f5deb3',
    'white' => 'ffffff', 'whitesmoke' => 'f5f5f5', 'yellow' => 'ffff00', 'yellowgreen' => '9acd32',
];

/**
 * Hexwert oder HTML-Farbname → [r, g, b]; null, wenn keine gültige Farbe (Alpha wird ignoriert).
 */
function zpt_color_to_rgb($value): ?array {
    $value = is_string($value) ? strtolower(trim($value)) : '';

    if (isset(ZPT_COLOR_NAMES[$value])) {
        $value = '#' . ZPT_COLOR_NAMES[$value];
    }
    if (!preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/', $value, $m)) {
        return null;
    }

    $hex = $m[1];
    if (strlen($hex) <= 4) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return array_map('hexdec', str_split(substr($hex, 0, 6), 2));
}

// ----------------------------------------------------------------- SHADE-SKALA

/**
 * Shade-Skala aus Einstellungen → Farben → Einstellungen, in Reihenfolge hell → dunkel:
 * ['100' => ['type' => 'tint', 'amount' => .65], …, '500' => ['type' => 'base', 'amount' => 0], …].
 * Ungültig oder leer → gewählte Vorlage (Standard Bootstrap).
 * Rohwerte per get_option: wird schon in acf/include_fields und acf/load_field gebraucht (kein get_field()).
 */
function zpt_color_scale(bool $fresh = false): array {
    static $scale = null;
    if ($scale !== null && !$fresh) {
        return $scale;
    }

    $scale = [];
    $rows  = (int) get_option('options_zpt_shade_scale', 0);
    for ($i = 0; $i < $rows; $i++) {
        $name   = zpt_shade_name(get_option("options_zpt_shade_scale_{$i}_name", ''));
        $type   = get_option("options_zpt_shade_scale_{$i}_type", '');
        $amount = (float) get_option("options_zpt_shade_scale_{$i}_amount", 0);
        if ($name && in_array($type, ['tint', 'base', 'shade'], true) && !isset($scale[$name])) {
            $scale[$name] = ['type' => $type, 'amount' => $type === 'base' ? 0 : max(0, min(100, $amount)) / 100];
        }
    }

    // genau eine Grundfarbe, sonst Vorlage
    if (count(array_filter($scale, fn($s) => $s['type'] === 'base')) !== 1) {
        $preset = get_option('options_zpt_shade_preset', 'bootstrap');
        $scale  = zpt_shade_preset_scale(isset(ZPT_SHADE_PRESETS[$preset]) ? $preset : 'bootstrap');
    }

    return $scale;
}

/**
 * Skala einer Vorlage im Format von zpt_color_scale().
 */
function zpt_shade_preset_scale(string $preset): array {
    $scale = [];
    foreach (ZPT_SHADE_PRESETS[$preset]['scale'] as [$name, $type, $amount]) {
        $scale[$name] = ['type' => $type, 'amount' => $amount / 100];
    }
    return $scale;
}

/**
 * Name des Shades, der die Grundfarbe ist („500“, „base“).
 */
function zpt_color_base(bool $fresh = false): string {
    foreach (zpt_color_scale($fresh) as $name => $shade) {
        if ($shade['type'] === 'base') {
            return (string) $name;
        }
    }
    return '500';
}

/**
 * Name eines Shades prüfen: Kleinbuchstaben und Ziffern („100“, „light“). Ungültig → ''.
 */
function zpt_shade_name($name): string {
    $name = is_string($name) || is_int($name) ? strtolower(trim((string) $name)) : '';
    return preg_match('/^[a-z0-9]+$/', $name) && $name !== 'name' ? $name : '';
}

/**
 * Berechneter Shade einer Grundfarbe.
 */
function zpt_color_shade(array $rgb, string $shade): array {
    $def = zpt_color_scale()[$shade] ?? ['type' => 'base', 'amount' => 0];
    return match ($def['type']) {
        'tint'  => zpt_tint($rgb, $def['amount']),
        'shade' => zpt_shade($rgb, $def['amount']),
        default => $rgb,
    };
}

/**
 * Alle Shades einer Farbe: eingetragene Werte ($overrides: Shade-Name => Wert), sonst aus der Grundfarbe berechnet.
 * ['100' => '#…', …] in der Reihenfolge der Skala.
 */
function zpt_color_shades(array $base, array $overrides = []): array {
    $shades = [];
    foreach (array_keys(zpt_color_scale()) as $shade) {
        $shade = (string) $shade;
        $rgb   = zpt_color_to_rgb($overrides[$shade] ?? '') ?: zpt_color_shade($base, $shade);
        $shades[$shade] = zpt_rgb_to_hex($rgb);
    }
    return $shades;
}

// ----------------------------------------------------------------- PALETTE + SYSTEM COLORS

/**
 * Farbpalette aus Einstellungen → Farben → Palette (Repeater zpt_palette):
 * ['blau' => ['100' => '#…', …], …]. Eingetragene Shades, sonst aus der Grundfarbe berechnet.
 */
function zpt_color_palette(): array {
    static $palette = null;
    if ($palette !== null) {
        return $palette;
    }

    $palette = [];
    $base    = zpt_color_base();
    foreach ((array) (get_field('zpt_palette', 'option') ?: []) as $row) {
        $name = zpt_color_name($row['name'] ?? '');
        $rgb  = zpt_color_to_rgb($row["shade_{$base}"] ?? '');
        if (!$name || !$rgb || isset($palette[$name])) {
            continue;
        }
        $overrides = [];
        foreach (array_keys(zpt_color_scale()) as $shade) {
            $overrides[(string) $shade] = $row["shade_{$shade}"] ?? '';
        }
        $palette[$name] = zpt_color_shades($rgb, $overrides);
    }

    return $palette;
}

/**
 * Systemfarben aus Einstellungen → Farben → Systemfarben (Repeater zpt_system_colors: Name + Farbe):
 * ['primary' => 'lightblue', 'accent' => 'orange-300', 'section' => '#f5f5f5', …]
 * (nur gültige Namen und Farben, siehe zpt_system_color_shades()).
 */
function zpt_system_color_names(): array {
    $names = [];
    foreach ((array) (get_field('zpt_system_colors', 'option') ?: []) as $row) {
        $name  = zpt_system_color_name($row['name'] ?? '');
        $color = is_string($row['color'] ?? null) ? strtolower(trim($row['color'])) : '';
        if ($name && !isset($names[$name]) && zpt_system_color_shades($color)) {
            $names[$name] = $color;
        }
    }
    return $names;
}

/**
 * Shades einer Systemfarbe aus ihrem Wert (Auswahl Farbe + Shading wie in den Props, nur Palettenfarben):
 * - Palettenfarbe („lightblue“): deren Shades, mit eingetragenen Überschreibungen
 * - Palettenfarbe mit Shade („lightblue-300“) oder eigene Farbe (Hex, Farbname): wird die Grundfarbe,
 *   die Shades werden daraus berechnet
 * Ungültig → null.
 */
function zpt_system_color_shades(string $value): ?array {
    $palette = zpt_color_palette();
    if (isset($palette[$value])) {
        return $palette[$value];
    }
    foreach ($palette as $name => $shades) {
        foreach ($shades as $shade => $hex) {
            if ("{$name}-{$shade}" === $value) {
                return zpt_color_shades(zpt_hex_to_rgb($hex));
            }
        }
    }
    $rgb = zpt_color_to_rgb($value);
    return $rgb ? zpt_color_shades($rgb) : null;
}

/**
 * Systemfarben mit ihren Shades: ['primary' => ['100' => '#…', …], …].
 * Nur angelegte – für nicht angelegte Bootstrap-Farben gilt Bootstraps Wert (--bs-primary …).
 */
function zpt_system_color_palette(): array {
    return array_map('zpt_system_color_shades', zpt_system_color_names());
}

/**
 * Namen aus einem Repeater der Farbeinstellungen, roh per get_option (auch in acf/load_field nutzbar):
 * zpt_option_color_names('zpt_palette') → ['lightblue', 'cyan', …].
 */
function zpt_option_color_names(string $repeater): array {
    $names = [];
    $rows  = (int) get_option("options_{$repeater}", 0);
    for ($i = 0; $i < $rows; $i++) {
        $names[] = strtolower(trim((string) get_option("options_{$repeater}_{$i}_name", '')));
    }
    return array_filter($names);
}

/**
 * Name einer Systemfarbe prüfen: Kleinbuchstaben, Ziffern, Bindestrich („primary“, „accent“),
 * nicht wie eine Palettenfarbe, nicht mit der Endung eines Shades. Ungültig → ''.
 */
function zpt_system_color_name($name): string {
    $name = is_string($name) ? strtolower(trim($name)) : '';
    if (!preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $name) || in_array($name, ZPT_COLOR_RESERVED, true)) {
        return '';
    }
    foreach (array_keys(zpt_color_scale()) as $shade) {
        if (str_ends_with($name, "-{$shade}")) {
            return '';
        }
    }
    return in_array($name, zpt_option_color_names('zpt_palette'), true) ? '' : $name;
}

/**
 * Name einer Palettenfarbe prüfen: Kleinbuchstaben, Ziffern, Bindestrich („ecos-blau“),
 * nicht wie eine Bootstrap- oder angelegte Systemfarbe, nicht mit der Endung eines Shades („-300“, „-light“). Ungültig → ''.
 */
function zpt_color_name($name): string {
    $name = is_string($name) ? strtolower(trim($name)) : '';
    if (!preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $name)) {
        return '';
    }
    foreach (array_keys(zpt_color_scale()) as $shade) {
        if (str_ends_with($name, "-{$shade}")) {
            return '';
        }
    }
    $taken = array_merge(ZPT_COLOR_RESERVED, ZPT_BOOTSTRAP_THEME_COLORS, ZPT_BOOTSTRAP_BASIC_COLORS, zpt_option_color_names('zpt_system_colors'));
    return in_array($name, $taken, true) ? '' : $name;
}

/**
 * Tokens der Systemfarben und der Farbpalette:
 * ['primary' => '#…', 'primary-100' => '#…', …, 'blau' => '#…', 'blau-100' => '#…', …].
 * Ohne Shade ist die Grundfarbe gemeint.
 */
function zpt_palette_tokens(): array {
    static $tokens = null;
    if ($tokens !== null) {
        return $tokens;
    }

    $tokens = [];
    foreach (array_merge(zpt_system_color_palette(), zpt_color_palette()) as $name => $shades) {
        $tokens[$name] = $shades[zpt_color_base()];
        foreach ($shades as $shade => $hex) {
            $tokens["{$name}-{$shade}"] = $hex;
        }
    }
    return $tokens;
}

/**
 * Alle Baukasten-Farben als Tokens (Systemfarben und Farbpalette).
 */
function zpt_color_tokens(): array {
    return zpt_palette_tokens();
}

/**
 * Wert eines Farbfelds → CSS-Wert für style="…": Baukasten-Farbe als CSS-Variable
 * (folgt späteren Änderungen in den Einstellungen), sonst Farbname/Hexwert; ungültig → ''.
 */
function zpt_color_value($value): string {
    $value = is_string($value) ? strtolower(trim($value)) : '';
    if ($value === '') {
        return '';
    }

    if (isset(zpt_color_tokens()[$value])) {
        return 'var(--theme-' . $value . ')';
    }
    if ($value === 'transparent' || $value === 'currentcolor' || zpt_color_to_rgb($value)) {
        return $value;
    }
    return '';
}

/**
 * CSS-Variablen der Palette: --theme-blau, --theme-blau-{shade}.
 */
function zpt_palette_vars(): array {
    $vars = [];
    foreach (zpt_color_palette() as $name => $shades) {
        $vars["--theme-{$name}"] = $shades[zpt_color_base()];
        foreach ($shades as $shade => $hex) {
            $vars["--theme-{$name}-{$shade}"] = $hex;
        }
    }
    return $vars;
}

/**
 * Farbklassen mit Shades (die Shade-Namen kommen aus den Einstellungen, daher nicht in zpt.css):
 * - Systemfarben: .text-/.bg-/.border-/.color-/.fill-primary-{shade} (ohne Shade bringen sie Bootstrap/zpt.css mit, außer bei eigenen Namen)
 * - Palette: dasselbe für .…-blau und .…-blau-{shade}
 */
function zpt_color_classes_css(): string {
    $rule = fn(string $c) => ".text-{$c}{color:var(--theme-{$c})!important}"
        . ".bg-{$c}{background-color:var(--theme-{$c})!important}"
        . ".border-{$c}{border-color:var(--theme-{$c})!important}"
        . ".color-{$c}{color:var(--theme-{$c})}"
        . ".fill-{$c},.fill-{$c} svg{fill:var(--theme-{$c})}";

    $css = '';
    foreach (zpt_system_color_palette() as $name => $shades) {
        // Grundklassen bringen Bootstrap bzw. zpt.css mit, eigene Namen (accent) brauchen sie hier
        if (!in_array($name, array_merge(ZPT_BOOTSTRAP_THEME_COLORS, ZPT_BOOTSTRAP_BASIC_COLORS), true)) {
            $css .= $rule($name);
        }
        foreach (array_keys($shades) as $shade) {
            $css .= $rule("{$name}-{$shade}");
        }
    }
    foreach (zpt_color_palette() as $name => $shades) {
        $css .= $rule($name);
        foreach (array_keys($shades) as $shade) {
            $css .= $rule("{$name}-{$shade}");
        }
    }
    return $css;
}

// ----------------------------------------------------------------- WORDPRESS-FARBPALETTE
// Die Farben aus dem Tab „WordPress“ (ohne Auswahl: alle Grundfarben) als Farbpalette von WordPress: erscheinen in jeder nativen
// Farbauswahl (Core-Blocks, Global Styles …) und im Textfarben-Knopf von TinyMCE (klassischer Editor, ACF-WYSIWYG).
// WordPress erzeugt dazu --wp--preset--color--{name} und .has-{name}-color / .has-{name}-background-color.
// Die WordPress-Standardfarben werden ausgeblendet. Abschalten: add_filter('zpt/wp_palette', '__return_empty_array');

/**
 * Farben aus Einstellungen → Farben → WordPress (Repeater zpt_wp_colors): ['primary', 'primary-300', …]
 * (nur gültige Tokens). Leer = keine Auswahl getroffen, alles erscheint.
 */
function zpt_wp_color_tokens(): array {
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    $tokens = zpt_color_tokens();
    $list   = [];
    foreach ((array) (get_field('zpt_wp_colors', 'option') ?: []) as $row) {
        $token = is_string($row['color'] ?? null) ? strtolower(trim($row['color'])) : '';
        if (isset($tokens[$token]) && !in_array($token, $list, true)) {
            $list[] = $token;
        }
    }
    return $list;
}

/**
 * Palettenfarben mit allen Shades als Tokens, in Skalen-Reihenfolge (die Grundfarbe ohne Endung):
 * ['lightblue-100', …, 'lightblue', 'lightblue-600', …] – für „Gesamte Farbpalette übernehmen“.
 */
function zpt_wp_palette_shade_tokens(): array {
    $base   = zpt_color_base();
    $tokens = [];
    foreach (zpt_color_palette() as $name => $shades) {
        foreach (array_keys($shades) as $shade) {
            $tokens[] = (string) $shade === $base ? (string) $name : "{$name}-{$shade}";
        }
    }
    return $tokens;
}

/**
 * Farben für WordPress: [['slug' => 'primary', 'name' => 'Primary', 'color' => '#…'], …].
 * Die Auswahl im Tab „WordPress“, sonst alle Grundfarben (Systemfarben, dann Palette).
 */
function zpt_wp_palette(): array {
    if (!did_action('acf/init')) {
        return [];
    }
    $tokens = zpt_color_tokens();
    $list   = zpt_wp_color_tokens() ?: array_keys(array_merge(zpt_system_color_palette(), zpt_color_palette()));
    $colors = [];
    foreach ($list as $token) {
        $token = (string) $token;
        if (isset($tokens[$token])) {
            $colors[] = ['slug' => $token, 'name' => ucfirst(str_replace('-', ' ', $token)), 'color' => $tokens[$token]];
        }
    }
    return apply_filters('zpt/wp_palette', $colors);
}

add_filter('wp_theme_json_data_theme', function ($theme_json) {
    $palette = zpt_wp_palette();
    if (!$palette) {
        return $theme_json;
    }
    return $theme_json->update_with([
        'version'  => 3,
        'settings' => ['color' => ['palette' => $palette, 'defaultPalette' => false]],
    ]);
});

// TinyMCE: Textfarben-Knopf mit denselben Farben
add_filter('tiny_mce_before_init', function ($init) {
    $map = [];
    foreach (zpt_wp_palette() as $color) {
        $map[] = ltrim($color['color'], '#');
        $map[] = $color['name'];
    }
    if ($map) {
        $init['textcolor_map'] = wp_json_encode($map);
        $init['textcolor_cols'] = (string) min(8, count($map) / 2);
    }
    return $init;
});

// ----------------------------------------------------------------- ADMIN: SHADE-SKALA

// --- Shade-Felder der Palette aus der Skala (lokale Unterfelder des Repeaters zpt_palette)
add_action('acf/include_fields', function () {
    $base  = zpt_color_base();
    $order = 1;
    foreach (array_keys(zpt_color_scale()) as $shade) {
        $shade   = (string) $shade;
        $is_base = $shade === $base;
        acf_add_local_field([
            'key'           => "field_zpt_settings_palette_shade_{$shade}",
            /* translators: %s: Name des Shades (z. B. 500) */
            'label'         => $is_base ? sprintf(__('%s · Grundfarbe', '12punkt-baukasten'), $shade) : $shade,
            'name'          => "shade_{$shade}",
            'type'          => 'text',
            'parent'        => 'field_zpt_settings_palette',
            'menu_order'    => $order++,
            'required'      => $is_base ? 1 : 0,
            'wrapper'       => ['width' => '', 'class' => 'zpt-color-field' . ($is_base ? ' zpt-color-base' : ''), 'id' => ''],
            'placeholder'   => $is_base ? '' : __('automatisch', '12punkt-baukasten'),
            'default_value' => '',
        ]);
    }
}, 20);

// --- Skala prüfen: gültige, eindeutige Namen, genau eine Grundfarbe
add_filter('acf/validate_value/key=field_zpt_settings_shade_scale', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $names = [];
    $bases = 0;
    foreach (is_array($value) ? $value : [] as $row) {
        $name = zpt_shade_name(wp_unslash($row['field_zpt_settings_shade_scale_name'] ?? ''));
        if (!$name) {
            return __('Shade-Namen: nur Kleinbuchstaben und Ziffern (z. B. 100, light).', '12punkt-baukasten');
        }
        if (isset($names[$name])) {
            /* translators: %s: Name des Shades */
            return sprintf(__('Der Shade-Name „%s“ ist mehrfach vergeben.', '12punkt-baukasten'), $name);
        }
        $names[$name] = true;
        $bases += ($row['field_zpt_settings_shade_scale_type'] ?? '') === 'base' ? 1 : 0;
    }
    if (!$names) {
        return __('Mindestens ein Shade (die Grundfarbe).', '12punkt-baukasten');
    }
    return $bases === 1 ? true : __('Genau ein Shade muss die Grundfarbe sein.', '12punkt-baukasten');
}, 10, 2);

// --- Grundfarbe umbenannt („500“ → „base“): Grundfarben der Palette mitnehmen (die bisherige gilt)
add_action('acf/save_post', function ($post_id) {
    if ($post_id === 'options') {
        $GLOBALS['zpt_color_base_before'] = zpt_color_base(true);
    }
}, 5);
add_action('acf/save_post', function ($post_id) {
    $old = $GLOBALS['zpt_color_base_before'] ?? '';
    $new = $post_id === 'options' ? zpt_color_base(true) : '';
    if (!$old || !$new || $old === $new) {
        return;
    }
    $rows = (int) get_option('options_zpt_palette', 0);
    for ($i = 0; $i < $rows; $i++) {
        $value = get_option("options_zpt_palette_{$i}_shade_{$old}", '');
        if ($value !== '') {
            update_option("options_zpt_palette_{$i}_shade_{$new}", $value);
            update_option("_options_zpt_palette_{$i}_shade_{$new}", "field_zpt_settings_palette_shade_{$new}");
        }
    }
}, 20);

// ----------------------------------------------------------------- ADMIN: SYSTEMFARBEN

// --- Farbe: nur Palettenfarben (mit Shade) oder eigene Farbe, keine anderen Systemfarben
add_filter('acf/validate_value/key=field_zpt_settings_system_colors_color', function ($valid, $value) {
    if ($valid !== true || !is_string($value) || $value === '') {
        return $valid;
    }
    return zpt_system_color_shades(strtolower(trim(wp_unslash($value))))
        ? true
        : __('Farbe aus der Palette (mit Shade) oder eigene Farbe (Hexwert, Farbname) – keine andere Systemfarbe.', '12punkt-baukasten');
}, 20, 2);

// --- Namen der Systemfarben prüfen (gültig und eindeutig)
add_filter('acf/validate_value/key=field_zpt_settings_system_colors_name', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $name = zpt_system_color_name($value);
    if (!$name) {
        return __('Nur Kleinbuchstaben, Ziffern und Bindestrich (z. B. primary oder accent), nicht wie eine Palettenfarbe, nicht mit der Endung eines Shades.', '12punkt-baukasten');
    }

    $rows  = $_POST['acf']['field_zpt_settings_system_colors'] ?? [];
    $count = 0;
    foreach (is_array($rows) ? $rows : [] as $row) {
        $count += strtolower(trim((string) wp_unslash($row['field_zpt_settings_system_colors_name'] ?? ''))) === $name ? 1 : 0;
    }
    return $count > 1 ? __('Der Name ist mehrfach vergeben.', '12punkt-baukasten') : true;
}, 10, 2);

// --- Namen der Palettenfarben prüfen (gültig und eindeutig)
add_filter('acf/validate_value/key=field_zpt_settings_palette_name', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $name = zpt_color_name($value);
    if (!$name) {
        return __('Nur Kleinbuchstaben, Ziffern und Bindestrich (z. B. ecos-blau), nicht wie eine Bootstrap- oder Systemfarbe (primary, dark …), nicht mit der Endung eines Shades (z. B. -300).', '12punkt-baukasten');
    }

    $rows  = $_POST['acf']['field_zpt_settings_palette'] ?? [];
    $count = 0;
    foreach (is_array($rows) ? $rows : [] as $row) {
        $count += strtolower(trim((string) wp_unslash($row['field_zpt_settings_palette_name'] ?? ''))) === $name ? 1 : 0;
    }
    return $count > 1 ? __('Der Name ist mehrfach vergeben.', '12punkt-baukasten') : true;
}, 10, 2);

// ----------------------------------------------------------------- ADMIN: FARBFELDER

add_action('acf/input/admin_enqueue_scripts', function () {
    wp_enqueue_script('zpt-admin-colors', ZPT_URL . 'assets/js/admin-colors.js', ['acf-input'], filemtime(ZPT_PATH . 'assets/js/admin-colors.js'), true);
    wp_localize_script('zpt-admin-colors', 'zptColors', [
        'tokens'  => zpt_color_tokens(),
        'base'    => zpt_color_base(),
        'scale'   => array_map(fn($name, $s) => ['name' => (string) $name] + $s, array_keys(zpt_color_scale()), zpt_color_scale()),
        'presets' => ZPT_SHADE_PRESETS,
        'bootstrap' => array_merge(ZPT_BOOTSTRAP_THEME_COLORS, ZPT_BOOTSTRAP_BASIC_COLORS),
        'palette'   => array_map('strval', array_keys(zpt_color_palette())),
        'allowed'   => zpt_wp_color_tokens(),
        'wpLists'   => [
            'palette' => zpt_wp_palette_shade_tokens(),
            'base'    => array_map('strval', array_keys(zpt_color_palette())),
            'system'  => array_map('strval', array_keys(zpt_system_color_palette())),
        ],
    ]);

    wp_add_inline_style('acf-input', '
        .zpt-color-field .acf-input-wrap {display: flex; align-items: stretch; gap: 6px;}
        .zpt-color-field .acf-input-wrap input[type=text] {flex: 1 1 auto; min-width: 0;}
        .zpt-color-swatch {position: relative; flex: 0 0 30px; min-height: 30px; border: 1px solid #8c8f94; border-radius: 4px; overflow: hidden;
            background: repeating-conic-gradient(#ddd 0 25%, #fff 0 50%) 0 0 / 10px 10px;}
        .zpt-color-swatch span {position: absolute; inset: 0;}
        .zpt-color-swatch input[type=color] {position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: 0; padding: 0;}
        .zpt-color-field.-invalid input[type=text] {border-color: #d63638;}
        .zpt-color-field.-tokens .acf-input-wrap select, .zpt-color-field.-tokens .acf-input-wrap input[type=text] {flex: 1 1 0; min-width: 0; max-width: none;}
        .zpt-color-field.-tokens:not(.-custom) .acf-input-wrap input[type=text],
        .zpt-color-field.-tokens.-custom .acf-input-wrap .zpt-color-shade {display: none;}
        .zpt-color-shades .acf-table td.zpt-color-base {background: #f0f6fc;}
        .zpt-color-shades .acf-table td.zpt-color-field .acf-input-wrap {flex-direction: column-reverse; gap: 4px;}
        .zpt-color-shades .acf-table td.zpt-color-field .zpt-color-swatch {flex: 0 0 26px; min-height: 26px;}
        .zpt-color-shades .acf-table td.zpt-color-field input[type=text] {font-size: 12px; padding: 0 4px; min-height: 28px;}
        .zpt-color-shades .acf-table td.zpt-color-field.-auto input[type=text]:not(:focus) {border-color: transparent; background: transparent; box-shadow: none;}
        .zpt-shade-scale .acf-row.-base td {background: #f0f6fc;}
    ');
});

// --- Eingabe prüfen
add_filter('acf/validate_value/type=text', function ($valid, $value, $field) {
    if ($valid !== true || $value === '' || !str_contains($field['wrapper']['class'] ?? '', 'zpt-color-field')) {
        return $valid;
    }
    $tokens = str_contains($field['wrapper']['class'], 'zpt-color-tokens');
    $ok     = $tokens ? zpt_color_value($value) !== '' : (bool) zpt_color_to_rgb($value);

    return $ok ? true : ($tokens
        ? __('Baukasten-Farbe (z. B. primary-300), HTML-Farbname (z. B. tomato) oder Hexwert (#ff6347).', '12punkt-baukasten')
        : __('HTML-Farbname (z. B. tomato) oder Hexwert (#ff6347).', '12punkt-baukasten'));
}, 10, 3);
