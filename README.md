# 12punkt Baukasten

Layout-Baukasten als ACF-Blocks für Gutenberg, mit Seiten-Templates, Sektionen und Style-Einstellungen.
Bringt Bootstrap 5.3 (CSS + JS), Grid-Erweiterungen, Spacing-/Typo-Utilities, Iconfonts (Bootstrap Icons, Font Awesome Free, Material Icons – lokal, zuschaltbar), Photoswipe und Masonry mit. Benötigt ACF Pro.

## Blocks

| Block          | Inhalt                                                                  |
|----------------|-------------------------------------------------------------------------|
| `zpt/row`      | Wrapper → `.container(-fluid)` → `.row`. Schrift (am Wrapper), Hintergrund (Farbe, Bild – wiederholen, Breite in % –, Video), Masonry, **oben anheften** (`sticky-top position-sticky`, nur Frontend; `assets/js/anchors.js` setzt `scroll-padding-top` = Höhe, damit Sprungziele nicht darunter liegen). Enthält nur Spalten (neue Row startet mit einer Spalte) |
| `zpt/column`   | Spalte `.zpt-col col-*`: Breite pro Breakpoint, Klassen, randabfallend, Schrift (Rolle, Größe, Gewicht, Ausrichtung, Farbe), Hintergrund. Enthält eine oder mehrere Komponenten |
| `zpt/headline` | Komponente (nur in einer Spalte)                                        |
| `zpt/text`     | dto.                                                                    |
| `zpt/image`    | dto., optional Photoswipe. Quelle **Mediathek** oder **Theme** (Bild aus dem Bilder-Ordner des Themes, s. Logo & Bilder) |
| `zpt/button`   | dto., ein oder mehrere Buttons/Links (Repeater): Link, Stil, Größe je Breakpoint (XS–XXL untereinander, leer = wie kleiner → `.btn-sm .btn-md-lg`), Icon (Icon-Auswahl, s. Icons) vor/nach dem Text; Ausrichtung (links/zentriert/rechts/verteilt). Stil: sind unter Baukasten → Buttons Varianten angelegt, nur diese, sonst Button/Outline je Farbe – dazu immer `.btn-link`, `.link-further`, Link. Kein Template (kommt über die Variante), keine Schrift (Baukasten → Buttons). Stile erweiterbar: Filter `zpt/button/styles` |
| `zpt/gallery`  | dto., Bilder als **Kacheln**, **Masonry** oder **Karussell** (Bootstrap-Carousel: Pfeile, Punkte, automatisch weiter, überblenden). Raster „pro Reihe“ je Breakpoint → `.row-cols-*`, Abstand → `.g-*`. Photoswipe, Bildunterschrift aus der Mediathek, Bildgröße, Seitenverhältnis (zugeschnitten) |
| `zpt/card`     | dto., Bootstrap-Card: Bild, Überschrift, Text, Link. Quelle eigener Inhalt oder **Beitrag/Seite** (Teaser: Beitragsbild, Titel, Auszug, Link – eigene Angaben gehen vor). Bild oben oder nebeneinander 50/50 bzw. 40/60 (ab MD), Reihenfolge, Link als Button (Stil wie Button-Block) und/oder ganze Card klickbar. Bild aus Mediathek oder Theme |
| `zpt/accordion`| dto., Bootstrap-Akkordeon: Einträge (Titel + Text), beim Laden offen (erster/keiner/alle), mehrere gleichzeitig offen, ohne Rahmen, Titel-Tag |
| `zpt/icon`     | dto., Icon aus den Icon-Sets (Icon-Auswahl, s. Einstellungen → Icons) oder SVG-Code (bereinigt: nur SVG-Elemente, keine Skripte/Ereignisse – `zpt_sanitize_svg()`), optional verlinkt, Größe (sonst Schriftgröße), Beschriftung für Screenreader |
| `zpt/video`    | dto., YouTube (`youtube-nocookie.com`) / Vimeo (`dnt=1`) – erst **nach Klick** geladen (Vorschaubild + Hinweis, `assets/js/video.js`), oder eigene Datei (Steuerung, Autoplay stumm/Schleife). Seitenverhältnis 16:9, 4:3, 1:1, 21:9, 9:16 |
| `zpt/posts`    | dto., Beiträge/Seiten **automatisch** (Inhaltstyp, Anzahl, Kategorien, Sortierung; ohne den aktuellen Beitrag) oder **von Hand**, als Kacheln (Raster wie Galerie), Liste oder Akkordeon. Bild, Datum, Auszug, Link-Text (leer = ganze Kachel klickbar). Abfrage änderbar: Filter `zpt/posts/query` |
| `zpt/navigation` | dto., WordPress-Menü: **Menü** oder **Menü-Position** des Themes („Position: Haupt-Navigation“ – folgt der Zuordnung unter Design → Menüs), Ebenen (nur oberste / mit Untermenüs), horizontal (Untermenüs als Bootstrap-Dropdown) oder vertikal (eingerückt), Stil Links / Pills / unterstrichen / schlichte Liste, **Ausrichtung je Breakpoint** (Gruppe `aligns`, leer = wie kleiner → `justify-content-md-end`, volle Breite → `.zpt-nav-fill-{bp}` / aufheben `.zpt-nav-nofill-{bp}`; `zpt_nav_align_classes()`; Blocks vor 0.4.4 mit `align` gelten als XS), Klassen je Link. Walker `ZPT_Nav_Walker` (`includes/navigation.php`). |
| `zpt/anchors`  | dto., **Sprungmarken**: Links zu CSS IDs dieser Seite (Repeater Text + ID, z. B. Row-IDs), gleiches Markup wie `zpt/navigation` (Komponente `navigation`, Stil/Richtung/Ausrichtung wie dort, Standard „unterstrichen“). Optional aktiven Abschnitt markieren (Scrollspy, `assets/js/anchors.js`, `.active` + `aria-current`) und **nicht umbrechen** (horizontal wischbar, `overflow-x-auto`; mittig/rechts dann per Auto-Margin statt `justify-content`, sonst wäre der Anfang abgeschnitten). Als Leiste oben: Row „Oben anheften“ + Hintergrundfarbe |

