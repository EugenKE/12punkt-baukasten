<?php
/**
 * Styleguide: Buttons – Größen, Templates, Varianten je Farbe, Zustände (Einstellungen: Baukasten → Buttons).
 */

defined('ABSPATH') || exit;

$colors   = ZPT_BUTTON_COLORS;
$sizes    = [];
foreach (zpt_button_size_list() as $slug => $label) {
    $sizes[$slug === 'md' ? '' : "btn-{$slug}"] = $label;
}
$variants = zpt_button_variants();
$templates = [];
foreach (zpt_button_template_list() as $slug => $label) {
    $templates[$slug === 'default' ? '' : "btn-{$slug}"] = $label;
}
?>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Größen', '12punkt-baukasten'); ?></h2>
    <p><?php esc_html_e('Gemessen: Schrift · Gewicht, Größe / Zeilenhöhe · Laufweite, Innenabstand, Rahmen.', '12punkt-baukasten'); ?></p>
    <table class="table">
        <?php foreach ($sizes as $class => $label): ?>
            <tr data-sg-box>
                <td style="width:120px"><span class="zpt-sg-label"><?php echo esc_html($class ? ".{$class}" : '.btn'); ?></span></td>
                <td style="width:260px"><button type="button" class="btn btn-primary <?php echo esc_attr($class); ?>" data-sg-target><?php echo esc_html($label); ?></button></td>
                <td><?php echo zpt_sg_out('font'); ?><br><?php echo zpt_sg_out('button'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<?php if ($variants): ?>
<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Templates', '12punkt-baukasten'); ?></h2>
    <p><?php
        /* translators: %s: Klassen-Beispiel (Code) */
        printf(esc_html__('Jedes Template mit jeder Farbe kombinierbar: %s. Gemessen am ersten Button.', '12punkt-baukasten'), '<code>.btn .btn-{farbe} .btn-{template}</code>');
    ?></p>
    <table class="table">
        <?php foreach ($templates as $class => $label): ?>
            <tr data-sg-box>
                <td style="width:160px"><?php echo esc_html($label); ?><br><span class="zpt-sg-label"><?php echo esc_html($class ? ".{$class}" : '.btn'); ?></span></td>
                <td>
                    <div class="d-flex flex-wrap align-items-center gap-10">
                        <?php foreach (['primary', 'secondary', 'success', 'danger', 'dark'] as $i => $color): ?>
                            <button type="button" class="btn btn-<?php echo esc_attr($color); ?> <?php echo esc_attr($class); ?>"<?php echo $i ? '' : ' data-sg-target'; ?>><?php echo esc_html(ucfirst($color)); ?></button>
                        <?php endforeach; ?>
                        <button type="button" class="btn btn-primary <?php echo esc_attr($class); ?>" disabled><?php esc_html_e('Deaktiviert', '12punkt-baukasten'); ?></button>
                    </div>
                    <div class="mt-10"><?php echo zpt_sg_out('button'); ?></div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20"><?php esc_html_e('Varianten aus den Einstellungen', '12punkt-baukasten'); ?></h2>
    <p><?php esc_html_e('Gemessen: Hintergrund · Text, Rahmen. Hover-Farben mit der Maus prüfen.', '12punkt-baukasten'); ?></p>
    <table class="table">
        <?php foreach (array_keys($variants) as $class): ?>
            <tr data-sg-box>
                <td style="width:180px"><span class="zpt-sg-label">.<?php echo esc_html($class); ?></span></td>
                <td style="width:420px">
                    <div class="d-flex flex-wrap align-items-center gap-10">
                        <button type="button" class="btn <?php echo esc_attr($class); ?>" data-sg-target><?php esc_html_e('Button', '12punkt-baukasten'); ?></button>
                        <button type="button" class="btn <?php echo esc_attr($class); ?> btn-sm"><?php esc_html_e('Klein', '12punkt-baukasten'); ?></button>
                        <button type="button" class="btn <?php echo esc_attr($class); ?>" disabled><?php esc_html_e('Deaktiviert', '12punkt-baukasten'); ?></button>
                    </div>
                </td>
                <td><?php echo zpt_sg_out('color'); ?><br><?php echo zpt_sg_out('button'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php endif; ?>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20">Farben</h2>
    <table class="table">
        <?php foreach ($colors as $color): ?>
            <tr>
                <td style="width:120px"><span class="zpt-sg-label"><?php echo esc_html($color); ?></span></td>
                <td>
                    <div class="d-flex flex-wrap align-items-center gap-10">
                        <button type="button" class="btn btn-<?php echo esc_attr($color); ?>"><?php esc_html_e('Button', '12punkt-baukasten'); ?></button>
                        <button type="button" class="btn btn-outline-<?php echo esc_attr($color); ?>">Outline</button>
                        <button type="button" class="btn btn-<?php echo esc_attr($color); ?>" disabled><?php esc_html_e('Deaktiviert', '12punkt-baukasten'); ?></button>
                        <button type="button" class="btn btn-<?php echo esc_attr($color); ?> btn-sm"><?php echo zpt_icon('bi:arrow-right') ?: '→'; ?> <?php esc_html_e('Mit Icon', '12punkt-baukasten'); ?></button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<section class="mb-60">
    <h2 class="font-size-l border-bottom pb-10 mb-20">Links</h2>
    <div class="d-flex flex-wrap align-items-center gap-20">
        <a class="btn btn-primary" href="#">a.btn</a>
        <button type="button" class="btn btn-link">.btn-link</button>
        <a class="link-further" href="#">.link-further</a>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary"><?php esc_html_e('Gruppe', '12punkt-baukasten'); ?></button>
            <button type="button" class="btn btn-outline-primary"><?php esc_html_e('Gruppe', '12punkt-baukasten'); ?></button>
            <button type="button" class="btn btn-outline-primary"><?php esc_html_e('Gruppe', '12punkt-baukasten'); ?></button>
        </div>
    </div>
</section>

<?php if (current_user_can('manage_options')): ?>
    <p><a href="<?php echo esc_url(admin_url('admin.php?page=zpt-buttons')); ?>"><?php esc_html_e('Einstellungen bearbeiten: Buttons', '12punkt-baukasten'); ?></a></p>
<?php endif; ?>
