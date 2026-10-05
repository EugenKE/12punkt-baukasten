<?php
/**
 * Elemente (Baukasten → Elemente): Einstellungen je HTML-Element (h1–h6, p, ul, a …).
 *
 * Welche Einstellungen ein Element hat, steht in ZPT_ELEMENTS (config.php). Daraus erzeugt
 * bin/build-acf-elements.php die Feldgruppe (acf-json/group_zpt_settings_elements.json):
 * Tabs je Gruppe, Akkordeon je Element, Größe/Margin/Padding als Matrix (Zeile × Breakpoint).
 *
 * Die Elemente der Gruppe ZPT_NAV_ELEMENT_GROUP (Navigationsleiste) stehen als eigene Box auf
 * Baukasten → Navigation (acf-json/group_zpt_settings_nav_elements.json) – gleiche Technik.
 *
 * Ausgabe (nach zpt.css, Frontend + Editor) mit Element-Selektoren, d. h. Klassen am Element
 * (.mt-20, .font-size-xl, .fw-700, .font-serif …) gewinnen. Leer = wie umgebend bzw. wie der
 * nächstkleinere Breakpoint.
 */

defined('ABSPATH') || exit;

/**
 * Gespeicherter Wert eines Element-Felds (direkt aus den Optionen, ohne ~650 ACF-Feld-Ladevorgänge).
 * $name: role, font_weight, options, color, size_md, margin_top_xs …
 */
function zpt_element_value(string $element, string $name) {
    return get_option("options_zpt_element_{$element}_{$name}", '');
}

/**
 * Werte einer Matrix-Zeile je Breakpoint: ['xs' => '20', 'md' => '40'] (nur gesetzte, leer = wie kleiner).
 */
function zpt_element_row(string $element, string $row): array {
    $values = [];
    foreach (array_keys(ZPT_ELEMENT_BREAKPOINTS) as $bp) {
        $value = (string) zpt_element_value($element, "{$row}_{$bp}");
        if ($value !== '') {
            $values[$bp] = $value;
        }
    }
    return $values;
}

/**
 * Abstand aus der 5px-Skala → CSS-Wert ('' = nicht gesetzt).
 */
function zpt_element_spacing($value, bool $negative): string {
    if (!is_numeric($value)) {
        return '';
    }
    $max   = ZPT_SPACER_STEP * ZPT_SPACER_COUNT;
    $value = max($negative ? -$max : 0, min($max, (int) $value));
    return $value === 0 ? '0' : $value . 'px';
}

// ----------------------------------------------------------------- CSS

/**
 * Regeln aller Elemente: Basis (Schrift, Farbe, Liste, Link) und je Breakpoint
 * (Größe = Stufen-Variablen der aktuellen Schrift, Margin, Padding) per Media Query.
 */
