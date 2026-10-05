<?php
/**
 * Buttons (Baukasten → Buttons).
 *
 * Tab „Grundeinstellungen“: Repeater „Größen“ – nur Name und CSS-Klasse (md = Standard ohne Klasse,
 *                           sonst .btn-{klasse}: sm, lg, xl …). Vorbelegt mit Standard, Klein, Groß.
 * Tab „Templates“:          Repeater – Schriftrolle, Gewicht, Großbuchstaben, Rahmen (Breite, Radius),
 *                           Hintergrund (an/aus), Outline (Schrift in Rahmenfarbe) und je Größe
 *                           Schriftgröße, Zeilenhöhe, Laufweite, Innenabstände.
 *                           „btn default“ (Kürzel default) gilt für alle .btn und lässt sich nicht
 *                           löschen; weitere Templates werden zu .btn-{kürzel} (.btn-pill, .btn-outlined).
 * Tab „Varianten“:          Repeater – eine Klasse (.btn-primary, .btn-outline-danger …) genauer
 *                           definieren oder eine eigene anlegen (.btn-black): Farben normal und
 *                           Hover/Aktiv (Schrift-, Hintergrund-, Rahmenfarbe), Template (übernimmt
 *                           dessen Werte), Rahmen (Breite, Radius), Schrift (Rolle, Gewicht,
 *                           Großbuchstaben) und je Größe wie bei den Templates.
 *
 * Werte je Größe: eigener Wert → btn default → Bootstrap-Wert der Größe → Standard (md).
 * Ausgabe als CSS nach zpt.css (zpt_style_css() in settings.php) über die Bootstrap-Variablen
 * --bs-btn-*. Reihenfolge: .btn → weitere Größen → Templates → Varianten (spätere Regel gewinnt),
 * zuletzt die Größen je Breakpoint (.btn-{bp}-{kürzel}, z. B. .btn-md-lg) in Media Queries.
 *
 * Button-Block: Stil = Varianten (falls angelegt, sonst Farben/Outline) + Link-Stile; Größe je
 * Breakpoint; kein Template (kommt über die Variante).
 * Ohne Variante kommen die Farben aus Baukasten → Farben (scss/baukasten/_links-btns.scss).
 *
 * Felder: acf-json/group_zpt_settings_buttons.json
 */

defined('ABSPATH') || exit;

// Standard-Größen (Bootstrap-Werte): Vorbelegung der Größen und von btn default, Ausweichwerte.
// CSS-Klasse „md“ = .btn ohne Größen-Klasse
const ZPT_BUTTON_SIZES = [
    'md' => ['label' => 'Standard', 'font_size' => '1rem',     'line_height' => '1.5', 'padding_y' => '0.375rem', 'padding_x' => '0.75rem'],
    'sm' => ['label' => 'Klein',    'font_size' => '0.875rem', 'line_height' => '1.5', 'padding_y' => '0.25rem',  'padding_x' => '0.5rem'],
    'lg' => ['label' => 'Groß',     'font_size' => '1.25rem',  'line_height' => '1.5', 'padding_y' => '0.5rem',   'padding_x' => '1rem'],
];

// Vorbelegung der Templates (solange nie gespeichert). Kürzel „default“ = .btn, nicht löschbar.
const ZPT_BUTTON_TEMPLATES = [
    'default'  => ['label' => 'btn default', 'background' => 1],
    'pill'     => ['label' => 'Pill', 'border_radius' => '50rem', 'background' => 1],
    'outlined' => ['label' => 'Outlined', 'outline' => 1, 'background' => 0],
];

// Kürzel, die keine Größe, kein Template, keine eigene Klasse sein können (Klassen von zpt.css / Bootstrap)
const ZPT_BUTTON_RESERVED = ['link', 'group', 'check', 'close', 'toolbar', 'outline'];

// Laufweite ohne eigenen Wert (wie :root in _typography.scss)
const ZPT_BUTTON_LS_DEFAULT = 'var(--zpt-ls-400, normal)';

// Werte je Größe (Templates, Varianten): Feld → Variable
const ZPT_BUTTON_SIZE_VARS = [
    'font_size'      => '--bs-btn-font-size',
    'line_height'    => '--bs-btn-line-height',
    'letter_spacing' => '--zpt-ls',
    'padding_y'      => '--bs-btn-padding-y',
    'padding_x'      => '--bs-btn-padding-x',
];

// Farben mit .btn-* / .btn-outline-* in zpt.css ($theme-colors)
const ZPT_BUTTON_COLORS = ['primary', 'secondary', 'tertiary', 'quaternary', 'info', 'success', 'danger', 'warning', 'light', 'dark'];

// ----------------------------------------------------------------- WERTE

/**
 * Schriftgröße: CSS-Wert (16px, 1rem) oder Stufe der Schriftgrößen-Skala (copy, copy-l …) → var(--theme-font-size-…).
 */
function zpt_button_font_size(string $value): string {
    $step = str_replace('-', '_', strtolower($value));
    $steps = zpt_type_steps();
    if (isset($steps[$step])) {
        return 'var(--theme-font-size-' . zpt_type_css($step) . ', ' . $steps[$step]['size'] . ')';
    }
    return $value;
}

/**
 * Farbfeld → RGB zum Rechnen (Kontrast, Hover). Baukasten-Farbe über ihren aktuellen Hexwert.
 */
function zpt_button_color_rgb($value): ?array {
    $value = is_string($value) ? strtolower(trim($value)) : '';
    $hex   = zpt_color_tokens()[$value] ?? '';
    return $hex ? zpt_hex_to_rgb($hex) : zpt_color_to_rgb($value);
}

/**
 * Name einer eigenen Klasse ohne „btn-“: „black“, „ghost-light“. Ungültig → ''.
 */