Aufbau: **Row → Spalte → Komponenten**. Spalte und Komponente werden getrennt eingestellt;
die Spaltenbreite steht nur an der Spalte.

Die Block-Felder sind in der Seitenleiste in aufklappbare Bereiche gegliedert (ACF-Feld „Akkordeon“
mit Wrapper-Klasse `zpt-panel`, erster Bereich offen, immer nur einer offen, am Ende ein
Akkordeon-Endpunkt). `assets/css/editor.css` gleicht sie optisch an die nativen Bereiche („Erweitert“) an.

**Bereich CSS** (letzter Bereich jedes Blocks): **CSS ID** (`css_id`, Row: Wrapper ID + Row ID) und
alle Klassen-Felder (Row: Wrapper, Container, Row, Inline Style; Spalte; Komponenten; Bild: Figure,
Container, Bild, Bildunterschrift). Die CSS ID geht vor dem HTML-Anker aus „Erweitert“; „#“ und
Leerzeichen werden bereinigt (`zpt_css_id()`). Hilfetext einheitlich
„ohne „.“, trennen mit Leerzeichen“. Ausnahmen (bleiben in ihrer Gruppe): Klassen je Button im
Repeater, Klassen des Hintergrundvideos der Row.

**Message** (Bild, Galerie – Bereich „Message“, Vorlage `group_zpt_tpl_message`): Text-Container über dem
Bild bzw. der ganzen Galerie. Position je Breakpoint (oben/Mitte/unten × links/mittig/rechts oder „unter dem
Bild“), Breite je Breakpoint (100 … 25 %, passend zum Text), Innenabstand, Abstand zum Rand,
Hintergrundfarbe und eigene Schrift (Schriftrolle, Gewicht, Farbe; **Schriftgröße und Ausrichtung je
Breakpoint** → `.font-size-{bp}-{stufe}`, `.text-{bp}-{start|center|end}`). Leer = keine Message.
Im Editor in Tabs: Text / Position / Schrift / Hintergrund.
Ausgabe `zpt_message_html()` → `.zpt-has-message` + `.zpt-message .zpt-msg-{bp}-{pos} .zpt-msg-w-{bp}-{breite}`
(`scss/components/_message.scss`), Klassen über „CSS Message“ im Bereich CSS.

**Feldbreiten**: In der Seitenleiste ist jedes Feld 100 % breit. Die Breiten in den Feldgruppen
(50 %, 33 %) gelten nur im Expanded Editor („Open Expanded Editor“). Ausnahme: Wrapper-Klasse
`zpt-cols-2` an einer Gruppe → deren Felder in der Seitenleiste 2 pro Zeile (Spaltenbreite XS–XXL).

Bereiche **Schrift** und **Hintergrund** (Row: nur Schrift, eigener Hintergrund; Spalte und Komponenten; Button: Bereich „Darstellung“ = Ausrichtung + Hintergrund): je ein Klon einer Gruppe aus
`group_zpt_tpl_properties` – `font` (Schriftrolle, Schriftgröße, Gewicht, Textausrichtung,
Schriftfarbe) bzw. `colors` (Hintergrundfarbe). Die Gruppen tragen die Klasse `zpt-group-plain`
(ohne Rahmen/Titel, die Felder stehen direkt im Bereich). Werte also `font_zpt_font_role`,
`font_color`, `colors_background_color` usw. Die **Schriftrolle** bietet nur Rollen mit zugeordneter Schrift
(Typografie → Rollen) und speichert den Rollen-Schlüssel (`serif`, `mono` …); ausgegeben wird
`.font-serif`, `.font-mono` … bzw. für die Grundschrift deren `.ff-{schrift}` (`zpt_font_role_class()`).
ausgelesen von `zpt_properties()` (`includes/helpers.php`).

Komponenten-Markup überschreiben: Datei aus `templates/components/` ins Theme kopieren nach
`{theme}/zpt-baukasten/components/{name}.php`.

## Block-Editor

Der Seitentitel wird im Inhaltsbereich ausgeblendet und rechts in der Seitenleiste (Übersicht bei
Status/Template) als Feld „Titel“ bearbeitet – für Seiten, Beiträge und Sektionen
(Filter `zpt/editor/hide_title`, leeres Array = Titel wieder normal). Code: `includes/editor.php`.

Die Seitenleiste des Block-Editors ist 360px statt 280px breit (ab 782px Fensterbreite).
Ändern: `add_filter('zpt/editor/sidebar_width', fn() => 400);` (0 = WordPress-Standard).

## Seiten-Templates und Sektionen

- **Block-Seite** (`tmpl-blocks.php`): Inhalt der Seite aus `zpt/row`-Blocks.
- **Sektionen-Seite** (`tmpl-sections_page.php`): gibt die im Feld „Sektionen“ gewählten
  Sektionen nacheinander aus (`<section id="section-{slug}" class="zpt-section">`; eigene ID: Feld „CSS ID“ der
  Sektion, `zpt_css_id`, `zpt_section_id()`).
  Solche Seiten öffnen im **klassischen Editor** (Titel, Feld „Sektionen“, Seiten-Attribute; Inhaltsfeld
  ausgeblendet) – Filter `use_block_editor_for_post`, Gutenberg bleibt für alles andere aktiv. Wird im
  Block-Editor auf „Sektionen-Seite“ gewechselt, lädt die Seite nach dem Speichern klassisch neu
  (`assets/js/editor-sections-page.js`); umgekehrt öffnet sie nach Wechsel auf ein anderes Template wieder im Block-Editor.
- **Sektionen** (CPT `section`, Menü „Sektionen“): wiederverwendbare Bereiche, ebenfalls mit
  `zpt/*`-Blocks gebaut. Container „wie Seite“ bezieht sich auf die Sektion selbst.
  Seitenleiste „Hintergrund“ (`group_zpt_section_bg.json`, Felder `zpt_bg_*`): Farbe, Bild (Mediathek/Theme),
  Wiederholen, Bildgröße in % – als Inline-Style an `<section>`, wie bei der Row (`zpt_background_style()`).
  Im Editor zeigt `assets/js/editor-section-bg.js` ihn live am `.editor-styles-wrapper`.
  Direkt aufgerufen (`/section/{slug}/`, auch „Vorschau“ im Editor): wer die Sektion bearbeiten darf,
  sieht sie einzeln mit Header/Footer (`templates/single-section.php`, `noindex`, nicht in der Sitemap);
  alle anderen werden per 302 umgeleitet – auf die erste Seite, die sie im Feld „Sektionen“ hat
  (`#` + ID der Sektion), bei der Footer-Sektion auf `/#footer`, sonst auf die Startseite
  (Filter `zpt/section/target_url`).