function zpt_element_css(): string {
    $fonts = zpt_css_role_fonts();
    $media = []; // Breakpoint => Regeln
    $css   = '';

    foreach (ZPT_ELEMENTS as $el => $def) {
        $selector = $def['selector'];
        $features = $def['features'];
        $options  = (array) (zpt_element_value($el, 'options') ?: []);
        $rules    = '';
        $hover    = '';

        // --- Schrift
        if (in_array('type', $features, true)) {
            $role   = (string) zpt_element_value($el, 'role');
            $weight = (string) zpt_element_value($el, 'font_weight');

            if (isset($fonts[$role]) && ($declarations = zpt_font_declarations($fonts[$role], ZPT_FONT_ROLES[$role]['generic']))) {
                $rules .= $declarations;
                // Laufweite aus der neuen Schrift; ohne Spezifität, damit strong, h5 … (scss) ihr Gewicht behalten
                // (nicht bei Pseudo-Elementen wie ::placeholder – in :where() ungültig)
                if (!str_contains($selector, '::')) {
                    $css .= ':where(' . $selector . '){--zpt-ls:var(--zpt-ls-400, normal);}';
                }
            }
            if (isset(ZPT_FONT_WEIGHTS[$weight])) {
                $rules .= 'font-weight:' . $weight . ';--zpt-ls:var(--zpt-ls-' . $weight . ', normal);';
            }
            if (in_array('uppercase', $options, true)) {
                $rules .= 'text-transform:uppercase;';
            }
        }

        // --- Farbe (hr: Linie, Bootstrap blendet sie mit opacity .25 ab)
        if (in_array('color', $features, true) && ($color = zpt_color_value(zpt_element_value($el, 'color')))) {
            $rules .= 'color:' . $color . ';' . ($el === 'hr' ? 'opacity:1;' : '');
        }

        // --- Hintergrund
        if (in_array('background', $features, true) && ($background = zpt_color_value(zpt_element_value($el, 'background')))) {
            $rules .= 'background-color:' . $background . ';';
        }

        // --- Liste
        if (in_array('list', $features, true)) {
            $style = (string) zpt_element_value($el, 'list_style');
            if (isset(ZPT_LIST_STYLES[$style])) {
                $rules .= 'list-style-type:' . $style . ';';
            }
        }

        // --- Link
        if (in_array('link', $features, true)) {
            if (in_array('no_underline', $options, true)) {
                $rules .= 'text-decoration:none;';
            }
            if ($color = zpt_color_value(zpt_element_value($el, 'color_hover'))) {
                $hover = implode(',', array_map(fn($s) => trim($s) . ':hover', explode(',', $selector))) . '{color:' . $color . ';}';
            }
        }

        $css .= ($rules ? $selector . '{' . $rules . '}' : '') . $hover;

        // --- je Breakpoint: Größe, Margin, Padding
        $rows = [];
        if (in_array('size', $features, true)) {
            $rows['size'] = zpt_element_row($el, 'size');
        }
        foreach (['margin', 'padding'] as $prop) {
            if (in_array($prop, $features, true)) {
                foreach (array_keys(ZPT_ELEMENT_SIDES) as $side) {
                    $rows["{$prop}_{$side}"] = zpt_element_row($el, "{$prop}_{$side}");
                }
            }
        }

        foreach (array_keys(ZPT_ELEMENT_BREAKPOINTS) as $bp) {
            $bp_rules = '';

            if (isset($rows['size'][$bp])) {
                $step = $rows['size'][$bp];
                if (isset(zpt_type_steps()[$step])) {
                    $var       = zpt_type_css($step);
                    $bp_rules .= 'font-size:var(--theme-font-size-' . $var . ');line-height:var(--theme-line-height-' . $var . ');';
                }
            }
            foreach (['margin', 'padding'] as $prop) {
                if (!in_array($prop, $features, true)) {
                    continue;
                }
                foreach (ZPT_ELEMENT_SIDES as $side => [$css_side]) {
                    $value = zpt_element_spacing($rows["{$prop}_{$side}"][$bp] ?? '', $prop === 'margin');
                    if ($value !== '') {
                        $bp_rules .= "{$prop}-{$css_side}:{$value};";
                    }
                }
            }

            if ($bp_rules) {
                $media[$bp] = ($media[$bp] ?? '') . $selector . '{' . $bp_rules . '}';
            }
        }
    }

    // Breakpoints aufsteigend, damit größere gewinnen
    $css .= $media['xs'] ?? '';
    foreach (zpt_breakpoint_min() as $bp => $min_width) {
        if (!empty($media[$bp])) {
            $css .= '@media (min-width:' . $min_width . 'px){' . $media[$bp] . '}';
        }
    }

    return $css;
}

// ----------------------------------------------------------------- SPEICHERN: LEERE WERTE ENTFERNEN
// ACF speichert jedes der ~1300 Felder (Wert + Feld-Key), auch leer → ~2500 Zeilen in wp_options,
// alle bei jedem Aufruf geladen. Leer = nicht gesetzt, daher nach dem Speichern löschen
// (samt Feld-Key-Zeile _options_…). Gelesen wird ohnehin mit Standard '' (zpt_element_value()).

function zpt_elements_cleanup(): int {
    global $wpdb;

    // Gruppen der obersten Ebene bleiben (Wert + Feld-Key), sonst findet get_field('zpt_element_h1') sie nicht
    $keep = "'" . implode("','", array_map(fn($el) => esc_sql("options_zpt_element_{$el}"), array_keys(ZPT_ELEMENTS))) . "'";

    $deleted = (int) $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'options\\_zpt\\_element\\_%' AND option_value = ''
         AND option_name NOT IN ({$keep})"
    );
    $deleted += (int) $wpdb->query(
        "DELETE k FROM {$wpdb->options} k
         LEFT JOIN {$wpdb->options} v ON v.option_name = SUBSTRING(k.option_name, 2)
         WHERE k.option_name LIKE '\\_options\\_zpt\\_element\\_%' AND v.option_id IS NULL"
    );

    if ($deleted) {
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');
        zpt_style_css_flush();
    }
    return $deleted;
}

add_action('acf/save_post', function ($post_id) {
    if ($post_id === 'options' && isset($_POST['acf']) && preg_grep('/^field_zpt_element_/', array_keys((array) $_POST['acf']))) {
        zpt_elements_cleanup();
    }
}, 30);

// ----------------------------------------------------------------- ADMIN: GRÖSSEN AUS DER TYPO-SKALA
// Die Feldgruppe enthält die Stufen der Vorlage „12punkt“; angeboten wird die aktuelle Skala
// (Baukasten → Typografie). Direkt aus den Optionen (zpt_type_steps), kein get_field().