function zpt_button_custom_name($name): string {
    $name = is_string($name) ? strtolower(trim($name)) : '';
    $name = preg_replace('/^\.?btn-/', '', $name);
    return preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $name) ? $name : '';
}

/**
 * Klasse einer Varianten-Zeile („btn-primary“, „btn-black“) oder ''.
 */
function zpt_button_variant_class(string $class, $custom): string {
    if ($class === 'custom') {
        $name = zpt_button_custom_name($custom);
        return $name ? 'btn-' . $name : '';
    }
    return preg_match('/^btn-[a-z0-9-]+$/', $class) ? $class : '';
}

/**
 * Laufweite: eigener Wert, sonst die des Gewichts (Typografie). [] = nichts setzen.
 */
function zpt_button_letter_spacing(string $ls, string $weight): array {
    if ($ls !== '') {
        return ['--zpt-ls' => $ls];
    }
    return $weight !== '' ? ['--zpt-ls' => "var(--zpt-ls-{$weight}, normal)"] : [];
}

/**
 * font-family einer Schrift-Rolle – gleiche Ausweichkette wie .font-serif usw. (_typography.scss).
 */
function zpt_button_role_font(string $role): string {
    $chains = [
        'base'       => ['--theme-font-family'],
        'sans_serif' => ['--theme-font-family-sans-serif', '--theme-font-family'],
        'serif'      => ['--theme-font-family-serif'],
        'deco'       => ['--theme-font-family-deco', '--theme-font-family-sans-serif', '--theme-font-family'],
        'mono'       => ['--theme-font-family-mono'],
    ];
    if (!isset($chains[$role], ZPT_FONT_ROLES[$role])) {
        return '';
    }
    $value = ZPT_FONT_ROLES[$role]['generic'];
    foreach (array_reverse($chains[$role]) as $var) {
        $value = "var({$var}, {$value})";
    }
    return $value;
}

/**
 * Längenangabe: Zahl ohne Einheit → px („4“ → „4px“), 0 bleibt 0, sonst unverändert.
 */
function zpt_button_length($value): string {
    $value = zpt_css_value($value);
    return preg_match('/^\d+(\.\d+)?$/', $value) && (float) $value != 0 ? $value . 'px' : $value;
}

/**
 * Rahmenbreite: wie zpt_button_length(), „none“/„keine“ = 0.
 */
function zpt_button_border_width($value): string {
    $value = zpt_css_value($value);
    return in_array(strtolower($value), ['none', 'keine'], true) ? '0' : zpt_button_length($value);
}

/**
 * Eckenradius als Variable und Eigenschaft – zpt.css ist mit $enable-rounded: false gebaut,
 * .btn hat dann kein border-radius.
 */
function zpt_button_radius(string $radius): array {
    return $radius === '' ? [] : ['--bs-btn-border-radius' => $radius, 'border-radius' => 'var(--bs-btn-border-radius)'];
}

// ----------------------------------------------------------------- CSS

/**
 * CSS für die Buttons aus den Einstellungen.
 */