- **Footer**: Das Plugin gibt eine Sektion als Footer aus (`<footer id="footer" class="zpt-footer">`,
  im `wp_footer`-Hook vor den Skripten, also nach `</main>`). Welche: Sektion mit Haken „Als Footer
  verwenden“ (Seitenleiste der Sektion, Feld `zpt_footer`, immer nur eine), sonst die mit Slug `footer`;
  in der Sektionen-Liste als „Footer“ markiert. Ohne passende Sektion kein Footer.
  Theme mit eigenem Footer: `add_theme_support('zpt-theme-footer');`. Andere Position: `zpt_the_footer()`
  in der `footer.php` des Themes aufrufen (wird nur einmal ausgegeben). Filter `zpt/footer/section`,
  `zpt/footer/enabled`.

- **Styleguide** (`tmpl-styleguide.php`): zeigt die Style-Einstellungen zur Kontrolle, Bereich per
  Feld (Übersicht, Typografie, Farben, Abstände, Buttons). Neben jedem Beispiel stehen die im Browser
  gemessenen Werte. Seiten sind `noindex`. Code: `includes/styleguide.php`, `templates/styleguide/`.

Die Templates liegen in `templates/` und nutzen `get_header()`/`get_footer()` des Themes.
Überschreiben: gleichnamige Datei ins Theme legen. Ausgabe im eigenen Template:
`zpt_the_sections()` bzw. `zpt_the_section($id)`.

## Abstände und Größen (Bootstrap Utility API)

Die Bootstrap-Spacing-Skala ist durch eine **5px-Skala (0–300)** ersetzt; die Klassennamen folgen
Bootstrap (logische Seiten `s`/`e` statt `l`/`r`, Breakpoint vor dem Wert):

| Klasse                                   | Wirkung                          |
|------------------------------------------|----------------------------------|
| `.mt-20`, `.px-md-40`, `.p-lg-0`         | margin/padding (`m`/`p` + `t b s e x y`) |
| `.mt-n20`, `.ms-lg-n40`                  | negative margins                 |
| `.gap-10`, `.g-30`, `.gx-md-40`          | gap / Grid-Gutter                |
| `.w-35`, `.w-md-50`                      | Breite 0–100 % in 5er-Schritten  |
| `.h-vh-50`, `.h-vh-md-100`               | Höhe in vh (10er-Schritte)       |
| `.h-px-300`, `.h-px-lg-400`              | Höhe in px (0–500, 10er-Schritte)|

Achtung: Bootstraps `.mb-1` … `.mb-4` gibt es nicht mehr, `.m-5` ist 5px (nicht 3rem).
Skala ändern (eigener Build): `$zpt-spacer-step`, `$zpt-spacer-count` bzw. `$spacers`.

## Einstellungen (Menü „Baukasten“ → Farben, Typografie, Elemente, Buttons, Icons, Logo & Bilder, Layout, Meta Infos, Import / Export)

- **Farben → Tab „Einstellungen“**: Shade-Skala (Repeater `zpt_shade_scale`: Name, Mischen aufhellen/Grundfarbe/
  abdunkeln, Anteil in %), Reihenfolge hell → dunkel, genau eine Grundfarbe. Vorlagen (`ZPT_SHADE_PRESETS`):
  Bootstrap (100–900), Tailwind (50–950), Simple (light, base, dark) – laden ihre Shades in die Tabelle,
  danach anpassbar („Eigene“). Benennt man die Grundfarbe um (500 → base), ziehen die Grundfarben der
  Palette beim Speichern mit. Code: `zpt_color_scale()`, `zpt_color_base()`.
- **Farben → Tab „Farbpalette“** (Repeater `zpt_palette`): eigene Farben mit Name (`ecos-blau`:
  Kleinbuchstaben, Ziffern, Bindestrich; nicht wie eine Systemfarbe, nicht mit Shade-Endung) und Grundfarbe.
  Die Shade-Felder (`shade_{name}`) erzeugt das Plugin aus der Skala als lokale ACF-Unterfelder
  (`acf/include_fields`). Leere Shades werden berechnet, eingetragene überschreiben; im Admin live als
  Platzhalter/Vorschau. Ausgabe: `--theme-ecos-blau`, `--theme-ecos-blau-{shade}`, Klassen
  `.text-/.bg-/.border-/.color-/.fill-ecos-blau[-{shade}]` (inline, `zpt_color_classes_css()`).
- **Farben → Tab „Systemfarben“** (Repeater `zpt_system_colors`: Name + Farbe, gewählt wie in den Props mit
  Farbe + Shading, aber nur Palettenfarben, oder „eigene“): ganze Palettenfarbe → deren Shades; mit Shading oder
  eigene Farbe → wird Grundfarbe, Shades berechnet (`zpt_system_color_shades()`) → `--theme-{name}`, `--theme-{name}-{shade}`, `-hover`, `-contrast`, Shade-Klassen
  `.bg-primary-300` usw. (inline). Bootstrap-Namen (`ZPT_BOOTSTRAP_THEME_COLORS`: primary … dark, `ZPT_BOOTSTRAP_BASIC_COLORS`:
  black, white, gray) setzen auch `--bs-{name}`, `-rgb` (Theme-Farben zusätzlich subtle/emphasis), primary auch die
  Link-Farben → `.text-*`, `.bg-*`, `.btn-*`, `.text-bg-*` … folgen. **Nicht angelegt** gilt Bootstraps Wert
  (SCSS: `--theme-primary: var(--bs-primary)`). Eigene Namen (`accent`) ergeben nur `--theme-accent…` und
  `.text-/.bg-/.border-/.color-/.fill-accent[-{shade}]` – so auch Hintergründe (`section` → `.bg-section`, `.bg-section-100`).
