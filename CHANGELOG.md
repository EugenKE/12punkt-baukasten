# Changelog

Neue Version: Version in `12punkt-baukasten.php` (Header + `ZPT_VERSION`) und `package.json` hochsetzen,
hier eintragen, committen, Tag `vX.Y.Z` setzen und pushen – Sites mit Update-Checker zeigen dann das Update.
Vermerken, wenn Inhalte/Einstellungen angepasst werden müssen.

## 0.3.0 – 2026-10-05
- Updates über GitHub: WordPress zeigt neue Versionen (Git-Tags) unter Dashboard → Aktualisierungen.
  Privates Repo: `define('ZPT_GITHUB_TOKEN', '…');` in der wp-config.php. In Git-Checkouts aus.

## 0.2.0 – 2026-10-05
- Bild-, Card- und Row-Block: Bild aus der Mediathek oder aus einem Ordner im Theme.
- Favicon aus der Mediathek oder aus einem Ordner im Theme; Einstellungsseite „Logo & Bilder“.
- Abwärtskompatibel: nur neue Felder, Standard Mediathek.

## 0.1.0 – 2026-10-05
- Erster Stand im Repo.
