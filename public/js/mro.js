(function () {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;

    var navigating = false;
    var HOLD_MS = 400;

    function showLoader() {
        loader.classList.add('show');
        loader.setAttribute('aria-hidden', 'false');
        // Force a style flush so the 150ms opacity transition plays
        void loader.offsetWidth;
    }

    var subTabs = document.querySelectorAll('.sidebar-nav .nav-sub');
    subTabs.forEach(function (link) {
        link.addEventListener('click', function (e) {
            // Let regular/tabbed/special clicks through untouched
            if (e.defaultPrevented || e.button !== 0) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
            if (link.target || link.origin !== window.location.origin) return;

            e.preventDefault();

            if (navigating) return;
            navigating = true;

            // Instant visual feedback: mark clicked link active
            subTabs.forEach(function (l) { l.classList.remove('active'); });
            link.classList.add('active');

            showLoader();

            setTimeout(function () {
                window.location.href = link.href;
            }, HOLD_MS);
        });
    });

    // Safety: hide a stuck loader if navigation was blocked (e.g. beforeunload cancelled)
    window.addEventListener('pagehide', function () {
        loader.classList.remove('show');
    });
})();