- **Farben → Tab „WordPress“** (Repeater `zpt_wp_colors`, Farbe + Shading): welche Farben in WordPress erscheinen –
  in allen Farbauswahlen der Komponenten/Einstellungen (Farbe + Shading, außer der Systemfarben-Definition) und in
  der WordPress-Farbpalette (Block-Editor, Global Styles, TinyMCE; `zpt_wp_palette()`). Leer = alle (Palette des
  Editors: alle Grundfarben). Buttons über der Liste (fehlende anhängen): „Gesamte Farbpalette übernehmen“ (Palette mit
  allen Shades, `zpt_wp_palette_shade_tokens()`), „nur Grundfarben übernehmen“ (Palette), „Systemfarben übernehmen“. Code: `zpt_wp_color_tokens()`, JS: `zptColors.allowed`.
- **Farbfelder** (Props „Hintergrund/Schrift Farbe“, Row-Wrapper, Einstellungen): Textfeld mit
  Wrapper-Klasse `zpt-color-field`, Vorschau und Farbwähler daneben. Mit `zpt-color-tokens`
  (Props, Row, Elemente) statt Textfeld: Auswahl **Farbe** (Baukasten-Farben, „eigene“ → Textfeld
  für Hex/Farbname) + **Shading** (100–900, 500 = Grundfarbe) + Swatch; gespeichert wird ein Wert. Werte: `primary`, `primary-300`, `tomato`, `#ff6347`. Baukasten-Farben werden
  als `var(--theme-…)` ausgegeben (`zpt_color_value()`).
- **WordPress-Farbpalette**: Die Grundfarben (Systemfarben, dann Palette) gehen als Theme-Palette an WordPress
  (Filter `wp_theme_json_data_theme`, `zpt_wp_palette()`) – in jeder nativen Farbauswahl (Core-Blocks …) und im
  Textfarben-Knopf von TinyMCE (klassischer Editor, ACF-WYSIWYG, `textcolor_map`). WordPress-Standardfarben ausgeblendet.
  Core-Blocks speichern die Klasse `.has-{name}-color` (folgt späteren Farbänderungen), TinyMCE einen festen Hexwert.
  Anpassen/abschalten: Filter `zpt/wp_palette`.
- **Typografie** (eigene Seite, Postboxen):
  - *Schriftquellen*: WP-Schriftbibliothek (installierte Schriften anhaken, Standard alle),
    System-Standard (Stack, Standard `Arial, "Helvetica Neue", Helvetica, sans-serif`), Theme-Ordner.
  - *Schriftgrößen*: Skala der Stufen (Repeater `zpt_type_scale`: Name, Größe, Zeilenhöhe, Fließtext)
    mit Vorlagen „12punkt“ (sm, copy, copy-l, l, xl, xxl, xxxl) und „Einfach“ (small, base, large,
    extra-large), `ZPT_TYPE_PRESETS` in `includes/config.php`. Daraus `--theme-font-size-{stufe}` /
    `--theme-line-height-{stufe}` auf `:root`, Klassen `.font-size-{stufe}` und `.font-size-{bp}-{stufe}`,
    `body` in der Fließtext-Stufe sowie die Größen-Felder je Schrift und die Auswahlen „Größe“.
  - *Schriftschnitte & Maße*: Repeater, je Zeile eine Schrift aus WP-Schriftbibliothek, System-Standard oder Theme-Ordner
    (Feld „Theme-Ordner“: CSS-Dateien werden geladen, sonst `@font-face` aus den Dateien – Familie
    = Unterordner bzw. Dateiname bis `-`/`_`, Gewicht/Stil aus dem Namen). Pro Schrift Größe und
    Zeilenhöhe je Stufe (leer = Skala) sowie Laufweite je Gewicht (Zeilen je geladenem Gewicht beim Speichern).
    Ausgabe als Klasse `.ff-{schrift}` (z. B. `.ff-inter`), die `--theme-font-size-*`,
    `--theme-line-height-*` und `--zpt-ls-{gewicht}` im eigenen Bereich setzt:
    `.ff-inter.font-size-xl.fw-700` = Größe, Zeilenhöhe und Laufweite von Inter.
  - *Rollen*: Grundschrift (→ `:root`), Sans-Serif, Serif, Deko, Mono (`.font-serif` …).
  Laufweite greift über `.fw-100` … `.fw-900`, `.fw-bold`, `strong` usw. (`_typography.scss`).
  Im Block-Editor: Bereich Schrift → Schriftrolle / Schriftgröße / Gewicht.
  Code: `includes/fonts.php` (Laden), `includes/typography.php` (Werte, Klassen).
- **Elemente** (Tabs Struktur / Überschriften / Text / Auszeichnung / Forms / Klassen, je Element ein Akkordeon mit
  Zusammenfassung der gesetzten Werte im Titel, darin Tabs Schrift / Farben / Margin / Padding; leere Tabs fallen weg): body, header,
  section, article, footer; h1–h6, p, ul, ol, blockquote, small, figcaption,
  hr, strong, em, a; Forms: Feld-Label, Eingabefeld (`.form-control`, `.form-select` + Textfelder ohne
  Klasse), Platzhalter, Checkbox-/Radio-Label, Hilfetext, Fehlermeldung; Klassen: `.lead`,
  `.blockquote-footer`, `.zpt-sg-label` (Styleguide-Beschriftungen).
  Jedes Element hat nur die passenden Einstellungen: Schrift-Rolle, Gewicht (nur Gewichte der Schrift),
  Großbuchstaben, Farbe (Token wie `primary-700`), Aufzählungszeichen (ul/ol), Hover-Farbe und
  „nicht unterstrichen“ (a); als Matrix je Breakpoint XS–XXL: Größe (Typo-Stufe), Margin und Padding
  oben/rechts/unten/links in px (5er-Schritte, Margin auch negativ). Leer = wie umgebend bzw. „wie
  kleiner“ (leere Felder zeigen den geerbten Wert als „← 20“) – nur XS ausgefüllt gilt also für alle Breiten. Ausgabe mit Element-Selektoren nach `zpt.css` → Klassen am Element
  (`.mt-20`, `.font-size-xl` …) gewinnen. Werte: `options_zpt_element_{element}_{feld}`.
  **Felder werden erzeugt**: Elemente und ihre Einstellungen stehen in `ZPT_ELEMENTS`
  (`includes/config.php`), danach `npm run build:acf` → `acf-json/group_zpt_settings_elements.json`
  (nicht im ACF-Backend bearbeiten). Code: `includes/elements.php`, `bin/build-acf-elements.php`.
  **Felder werden erst beim Aufklappen geladen** (AJAX `zpt_element_fields`, `assets/js/admin-elements.js`):
  die Seite enthält je Element nur Titel + Zusammenfassung (`zpt_element_summary()`); gespeichert werden
  nur geöffnete Elemente, alle anderen bleiben unverändert. Größen-Auswahl = aktuelle Typo-Skala.
  Leere Werte werden nach dem Speichern aus `wp_options` gelöscht (`zpt_elements_cleanup()`), nur die
  Gruppen-Zeilen `options_zpt_element_{element}` bleiben (für `get_field()`).
  Alle Style-Variablen lassen sich per Filter `zpt/style/vars` ergänzen.
