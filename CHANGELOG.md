# Changelog

Neue Version: Version in `12punkt-baukasten.php` (Header + `ZPT_VERSION`) und `package.json` hochsetzen,
hier eintragen, committen, Tag `vX.Y.Z` setzen und pushen – Sites mit Update-Checker zeigen dann das Update.
Vermerken, wenn Inhalte/Einstellungen angepasst werden müssen.

## 0.4.9 – 2026-10-06
- Navigation → Schrift, Farben, Größen: neues Element **Menü-Bereich (.navbar-collapse)** – Schrift, Farbe,
  Hintergrund, Margin und Padding je Breakpoint (z. B. Hintergrund und Abstand des aufgeklappten Mobilmenüs).

## 0.4.8 – 2026-10-06
- Navigation → Logo / Name: Tab „Logo-Größe“ heißt „Logo“ und hat die neue Einstellung *Ausrichtung*: oben / mittig /
  unten auf der Grundlinie der Menüschrift (mobil des Menü-Buttons).
- Leiste (`.navbar`, vorher 0.5rem oben/unten), Logo / Name (vorher 0.3125rem oben/unten) und Menüpunkte (vorher
  0.5rem rundum) ohne Bootstrap-Padding – nur eingetragenes Padding gilt. Das Logo ist `display: block` (keine
  Textzeile mehr um das Bild). Leiste und Menü werden dadurch niedriger bzw. enger, wo kein Padding eingetragen ist.
- Menüpunkt / aktiver Menüpunkt: Selektor `.zpt-navbar .navbar-nav .nav-link` – Padding links/rechts aus den
  Einstellungen wurde bei ausgeklapptem Menü von Bootstrap überstimmt und greift jetzt.

## 0.4.7 – 2026-10-06
- Logo-Größe als Tab „Logo-Größe“ im Akkordeon Logo / Name (Navigation → Schrift, Farben, Größen) statt eigener
  Box; Zusammenfassung im Titel („Logo-Höhe 40, LG 50“ / „Logo: Höhe der Navigation“). Neue Feldnamen
  (`zpt_element_navbar_brand_logo_size_*`) – in 0.4.4–0.4.6 gesetzte Werte neu eintragen (hier und auf Kokado war nichts gesetzt).

## 0.4.6 – 2026-10-06
- Logo-Größe von Logo & Bilder (Tab „Größe“) nach Baukasten → Navigation → Box „Logo“ verschoben.
  Gleiche Feldnamen – gesetzte Werte bleiben erhalten. Wird jetzt mit der Seite Navigation exportiert.

## 0.4.5 – 2026-10-06
- Sektionen: Hintergrund (Box „Hintergrund“) auch im Editor sichtbar und live beim Ändern
  (`assets/js/editor-section-bg.js`, am `.editor-styles-wrapper`).

## 0.4.4 – 2026-10-06
- Baukasten → Elemente: Element „Navigation (nav)“ entfernt – die Navigation wird nur noch unter
  Baukasten → Navigation gestaltet (vorher zwei Stellen, `.zpt-navbar` überschrieb `nav`). Gesetzte Werte
  am Element nav gelten nicht mehr: ggf. unter Navigation → Leiste eintragen.
- Logo & Bilder → Logo: Tabs Logo / Größe. Größe des Logos in der Navigationsleiste: feste Höhe oder Breite je
  Breakpoint oder „Höhe der Navigation“ (Logo so hoch wie die Menüpunkte, mobil wie der Menü-Button; misst
  `assets/js/navbar-logo.js`). Standard wie bisher 40 px, ab LG 50 px.
  **Theme anpassen:** in `header.php` am Logo `$zpt_navbar['logo_class'] ?? 'h-px-40 h-px-lg-50 w-auto'` statt der
  festen Klassen – sonst gewinnen die Klassen (`!important`) gegen die Einstellung.
- Block Navigation: Ausrichtung je Breakpoint (XS–XXL, leer = wie kleiner), „volle Breite“ jetzt auch responsive.
  Feld `align` → Gruppe `aligns` (`bp_xs` …): alte Blocks gelten weiter (Wert = XS); Inhalte hier und auf Kokado
  migriert (`aligns_bp_xs` ergänzt, `align` bleibt bis zum Neuspeichern).
- Sektionen: Feld „CSS ID“ in der Seitenleiste (Anker für Links/Sprungmarken), leer = wie bisher `section-{slug}`.

## 0.4.3 – 2026-10-06
- Sektionen: Hintergrund in der Seitenleiste (Farbe, Bild aus Mediathek oder Theme, Wiederholen, Bildgröße in %),
  gleiche Logik wie bei der Row (`zpt_background_style()`). Neue Felder – bestehende Sektionen unverändert.
- Baukasten → Elemente: je Element Tabs Schrift / Farben / Margin / Padding (wie bei der Navigation);
  Margin und Padding je in eigenem Tab mit 4 Zeilen (oben/rechts/unten/links). Navigation: Tab „Abstände“
  ebenso geteilt. Gespeicherte Werte unverändert (gleiche Feldnamen).
- Elemente/Navigation: leere Breakpoint-Felder zeigen den geerbten Wert kurz („← 20“ statt abgeschnittenem
  „wie kleiner (20)“).
- Baukasten → Buttons: Templates und Varianten laden eingeklappt, je Eintrag Tabs (Templates: Schrift /
  Rahmen & Hintergrund / Größen; Varianten: Farben / Rahmen / Schrift / Größen).

## 0.4.2 – 2026-10-06
- Row: Hintergrundbild wiederholen (Auswahl: nicht / ja / horizontal / vertikal) und Bildgröße in % der Row-Breite
  (`background-repeat`, `background-size`). Neue Felder, Standard aus – bestehende Rows unverändert.

## 0.4.1 – 2026-10-05
- Video-Block ohne Datenschutz-Hinweis: Vorschaubild des Anbieters (YouTube/Vimeo), wenn kein eigenes gesetzt ist –
  einmalig vom Server geholt und in `uploads/zpt-video/` gespeichert, Besucher laden es von der eigenen Site.
  Bild nicht abgedunkelt.

## 0.4.0 – 2026-10-05
- Video-Block: Schalter „Datenschutz-Hinweis anzeigen“ (Darstellung, nur YouTube/Vimeo). Aus = Hinweis und
  Titel nur für Screenreader; das Video lädt weiterhin erst nach Klick.
- Abwärtskompatibel: neues Feld, Standard an – bestehende Videos unverändert.

## 0.3.1 – 2026-10-05
- Video-Komponente: Hinweistext „Video abspielen – dabei werden Daten an … übertragen.“ im Frontend
  übersetzbar (Sprache der Website).
- Übersetzungen EN/CZ vervollständigt (Logo & Bilder, Favicon, Bilder aus dem Theme).

## 0.3.0 – 2026-10-05
- Updates über GitHub: WordPress zeigt neue Versionen (Git-Tags) unter Dashboard → Aktualisierungen.
  Privates Repo: `define('ZPT_GITHUB_TOKEN', '…');` in der wp-config.php. In Git-Checkouts aus.

## 0.2.0 – 2026-10-05
- Bild-, Card- und Row-Block: Bild aus der Mediathek oder aus einem Ordner im Theme.
- Favicon aus der Mediathek oder aus einem Ordner im Theme; Einstellungsseite „Logo & Bilder“.
- Abwärtskompatibel: nur neue Felder, Standard Mediathek.

## 0.1.0 – 2026-10-05
- Erster Stand im Repo.