function zpt_buttons_css(): string {
    $rules     = [];
    $media     = [];   // Selektor (ohne Breakpoint) → Größe → Deklarationen, für .btn-{bp}-{größe}
    $templates = zpt_button_templates();
    $sizes     = array_keys(zpt_button_size_list());
    $sizes     = in_array('md', $sizes, true) ? array_merge(['md'], array_diff($sizes, ['md'])) : $sizes;

    // --- btn default (.btn) – Rahmenbreite leer = kein Rahmen; Werte je Größe vollständig
    $default = $templates['default'] ?? [];
    $btn     = zpt_button_template_decl($default) + ['--bs-btn-border-width' => '0'];
    $radius  = zpt_button_length($default['border_radius'] ?? '');
    $full    = zpt_button_full_sizes(zpt_button_size_values($default), $sizes, $btn['--zpt-ls'] ?? ZPT_BUTTON_LS_DEFAULT);

    // .btn-Regel steht hinter Bootstraps .btn-sm/.btn-lg: deshalb bekommt jede Größe den vollständigen Satz
    $rules['.btn'] = $btn + ($full['md'] ?? []);
    foreach ($sizes as $slug) {
        if ($slug === 'md') {
            continue;
        }
        $decl = $full[$slug] ?? [];
        // Radius aus btn default auch für sm/lg (Bootstrap setzt dort eigene Radien)
        if (in_array($slug, ['sm', 'lg'], true)) {
            $decl += zpt_button_radius($radius);
        }
        $rules[".btn-{$slug},.btn-group-{$slug}>.btn"] = $decl;
    }
    $media[''] = $full;

    // --- weitere Templates (.btn-pill …); Outline/Hintergrund mit höherer Spezifität, damit sie die Farben
    //     der Farbklassen und Varianten (.btn-primary) überschreiben
    foreach ($templates as $slug => $row) {
        if ($slug !== 'default') {
            $rules[".btn-{$slug}"] = zpt_button_template_decl($row);
            [$size_rules, $media[".btn-{$slug}"]] = zpt_button_size_rules(".btn-{$slug}", zpt_button_size_values($row), $full, $sizes, $rules[".btn-{$slug}"]['--zpt-ls'] ?? $btn['--zpt-ls'] ?? ZPT_BUTTON_LS_DEFAULT);
            $rules = zpt_button_merge_rules($rules, $size_rules);
        }
        if ($colors = zpt_button_outline_decl($row)) {
            $rules[$slug === 'default' ? '.btn.btn' : ".btn.btn-{$slug}"] = $colors;
        }
    }

    // --- Varianten: erst das Template der Variante (nicht btn default – das gilt ohnehin), dann eigene Werte
    foreach (zpt_button_variants() as $class => $row) {
        $tpl  = (string) ($row['template'] ?? '');
        $base = ($tpl !== '' && $tpl !== 'default' && isset($templates[$tpl])) ? $templates[$tpl] : [];

        $rules['.' . $class] = array_merge($base ? zpt_button_template_decl($base) : [], zpt_button_variant_decl($row));
        $values = array_replace_recursive($base ? zpt_button_size_values($base) : [], zpt_button_size_values($row));
        [$size_rules, $media['.' . $class]] = zpt_button_size_rules('.' . $class, $values, $full, $sizes, $rules['.' . $class]['--zpt-ls'] ?? $btn['--zpt-ls'] ?? ZPT_BUTTON_LS_DEFAULT);
        $rules = zpt_button_merge_rules($rules, $size_rules);

        if ($base && ($colors = zpt_button_outline_decl($base))) {
            $rules[".btn.{$class}"] = $colors;
        }
    }
    $rules = array_filter($rules);

    // --- Button-Gruppen: innere Ecken gerade (wie Bootstrap mit $enable-rounded)
    if (array_filter($rules, fn($decl) => isset($decl['border-radius']))) {
        $rules['.btn-group>.btn:not(:last-child):not(.dropdown-toggle),.btn-group>.btn-group:not(:last-child)>.btn'] = ['border-top-right-radius' => 0, 'border-bottom-right-radius' => 0];
        $rules['.btn-group>.btn:nth-child(n+3),.btn-group>:not(.btn-check)+.btn,.btn-group>.btn-group:not(:first-child)>.btn'] = ['border-top-left-radius' => 0, 'border-bottom-left-radius' => 0];
    }
    // Überlappung = Rahmenbreite des Buttons (Bootstrap nimmt --bs-border-width)
    $rules['.btn-group>:not(.btn-check:first-child)+.btn,.btn-group>.btn-group:not(:first-child)'] = ['margin-left' => 'calc(-1 * var(--bs-btn-border-width))'];

    $css = '';
    foreach ($rules as $selector => $decl) {
        $css .= $selector . '{' . zpt_button_decl_css($decl) . '}';
    }

    // --- Größen je Breakpoint (Button-Block: .btn-md-lg = ab MD groß) – jeweils der vollständige Satz,
    //     damit jeder Wechsel alle Werte setzt; Templates/Varianten mit eigenen Werten als .btn-pill.btn-md-lg
    foreach (zpt_breakpoint_min() as $bp => $min_width) {
        $css .= '@media (min-width:' . $min_width . 'px){';
        foreach ($media as $selector => $per_size) {
            foreach ($per_size as $slug => $decl) {
                if ($decl) {
                    $css .= "{$selector}.btn-{$bp}-{$slug}{" . zpt_button_decl_css($decl) . '}';
                }
            }
        }
        $css .= '}';
    }
    return $css;
}

/**
 * Deklarationen als CSS-Text.
 */
function zpt_button_decl_css(array $decl): string {
    return implode(';', array_map(fn($p, $v) => "{$p}:{$v}", array_keys($decl), $decl));
}

/**
 * Werte je Größe aus dem Repeater „sizes“ eines Templates/einer Variante:
 * ['md' => ['--bs-btn-font-size' => '1rem', …], 'sm' => […]] – nur gesetzte Werte.
 */
function zpt_button_size_values(array $row): array {
    $values = [];
    foreach ((array) ($row['sizes'] ?? []) as $size) {
        $slug = zpt_button_size_slug($size['size'] ?? '');
        if (!$slug) {
            continue;
        }
        foreach (ZPT_BUTTON_SIZE_VARS as $field => $var) {
            $value = zpt_button_size_value($field, $size[$field] ?? '');
            if ($value !== '') {
                $values[$slug][$var] = $value;
            }
        }
    }
    return $values;
}

/**
 * Ein Wert je Größe als CSS: Schriftgröße auch als Stufe (copy, l …), Abstände ohne Einheit = px.
 */
function zpt_button_size_value(string $field, $value): string {
    if (in_array($field, ['padding_y', 'padding_x'], true)) {
        return zpt_button_length($value);
    }
    $value = zpt_css_value($value);
    return ($field === 'font_size' && $value !== '') ? zpt_button_font_size($value) : $value;
}

/**
 * Vollständige Werte je Größe für btn default: eigener Wert → Bootstrap-Wert der Größe →
 * Standard (md) → Bootstrap-Standard. Laufweite hat keinen Bootstrap-Wert: nur wenn eine Größe sie
 * setzt, bekommen die übrigen $ls (Laufweite des Gewichts bzw. Standard) – sonst nicht gesetzt.
 */
function zpt_button_full_sizes(array $values, array $sizes, string $ls): array {
    $full = [];
    foreach ($sizes as $slug) {
        foreach (ZPT_BUTTON_SIZE_VARS as $field => $var) {
            $candidates = [
                $values[$slug][$var] ?? '',
                zpt_button_size_value($field, ZPT_BUTTON_SIZES[$slug][$field] ?? ''),
                $values['md'][$var] ?? '',
                zpt_button_size_value($field, ZPT_BUTTON_SIZES['md'][$field] ?? ''),
            ];
            foreach ($candidates as $value) {
                if ($value !== '') {
                    $full[$slug][$var] = $value;
                    break;
                }
            }
        }
    }
    if (array_filter($full, fn($decl) => isset($decl['--zpt-ls']))) {
        foreach ($sizes as $slug) {
            $full[$slug]['--zpt-ls'] ??= $ls;
        }
    }
    return $full;
}

