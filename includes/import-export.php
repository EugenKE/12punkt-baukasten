<?php
/**
 * Import / Export (Baukasten → Import / Export): Einstellungen der Baukasten-Seiten als JSON-Datei.
 *
 * Exportiert werden die Rohwerte aus wp_options (options_{feld}… und _options_{feld}… mit dem
 * Feld-Key), so wie ACF sie speichert – auch Repeater-Zeilen und Gruppen. Zugeordnet wird über die
 * Feldgruppen der jeweiligen Options-Seite (Feld-Namen der obersten Ebene).
 *
 * Import ersetzt die Werte der gewählten Seiten vollständig (alte Werte der Seite werden vorher
 * gelöscht, damit z. B. entfernte Repeater-Zeilen nicht stehen bleiben). Übernommen werden nur
 * Optionen, die zu Feldern der jeweiligen Seite gehören.
 *
 * Schriften werden nur über ihren Namen gespeichert – die Dateien müssen auf der Ziel-Seite liegen.
 */

defined('ABSPATH') || exit;

const ZPT_IE_SLUG = 'zpt-import-export';

// Seiten, die standardmäßig nicht angehakt sind (seitenspezifisch statt Gestaltung)
const ZPT_IE_UNCHECKED = ['zpt-meta', 'zpt-logo'];

// ----------------------------------------------------------------- DATEN

/**
 * Feld-Namen der obersten Ebene je Einstellungsseite: ['zpt_palette' => 'zpt-colors', …].
 */
function zpt_ie_field_pages(): array {
    $names = [];
    foreach (array_keys(zpt_settings_subpages()) as $slug) {
        foreach (acf_get_field_groups(['options_page' => $slug]) as $group) {
            foreach (acf_get_fields($group) ?: [] as $field) {
                if ($field['name'] !== '') {
                    $names[$field['name']] = $slug;
                }
            }
        }
    }
    return $names;
}

/**
 * Seite einer Option (options_zpt_palette_0_name → zpt-colors) oder '' (gehört zu keiner Seite).
 * Längster passender Feld-Name gewinnt (zpt_element_blockquote_footer vor zpt_element_blockquote).
 */
function zpt_ie_option_page(string $option, array $field_pages): string {
    if (!preg_match('/^_?options_(.+)$/', $option, $m)) {
        return '';
    }
    $page = '';
    $len  = 0;
    foreach ($field_pages as $name => $slug) {
        if (($m[1] === $name || str_starts_with($m[1], $name . '_')) && strlen($name) > $len) {
            $page = $slug;
            $len  = strlen($name);
        }
    }
    return $page;
}

/**
 * Gespeicherte Werte je Seite: ['zpt-colors' => ['options_zpt_palette' => 5, '_options_zpt_palette' => 'field_…'], …].
 */
function zpt_ie_saved_options(?array $field_pages = null): array {
    global $wpdb;
    $field_pages ??= zpt_ie_field_pages();

    $rows = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
         WHERE option_name LIKE 'options\\_%' OR option_name LIKE '\\_options\\_%'
         ORDER BY option_name"
    );

    $pages = [];
    foreach ($rows as $row) {
        if ($slug = zpt_ie_option_page($row->option_name, $field_pages)) {
            $pages[$slug][$row->option_name] = maybe_unserialize($row->option_value);
        }
    }
    return $pages;
}

/**
 * Anzahl der Werte (ohne die _options_…-Feld-Keys).
 */
function zpt_ie_count(array $options): int {
    return count(array_filter(array_keys($options), fn($name) => $name[0] !== '_'));
}

// ----------------------------------------------------------------- MENÜ
// nach den ACF-Options-Seiten (admin_menu, 99) → letzter Eintrag unter „Baukasten“.
// Eltern-Slug = erste Unterseite: ACF ersetzt bei 'redirect' => true den Slug des Hauptmenüs
// (zpt-settings) durch den der ersten Unterseite.
add_action('admin_menu', function () {
    add_submenu_page(
        array_key_first(zpt_settings_subpages()),
        __('Baukasten – Import / Export', '12punkt-baukasten'),
        __('Import / Export', '12punkt-baukasten'),
        'manage_options',
        ZPT_IE_SLUG,
        'zpt_ie_render_page'
    );
}, 100);

