<?php
/**
 * Seiten-Template "Styleguide": zeigt die Style-Einstellungen des Baukastens zur Kontrolle.
 * Bereich (Typografie, Farben, Abstände, Buttons, Übersicht) per Feld auf der Seite.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) {
    the_post();
    $section = zpt_styleguide_section();
    ?>
    <div class="zpt-styleguide container py-60">

        <nav class="nav nav-pills mb-40">
            <?php foreach (zpt_styleguide_pages() as $key => $page): ?>
                <a class="nav-link<?php echo $key === $section ? ' active' : ''; ?>" href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html(get_the_title($page)); ?></a>
            <?php endforeach; ?>
        </nav>

        <h1 class="mb-20"><?php the_title(); ?></h1>
        <?php the_content(); ?>

        <?php include ZPT_PATH . 'templates/styleguide/' . $section . '.php'; ?>
    </div>
    <?php
}
?>

<style>
    /* .zpt-sg-label ohne Spezifität: Baukasten → Elemente → Klassen überschreibt */
    :where(.zpt-styleguide .zpt-sg-label) {font-family: var(--bs-font-monospace); font-size: 12px; line-height: 1.4; letter-spacing: normal;}
    .zpt-styleguide .zpt-sg-out   {font-family: var(--bs-font-monospace); font-size: 12px; line-height: 1.4; letter-spacing: normal; white-space: pre-line;}
    .zpt-styleguide .zpt-sg-swatch {height: 90px; border: 1px solid var(--bs-border-color);}
    .zpt-styleguide .zpt-sg-bar   {height: 12px; background: var(--bs-primary);}
    .zpt-styleguide .zpt-sg-track {background: var(--bs-secondary-bg);}
    .zpt-styleguide table td      {vertical-align: middle;}
</style>

<script>
// Gemessene Werte neben den Beispielen anzeigen (Element: [data-sg-target] in derselben Zeile/Box)
(function () {
    function zptPx(v) { return v.replace(/(\d+\.\d{2})\d+px/g, '$1px'); }
    function zptRgbToHex(rgb) {
        var m = rgb.match(/\d+(\.\d+)?/g);
        if (!m) { return rgb; }
        var hex = '#' + m.slice(0, 3).map(function (n) { return (+n).toString(16).padStart(2, '0'); }).join('');
        return m.length > 3 && +m[3] < 1 ? hex + ' (α ' + m[3] + ')' : hex;
    }
    function zptMeasure() {
        document.querySelectorAll('[data-sg-measure]').forEach(function (out) {
            var box = out.closest('[data-sg-box]');
            var el = box && box.querySelector('[data-sg-target]');
            if (!el) { return; }
            var cs = getComputedStyle(el);
            var text;
            switch (out.dataset.sgMeasure) {
                case 'font':
                    text = cs.fontFamily.split(',')[0].replace(/["']/g, '') + ' · ' + cs.fontWeight +
                        '\n' + zptPx(cs.fontSize) + ' / ' + zptPx(cs.lineHeight) + ' · LS ' + zptPx(cs.letterSpacing);
                    break;
                case 'color':
                    text = zptRgbToHex(cs.backgroundColor) + ' · <?php echo esc_js(__('Text', '12punkt-baukasten')); ?> ' + zptRgbToHex(cs.color);
                    break;
                case 'padding':
                    text = zptPx(cs.paddingLeft);
                    break;
                case 'button':
                    text = '<?php echo esc_js(__('Padding', '12punkt-baukasten')); ?> ' + zptPx(cs.paddingTop) + ' ' + zptPx(cs.paddingRight) +
                        ' · <?php echo esc_js(__('Rahmen', '12punkt-baukasten')); ?> ' + zptPx(cs.borderTopWidth) + ' ' + cs.borderTopStyle + ' ' + zptRgbToHex(cs.borderTopColor) +
                        ' · <?php echo esc_js(__('Radius', '12punkt-baukasten')); ?> ' + zptPx(cs.borderTopLeftRadius);
                    break;
                case 'margin':
                    text = zptPx(cs.marginLeft);
                    break;
                case 'width':
                    text = zptPx(cs.width);
                    break;
                default:
                    text = '';
            }
            out.textContent = text;
        });
        var bp = document.querySelector('[data-sg-breakpoint]');
        if (bp) { bp.textContent = window.innerWidth + 'px'; }
    }
    document.fonts && document.fonts.ready ? document.fonts.ready.then(zptMeasure) : zptMeasure();
    window.addEventListener('resize', zptMeasure);
})();
</script>

<?php
get_footer();
