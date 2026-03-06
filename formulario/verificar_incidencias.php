<div class="iframe-container-quicktr" style="width: 100%; overflow: hidden; -webkit-overflow-scrolling: touch;">
    <iframe 
        src="https://quicktr.es/formulario/seguimiento.php" 
        id="ticketIframe" 
        style="width: 1px; min-width: 100%; border: none; min-height: 600px; transition: height 0.3s ease;" 
        scrolling="yes">
    </iframe>
</div>

<script data-no-optimize="1" data-sgoptimization-ignore="1">
(function() {
    function initIframe() {
        const iframe = document.getElementById('ticketIframe');
        if (iframe) {
            window.addEventListener('message', function(e) {
                if (e.data && e.data.height) {
                    iframe.style.height = e.data.height + 'px';
                }
            }, false);
        }
    }
    // Ejecutar si el DOM está listo o esperar a que lo esté
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIframe);
    } else {
        initIframe();
    }
})();
</script>