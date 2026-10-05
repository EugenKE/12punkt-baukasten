<?php
/**
 * Styleguide: Abstände – Spacer-Skala, negative Margins, Gutter, Breiten, Breakpoints.
 */

defined('ABSPATH') || exit;

$spacers = range(0, 300, 5);
$breakpoints = zpt_breakpoints();
?>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20">Breakpoints</h2>
    <p><?php esc_html_e('Fensterbreite:', '12punkt-baukasten'); ?> <code data-sg-breakpoint>…</code> · <?php esc_html_e('aktiv:', '12punkt-baukasten'); ?>
        <span class="badge text-bg-primary d-sm-none">XS</span>
        <span class="badge text-bg-primary d-none d-sm-inline d-md-none">SM</span>
        <span class="badge text-bg-primary d-none d-md-inline d-lg-none">MD</span>
        <span class="badge text-bg-primary d-none d-lg-inline d-xl-none">LG</span>
        <span class="badge text-bg-primary d-none d-xl-inline d-xxl-none">XL</span>
        <span class="badge text-bg-primary d-none d-xxl-inline">XXL</span>
    </p>
    <table class="table table-sm w-auto">
        <tr><th><?php esc_html_e('Breakpoint', '12punkt-baukasten'); ?></th><th><?php esc_html_e('ab Breite', '12punkt-baukasten'); ?></th><th><?php esc_html_e('Container', '12punkt-baukasten'); ?></th></tr>
        <tr><td>XS</td><td>0</td><td>100 %</td></tr>
        <?php foreach ($breakpoints as $bp => $values): ?>
            <tr><td><?php echo esc_html(strtoupper($bp)); ?></td><td><?php echo (int) $values['min']; ?>px</td><td><?php echo (int) $values['container']; ?>px</td></tr>
        <?php endforeach; ?>
    </table>
    <p class="mb-0"><?php
        /* translators: %s: Klassen-Beispiel (Code) */
        printf(esc_html__('Responsive Klassen: Breakpoint vor dem Wert, z. B. %s.', '12punkt-baukasten'), '<code>.mt-20 .mt-md-40 .mt-xl-80</code>');
    ?></p>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Skala (margin / padding / gap)', '12punkt-baukasten'); ?></h2>
    <p><?php
        /* translators: 1: Präfixe (Code), 2: Seiten (Code), 3: Beispiele (Code), 4: Balken-Klasse (Code) */
        printf(esc_html__('Klassen %1$s + %2$s + Wert, z. B. %3$s. Balken = %4$s, gemessen.', '12punkt-baukasten'), '<code>m p</code>', '<code>t b s e x y</code>', '<code>.pt-20</code>, <code>.mx-md-40</code>, <code>.gap-10</code>', '<code>.ps-{wert}</code>');
    ?></p>
    <table class="table table-sm">
        <?php foreach ($spacers as $value): ?>
            <tr data-sg-box>
                <td class="zpt-sg-label" style="width:120px">…-<?php echo $value; ?></td>
                <td style="width:100px"><?php echo zpt_sg_out('padding'); ?></td>
                <td><div class="d-inline-block zpt-sg-bar ps-<?php echo $value; ?>" data-sg-target></div></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Negative Margins', '12punkt-baukasten'); ?></h2>
    <p><code>.mt-n20</code>, <code>.ms-lg-n40</code> … – <?php
        /* translators: %s: Klasse (Code) */
        printf(esc_html__('Beispiel: Kasten mit %s ragt 20px nach links.', '12punkt-baukasten'), '<code>.ms-n20</code>');
    ?></p>
    <div class="zpt-sg-track p-20" data-sg-box>
        <div class="bg-primary text-bg-primary p-10 ms-n20" data-sg-target><span class="zpt-sg-label">.ms-n20</span></div>
        <?php echo zpt_sg_out('margin'); ?>
    </div>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Grid-Gutter', '12punkt-baukasten'); ?></h2>
    <?php foreach ([0, 10, 20, 40] as $gutter): ?>
        <div class="zpt-sg-label mb-10">.row.g-<?php echo $gutter; ?></div>
        <div class="row g-<?php echo $gutter; ?> mb-30">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="col-3"><div class="zpt-sg-track p-10 zpt-sg-label">col-3</div></div>
            <?php endfor; ?>
        </div>
    <?php endforeach; ?>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Breiten', '12punkt-baukasten'); ?></h2>
    <p><?php
        /* translators: 1: Klassen (Code), 2: Beispiel (Code) */
        printf(esc_html__('%1$s in 5er-Schritten, responsiv %2$s.', '12punkt-baukasten'), '<code>.w-{0…100}</code>', '<code>.w-md-50</code>');
    ?></p>
    <?php foreach (range(10, 100, 10) as $width): ?>
        <div class="zpt-sg-track mb-10" data-sg-box>
            <div class="zpt-sg-bar w-<?php echo $width; ?>" data-sg-target></div>
            <span class="zpt-sg-label">.w-<?php echo $width; ?></span> <?php echo zpt_sg_out('width'); ?>
        </div>
    <?php endforeach; ?>
</section>