- **Buttons** (Tabs Grundeinstellungen / Templates / Varianten), leer = Bootstrap-Standard. Templates und Varianten
  laden eingeklappt (Titel = Name bzw. Klasse) und haben je Eintrag Tabs (Templates: Schrift / Rahmen & Hintergrund /
  Größen; Varianten: Farben / Rahmen / Schrift / Größen):
  - *Grundeinstellungen* – Repeater „Größen“ (vorbelegt mit Standard / Klein / Groß): nur Name und
    CSS-Klasse. `md` = Standard (`.btn` ohne Klasse), sonst `.btn-{klasse}` – eigene Größen wie `xl`
    stehen im Button-Block unter „Größe“ (je Breakpoint, `.btn-md-lg`).
  - *Templates* (Repeater, vorbelegt mit btn default / Pill / Outlined): Name, Kürzel, Schriftrolle,
    Gewicht, Großbuchstaben, Rahmenbreite, Eckenradius, Hintergrund (aus = transparent, Hover mit
    Rahmenfarbe gefüllt), Outline (Schrift in Rahmenfarbe) und Repeater **Größen**: je Größe
    Schriftgröße (CSS-Wert oder Stufe `copy`, `l` …), Zeilenhöhe, Laufweite, Innenabstand
    oben/unten und links/rechts.
    **btn default** (Kürzel `default`) gilt für alle `.btn` und lässt sich nicht löschen
    (Admin-Skript `assets/js/admin-buttons.js` + Prüfung beim Speichern); leere Werte je Größe =
    Bootstrap-Wert der Größe. Weitere Templates werden zu `.btn-{kürzel}` (`.btn-pill`); ihre Werte
    je Größe gelten als `.btn-pill` / `.btn-pill.btn-sm` / `.btn-pill.btn-md-lg`, leer = btn default.
    Längen ohne Einheit = px. Rahmenbreite: leer bei btn default = kein Rahmen; „none“/„keine“ = 0.
  - *Varianten* (Repeater): eine Klasse wie `.btn-primary`, `.btn-outline-danger` überschreiben oder
    eine **eigene** anlegen (`.btn-black`). Sobald Varianten angelegt sind, bietet der Button-Block
    unter „Stil“ nur diese an (plus Link-Stile). Je Variante Farben
    normal und Hover/Aktiv (Schrift-, Hintergrund-, Rahmenfarbe – Farbfelder mit Baukasten-Farben), Template (übernimmt dessen Werte inkl. Outline/Hintergrund,
    eigene Angaben gehen vor), Rahmen
    (Breite, Radius), Schriftrolle (Grundschrift, Sans-Serif, Serif, Deko, Mono), Gewicht,
    Großbuchstaben und Größen wie bei den Templates (leer = wie Template). Ohne Angabe: Schriftfarbe = Kontrastfarbe,
    Rahmenfarbe = Hintergrund, Hover = 30 % dunkler; Deaktiviert = normal.
  Ausgabe als `--bs-btn-*` nach `zpt.css` (Reihenfolge `.btn` → Größen → Templates → Varianten → Größen je Breakpoint;
  Outline/Hintergrund als `.btn.btn-{kürzel}`, damit sie die Farben überschreiben). Da `zpt.css` mit
  `$enable-rounded: false` gebaut ist, setzt das Plugin bei Eckenradius auch `border-radius` und die
  Ecken in `.btn-group`. Ohne Variante kommen die Farben aus Baukasten → Farben.
  Code: `includes/buttons.php`, Kontrolle: Styleguide → Buttons.
- **Icons** (je Set eine Box, einzeln zuschaltbar; nur aktive Sets werden geladen und in der Icon-Auswahl angeboten):
  - *Bootstrap Icons* (Standard: an), *Font Awesome Free 7* (Stile solid/regular/brands; optional eigene CSS-URL, z. B. Pro),
    *Material Icons* (Stile filled/outlined/round/sharp/two-tone, Ligaturen) – alle **lokal** aus `assets/vendor/`
    (nicht vom CDN/Google). Bootstrap Icons stecken nicht mehr in `zpt.css` (`.link-further`-Pfeil ist eine SVG-Maske).
  - *Eigenes SVG Icon Set*: Ordner im Theme (`*.svg`, Name = Dateiname, Child- vor Eltern-Theme) und/oder Repeater
    Name + SVG-Code (geht bei gleichem Namen vor). Bereinigt (`zpt_sanitize_svg()`; CSS aus `<style>`/`style=""` wird
    zu Attributen, z. B. Illustrator-Klassen), Breite/Höhe entfernt (1em groß), „Farbe = Textfarbe“ ersetzt
    fill/stroke durch `currentColor`. Vorschau unten in der Box.
  - **Icon-Auswahl**: ACF-Select mit Wrapper-Klasse `zpt-icon-picker` (Icon-Block, Buttons) – Suche per AJAX über alle
    aktiven Sets (Name; bei Font Awesome auch Suchbegriffe), Vorschau in der Liste (`assets/js/admin-icons.js`).
    Gespeichert wird ein Kürzel: `bi:arrow-right`, `fa-solid:house`, `fa-brands:github`, `mi-outlined:home`, `svg:kamera`
    (ohne „:“ = Bootstrap, alte Werte). Ausgabe: `zpt_icon('fa-solid:house')` – leer, wenn das Set nicht geladen ist.
    Icons sind so groß wie die Schrift (`.zpt-svg-icon`, `.zpt-mi`). Code: `includes/icons.php`.