add_filter('acf/load_field/type=select', function ($field) {
    if (!preg_match('/^field_zpt_element_.+_size_(' . implode('|', array_keys(ZPT_ELEMENT_BREAKPOINTS)) . ')$/', $field['key'], $m)) {
        return $field;
    }
    $field['choices'] = ['' => $m[1] === 'xs' ? '–' : __('wie kleiner', '12punkt-baukasten')];
    foreach (zpt_type_steps() as $key => $step) {
        $field['choices'][$key] = $step['name'];
    }
    return $field;
});

// ----------------------------------------------------------------- ADMIN: FELDER ERST BEIM AUFKLAPPEN
// Alle Elemente auf einmal wären ~1300 Felder (1,2 MB HTML). Die Seite rendert je Element nur einen
// leeren Platzhalter (.zpt-element.-lazy); admin-elements.js holt die Felder beim Aufklappen per AJAX
// (zpt_element_fields) und meldet sie bei ACF an. Gespeichert werden nur geöffnete Elemente – ACF
// speichert ausschließlich gesendete Felder, die anderen bleiben unverändert.

/**
 * Seite mit Element-Feldern (Elemente, Navigation) wird gerade gerendert – bei der AJAX-Anfrage für ein Element false.
 */
function zpt_elements_screen(): bool {
    if (wp_doing_ajax() || !is_admin() || !function_exists('get_current_screen')) {
        return false;
    }
    $screen = get_current_screen();
    return $screen && (str_ends_with($screen->id, 'zpt-elements') || str_ends_with($screen->id, 'zpt-navigation'));
}


add_filter('acf/prepare_field/type=group', function ($field) {
    $el = substr((string) $field['key'], strlen('field_zpt_element_'));
    if (!str_starts_with((string) $field['key'], 'field_zpt_element_') || !isset(ZPT_ELEMENTS[$el]) || !zpt_elements_screen()) {
        return $field;
    }
    $field['sub_fields']        = [];
    $field['wrapper']['class'] .= ' -lazy';
    return $field;
});

add_action('wp_ajax_zpt_element_fields', function () {
    check_ajax_referer('zpt_element_fields');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(null, 403);
    }

    $el    = sanitize_key(wp_unslash($_POST['element'] ?? ''));
    $field = isset(ZPT_ELEMENTS[$el]) ? acf_get_field("field_zpt_element_{$el}") : null;
    if (!$field) {
        wp_send_json_error(null, 404);
    }

    ob_start();
    acf_render_fields([$field], 'options', 'div', 'label');
    wp_send_json_success(['html' => ob_get_clean()]);
});

/**
 * Zusammenfassung für den Akkordeon-Titel (wie zptSummary() in admin-elements.js):
 * „Mono · 400 · Großbuchstaben · primary-700 · Größe xl, MD xxl · Margin“.
 */
function zpt_element_summary(string $el): string {
    $features = ZPT_ELEMENTS[$el]['features'];
    $parts    = [];

    $role = (string) zpt_element_value($el, 'role');
    if (isset(ZPT_FONT_ROLES[$role])) {
        $parts[] = __(ZPT_FONT_ROLES[$role]['label'], '12punkt-baukasten');
    }
    if (($weight = (string) zpt_element_value($el, 'font_weight')) !== '') {
        $parts[] = $weight;
    }
    $options = array_intersect_key(ZPT_ELEMENT_OPTIONS, array_flip((array) (zpt_element_value($el, 'options') ?: [])));
    if ($options) {
        $parts[] = implode(', ', array_map(fn($label) => __($label, '12punkt-baukasten'), $options));
    }
    $color = (string) zpt_element_value($el, 'color');
    $hover = (string) zpt_element_value($el, 'color_hover');
    if ($color !== '') {
        $parts[] = $color . ($hover !== '' ? ' / ' . $hover : '');
    } elseif ($hover !== '') {
        /* translators: %s: Farbe beim Hover */
        $parts[] = sprintf(__('Hover %s', '12punkt-baukasten'), $hover);
    }
    if (($background = (string) zpt_element_value($el, 'background')) !== '') {
        /* translators: %s: Hintergrundfarbe */
        $parts[] = sprintf(__('Hintergrund %s', '12punkt-baukasten'), $background);
    }
    if (isset(ZPT_LIST_STYLES[$style = (string) zpt_element_value($el, 'list_style')])) {
        $parts[] = __(ZPT_LIST_STYLES[$style], '12punkt-baukasten');
    }
    if (in_array('size', $features, true) && ($sizes = zpt_element_row($el, 'size'))) {
        $steps   = zpt_type_steps();
        /* translators: %s: Schriftgrößen je Breakpoint, z. B. „xl, MD xxl“ */
        $parts[] = sprintf(__('Größe %s', '12punkt-baukasten'), implode(', ', array_map(
            fn($bp, $step) => ($bp === 'xs' ? '' : strtoupper($bp) . ' ') . ($steps[$step]['name'] ?? $step),
            array_keys($sizes),
            $sizes
        )));
    }
    foreach (['margin' => __('Margin', '12punkt-baukasten'), 'padding' => __('Padding', '12punkt-baukasten')] as $prop => $label) {
        if (in_array($prop, $features, true)) {
            foreach (array_keys(ZPT_ELEMENT_SIDES) as $side) {
                if (zpt_element_row($el, "{$prop}_{$side}")) {
                    $parts[] = $label;
                    break;
                }
            }
        }
    }

    return implode(' · ', $parts);
}

