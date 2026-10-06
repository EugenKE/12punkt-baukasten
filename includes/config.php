<?php
/**
 * Feste Werte des Baukastens – reine Konstanten, ohne WordPress-Aufrufe,
 * damit auch der Feld-Generator (bin/build-acf-elements.php) sie laden kann.
 */

defined('ABSPATH') || exit;

// ----------------------------------------------------------------- TYPOGRAFIE

// Vorlagen der Schriftgrößen-Skala (Baukasten → Typografie → Schriftgrößen): [Name, Größe, Zeilenhöhe].
// 'base' = Stufe für den Fließtext. „12punkt“ entspricht scss/_config.scss ($default-font-size-* …).
const ZPT_TYPE_PRESETS = [
    '12punkt' => [
        'label' => '12punkt (sm, copy, copy-l, l, xl, xxl, xxxl)',
        'base'  => 'copy',
        'steps' => [
            ['sm', '12px', '1.2em'], ['copy', '16px', '1.2em'], ['copy-l', '20px', '1.2em'], ['l', '25px', '1.2em'],
            ['xl', '32px', '1em'], ['xxl', '46px', '1em'], ['xxxl', '74px', '1em'],
        ],
    ],
    'einfach' => [
        'label' => 'Einfach (small, base, large, extra-large)',
        'base'  => 'base',
        'steps' => [
            ['small', '14px', '1.4em'], ['base', '16px', '1.5em'], ['large', '20px', '1.4em'], ['extra-large', '28px', '1.2em'],
        ],
    ],
];

const ZPT_FONT_WEIGHTS = [
    '100' => 'Thin', '200' => 'ExtraLight', '300' => 'Light', '400' => 'Regular', '500' => 'Medium',
    '600' => 'SemiBold', '700' => 'Bold', '800' => 'ExtraBold', '900' => 'Black',
];

// Rollen: Feld zpt_font_families_{rolle} → Selektor + generische Ausweichschrift
const ZPT_FONT_ROLES = [
    'base'       => ['label' => 'Grundschrift', 'selector' => ':root',            'generic' => 'sans-serif'],
    'sans_serif' => ['label' => 'Sans-Serif',   'selector' => '.font-sans-serif', 'generic' => 'sans-serif'],
    'serif'      => ['label' => 'Serif',        'selector' => '.font-serif',      'generic' => 'serif'],
    'deco'       => ['label' => 'Deko',         'selector' => '.font-style-deco', 'generic' => 'sans-serif'],
    'mono'       => ['label' => 'Mono',         'selector' => '.font-mono',       'generic' => 'monospace'],
];

// ----------------------------------------------------------------- ABSTÄNDE

// 5px-Skala wie scss/_config.scss ($zpt-spacer-step, $zpt-spacer-count) → 0 … 300px
const ZPT_SPACER_STEP  = 5;
const ZPT_SPACER_COUNT = 60;

// ----------------------------------------------------------------- ELEMENTE (Baukasten → Elemente)

// Breakpoints der Element-Einstellungen (XS = alle Breiten, sonst ab Mindestbreite aus breakpoints.php)
const ZPT_ELEMENT_BREAKPOINTS = ['xs' => 'XS', 'sm' => 'SM', 'md' => 'MD', 'lg' => 'LG', 'xl' => 'XL', 'xxl' => 'XXL'];

const ZPT_ELEMENT_GROUPS = [
    'structure' => 'Struktur',
    'headings'  => 'Überschriften',
    'text'     => 'Text',
    'inline'   => 'Auszeichnung',
    'forms'    => 'Forms',
    'classes'  => 'Klassen',
];

/**
 * Elemente mit ihren Einstellungen. Ein Element bekommt nur die Felder seiner features:
 *   type    Schrift-Rolle, Gewicht, Großbuchstaben
 *   color   Schriftfarbe (hr: Linienfarbe)
 *   background Hintergrundfarbe
 *   size    Stufe je Breakpoint
 *   margin  Außenabstand oben/rechts/unten/links je Breakpoint
 *   padding Innenabstand oben/rechts/unten/links je Breakpoint
 *   list    Aufzählungszeichen (list-style-type)
 *   link    Hover-Farbe, ohne Unterstreichung
 *
 * Neues Element / neues Feature: hier eintragen, dann `npm run build:acf` (Feldgruppe neu erzeugen).
 */