/**
 * Regeln je Größe für ein Template/eine Variante (Selektor z. B. „.btn-pill“).
 * Nur Variablen, die es selbst setzt; die übrigen Größen bekommen dafür den Wert von btn default
 * zurück – sonst würde .btn-pill (nach .btn-sm) auch Klein überschreiben.
 * Laufweite ohne Wert: $ls (Laufweite des Gewichts des Templates/der Variante bzw. von btn default).
 * Standard → .btn-pill, andere → .btn-pill.btn-sm; Rückgabe: [Regeln, Werte je Größe für die Breakpoints].
 */
function zpt_button_size_rules(string $selector, array $values, array $full, array $sizes, string $ls): array {
    $vars = [];
    foreach ($values as $decl) {
        $vars += array_flip(array_keys($decl));
    }
    if (!$vars) {
        return [[], []];
    }

    $rules = [];
    $per   = [];
    foreach ($sizes as $slug) {
        $decl = [];
        foreach (array_keys($vars) as $var) {
            $value = $values[$slug][$var] ?? ($full[$slug][$var] ?? ($var === '--zpt-ls' ? $ls : ''));
            if ($value !== '') {
                $decl[$var] = $value;
            }
        }
        $per[$slug] = $decl;
        if ($decl) {
            $rules[$slug === 'md' ? $selector : "{$selector}.btn-{$slug}"] = $decl;
        }
    }
    return [$rules, $per];
}

/**
 * Varianten aus den Einstellungen: ['btn-primary' => Zeile, 'btn-black' => Zeile, …].
 * Gibt es eine Klasse mehrfach, gilt die letzte Zeile.
 */
function zpt_button_variants(): array {
    $variants = [];
    foreach ((array) (get_field('zpt_btn_variants', 'option') ?: []) as $row) {
        $class = zpt_button_variant_class((string) ($row['class'] ?? ''), $row['custom'] ?? '');
        if ($class) {
            $variants[$class] = $row;
        }
    }
    return $variants;
}

/**
 * Deklarationen einer Variante.
 * Normal: Schriftfarbe leer = Kontrastfarbe zum Hintergrund, Rahmenfarbe leer = Hintergrund.
 * Hover:  leer = 30 % dunkler als normal (wie Bootstrap), Schriftfarbe = normal bzw. Kontrastfarbe,
 *         Rahmenfarbe = Hintergrund Hover.
 * Aktiv = Hover, Deaktiviert = normal.
 */
function zpt_button_variant_decl(array $row): array {
    $decl   = [];
    $normal = (array) ($row['normal'] ?? []);
    $hover  = (array) ($row['hover'] ?? []);
    $hex    = fn(?array $rgb) => $rgb ? zpt_rgb_to_hex($rgb) : '';

    // --- normal (Baukasten-Farbe → var(--theme-…), folgt Änderungen der Farben)
    $bg_rgb     = zpt_button_color_rgb($normal['background'] ?? '');
    $border_rgb = zpt_button_color_rgb($normal['border'] ?? '') ?: $bg_rgb;
    $bg         = zpt_color_value($normal['background'] ?? '');
    $border     = zpt_color_value($normal['border'] ?? '') ?: $bg;
    $color      = zpt_color_value($normal['color'] ?? '') ?: ($bg_rgb ? zpt_contrast_color($bg_rgb) : '');

    // --- hover (aus normal gerechnet, wenn leer)
    $h_bg_rgb = zpt_button_color_rgb($hover['background'] ?? '') ?: ($bg_rgb ? zpt_shade($bg_rgb, .3) : null);
    $h_bg     = zpt_color_value($hover['background'] ?? '') ?: $hex($bg_rgb ? $h_bg_rgb : null);
    $h_border = zpt_color_value($hover['border'] ?? '')
        ?: ($h_bg ?: $hex($border_rgb && $border !== '' ? zpt_shade($border_rgb, .3) : null));
    $h_color  = zpt_color_value($hover['color'] ?? '')
        ?: (zpt_color_value($normal['color'] ?? '') ?: ($h_bg_rgb ? zpt_contrast_color($h_bg_rgb) : ''));

    foreach (['color' => $color, 'bg' => $bg, 'border-color' => $border] as $name => $value) {
        if ($value !== '') {
            $decl["--bs-btn-{$name}"]          = $value;
            $decl["--bs-btn-disabled-{$name}"] = $value;
        }
    }
    foreach (['color' => $h_color, 'bg' => $h_bg, 'border-color' => $h_border] as $name => $value) {
        if ($value !== '') {
            $decl["--bs-btn-hover-{$name}"]  = $value;
            $decl["--bs-btn-active-{$name}"] = $value;
        }
    }
    if ($bg_rgb) {
        $decl['--bs-btn-focus-shadow-rgb'] = implode(', ', $bg_rgb);
    }

    // --- Rahmen
    if (($value = zpt_button_border_width($row['border_width'] ?? '')) !== '') {
        $decl['--bs-btn-border-width'] = $value;
    }
    $decl += zpt_button_radius(zpt_button_length($row['border_radius'] ?? ''));

    // --- Schrift
    if ($font = zpt_button_role_font((string) ($row['font_role'] ?? ''))) {
        $decl['--bs-btn-font-family'] = $font;
    }
    $weight = (string) ($row['weight'] ?? '');
    $weight = isset(ZPT_FONT_WEIGHTS[$weight]) ? $weight : '';
    if ($weight !== '') {
        $decl['--bs-btn-font-weight'] = $weight;
    }
    $decl += zpt_button_letter_spacing('', $weight);
    if (($row['uppercase'] ?? '') !== '') {
        $decl['text-transform'] = $row['uppercase'] ? 'uppercase' : 'none';
    }

    return $decl;
}

// ----------------------------------------------------------------- TEMPLATES

