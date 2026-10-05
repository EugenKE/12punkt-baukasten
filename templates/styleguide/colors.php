<?php
/**
 * Styleguide: Farben – Systemfarben (mit zugewiesener Palettenfarbe) mit ihren Shades,
 * Hintergrund, Text, Buttons; darunter die Farbpalette.
 */

defined('ABSPATH') || exit;

// Systemfarben: Bootstrap-Farben (angelegt oder mit Bootstraps Wert) und eigene; light/dark nur angelegt hier
$assigned = zpt_system_color_names();
$system   = array_merge(array_diff(ZPT_BOOTSTRAP_THEME_COLORS, ['light', 'dark']), ZPT_BOOTSTRAP_BASIC_COLORS);
$groups   = [
    __('Systemfarben', '12punkt-baukasten') => array_values(array_unique(array_merge($system, array_keys($assigned)))),
    'Bootstrap'    => array_values(array_diff(['light', 'dark'], array_keys($assigned))),
];
$tokens = zpt_color_tokens();
?>

<p class="mb-40"><?php esc_html_e('Gemessen wird im Browser: Hintergrund · Textfarbe (Kontrastfarbe aus den Einstellungen).', '12punkt-baukasten'); ?></p>

<?php foreach ($groups as $title => $colors): ?>
    <section class="mb-60">
        <h2 class="font-size-l border-bottom pb-10 mb-30"><?php echo esc_html($title); ?></h2>
        <div class="row g-30">
            <?php foreach ($colors as $color): $basic = !in_array($color, ZPT_BOOTSTRAP_THEME_COLORS, true); // black/white/gray, eigene: ohne Bootstrap-Komponentenklassen ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div data-sg-box>
                        <div class="zpt-sg-swatch d-flex align-items-end p-10 <?php echo esc_attr($basic ? "bg-{$color} border" : "text-bg-{$color}"); ?>"<?php echo $basic && isset($tokens[$color]) ? ' style="color:' . esc_attr(zpt_contrast_color(zpt_hex_to_rgb($tokens[$color]))) . '"' : ''; ?> data-sg-target>
                            <span class="zpt-sg-label"><?php echo esc_html($color . ' · ' . ($assigned[$color] ?? 'Bootstrap')); ?></span>
                        </div>
                        <?php echo zpt_sg_out('color'); ?>
                    </div>
                    <?php if (isset($tokens[$color])): ?>
                        <div class="d-flex mt-10 zpt-sg-label">
                            <?php foreach (array_keys(zpt_color_scale()) as $shade): $token = "{$color}-{$shade}"; ?>
                                <div class="flex-fill py-5 text-center bg-<?php echo esc_attr($token); ?>" style="color:<?php echo esc_attr(zpt_contrast_color(zpt_hex_to_rgb($tokens[$token]))); ?>" title=".bg-<?php echo esc_attr($token); ?> · <?php echo esc_attr($tokens[$token]); ?>"><?php echo esc_html($shade); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($basic): ?>
                        <p class="mt-10 mb-0">.text-<?php echo esc_html($color); ?>, .bg-<?php echo esc_html($color); ?><?php echo isset($tokens[$color]) ? ', .bg-' . esc_html($color . '-' . array_key_first(zpt_color_scale())) . ' …' : ''; ?></p>
                    <?php else: ?>
                    <div class="d-flex flex-wrap gap-10 mt-10">
                        <button type="button" class="btn btn-<?php echo esc_attr($color); ?>"><?php esc_html_e('Button', '12punkt-baukasten'); ?></button>
                        <button type="button" class="btn btn-outline-<?php echo esc_attr($color); ?>">Outline</button>
                    </div>
                    <p class="mt-10 mb-0 text-<?php echo esc_attr($color); ?>">.text-<?php echo esc_html($color); ?></p>
                    <div class="mt-10 p-10 bg-<?php echo esc_attr($color); ?>-subtle text-<?php echo esc_attr($color); ?>-emphasis border border-<?php echo esc_attr($color); ?>-subtle zpt-sg-label">.bg-<?php echo esc_html($color); ?>-subtle</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if ($palette = zpt_color_palette()): ?>
    <section class="mb-60">
        <h2 class="font-size-l border-bottom pb-10 mb-30"><?php esc_html_e('Farbpalette', '12punkt-baukasten'); ?></h2>
        <?php foreach ($palette as $name => $shades): ?>
            <div class="row g-10 align-items-center mb-10">
                <div class="col-12 col-md-2 zpt-sg-label">
                    <?php echo esc_html($name); ?>
                    <?php if ($roles = array_keys($assigned, $name, true)): ?>
                        <span class="text-secondary">(<?php echo esc_html(implode(', ', $roles)); ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-10 d-flex zpt-sg-label">
                    <?php foreach ($shades as $shade => $hex): ?>
                        <div class="flex-fill py-10 text-center bg-<?php echo esc_attr("{$name}-{$shade}"); ?><?php echo (string) $shade === zpt_color_base() ? ' fw-bold' : ''; ?>" style="color:<?php echo esc_attr(zpt_contrast_color(zpt_hex_to_rgb($hex))); ?>" title=".bg-<?php echo esc_attr("{$name}-{$shade}"); ?> · <?php echo esc_attr($hex); ?>"><?php echo esc_html($shade); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-30"><?php esc_html_e('Links und Alerts', '12punkt-baukasten'); ?></h2>
    <p><?php
        /* translators: %s: Link „Link (primary)“ */
        printf(esc_html__('Fließtext mit einem %s.', '12punkt-baukasten'), '<a href="#">' . esc_html__('Link (primary)', '12punkt-baukasten') . '</a>');
    ?></p>
    <?php foreach (['primary', 'success', 'danger', 'warning', 'info'] as $color): ?>
        <div class="alert alert-<?php echo esc_attr($color); ?>">.alert-<?php echo esc_html($color); ?></div>
    <?php endforeach; ?>
</section>