- **Logo & Bilder** (Slug `zpt-logo`, drei Boxen; beim Export standardmäßig nicht angehakt, da Mediathek-IDs seitenspezifisch):
  - *Logo*: Hauptlogo (`zpt_logo`, SVG/PNG/JPG/WebP; leer = Logo aus dem Customizer), Alternativtext (leer = Seitenname),
    Repeater **Varianten** (`zpt_logo_variants`: Name, Kürzel – leer = aus dem Namen, „Weiß“ → `weiss`, eindeutig –, Bild).
    Ausgabe: `zpt_logo()`, `zpt_logo('weiss', ['class' => 'h-px-40 w-auto', 'inline' => true])` (SVG inline, bereinigt),
    Shortcode `[zpt_logo variant="weiss" class="…" inline="1"]`; Klassen `.zpt-logo .zpt-logo-{kürzel}`. Unbekannte
    Variante → Hauptlogo. Daten: `zpt_logos()`, `zpt_logo_id()`, Filter `zpt/logos`. Das Theme nimmt `zpt_logo()` im Header.
    Größe des Logos in der Leiste: Baukasten → Navigation → Schrift, Farben, Größen → Logo / Name → Tab *Logo*.
  - *Favicon*: Quelle (`zpt_favicon_source`) **Mediathek** oder **Ordner im Theme**.
    Mediathek: Bild (`zpt_favicon`, quadratisch, mind. 512 px) → erzeugt in `uploads/zpt-favicon/`: `favicon.ico`
    (16/32/48, PNG im ICO), `apple-touch-icon.png` (180, auf Hintergrundfarbe `zpt_favicon_background`, Standard weiß),
    `icon-192.png`, `icon-512.png`, `site.webmanifest`. Neu erzeugt, sobald sich Bild, Farben oder Seitenname ändern
    (Fingerabdruck, geprüft bei `admin_init`; Stand in Option `zpt_favicon`). Optional SVG-Favicon (`zpt_favicon_svg`),
    Theme-Farbe (`zpt_theme_color` → `<meta name="theme-color">`). Ersetzt das Website-Icon von WordPress
    (`wp_site_icon`) in Frontend, Admin und Login; `/favicon.ico` leitet auf die erzeugte Datei um.
    Ordner im Theme (`zpt_favicon_folder`, Standard `assets/favicons`, Child- vor Eltern-Theme): fertige Dateien
    `favicon.ico`, `favicon.svg` (sonst erstes `*.svg`), `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`,
    `site.webmanifest` werden direkt verlinkt, fehlende aus dem größten PNG/JPG/WebP erzeugt (bevorzugt `favicon.png`) –
    ein einzelnes `favicon.png` reicht. Geänderte Theme-Dateien greifen beim nächsten Aufruf des Admin-Bereichs.
  - *Bilder aus dem Theme*: Ordner (`zpt_theme_images_folder`, z. B. `assets/img`, mit Unterordnern; png/jpg/gif/webp/avif/svg)
    für Illustrationen und Grafiken, die zum Theme gehören. Bild-, Card- und Row-Block (Hintergrundbild) haben eine
    **Quelle Mediathek / Theme**; mit Theme eine Auswahl mit Vorschau (Feld mit Wrapper-Klasse `zpt-theme-image`),
    gespeichert als Pfad relativ zum Theme (`assets/img/illustrationen/sonne.svg`), plus Alternativtext (leer = aus dem
    Dateinamen). Ausgabe als `<img>` mit Breite/Höhe, ohne srcset. Helfer: `zpt_theme_file()`, `zpt_theme_files()`,
    `zpt_image_data()` / `zpt_image_html()` nehmen Attachment-ID oder Pfad. Code: `includes/theme-files.php`.
  - **SVG-Upload**: Administratoren dürfen SVG in die Mediathek laden; die Datei wird beim Hochladen mit
    `zpt_sanitize_svg()` bereinigt, Maße aus `width`/`height`/`viewBox` in den Metadaten, Vorschau in Mediathek/ACF,
    `wp_get_attachment_image()` funktioniert. Abschalten: `add_filter('zpt/svg_upload', '__return_false');`.
    Code: `includes/logo.php`.