/**
 * Templates aus den Einstellungen: ['default' => Zeile, 'pill' => Zeile, …] in Repeater-Reihenfolge.
 * Per Feldschlüssel (siehe Größen), noch nie gespeichert → Vorbelegung (load_value-Filter).
 */
function zpt_button_templates(): array {
    $templates = [];
    foreach ((array) (get_field('field_zpt_btn_templates', 'option') ?: []) as $row) {
        if ($slug = zpt_button_size_slug($row['slug'] ?? '')) {
            $templates[$slug] = $row;
        }
    }
    return $templates;
}

/**
 * Templates als Liste: ['default' => 'btn default', 'pill' => 'Pill', …].
 * Direkt aus den Optionen – wird auch in acf/load_field gebraucht (dort kein get_field()).
 */
function zpt_button_template_list(): array {
    $rows = get_option('options_zpt_btn_templates', false);
    if ($rows === false) {
        return array_map(fn($tpl) => $tpl['label'], ZPT_BUTTON_TEMPLATES);
    }
    $list = [];
    for ($i = 0; $i < (int) $rows; $i++) {
        if ($slug = zpt_button_size_slug(get_option("options_zpt_btn_templates_{$i}_slug", ''))) {
            $list[$slug] = (string) get_option("options_zpt_btn_templates_{$i}_label", '') ?: $slug;
        }
    }
    return $list;
}

/**
 * Deklarationen eines Templates: Schriftrolle, Gewicht (mit dessen Laufweite), Großbuchstaben, Rahmen.
 * Schriftgröße, Zeilenhöhe, Laufweite, Innenabstände: je Größe (zpt_button_size_values()).
 */
function zpt_button_template_decl(array $row): array {
    $decl = [];
    if ($font = zpt_button_role_font((string) ($row['font_role'] ?? ''))) {
        $decl['--bs-btn-font-family'] = $font;
    }
    $weight = (string) ($row['weight'] ?? '');
    $weight = isset(ZPT_FONT_WEIGHTS[$weight]) ? $weight : '';
    if ($weight !== '') {
        $decl['--bs-btn-font-weight'] = $weight;
    }
    $decl += zpt_button_letter_spacing('', $weight);
    if (($row['uppercase'] ?? '') !== '') {
        $decl['text-transform'] = $row['uppercase'] ? 'uppercase' : 'none';
    }
    // Rahmen ("0" ist ein gültiger Wert)
    if (($value = zpt_button_border_width($row['border_width'] ?? '')) !== '') {
        $decl['--bs-btn-border-width'] = $value;
    }
    return $decl + zpt_button_radius(zpt_button_length($row['border_radius'] ?? ''));
}

/**
 * Regeln zusammenführen: gleicher Selektor → Deklarationen ergänzen, neue werden angehängt.
 */
function zpt_button_merge_rules(array $rules, array $add): array {
    foreach ($add as $selector => $decl) {
        $rules[$selector] = array_merge($rules[$selector] ?? [], $decl);
    }
    return $rules;
}

/**
 * Farb-Schalter eines Templates – mit höherer Spezifität ausgegeben, damit sie die Farben der
 * Farbklassen und Varianten (.btn-primary) überschreiben:
 * - Outline:            Schrift in Rahmenfarbe (Hover: Hover-Schriftfarbe der Farbklasse)
 * - Hintergrund aus:    transparent, Hover/Aktiv gefüllt mit der Rahmenfarbe
 */
function zpt_button_outline_decl(array $row): array {
    $decl = [];
    if (!empty($row['outline'])) {
        $decl['--bs-btn-color']          = 'var(--bs-btn-border-color)';
        $decl['--bs-btn-disabled-color'] = 'var(--bs-btn-border-color)';
    }
    // nie gespeichert (null/'') = an
    if (isset($row['background']) && $row['background'] !== '' && !$row['background']) {
        $decl['--bs-btn-bg']          = 'transparent';
        $decl['--bs-btn-hover-bg']    = 'var(--bs-btn-border-color)';
        $decl['--bs-btn-active-bg']   = 'var(--bs-btn-border-color)';
        $decl['--bs-btn-disabled-bg'] = 'transparent';
    }
    return $decl;
}

/**
 * Belegte Kürzel/Klassennamen (ohne „btn-“): Farben, Outline-Farben, reservierte, Größen,
 * Templates, eigene Varianten – ohne die Gruppe, die gerade geprüft wird.
 */
function zpt_button_taken_names(string $except): array {
    $taken = array_merge(ZPT_BUTTON_COLORS, array_map(fn($c) => "outline-{$c}", ZPT_BUTTON_COLORS), ZPT_BUTTON_RESERVED);
    if ($except !== 'sizes') {
        $taken = array_merge($taken, array_keys(zpt_button_size_list()), ['sm', 'md', 'lg']);
    }
    if ($except !== 'templates') {
        $taken = array_merge($taken, array_keys(zpt_button_template_list()), ['default']);
    }
    if ($except !== 'variants') {
        $taken = array_merge($taken, array_values(zpt_button_custom_classes()));
    }
    return array_unique($taken);
}

// Vorbelegung: btn default, Pill, Outlined, solange der Repeater nie gespeichert wurde
add_filter('acf/load_value/key=field_zpt_btn_templates', function ($value, $post_id) {
    if ($post_id !== 'options' || get_option('options_zpt_btn_templates', false) !== false) {
        return $value;
    }
    $rows = [];
    foreach (ZPT_BUTTON_TEMPLATES as $slug => $tpl) {
        $row = ['field_zpt_btn_tpl_slug' => $slug];
        foreach ($tpl as $name => $val) {
            $row["field_zpt_btn_tpl_{$name}"] = $val;
        }
        if ($slug === 'default') {
            $row['field_zpt_btn_tpl_sizes'] = zpt_button_default_size_rows('field_zpt_btn_tpl_size_');
        }
        $rows[] = $row;
    }
    return $rows;
}, 10, 2);