// ----------------------------------------------------------------- SEITE

function zpt_ie_render_page(): void {
    $pages = zpt_settings_subpages();
    $saved = zpt_ie_saved_options();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Baukasten – Import / Export', '12punkt-baukasten'); ?></h1>
        <?php zpt_ie_notice(); ?>

        <div id="poststuff">
            <div class="metabox-holder">

                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Export', '12punkt-baukasten'); ?></h2></div>
                    <div class="inside">
                        <p><?php esc_html_e('Lädt die Einstellungen der gewählten Seiten als JSON-Datei herunter.', '12punkt-baukasten'); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="zpt_export">
                            <?php wp_nonce_field('zpt_export'); ?>
                            <?php zpt_ie_page_checkboxes($pages, $saved); ?>
                            <?php submit_button(__('Exportieren', '12punkt-baukasten'), 'primary', 'submit', false); ?>
                        </form>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle"><?php esc_html_e('Import', '12punkt-baukasten'); ?></h2></div>
                    <div class="inside">
                        <p>
                            <?php echo __('Übernimmt die Einstellungen aus einer Export-Datei. Die Werte der gewählten Seiten werden <strong>vollständig ersetzt</strong>; Seiten, die in der Datei fehlen, bleiben unverändert.', '12punkt-baukasten'); ?>
                            <?php esc_html_e('Schriften werden nur über den Namen zugeordnet – die Schriftdateien müssen hier vorhanden sein.', '12punkt-baukasten'); ?>
                        </p>
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                              onsubmit="return confirm('<?php echo esc_js(__('Einstellungen der gewählten Seiten überschreiben?', '12punkt-baukasten')); ?>');">
                            <input type="hidden" name="action" value="zpt_import">
                            <?php wp_nonce_field('zpt_import'); ?>
                            <p><input type="file" name="zpt_import_file" accept=".json,application/json" required></p>
                            <?php zpt_ie_page_checkboxes($pages); ?>
                            <?php submit_button(__('Importieren', '12punkt-baukasten'), 'secondary', 'submit', false); ?>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php
}

/**
 * Checkboxen je Einstellungsseite; mit $saved die Anzahl gespeicherter Werte.
 */
function zpt_ie_page_checkboxes(array $pages, ?array $saved = null): void {
    echo '<fieldset style="margin: 0 0 1em;">';
    foreach ($pages as $slug => $title) {
        printf(
            '<label style="display:block; margin: .3em 0;"><input type="checkbox" name="zpt_pages[]" value="%s"%s> %s%s</label>',
            esc_attr($slug),
            checked(!in_array($slug, ZPT_IE_UNCHECKED, true), true, false),
            esc_html($title),
            /* translators: %d: Anzahl gespeicherter Werte */
            $saved === null ? '' : ' <span class="description">' . esc_html(sprintf(_n('(%d Wert)', '(%d Werte)', zpt_ie_count($saved[$slug] ?? []), '12punkt-baukasten'), zpt_ie_count($saved[$slug] ?? []))) . '</span>'
        );
    }
    echo '</fieldset>';
}

/**
 * Meldung nach Import (Weiterleitung mit ?zpt_ie=…).
 */
function zpt_ie_notice(): void {
    $status = sanitize_key($_GET['zpt_ie'] ?? '');
    if (!$status) {
        return;
    }

    $errors = [
        'nofile'  => __('Keine Datei hochgeladen.', '12punkt-baukasten'),
        'invalid' => __('Die Datei ist keine gültige Baukasten-Export-Datei.', '12punkt-baukasten'),
        'nopages' => __('Keine Seite gewählt oder keine der gewählten Seiten ist in der Datei enthalten.', '12punkt-baukasten'),
    ];

    if (isset($errors[$status])) {
        printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($errors[$status]));
        return;
    }

    if ($status === 'imported') {
        $pages   = array_intersect_key(zpt_settings_subpages(), array_flip(explode(',', sanitize_text_field($_GET['pages'] ?? ''))));
        $count   = (int) ($_GET['count'] ?? 0);
        $skipped = (int) ($_GET['skipped'] ?? 0);
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s%s</p></div>',
            /* translators: 1: importierte Einstellungsseiten (kommagetrennt), 2: Anzahl der Werte */
            esc_html(sprintf(_n('Importiert: %1$s (%2$d Wert).', 'Importiert: %1$s (%2$d Werte).', $count, '12punkt-baukasten'), implode(', ', $pages), $count)),
            /* translators: %d: Anzahl übersprungener Einträge */
            $skipped ? ' ' . esc_html(sprintf(_n('%d Eintrag gehörte zu keinem Feld der Seite und wurde übersprungen.', '%d Einträge gehörten zu keinem Feld der Seite und wurden übersprungen.', $skipped, '12punkt-baukasten'), $skipped)) : ''
        );
    }
}

