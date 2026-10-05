<?php
/**
 * Styleguide: Übersicht – aktuelle Einstellungen auf einen Blick.
 */

defined('ABSPATH') || exit;

$roles   = zpt_font_roles();
$labels  = ['base' => __('Grundschrift', '12punkt-baukasten'), 'sans_serif' => __('Sans-Serif', '12punkt-baukasten'), 'serif' => __('Serif', '12punkt-baukasten'), 'deco' => __('Deko', '12punkt-baukasten'), 'mono' => __('Mono', '12punkt-baukasten')];
?>

<div class="row g-30">
    <div class="col-12 col-lg-6">
        <h2 class="font-size-l mb-20"><?php esc_html_e('Schriften', '12punkt-baukasten'); ?></h2>
        <table class="table">
            <?php foreach (zpt_loaded_fonts() as $family => $font): ?>
                <tr><th><?php echo esc_html($family); ?></th><td><code>.ff-<?php echo esc_html($font['slug']); ?></code> · <?php echo esc_html(implode(', ', $font['weights'])); ?>
                    <div class="small text-body-secondary"><?php echo esc_html($font['source']['label']); ?></div></td></tr>
            <?php endforeach; ?>
            <?php foreach ($labels as $role => $label): ?>
                <tr><th><?php echo esc_html($label); ?></th><td><?php echo esc_html($roles[$role] ?? __('Standard', '12punkt-baukasten')); ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="col-12 col-lg-6">
        <h2 class="font-size-l mb-20"><?php esc_html_e('Bereiche', '12punkt-baukasten'); ?></h2>
        <div class="list-group">
            <?php foreach (zpt_styleguide_pages() as $key => $page): if ($key === 'overview') continue; ?>
                <a class="list-group-item list-group-item-action" href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html(get_the_title($page)); ?></a>
            <?php endforeach; ?>
        </div>
        <?php if (current_user_can('manage_options')): ?>
            <p class="mt-20"><?php esc_html_e('Einstellungen bearbeiten:', '12punkt-baukasten'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpt-colors')); ?>"><?php esc_html_e('Farben', '12punkt-baukasten'); ?></a> ·
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpt-typography')); ?>"><?php esc_html_e('Typografie', '12punkt-baukasten'); ?></a> ·
                <a href="<?php echo esc_url(admin_url('admin.php?page=zpt-buttons')); ?>"><?php esc_html_e('Buttons', '12punkt-baukasten'); ?></a></p>
        <?php endif; ?>
    </div>
</div>