/**
 * Zeilen „Größen“ mit den Bootstrap-Werten (Vorbelegung btn default), Feldschlüssel mit Präfix.
 */
function zpt_button_default_size_rows(string $prefix): array {
    $rows = [];
    foreach (ZPT_BUTTON_SIZES as $slug => $size) {
        $row = ["{$prefix}size" => $slug];
        foreach (array_keys(ZPT_BUTTON_SIZE_VARS) as $field) {
            $row["{$prefix}{$field}"] = $size[$field] ?? '';
        }
        $rows[] = $row;
    }
    return $rows;
}

// btn default muss bleiben (Löschen sperrt zusätzlich assets/js/admin-buttons.js)
add_filter('acf/validate_value/key=field_zpt_btn_templates', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    foreach ((array) $value as $row) {
        if (zpt_button_size_slug(wp_unslash($row['field_zpt_btn_tpl_slug'] ?? '')) === 'default') {
            return true;
        }
    }
    return __('Das Template „btn default“ (Kürzel default) kann nicht gelöscht werden.', '12punkt-baukasten');
}, 10, 2);

// Kürzel: gültig, eindeutig, nicht von anderen Button-Klassen belegt
add_filter('acf/validate_value/key=field_zpt_btn_tpl_slug', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $slug = zpt_button_size_slug($value);
    if (!$slug) {
        return __('Nur Kleinbuchstaben, Ziffern und Bindestrich, z. B. pill.', '12punkt-baukasten');
    }
    if (in_array($slug, zpt_button_taken_names('templates'), true) || str_starts_with($slug, 'outline-')) {
        return __('Dieses Kürzel ist schon eine Button-Klasse (Farbe, Größe oder Variante).', '12punkt-baukasten');
    }
    $count = 0;
    foreach ((array) ($_POST['acf']['field_zpt_btn_templates'] ?? []) as $row) {
        $count += zpt_button_size_slug(wp_unslash($row['field_zpt_btn_tpl_slug'] ?? '')) === $slug ? 1 : 0;
    }
    return $count > 1 ? __('Das Kürzel ist mehrfach vergeben.', '12punkt-baukasten') : true;
}, 10, 2);

// Template einer Variante: aus dem Repeater (btn default = keine zusätzliche Klasse).
// Im Button-Block gibt es keine Template-Auswahl – die Variante bringt ihr Template mit.
// Werte je Größe in Templates und Varianten: Auswahl der Größen (Grundeinstellungen)
foreach (['field_zpt_btn_tpl_size_size', 'field_zpt_btn_var_size_size'] as $zpt_key) {
    add_filter("acf/load_field/key={$zpt_key}", function ($field) {
        $field['choices'] = zpt_button_size_list();
        return $field;
    });
}
unset($zpt_key);

add_filter('acf/load_field/key=field_zpt_btn_var_template', function ($field) {
    $field['choices'] = [];
    foreach (zpt_button_template_list() as $slug => $label) {
        $field['choices'][$slug === 'default' ? '' : $slug] = $label;
    }
    $field['choices'] = ['' => $field['choices'][''] ?? 'btn default'] + $field['choices'];
    return $field;
});


// ----------------------------------------------------------------- FELDER


/**
 * Gewichte der geladenen Schriften als Auswahl.
 */
function zpt_button_weight_choices(string $empty): array {
    $weights = [];
    foreach (zpt_loaded_fonts() as $font) {
        $weights = array_merge($weights, $font['weights']);
    }
    $weights = array_unique($weights);
    sort($weights, SORT_NUMERIC);

    $choices = ['' => $empty];
    foreach ($weights ?: array_keys(ZPT_FONT_WEIGHTS) as $weight) {
        $choices[(string) $weight] = $weight . ' ' . (ZPT_FONT_WEIGHTS[$weight] ?? '');
    }
    return $choices;
}

add_filter('acf/load_field/key=field_zpt_btn_tpl_weight', function ($field) {
    $field['choices'] = zpt_button_weight_choices(__('Standard', '12punkt-baukasten'));
    return $field;
});
add_filter('acf/load_field/key=field_zpt_btn_var_weight', function ($field) {
    $field['choices'] = zpt_button_weight_choices(__('wie Template', '12punkt-baukasten'));
    return $field;
});

/**
 * Schriftrollen mit der zugeordneten Schrift als Auswahl (Rohwerte – in acf/load_field kein get_field()).
 */
function zpt_button_role_choices(string $empty): array {
    $choices = ['' => $empty];
    foreach (ZPT_FONT_ROLES as $role => $def) {
        $font = (string) get_option("options_zpt_font_families_{$role}", '');
        $choices[$role] = __($def['label'], '12punkt-baukasten') . ($font !== '' ? " ({$font})" : '');
    }
    return $choices;
}

add_filter('acf/load_field/key=field_zpt_btn_tpl_font_role', function ($field) {
    $field['choices'] = zpt_button_role_choices(__('Standard', '12punkt-baukasten'));
    return $field;
});
add_filter('acf/load_field/key=field_zpt_btn_var_font_role', function ($field) {
    $field['choices'] = zpt_button_role_choices(__('wie Template', '12punkt-baukasten'));
    return $field;
});

// Varianten: Klassen aus zpt.css + eigene
add_filter('acf/load_field/key=field_zpt_btn_var_class', function ($field) {
    $field['choices'] = [];
    foreach (ZPT_BUTTON_COLORS as $color) {
        $field['choices']["btn-{$color}"] = ".btn-{$color}";
    }
    foreach (ZPT_BUTTON_COLORS as $color) {
        $field['choices']["btn-outline-{$color}"] = ".btn-outline-{$color}";
    }
    $field['choices']['btn-link'] = '.btn-link';
    $field['choices']['custom']   = __('eigene Klasse …', '12punkt-baukasten');
    return $field;
});