const ZPT_ELEMENTS = [
    // Struktur: Seitenbausteine (body = Grundwerte der ganzen Seite). Kein <nav>: die Leiste im Kopf hat eigene
    // Einstellungen (Gruppe navbar, Baukasten → Navigation) – sonst zwei Stellen für dieselbe Navi
    'body'    => ['label' => 'Seite (body)',      'group' => 'structure', 'selector' => 'body',    'features' => ['type', 'color', 'background', 'size']],
    'header'  => ['label' => 'Kopfbereich (header)', 'group' => 'structure', 'selector' => 'header', 'features' => ['type', 'color', 'background', 'size', 'margin', 'padding']],
    'section' => ['label' => 'Abschnitt (section)', 'group' => 'structure', 'selector' => 'section', 'features' => ['type', 'color', 'background', 'size', 'margin', 'padding']],
    'article' => ['label' => 'Artikel (article)', 'group' => 'structure', 'selector' => 'article', 'features' => ['type', 'color', 'background', 'size', 'margin', 'padding']],
    'footer'  => ['label' => 'Fußbereich (footer)', 'group' => 'structure', 'selector' => 'footer', 'features' => ['type', 'color', 'background', 'size', 'margin', 'padding']],

    'h1'         => ['label' => 'H1', 'group' => 'headings', 'selector' => 'h1, .h1', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'h2'         => ['label' => 'H2', 'group' => 'headings', 'selector' => 'h2, .h2', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'h3'         => ['label' => 'H3', 'group' => 'headings', 'selector' => 'h3, .h3', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'h4'         => ['label' => 'H4', 'group' => 'headings', 'selector' => 'h4, .h4', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'h5'         => ['label' => 'H5', 'group' => 'headings', 'selector' => 'h5, .h5', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'h6'         => ['label' => 'H6', 'group' => 'headings', 'selector' => 'h6, .h6', 'features' => ['type', 'color', 'size', 'margin', 'padding']],

    'p'          => ['label' => 'Absatz (p)',                   'group' => 'text', 'selector' => 'p',                     'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'ul'         => ['label' => 'Liste (ul)',                   'group' => 'text', 'selector' => 'ul',                    'features' => ['type', 'color', 'size', 'list', 'margin', 'padding']],
    'ol'         => ['label' => 'Nummerierte Liste (ol)',       'group' => 'text', 'selector' => 'ol',                    'features' => ['type', 'color', 'size', 'list', 'margin', 'padding']],
    'blockquote' => ['label' => 'Zitat (blockquote)',           'group' => 'text', 'selector' => 'blockquote, .blockquote', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'small'      => ['label' => 'Klein (small)',                'group' => 'text', 'selector' => 'small, .small',         'features' => ['type', 'color', 'size']],
    'caption'    => ['label' => 'Bildunterschrift (figcaption)', 'group' => 'text', 'selector' => 'figcaption, .figure-caption, .wp-element-caption', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'hr'         => ['label' => 'Trennlinie (hr)',              'group' => 'text', 'selector' => 'hr',                    'features' => ['color', 'margin']],

    'strong'     => ['label' => 'Fett (strong)',  'group' => 'inline', 'selector' => 'strong, b, .font-bold',    'features' => ['type', 'color']],
    'em'         => ['label' => 'Kursiv (em)',    'group' => 'inline', 'selector' => 'em, .italic, .font-italic', 'features' => ['type', 'color']],
    'a'          => ['label' => 'Link (a)',       'group' => 'inline', 'selector' => 'a',                        'features' => ['type', 'color', 'link']],

    // Formulare: Bootstrap-Klassen + gleichwertige Elemente ohne Klasse (z. B. Formular-Plugins)
    'label'          => ['label' => 'Feld-Label (label, .form-label)',        'group' => 'forms', 'selector' => 'label, .form-label', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'input'          => ['label' => 'Eingabefeld (.form-control, .form-select)', 'group' => 'forms', 'selector' => '.form-control, .form-select, textarea, select, input:is([type=text], [type=email], [type=tel], [type=url], [type=number], [type=password], [type=search], [type=date])', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'placeholder'    => ['label' => 'Platzhalter (::placeholder)',            'group' => 'forms', 'selector' => '::placeholder',      'features' => ['color']],
    'form_check'     => ['label' => 'Checkbox-/Radio-Label (.form-check-label)', 'group' => 'forms', 'selector' => '.form-check-label', 'features' => ['type', 'color', 'size']],
    'form_text'      => ['label' => 'Hilfetext (.form-text)',                 'group' => 'forms', 'selector' => '.form-text',         'features' => ['type', 'color', 'size', 'margin']],
    'form_feedback'  => ['label' => 'Fehlermeldung (.invalid-feedback)',      'group' => 'forms', 'selector' => '.invalid-feedback',  'features' => ['type', 'color', 'size', 'margin']],

    // Klassen
    'lead'              => ['label' => 'Einleitung (.lead)',              'group' => 'classes', 'selector' => '.lead',              'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'blockquote_footer' => ['label' => 'Zitat-Quelle (.blockquote-footer)', 'group' => 'classes', 'selector' => '.blockquote-footer', 'features' => ['type', 'color', 'size', 'margin', 'padding']],
    'sg_label'          => ['label' => 'Styleguide-Label (.zpt-sg-label)',  'group' => 'classes', 'selector' => '.zpt-sg-label',      'features' => ['type', 'color', 'size', 'margin', 'padding']],

    // Navigationsleiste im Theme-Kopf (.zpt-navbar) – Felder auf der Seite Baukasten → Navigation (ZPT_NAV_ELEMENT_GROUP)
    'navbar'          => ['label' => 'Leiste (.navbar)',                     'group' => 'navbar', 'selector' => '.zpt-navbar',                 'features' => ['type', 'color', 'background', 'padding']],
    // logo = Tab „Logo-Größe“ (Höhe/Breite des Logos, zpt_logo_size() in logo.php)
    'navbar_brand'    => ['label' => 'Logo / Name (.navbar-brand)',          'group' => 'navbar', 'selector' => '.zpt-navbar .navbar-brand',   'features' => ['logo', 'type', 'color', 'size', 'margin', 'padding']],
    'nav_link'        => ['label' => 'Menüpunkt (.nav-link)',                'group' => 'navbar', 'selector' => '.zpt-navbar .nav-link',       'features' => ['type', 'color', 'link', 'background', 'size', 'margin', 'padding']],
    'nav_link_active' => ['label' => 'Aktiver Menüpunkt (.nav-link.active)', 'group' => 'navbar', 'selector' => '.zpt-navbar .nav-link.active, .zpt-navbar .nav-link.show', 'features' => ['type', 'color', 'background']],
    'nav_dropdown'    => ['label' => 'Untermenü (.dropdown-menu)',           'group' => 'navbar', 'selector' => '.zpt-navbar .dropdown-menu',  'features' => ['background', 'margin', 'padding']],
    'nav_dropdown_item' => ['label' => 'Untermenü-Eintrag (.dropdown-item)', 'group' => 'navbar', 'selector' => '.zpt-navbar .dropdown-item',  'features' => ['type', 'color', 'link', 'background', 'size', 'padding']],
    'nav_toggler'     => ['label' => 'Menü-Button mobil (.navbar-toggler)',  'group' => 'navbar', 'selector' => '.zpt-navbar .navbar-toggler', 'features' => ['color', 'background', 'padding']],
];

// Gruppe der Navigations-Elemente: nicht auf der Seite Elemente, sondern als Box auf Baukasten → Navigation
const ZPT_NAV_ELEMENT_GROUP = 'navbar';

// Seiten der Abstände: Feldname-Suffix => [CSS-Seite, Label]
const ZPT_ELEMENT_SIDES = [
    'top'    => ['top',    'oben'],
    'right'  => ['right',  'rechts'],
    'bottom' => ['bottom', 'unten'],
    'left'   => ['left',   'links'],
];

// Checkbox „Optionen“: type → uppercase, link → no_underline
const ZPT_ELEMENT_OPTIONS = [
    'uppercase'    => 'Großbuchstaben',
    'no_underline' => 'nicht unterstrichen',
];

const ZPT_LIST_STYLES = [
    'disc'                 => '● Punkt',
    'circle'               => '○ Kreis',
    'square'               => '■ Quadrat',
    'decimal'              => '1. Zahl',
    'decimal-leading-zero' => '01. Zahl mit Null',
    'lower-alpha'          => 'a. Buchstabe',
    'upper-alpha'          => 'A. Buchstabe groß',
    'lower-roman'          => 'i. Römisch',
    'upper-roman'          => 'I. Römisch groß',
    'none'                 => 'keine',
];
