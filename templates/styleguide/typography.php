<?php
/**
 * Styleguide: Typografie – Stufen, Gewichte und Überschriften je Schrift.
 */

defined('ABSPATH') || exit;

$sample = __('Franz jagt im komplett verwahrlosten Taxi quer durch Bayern', '12punkt-baukasten');

// Standard (Grundschrift bzw. SCSS) + jede geladene Schrift
$fonts = ['' => ['label' => __('Standard (Grundschrift)', '12punkt-baukasten'), 'weights' => ['400', '700']]];
foreach (zpt_loaded_fonts() as $family => $font) {
    $fonts['ff-' . $font['slug']] = ['label' => $family, 'weights' => $font['weights']];
}
?>

<p class="mb-40"><?php esc_html_e('Gemessen wird im Browser: Schrift · Gewicht, Größe / Zeilenhöhe · Laufweite (LS). Werte ändern sich je Breakpoint – Fenster verkleinern zum Prüfen.', '12punkt-baukasten'); ?></p>

<?php foreach ($fonts as $class => $font): ?>
    <section class="mb-80">
        <h2 class="font-size-l border-bottom pb-10 mb-30"><?php echo esc_html($font['label']); ?>
            <?php if ($class): ?><code class="zpt-sg-label">.<?php echo esc_html($class); ?></code><?php endif; ?></h2>

        <h3 class="zpt-sg-label text-uppercase mb-10"><?php esc_html_e('Stufen', '12punkt-baukasten'); ?></h3>
        <?php foreach (array_keys(zpt_type_steps()) as $step): $size_class = 'font-size-' . zpt_type_css($step); ?>
            <div class="row g-20 align-items-baseline mb-20" data-sg-box>
                <div class="col-12 col-md-3">
                    <div class="zpt-sg-label">.<?php echo esc_html($size_class); ?></div>
                    <?php echo zpt_sg_out('font'); ?>
                </div>
                <div class="col-12 col-md-9">
                    <div class="<?php echo esc_attr(trim($class . ' ' . $size_class)); ?>" data-sg-target><?php echo esc_html($sample); ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <h3 class="zpt-sg-label text-uppercase mt-40 mb-10"><?php
            /* translators: %s: Name der Grundstufe */
            printf(esc_html__('Gewichte (%s)', '12punkt-baukasten'), esc_html(zpt_type_steps()[zpt_type_base()]['name']));
        ?></h3>
        <?php foreach ($font['weights'] as $weight): ?>
            <div class="row g-20 align-items-baseline mb-20" data-sg-box>
                <div class="col-12 col-md-3">
                    <div class="zpt-sg-label">.fw-<?php echo esc_html($weight); ?> <?php echo esc_html(ZPT_FONT_WEIGHTS[$weight] ?? ''); ?></div>
                    <?php echo zpt_sg_out('font'); ?>
                </div>
                <div class="col-12 col-md-9">
                    <div class="<?php echo esc_attr(trim($class . ' font-size-' . zpt_type_css(zpt_type_base()) . ' fw-' . $weight)); ?>" data-sg-target><?php echo esc_html($sample); ?></div>
                </div>
            </div>
        <?php endforeach; ?>

        <h3 class="zpt-sg-label text-uppercase mt-40 mb-10"><?php esc_html_e('Überschriften und Fließtext', '12punkt-baukasten'); ?></h3>
        <div class="<?php echo esc_attr($class); ?>">
            <?php foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag): ?>
                <div class="row g-20 align-items-baseline mb-10" data-sg-box>
                    <div class="col-12 col-md-3">
                        <div class="zpt-sg-label">&lt;<?php echo $tag; ?>&gt;</div>
                        <?php echo zpt_sg_out('font'); ?>
                    </div>
                    <div class="col-12 col-md-9">
                        <<?php echo $tag; ?> class="m-0" data-sg-target><?php
                            /* translators: %s: Überschriften-Ebene, z. B. H1 */
                            printf(esc_html__('Überschrift %s', '12punkt-baukasten'), strtoupper($tag));
                        ?></<?php echo $tag; ?>>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="row g-20 align-items-baseline mt-20" data-sg-box>
                <div class="col-12 col-md-3">
                    <div class="zpt-sg-label">&lt;p&gt; <?php esc_html_e('mit', '12punkt-baukasten'); ?> &lt;strong&gt;, &lt;em&gt;</div>
                    <?php echo zpt_sg_out('font'); ?>
                </div>
                <div class="col-12 col-md-9">
                    <p data-sg-target><?php
                        /* translators: %1$s: Beispielsatz */
                        printf(wp_kses_post(__('%1$s. Mit <strong>fettem Text</strong>, <em>kursivem Text</em> und einem <a href="#">Link</a>. Zweite Zeile, damit die Zeilenhöhe sichtbar wird: %1$s.', '12punkt-baukasten')), esc_html($sample));
                    ?></p>
                </div>
            </div>
        </div>
    </section>
<?php endforeach; ?>