// Eigene Klasse: gültiger Name, nicht schon von zpt.css belegt
add_filter('acf/validate_value/key=field_zpt_btn_var_custom', function ($valid, $value) {
    if ($valid !== true || trim((string) $value) === '') {
        return $valid;
    }
    $name = zpt_button_custom_name($value);
    if (!$name) {
        return __('Nur Kleinbuchstaben, Ziffern und Bindestrich, z. B. black oder ghost-light.', '12punkt-baukasten');
    }
    if (isset(zpt_button_size_list()[$name]) || in_array($name, ['sm', 'md', 'lg'], true)) {
        return __('Das ist die CSS-Klasse einer Größe (Tab „Grundeinstellungen“).', '12punkt-baukasten');
    }
    if (isset(zpt_button_template_list()[$name]) || $name === 'default') {
        return __('Das ist das Kürzel eines Templates (Tab „Templates“).', '12punkt-baukasten');
    }
    return in_array($name, zpt_button_taken_names('variants'), true) ? __('Diese Klasse gibt es schon – oben in der Auswahl wählen.', '12punkt-baukasten') : true;
}, 10, 2);

// ----------------------------------------------------------------- GRÖSSEN

/**
 * Kürzel einer Größe prüfen: „sm“, „xl“, „xx-large“. Ungültig → ''.
 */
function zpt_button_size_slug($slug): string {
    $slug = is_string($slug) ? strtolower(trim($slug)) : '';
    $slug = preg_replace('/^\.?btn-/', '', $slug);
    return preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) ? $slug : '';
}

/**
 * Größen: ['md' => 'Standard', 'sm' => 'Klein', 'lg' => 'Groß', …] in Repeater-Reihenfolge.
 * Direkt aus den Optionen – wird auch in acf/load_field gebraucht (dort kein get_field()).
 * Noch nie gespeichert → Standard-Größen.
 */
function zpt_button_size_list(): array {
    $rows = get_option('options_zpt_btn_sizes', false);
    if ($rows === false) {
        return array_map(fn($size) => __($size['label'], '12punkt-baukasten'), ZPT_BUTTON_SIZES);
    }
    $list = [];
    for ($i = 0; $i < (int) $rows; $i++) {
        if ($slug = zpt_button_size_slug(get_option("options_zpt_btn_sizes_{$i}_slug", ''))) {
            $list[$slug] = (string) get_option("options_zpt_btn_sizes_{$i}_label", '') ?: $slug;
        }
    }
    return $list;
}

// Vorbelegung: Standard, Klein, Groß, solange der Repeater nie gespeichert wurde
add_filter('acf/load_value/key=field_zpt_btn_sizes', function ($value, $post_id) {
    if ($post_id !== 'options' || get_option('options_zpt_btn_sizes', false) !== false) {
        return $value;
    }
    $rows = [];
    foreach (ZPT_BUTTON_SIZES as $slug => $size) {
        $rows[] = [
            'field_zpt_btn_size_label' => __($size['label'], '12punkt-baukasten'),
            'field_zpt_btn_size_slug'  => $slug,
        ];
    }
    return $rows;
}, 10, 2);

// Kürzel: gültig, eindeutig, nicht von anderen Button-Klassen belegt
add_filter('acf/validate_value/key=field_zpt_btn_size_slug', function ($valid, $value) {
    if ($valid !== true) {
        return $valid;
    }
    $slug = zpt_button_size_slug($value);
    if (!$slug) {
        return __('Nur Kleinbuchstaben, Ziffern und Bindestrich, z. B. xl. „md“ = Standard (.btn ohne Klasse).', '12punkt-baukasten');
    }
    if (in_array($slug, zpt_button_taken_names('sizes'), true) || str_starts_with($slug, 'outline-')) {
        return __('Dieses Kürzel ist schon eine Button-Klasse (Farbe, Template oder Variante).', '12punkt-baukasten');
    }
    $count = 0;
    foreach ((array) ($_POST['acf']['field_zpt_btn_sizes'] ?? []) as $row) {
        $count += zpt_button_size_slug(wp_unslash($row['field_zpt_btn_size_slug'] ?? '')) === $slug ? 1 : 0;
    }
    return $count > 1 ? __('Das Kürzel ist mehrfach vergeben.', '12punkt-baukasten') : true;
}, 10, 2);

// Größe im Button-Block, je Breakpoint (Gruppe „sizes“: bp_xs … bp_xxl). Gespeichert wird das Kürzel:
// XS: '' = Standard (md), sonst sm, lg …; ab SM: '' = wie kleiner, sonst Kürzel (auch md = zurück auf Standard).
add_filter('acf/load_field/type=select', function ($field) {
    if (!preg_match('/^field_zpt_button_size_bp_([a-z]+)$/', $field['key'] ?? '', $m)) {
        return $field;
    }
    $sizes = zpt_button_size_list();
    if ($m[1] === 'xs') {
        $field['choices'] = ['' => $sizes['md'] ?? __('Standard', '12punkt-baukasten')];
        foreach ($sizes as $slug => $label) {
            if ($slug !== 'md') {
                $field['choices'][$slug] = $label;
            }
        }
    } else {
        $field['choices'] = ['' => __('wie kleiner', '12punkt-baukasten')];
        foreach ($sizes as $slug => $label) {
            $field['choices'][$slug] = $label;
        }
    }
    return $field;
});

/**
 * Größen-Klassen eines Buttons aus der Gruppe „sizes“: ['sm', 'md' => 'lg'] → btn-sm btn-md-lg.
 * XS ohne Breakpoint-Kürzel (btn-sm), Standard (md) auf XS = keine Klasse.
 */
