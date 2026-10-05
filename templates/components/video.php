<?php
/**
 * Komponente: Video
 * Override im Theme: zpt-baukasten/components/video.php
 *
 * YouTube/Vimeo: Button mit Vorschaubild; assets/js/video.js ersetzt ihn nach Klick durch das iframe
 * (data-zpt-video-src). Ohne Klick keine Verbindung zum Anbieter.
 *
 * @var array $args {
 *     @type string $source     youtube | vimeo | file
 *     @type string $embed      Einbettungs-Adresse (YouTube/Vimeo).
 *     @type string $file       Adresse der Videodatei.
 *     @type string $title
 *     @type int    $poster     Attachment-ID des Vorschaubilds.
 *     @type string $ratio      16x9, 4x3, 1x1, 21x9, 9x16
 *     @type bool   $controls   Datei: Steuerung.
 *     @type bool   $autoplay   Datei: stumm, Schleife, automatisch.
 *     @type array  $attrs      ['id', 'class', 'style'] für zpt_html_attrs().
 *     @type bool   $is_preview true im Editor.
 * }
 */

defined('ABSPATH') || exit;

$embed = (string) ($args['embed'] ?? '');
$file  = (string) ($args['file'] ?? '');

if ($embed === '' && $file === ''):
    if (!empty($args['is_preview'])):
        echo '<div class="zpt-placeholder">Video: Adresse oder Datei angeben …</div>';
    endif;
    return;
endif;

$title    = (string) ($args['title'] ?? '');
$poster   = (int) ($args['poster'] ?? 0);
$provider = ($args['source'] ?? '') === 'vimeo' ? 'Vimeo' : 'YouTube';
$attrs    = $args['attrs'] ?? [];
$attrs['class'] = zpt_class_list($attrs['class'] ?? [], 'ratio', 'ratio-' . ($args['ratio'] ?? '16x9'));
?>

<div<?php echo zpt_html_attrs($attrs); ?>>
    <?php if ($file !== ''): ?>
        <video src="<?php echo esc_url($file); ?>" playsinline
            <?php echo $poster ? 'poster="' . esc_url(wp_get_attachment_image_url($poster, 'large')) . '"' : ''; ?>
            <?php echo !empty($args['controls']) ? 'controls' : ''; ?>
            <?php echo !empty($args['autoplay']) && empty($args['is_preview']) ? 'autoplay muted loop' : ''; ?>
            <?php echo $title !== '' ? 'aria-label="' . esc_attr($title) . '"' : ''; ?>></video>
    <?php else: ?>
        <button type="button" class="zpt-video-consent" data-zpt-video-src="<?php echo esc_url($embed); ?>"
            data-zpt-video-title="<?php echo esc_attr($title ?: $provider . '-Video'); ?>">
            <?php if ($poster): ?>
                <?php echo wp_get_attachment_image($poster, 'large', false, ['class' => 'zpt-video-poster', 'alt' => '']); ?>
            <?php endif; ?>
            <span class="zpt-video-play" aria-hidden="true"><svg class="zpt-svg-icon" viewBox="0 0 16 16" fill="currentColor" focusable="false"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M6.79 5.093A.5.5 0 0 0 6 5.5v5a.5.5 0 0 0 .79.407l3.5-2.5a.5.5 0 0 0 0-.814z"/></svg></span>
            <span class="zpt-video-notice">
                <?php if ($title !== ''): ?><strong><?php echo esc_html($title); ?></strong><br><?php endif; ?>
                Video abspielen – dabei werden Daten an <?php echo esc_html($provider); ?> übertragen.
            </span>
        </button>
    <?php endif; ?>
</div><!-- .zpt-video -->