/**
 * Gewählte Seiten aus dem Formular (nur bekannte).
 */
function zpt_ie_requested_pages(): array {
    $requested = array_map('sanitize_key', (array) ($_POST['zpt_pages'] ?? []));
    return array_values(array_intersect(array_keys(zpt_settings_subpages()), $requested));
}

// ----------------------------------------------------------------- EXPORT
add_action('admin_post_zpt_export', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Keine Berechtigung.', '12punkt-baukasten'));
    }
    check_admin_referer('zpt_export');

    $titles = zpt_settings_subpages();
    $saved  = zpt_ie_saved_options();
    $data   = [
        'zpt_export'     => 1,
        'plugin_version' => ZPT_VERSION,
        'created'        => gmdate('c'),
        'site'           => home_url(),
        'pages'          => [],
    ];
    foreach (zpt_ie_requested_pages() as $slug) {
        $data['pages'][$slug] = [
            'title'   => $titles[$slug],
            'options' => $saved[$slug] ?? [],
        ];
    }

    $host = sanitize_file_name((string) wp_parse_url(home_url(), PHP_URL_HOST));
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="zpt-baukasten-' . $host . '-' . wp_date('Y-m-d') . '.json"');
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
});

// ----------------------------------------------------------------- IMPORT
add_action('admin_post_zpt_import', function () {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Keine Berechtigung.', '12punkt-baukasten'));
    }
    check_admin_referer('zpt_import');

    $back = function (array $args): void {
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php?page=' . ZPT_IE_SLUG)));
        exit;
    };

    $file = $_FILES['zpt_import_file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $back(['zpt_ie' => 'nofile']);
    }

    $data = json_decode((string) file_get_contents($file['tmp_name']), true);
    if (!is_array($data) || empty($data['zpt_export']) || !is_array($data['pages'] ?? null)) {
        $back(['zpt_ie' => 'invalid']);
    }

    $result = zpt_ie_import($data, zpt_ie_requested_pages());
    if (!$result['pages']) {
        $back(['zpt_ie' => 'nopages']);
    }

    $back([
        'zpt_ie'  => 'imported',
        'pages'   => implode(',', $result['pages']),
        'count'   => $result['count'],
        'skipped' => $result['skipped'],
    ]);
});

/**
 * Importiert die Seiten $slugs aus $data (Export-Format).
 * Rückgabe: ['pages' => [importierte Slugs], 'count' => Werte, 'skipped' => übersprungene Einträge].
 */
function zpt_ie_import(array $data, array $slugs): array {
    $field_pages = zpt_ie_field_pages();
    $saved       = zpt_ie_saved_options($field_pages);
    $result      = ['pages' => [], 'count' => 0, 'skipped' => 0];

    foreach ($slugs as $slug) {
        $options = $data['pages'][$slug]['options'] ?? null;
        if (!is_array($options)) {
            continue;
        }

        // bisherige Werte der Seite entfernen
        foreach (array_keys($saved[$slug] ?? []) as $name) {
            delete_option($name);
        }

        foreach ($options as $name => $value) {
            if (!is_string($name) || zpt_ie_option_page($name, $field_pages) !== $slug
                || !(is_scalar($value) || is_array($value) || $value === null)) {
                $result['skipped']++;
                continue;
            }
            update_option($name, $value ?? '');
            $result['count'] += $name[0] === '_' ? 0 : 1;
        }

        $result['pages'][] = $slug;
    }

    return $result;
}
