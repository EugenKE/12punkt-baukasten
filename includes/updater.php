<?php
/**
 * Updates über GitHub (Bibliothek Plugin Update Checker, vendor/plugin-update-checker).
 *
 * - Neue Version = Git-Tag vX.Y.Z im Repo (Version im Plugin-Header vorher hochsetzen). WordPress zeigt
 *   dann unter Dashboard → Aktualisierungen „Update verfügbar“; das Paket ist das Tag-Archiv von GitHub
 *   (ohne die Pfade mit export-ignore in .gitattributes).
 * - Privates Repo: Lese-Token in der wp-config.php der Site: define('ZPT_GITHUB_TOKEN', '…');
 * - Nicht in Entwicklungs-Checkouts (Plugin-Ordner mit .git): Ein Update würde die Arbeitskopie überschreiben.
 */

defined('ABSPATH') || exit;

const ZPT_UPDATE_REPO = 'https://github.com/EugenKE/12punkt-baukasten/';

if (is_dir(ZPT_PATH . '.git') || !is_readable(ZPT_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php')) {
    return;
}

require_once ZPT_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

$zpt_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    ZPT_UPDATE_REPO,
    ZPT_PATH . '12punkt-baukasten.php',
    '12punkt-baukasten'
);

if (defined('ZPT_GITHUB_TOKEN') && ZPT_GITHUB_TOKEN) {
    $zpt_update_checker->setAuthentication(ZPT_GITHUB_TOKEN);
}

unset($zpt_update_checker);
