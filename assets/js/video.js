/**
 * 12punkt Baukasten – Video (YouTube/Vimeo erst nach Klick laden)
 * Ersetzt .zpt-video-consent durch das iframe aus data-zpt-video-src.
 */
document.addEventListener('click', function (event) {
    var button = event.target.closest('.zpt-video-consent');
    if (!button) {
        return;
    }
    var iframe = document.createElement('iframe');
    iframe.src = button.getAttribute('data-zpt-video-src');
    iframe.title = button.getAttribute('data-zpt-video-title') || 'Video';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
    iframe.allowFullscreen = true;
    iframe.setAttribute('frameborder', '0');
    button.replaceWith(iframe);
    iframe.focus();
});
