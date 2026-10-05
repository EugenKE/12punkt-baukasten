/**
 * Block-Editor: Feld „Titel“ oben in der Seitenleiste (Übersicht bei Status, Template …),
 * weil der Titel im Inhaltsbereich ausgeblendet ist (siehe includes/editor.php).
 */
(function (wp) {
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Slot = (wp.editor && wp.editor.PluginPostStatusInfo) || (wp.editPost && wp.editPost.PluginPostStatusInfo);

    if (!Slot) {
        return;
    }

    function ZptTitleField() {
        var title = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('title') || '';
        }, []);
        var editPost = useDispatch('core/editor').editPost;

        return el(
            Slot,
            {className: 'zpt-title-field'},
            el(wp.components.TextControl, {
                label: __('Titel', '12punkt-baukasten'),
                value: title,
                placeholder: __('Titel hinzufügen', '12punkt-baukasten'),
                onChange: function (value) {
                    editPost({title: value});
                },
                __nextHasNoMarginBottom: true,
                __next40pxDefaultSize: true
            })
        );
    }

    wp.plugins.registerPlugin('zpt-title-panel', {render: ZptTitleField});
})(window.wp);
