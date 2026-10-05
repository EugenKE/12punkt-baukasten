/**
 * 12punkt Baukasten – Masonry für Rows mit .row-masonry
 * Item-Selector anpassbar über data-msnry-selector an der Row.
 */
window.zptMasonry = [];

window.addEventListener('load', function () {
    document.querySelectorAll('.row-masonry').forEach(function (row) {
        var selector = row.getAttribute('data-msnry-selector') || '.zpt-col, .section-col';
        window.zptMasonry.push(new Masonry(row, {
            itemSelector: selector,
            percentPosition: true,
            transitionDuration: 0,
            columnWidth: row.querySelector('.masonry-grid-sizer')
        }));
    });
});
