# Changelog

Neue Version: Version in `12punkt-baukasten.php` (Header + `ZPT_VERSION`) und `package.json` hochsetzen,
hier eintragen, committen, Tag `vX.Y.Z` setzen und pushen – Sites mit Update-Checker zeigen dann das Update.
Vermerken, wenn Inhalte/Einstellungen angepasst werden müssen.

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