function zpt_button_size_classes($sizes): array {
    $sizes   = is_array($sizes) ? $sizes : [];
    $classes = [];
    foreach (array_merge(['xs'], array_keys(zpt_breakpoint_min())) as $bp) {
        $slug = zpt_button_size_slug($sizes["bp_{$bp}"] ?? '');
        if ($slug === '' || ($bp === 'xs' && $slug === 'md')) {
            continue;
        }
        $classes[] = $bp === 'xs' ? "btn-{$slug}" : "btn-{$bp}-{$slug}";
    }
    return $classes;
}

// Templates und Varianten als Akkordeon (ACF „collapsed“); btn default nicht löschbar (admin-buttons.js)
add_action('acf/input/admin_enqueue_scripts', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !str_ends_with($screen->id, 'zpt-buttons')) {
        return;
    }
    wp_enqueue_script('zpt-admin-buttons', ZPT_URL . 'assets/js/admin-buttons.js', ['acf-input'], filemtime(ZPT_PATH . 'assets/js/admin-buttons.js'), true);

    $css = '';
    foreach (['field_zpt_btn_templates', 'field_zpt_btn_variants'] as $key) {
        $repeater = '.acf-field[data-key="' . $key . '"] > .acf-input > .acf-repeater > table > tbody';
        $css .= "
            {$repeater} > .acf-row > .acf-row-handle .acf-icon.-collapse {display: block;}
            {$repeater} > .acf-row.-collapsed > .acf-fields {background: #f6f7f7;}
            {$repeater} > .acf-row:not(.-collapsed) > .acf-fields {box-shadow: inset 3px 0 0 #2271b1;}";
    }
    // gesperrte Zeile: keine Lösch-/Duplizier-Icons, Kürzel nur lesbar
    $css .= '
        .acf-row.zpt-row-locked > .acf-row-handle.remove .acf-icon:not(.-collapse) {display: none !important;}
        .acf-row.zpt-row-locked input[readonly] {background: #f0f0f1; color: #50575e;}';
    wp_add_inline_style('acf-input', $css);
});

// ----------------------------------------------------------------- BLOCK zpt/button: STIL

/**
 * Eigene Klassen aus den Varianten: ['btn-black' => 'black', …].
 * Direkt aus den Optionen – wird auch in acf/load_field gebraucht (dort kein get_field()).
 */
function zpt_button_custom_classes(): array {
    $classes = [];
    $rows    = (int) get_option('options_zpt_btn_variants', 0);
    for ($i = 0; $i < $rows; $i++) {
        if (get_option("options_zpt_btn_variants_{$i}_class") !== 'custom') {
            continue;
        }
        if ($name = zpt_button_custom_name(get_option("options_zpt_btn_variants_{$i}_custom", ''))) {
            $classes['btn-' . $name] = $name;
        }
    }
    return $classes;
}

/**
 * Klassen aller Varianten in Repeater-Reihenfolge: ['btn-primary', 'btn-black', …].
 * Direkt aus den Optionen – wird auch in acf/load_field gebraucht (dort kein get_field()).
 */
function zpt_button_variant_list(): array {
    $classes = [];
    $rows    = (int) get_option('options_zpt_btn_variants', 0);
    for ($i = 0; $i < $rows; $i++) {
        $class = zpt_button_variant_class((string) get_option("options_zpt_btn_variants_{$i}_class", ''), get_option("options_zpt_btn_variants_{$i}_custom", ''));
        if ($class && !in_array($class, $classes, true)) {
            $classes[] = $class;
        }
    }
    return $classes;
}

/**
 * Stile für den Button-Block. Sind Varianten angelegt (Baukasten → Buttons → Varianten), nur diese,
 * sonst Button und Outline je Baukasten-Farbe. Dazu immer die Link-Stile.
 */
function zpt_button_styles(): array {
    $styles   = [];
    $variants = zpt_button_variant_list();
    if ($variants) {
        $custom = zpt_button_custom_classes();
        foreach ($variants as $class) {
            if (isset($custom[$class])) {
                /* translators: %s: Name der eigenen Button-Klasse */
                $styles[$class] = sprintf(__('Button %s', '12punkt-baukasten'), $custom[$class]);
            } elseif (preg_match('/^btn-outline-(.+)$/', $class, $m)) {
                /* translators: %s: Farbname */
                $styles[$class] = sprintf(__('Outline %s', '12punkt-baukasten'), ucfirst($m[1]));
            } elseif ($class !== 'btn-link') {
                /* translators: %s: Farb- bzw. Klassenname */
                $styles[$class] = sprintf(__('Button %s', '12punkt-baukasten'), ucfirst(substr($class, 4)));
            }
        }
    } else {
        foreach (ZPT_BUTTON_COLORS as $color) {
            /* translators: %s: Farbname */
            $styles["btn-{$color}"] = sprintf(__('Button %s', '12punkt-baukasten'), ucfirst($color));
        }
        foreach (ZPT_BUTTON_COLORS as $color) {
            /* translators: %s: Farbname */
            $styles["btn-outline-{$color}"] = sprintf(__('Outline %s', '12punkt-baukasten'), ucfirst($color));
        }
    }
    $styles['btn-link']     = __('Button als Link (.btn-link)', '12punkt-baukasten');
    $styles['link-further'] = __('Link mit Pfeil (.link-further)', '12punkt-baukasten');
    $styles['link']         = __('Link (ohne Klasse)', '12punkt-baukasten');

    return apply_filters('zpt/button/styles', $styles);
}

add_filter('acf/load_field/key=field_zpt_button_style', function ($field) {
    $field['choices'] = zpt_button_styles();
    return $field;
});