// ----------------------------------------------------------------- ADMIN

add_action('acf/input/admin_enqueue_scripts', function () {
    if (!zpt_elements_screen()) {
        return;
    }

    wp_enqueue_script('zpt-admin-elements', ZPT_URL . 'assets/js/admin-elements.js', ['acf-input'], filemtime(ZPT_PATH . 'assets/js/admin-elements.js'), true);

    // Gewichte der Schrift je Rolle (Gewicht-Auswahl zeigt nur vorhandene; leer = Grundschrift)
    $loaded  = zpt_loaded_fonts();
    $weights = [];
    foreach (zpt_css_role_fonts() as $role => $family) {
        $weights[$role] = array_map('strval', $loaded[$family]['weights'] ?? []);
    }
    wp_add_inline_script('zpt-admin-elements', 'var zptRoleWeights = ' . wp_json_encode((object) $weights) . ';', 'before');

    // Nachladen beim Aufklappen + Zusammenfassungen der noch nicht geladenen Elemente
    $summaries = [];
    foreach (array_keys(ZPT_ELEMENTS) as $el) {
        $summaries[$el] = zpt_element_summary($el);
    }
    wp_add_inline_script('zpt-admin-elements', 'var zptElements = ' . wp_json_encode([
        'ajax'      => admin_url('admin-ajax.php'),
        'nonce'     => wp_create_nonce('zpt_element_fields'),
        'summaries' => (object) $summaries,
    ]) . ';', 'before');

    wp_add_inline_style('acf-input', '
        .zpt-element-accordion .acf-accordion-title label {display: inline;}
        .zpt-el-summary {margin-left: 10px; font-weight: 400; font-size: 12px; color: #646970;}
        .acf-field.zpt-element > .acf-label {display: none;}
        .acf-field.zpt-element > .acf-input > .acf-fields {border: 0;}
        .zpt-el-loading {display: flex; align-items: center; gap: 8px; margin: 0; color: #646970;}
        .zpt-el-loading.-error {color: #d63638;}

        /* Matrix: Label links, Breakpoints als Spalten, Kopfzeile nur in der ersten Zeile */
        .acf-field.zpt-bp-row {display: flex; align-items: flex-end; gap: 12px; padding-top: 2px; padding-bottom: 2px; border-top: 0 !important;}
        .acf-field.zpt-bp-head {margin-top: 8px; padding-top: 12px; border-top: 1px solid #eee !important;}
        .acf-field.zpt-bp-row > .acf-label {flex: 0 0 130px; margin: 0; padding-bottom: 8px;}
        .acf-field.zpt-bp-row > .acf-label label {font-weight: 400;}
        .acf-field.zpt-bp-row > .acf-input {flex: 1 1 auto; min-width: 0;}
        .zpt-bp-row > .acf-input > .acf-table {border: 0; background: transparent; table-layout: fixed; width: 100%;}
        .zpt-bp-row > .acf-input > .acf-table > thead > tr > th {width: auto !important;}
        .zpt-bp-row > .acf-input > .acf-table > thead > tr > th {padding: 0 4px 4px; border: 0; background: transparent; font-weight: 600; text-align: center;}
        .zpt-bp-row > .acf-input > .acf-table > tbody > tr > td {padding: 2px 4px; border: 0 !important; background: transparent;}
        .zpt-bp-row:not(.zpt-bp-head) > .acf-input > .acf-table > thead {display: none;}
        .zpt-bp-row input[type=number] {text-align: center; -moz-appearance: textfield;}
        .zpt-bp-row input[type=number]::-webkit-inner-spin-button,
        .zpt-bp-row input[type=number]::-webkit-outer-spin-button {-webkit-appearance: none; margin: 0;}
        .zpt-bp-row select, .zpt-bp-row input {width: 100%; min-width: 0; max-width: none;}
        .zpt-bp-row .acf-input [value=""], .zpt-bp-row input:placeholder-shown {color: #a7aaad;}
        .zpt-bp-row select:has(option[value=""]:checked) {color: #a7aaad;}
        .zpt-bp-row select option {color: #1d2327;}

    ');
});
