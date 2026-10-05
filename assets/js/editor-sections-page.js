/**
 * Block-Editor: Seiten mit Template „Sektionen-Seite“ werden im klassischen Editor bearbeitet
 * (includes/sections.php, Filter use_block_editor_for_post). Wechselt man hier auf dieses Template,
 * wird die Seite nach dem Speichern neu geladen – dann klassisch.
 */
(function (wp) {
    var wasSaving = false;

    wp.domReady(function () {
        wp.data.subscribe(function () {
            var editor = wp.data.select('core/editor');
            var editPost = wp.data.select('core/edit-post');
            // Meta-Boxen (ACF) werden nach dem Beitrag gespeichert – erst danach neu laden
            var saving = (editor.isSavingPost() && !editor.isAutosavingPost()) || !!(editPost && editPost.isSavingMetaBoxes());

            if (wasSaving && !saving && editor.didPostSaveRequestSucceed()
                && editor.getCurrentPostAttribute('template') === 'tmpl-sections_page.php') {
                window.location.reload();
            }
            wasSaving = saving;
        });
    });
})(window.wp);