- **Navigation** (Leiste im Theme-Kopf; das Theme holt die Werte über `zpt_navbar()`, Filter `zpt/navbar`):
  - *Navigationsleiste*: Container volle Breite (`.container-fluid`, Standard) oder feste Breite (`.container`);
    Position normal oder sticky (`.sticky-top` am `<header>`). Hintergrundfarbe: Box „Schrift, Farben, Größen“ →
    Leiste → Farben (bis 2026-10-04 Feld `zpt_nav_background` hier, Werte nach `zpt_element_navbar_background` migriert).
  - *Mobile Navigation*: **Umbruchpunkt** (ab welchem Breakpoint das Menü ausgeklappt steht → `.navbar-expand-{bp}`,
    Standard LG; „nie“ = immer Button, „immer ausgeklappt“ = `.navbar-expand`; Breakpoints aus Baukasten → Layout), Icons „Menü öffnen“ / „Menü schließen“ für den Toggler (Icon-Auswahl wie im Icon-Block;
    leer = Bootstrap-Hamburger bzw. wie „öffnen“), Icon-Größe (`--bs-navbar-toggler-font-size`). Umschalten per CSS
    über `aria-expanded` (`.zpt-toggler-open` / `.zpt-toggler-close`). Code: `includes/navigation.php`.
  - *Schrift, Farben, Größen*: wie Baukasten → Elemente (Schrift-Rolle, Gewicht, Großbuchstaben, Farbe/Hover,
    Hintergrund, Größe und Margin/Padding je Breakpoint) für Leiste (inkl. Hintergrund), Logo/Name, Menüpunkt, aktiven Menüpunkt,
    Untermenü, Untermenü-Eintrag und Menü-Button (Selektoren unter `.zpt-navbar`). Je Akkordeon Tabs Schrift / Farben /
    Margin / Padding wie unter Elemente (`zpt_build_element()`). Es sind Elemente der Gruppe
    `navbar` in `ZPT_ELEMENTS` (`ZPT_NAV_ELEMENT_GROUP`); `npm run build:acf` schreibt sie in
    `group_zpt_settings_nav_elements.json`. Hinweis: Den Bootstrap-Standard-Hamburger färbt „Farbe“ nicht (Hintergrundbild) –
    dafür ein Icon unter „Menü öffnen“ wählen.
    **Logo / Name** hat zusätzlich den Tab *Logo* (Feature `logo`). *Ausrichtung* (`logo_align`): oben / mittig / unten
    auf der Grundlinie der Menüschrift (mobil des Menü-Buttons) → `.zpt-logo-align-top|baseline` an der Leiste
    (`zpt_logo_align_class()`). Das Logo ist `display: block`, Bootstrap-Padding von `.navbar`, `.navbar-brand` und `.nav-link`
    ist 0 (`--bs-navbar-padding-y`, `--bs-navbar-brand-padding-y`, `--bs-navbar-nav-link-padding-x`, `--bs-nav-link-padding-y`)
    – nur eingetragenes Padding gilt. *Größe* (Felder `logo_size_mode`, `logo_size_{bp}`;
    Größe des Logos in der Leiste, `zpt_logo_size()` → `zpt_logo_css()`): *Feste Höhe* oder *Feste Breite*
    je Breakpoint in px (andere Seite `auto`; leer = wie kleiner, alles leer = 40 px, ab LG 50 px) oder *Höhe der
    Navigation*: `assets/js/navbar-logo.js` misst die Menüpunkte (eingeklappt den Menü-Button) und setzt
    `--zpt-logo-height` an `.zpt-navbar.zpt-logo-fit` (minus Padding von `.navbar-brand`). Nur mit CSS geht das nicht:
    die Breite des Logo-Links stünde fest, bevor die gestreckte Höhe bekannt ist. Theme: Klassen am Logo aus
    `zpt_navbar()['logo_class']` (leer) – feste Klassen wie `h-px-40` (`!important`) würden die Einstellung überstimmen.
  - *Sprungmarken im Menü*: Ziel = **CSS ID** einer Row (Bereich CSS), im Menü (Design → Menüs) ein individueller Link
    `#kontakt` bzw. `/#kontakt` (funktioniert auch von Unterseiten). `assets/js/anchors.js`: Abstand unter dem sticky
    Header (`scroll-padding-top`, `.zpt-sticky-header`), Scrollspy in `.zpt-navbar` (nur der Link zum sichtbaren Abschnitt
    ist `.active`; WordPress' „aktuelle Seite“ wird bei Anker-Links ignoriert – beide Walker).
- **Layout** – Box **Breakpoints**: Mindestbreite und Container-Breite je Breakpoint (Standard: Bootstrap 5.3).
  Weichen die Werte ab, lädt das Plugin statt `zpt.css` eine angepasste Kopie aus
  `uploads/zpt-baukasten/` (Media Queries + Container ersetzt, neu bei Änderung). Element-Größen,
  Editor-Spaltenbreiten und Styleguide nutzen dieselben Werte. Code: `includes/breakpoints.php`.
  Für einen eigenen Sass-Build gelten weiterhin `$grid-breakpoints` / `$container-max-widths`.
  Box **Grid**: Gutter (Standard 20px) und Container-Rand (je Seite, Standard halber Gutter), je
  Breakpoint; leer = Wert des kleineren Breakpoints. → `--zpt-grid-gutter` / `--zpt-container-padding`
  (auch im Theme-CSS setzbar), darauf bauen `$grid-gutter-width` und `$container-padding-x` auf
  (`.row`, `.grid`-Gap, `.container`). `.g-*`/`.gx-*` überschreiben den Gutter.
- **Globale Meta Infos**: Meta Description / Keywords. Pro Seite überschreibbar
  (Seitenleiste „Meta Infos“). Ausgabe im `<head>` durch das Plugin.
- **Import / Export**: Einstellungen gewählter Seiten als JSON-Datei exportieren bzw. importieren
  (Rohwerte aus `wp_options`, Zuordnung über die Feldgruppen der Seite; Import ersetzt die Seite
  vollständig, fremde Optionen werden ignoriert). Meta Infos standardmäßig nicht angehakt.
  Schriften nur per Name – Dateien müssen auf der Ziel-Seite liegen. Code: `includes/import-export.php`.

**Zwischenspeicher:** Das CSS aus allen Einstellungen (`zpt_style_css()`, kalt ~60 ms) liegt fertig in
der Option `zpt_style_css_cache`. Es wird neu erzeugt, sobald sich eine Option `options_*` ändert
(Speichern, Import, CLI), Schriften der WP-Schriftbibliothek, das Theme, der Schriften-Ordner im Theme
oder Plugin-Code/Feldgruppen (neueste Änderungszeit in `includes/`, `acf-json/`). Code: `includes/settings.php`.

## CSS anpassen (im Theme)

**Ohne Build** – CSS-Variablen in der Theme-CSS setzen (wird nach `zpt.css` geladen):

```css
:root {
    --theme-primary: #e30613;          /* Links, Buttons */
    --theme-primary-hover: #a1040d;
    --theme-font-family: "Lato", sans-serif;
    --theme-font-size-copy: 18px;      /* Stufen der Schriftgrößen-Skala */
    --bs-body-color: #222;             /* alle Bootstrap-5.3-Variablen */
}
```

**Mit eigenem Sass-Build** – nötig für Breakpoints, Spaltenzahl, Gutter, Spacing-Skala oder
zusätzliche Farb-Utilities (`.text-brand`):

```scss
$grid-gutter-width: 30px;
$primary: #e30613;
@import "zpt";   // Load-Paths: {plugin}/scss und {plugin}/node_modules
```

```php
add_filter('zpt/load_css', '__return_false');   // Plugin-CSS dann nicht laden
```

Nur Mixins/Variablen (z. B. `media-breakpoint-up()`) ohne CSS-Ausgabe: `@import "tools";`

## Filter

| Filter                      | Zweck                                              |
|-----------------------------|----------------------------------------------------|
| `zpt/load_css`              | `false` = zpt.css nicht laden (Frontend + Editor)  |
| `zpt/style/cache`           | `false` = Einstellungs-CSS nicht zwischenspeichern |
| `zpt/load_bootstrap_js`     | `false` = Bootstrap JS nicht laden                 |
| `zpt/allowed_blocks`        | Erlaubte Blocks im Editor (Standard: alle; nur Baukasten: `fn() => zpt_block_names()`) |
| `zpt/row/allowed_blocks`    | Erlaubte Blocks in einer Row (Standard: `zpt/column`) |
| `zpt/column/allowed_blocks` | Erlaubte Komponenten in einer Spalte               |
| `zpt/component/args/{name}` | Argumente einer Komponente ändern                  |
| `zpt/button/styles`        | Stil-Auswahl im Button-Block (`klasse => Label`)   |
| `zpt/posts/query`           | Abfrage des Posts-Blocks (automatisch), Argumente für `get_posts()` |
| `zpt/editor/sidebar_width`  | Breite der Editor-Seitenleiste in px (Standard 360, 0 = WP-Standard) |

## Entwicklung

```bash
npm install
npm run build    # scss → assets/css/zpt.css, Bootstrap JS → assets/vendor/, dazu build:icons
npm run watch    # nur CSS
npm run build:acf  # Feldgruppen „Elemente“ und Navigation „Schrift, Farben, Größen“ aus ZPT_ELEMENTS erzeugen
npm run build:icons  # Iconfonts → assets/vendor/, Namenslisten der Icon-Auswahl → assets/icons/lists/
```

Node 20.14: `sass` ist auf 1.93.2 fixiert (neuere Versionen brauchen Node ≥ 20.19).
`node_modules/` wird für den Betrieb nicht benötigt.

Feldgruppen liegen als ACF Local JSON in `acf-json/` (`group_zpt_*` werden beim Speichern im
ACF-Backend automatisch wieder dorthin geschrieben).

Infos (Anweisungen) stehen bei Baukasten-Feldern **unter dem Eingabefeld**, bei Gruppen, Repeatern,
Flexible Content und Clones **davor** (unter dem Label). Feldgruppen bleiben dafür auf
„Anweisungen: unter Beschriftung“. Einzelnes Feld mit Info oben: Wrapper-Klasse `zpt-info-top`.
Code: `includes/acf-json.php`.

### Übersetzungen (Admin-Sprachen EN / CZ)

Quellsprache ist **Deutsch** – die deutschen Texte sind die msgids, Deutsch braucht keine Sprachdatei.
Übersetzt werden: PHP (`__('…', '12punkt-baukasten')`), ACF-Feldgruppen aus `acf-json/` (ACF über `l10n_textdomain`,
Platzhalter/Prepend/Append per Filter), `block.json` (Titel, Beschreibung, Stichwörter) und JS (`wp.i18n.__`).
Code: `includes/i18n.php`, Build: `bin/build-i18n.php`, Dateien: `languages/`.

```bash
npm run i18n:pot    # languages/12punkt-baukasten.pot neu erzeugen und in die .po-Dateien einmischen
                    # → neue/geänderte Texte in 12punkt-baukasten-en_US.po / -cs_CZ.po übersetzen (z. B. Poedit)
npm run i18n:build  # .po → .mo (PHP, ACF, block.json) und .json (JS)
```

- Neue Texte im Code immer mit Textdomain als Literal; Platzhalter per `sprintf` + `/* translators: … */`.
- Kein `__()` in Konstanten oder vor `init`. Labels aus Konstanten (`config.php`, `ZPT_BUTTON_SIZES`) an der Verwendungsstelle
  mit `__($label, '12punkt-baukasten')` übersetzen – `bin/build-i18n.php` holt sie ins POT (`zpt_i18n_constants()`).
- Neues Skript mit Texten: Handle in `ZPT_I18N_SCRIPTS` (`includes/i18n.php`) eintragen.
- Im ACF-Feldgruppen-Editor wird nicht übersetzt, damit beim Speichern deutsche Texte im `acf-json/` bleiben.
- Die Sprache richtet sich nach der Profilsprache des Benutzers (Admin) bzw. der Website-Sprache (Frontend, z. B. Styleguide).

### Versionen und Updates

Repo: `git@github.com:EugenKE/12punkt-baukasten.git` (Branch `main`). Änderungen je Version in `CHANGELOG.md`.

Neue Version veröffentlichen:

1. Version in `12punkt-baukasten.php` (Header `Version` und `ZPT_VERSION`) und `package.json` hochsetzen, `CHANGELOG.md` ergänzen.
2. Committen, Tag setzen, pushen: `git tag v0.4.0 && git push origin main --tags`.

Sites mit dem Plugin (ohne `.git` im Plugin-Ordner) prüfen alle 12 Stunden bzw. unter Dashboard → Aktualisierungen
das Repo (Bibliothek *Plugin Update Checker*, `vendor/plugin-update-checker/`, Code: `includes/updater.php`) und bieten
das neueste Tag als Update an. Das Paket ist das Tag-Archiv von GitHub, ohne die Pfade mit `export-ignore` in
`.gitattributes` (`scss/`, `bin/`, `package*.json`) – die gebaute `assets/css/zpt.css` muss also mit committet sein.
Solange das Repo privat ist, braucht jede Site einen GitHub-Token mit Leserecht in der `wp-config.php`:
`define('ZPT_GITHUB_TOKEN', '…');`. Update-Paket von Hand: `git archive --prefix=12punkt-baukasten/ -o 12punkt-baukasten.zip v0.4.0`
(in WordPress unter Plugins → Installieren → Plugin hochladen, „Aktuelle Version ersetzen“).
