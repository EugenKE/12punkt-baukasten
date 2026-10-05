/**
 * 12punkt Baukasten – Photoswipe
 * Alle Links mit .add-to-photoswipe auf der Seite bilden eine gemeinsame Galerie.
 * Bildunterschrift: .caption-for-photoswipe im Link, sonst alt-Text des Bildes.
 */
(function () {
    var iconUrl = (window.zptPhotoswipe && zptPhotoswipe.iconUrl) || '';
    var icons = {arrowPrevSVG: 'back.svg', arrowNextSVG: 'forward.svg', closeSVG: 'close.svg', zoomSVG: 'zoom_in.svg'};

    function loadIcons() {
        return Promise.all(Object.keys(icons).map(function (key) {
            return fetch(iconUrl + icons[key])
                .then(function (response) { return response.ok ? response.text() : ''; })
                .then(function (svg) { return [key, svg]; })
                .catch(function () { return [key, '']; });
        }));
    }

    function init(iconOptions) {
        var options = {
            gallery: 'body',
            children: 'a.add-to-photoswipe',
            pswpModule: PhotoSwipe
        };
        iconOptions.forEach(function (entry) {
            if (entry[1]) { options[entry[0]] = entry[1]; }
        });

        var lightbox = new PhotoSwipeLightbox(options);

        lightbox.on('uiRegister', function () {
            lightbox.pswp.ui.registerElement({
                name: 'custom-caption',
                order: 9,
                isButton: false,
                appendTo: 'root',
                html: '',
                onInit: function (el) {
                    lightbox.pswp.on('change', function () {
                        var element = lightbox.pswp.currSlide.data.element;
                        var captionHTML = '';
                        if (element) {
                            var hiddenCaption = element.querySelector('.caption-for-photoswipe');
                            captionHTML = hiddenCaption
                                ? hiddenCaption.innerHTML
                                : (element.querySelector('img') || {getAttribute: function () { return ''; }}).getAttribute('alt') || '';
                        }
                        el.innerHTML = captionHTML ? '<div class="caption-inner p-10">' + captionHTML + '</div>' : '';
                    });
                }
            });
        });

        lightbox.init();
    }

    loadIcons().then(init);
})();
