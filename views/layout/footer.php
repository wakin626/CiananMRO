    </div>
</div>
<button type="button" class="sidebar-reopen" id="sidebarReopen"
        title="Open sidebar" aria-label="Open sidebar">&#9654;</button>
<script>
(function () {
    var sidebar = document.querySelector('.sidebar');
    var toggleBtn = document.getElementById('sidebarToggle');
    var reopenBtn = document.getElementById('sidebarReopen');
    if (!sidebar || !toggleBtn || !reopenBtn) return;

    function sync() {
        var collapsed = sidebar.classList.contains('collapsed');
        reopenBtn.style.display = collapsed ? 'block' : 'none';
        document.cookie = 'sidebar_collapsed=' + (collapsed ? '1' : '0') + '; path=/CIANANMRO; max-age=31536000';
    }

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.add('collapsed');
        sync();
    });

    reopenBtn.addEventListener('click', function () {
        sidebar.classList.remove('collapsed');
        sync();
    });

    sync();
})();
</script>
<script>
(function () {
    // One-shot: clear the fresh_login suppression so later page loads
    // expand the active group normally again
    document.cookie = 'fresh_login=; max-age=0; path=/CIANANMRO';

    var groups = document.querySelectorAll('.nav-group');
    if (groups.length === 0) return;

    var COOKIE_NAMES = ['mi_group_open', 'sr_group_open'];

    groups.forEach(function (group, i) {
        var toggle = group.querySelector('.nav-group-toggle');
        if (!toggle) return;

        var cookieName = COOKIE_NAMES[i] || 'group_' + i + '_open';

        toggle.addEventListener('click', function () {
            var willOpen = !group.classList.contains('open');

            if (willOpen) {
                // Exclusive accordions: opening one master menu collapses the others
                groups.forEach(function (other, j) {
                    if (other !== group && other.classList.contains('open')) {
                        other.classList.remove('open');
                        other.querySelector('.nav-group-toggle').setAttribute('aria-expanded', 'false');
                        writeCookie(COOKIE_NAMES[j] || 'group_' + j + '_open', false);
                    }
                });
            }

            group.classList.toggle('open', willOpen);
            writeCookie(cookieName, willOpen);
        });
    });

    function writeCookie(name, open) {
        var val = open === true ? '1' : '0';
        document.cookie = name + '=' + val + '; path=/CIANANMRO; max-age=31536000';
    }
})();
</script>
<script>
(function () {
    var targets = document.querySelectorAll('#withdrawSpec, #requestItemSpec');
    if (targets.length === 0) return;

    var tip = document.createElement('div');
    tip.className = 'spec-tooltip';
    document.body.appendChild(tip);

    function show(el) {
        var text = el.value;
        var label = el.dataset.emptyLabel || '';
        if (!text || text === label) return;
        tip.textContent = text;
        tip.style.display = 'block';
        var rect = el.getBoundingClientRect();
        var top = rect.bottom + 6;
        tip.style.left = Math.max(8, rect.left) + 'px';
        tip.style.top = top + 'px';
        if (tip.offsetHeight + top > window.innerHeight - 8) {
            tip.style.top = Math.max(8, rect.top - tip.offsetHeight - 6) + 'px';
        }
    }

    targets.forEach(function (el) {
        el.addEventListener('mouseenter', function () { show(el); });
        el.addEventListener('mousemove', function () {
            if (tip.style.display === 'block' && tip.textContent !== el.value) show(el);
        });
        el.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
        el.addEventListener('blur', function () { tip.style.display = 'none'; });
    });
})();
</script>
<script src="/CIANANMRO/public/js/mro.js"></script>
</body>
</html>
