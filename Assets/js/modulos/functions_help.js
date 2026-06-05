/**
 * LDR Help Center - Image Zoom Logic
 */
const HelpCenter = {
    init: function() {
        this.bindEvents();
    },

    bindEvents: function() {
        const $overlay = $('#doc-zoom-overlay');
        const $zoomImg = $overlay.find('img');

        // Al hacer clic en cualquier imagen del manual
        $(document).on('click', '.img-doc', function() {
            const src = $(this).attr('src');
            $zoomImg.attr('src', src);
            $overlay.css('display', 'flex').hide().fadeIn(200);
            $('body').css('overflow', 'hidden'); // Bloquear scroll de fondo
        });

        // Cerrar al hacer clic en el overlay o en la imagen
        $overlay.on('click', function() {
            $(this).fadeOut(200, function() {
                $('body').css('overflow', 'auto');
            });
        });

        // Cerrar con la tecla ESC
        $(document).on('keydown', function(e) {
            if (e.key === "Escape") $overlay.click();
        });
    }
};

$(document).ready(() => HelpCenter.init());