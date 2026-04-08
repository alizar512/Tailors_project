    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const body = document.body;
        const portal = body ? (body.getAttribute('data-portal') || '') : '';
        const key = portal ? ('silah_sidebar_' + portal) : 'silah_sidebar';
        const sidebar = document.querySelector('[data-sidebar]');
        const backdrop = document.querySelector('[data-sidebar-backdrop]');
        if (!body || !sidebar) return;

        const applyStored = () => {
            if (window.innerWidth < 1024) return;
            const v = localStorage.getItem(key);
            if (v === 'collapsed') {
                body.classList.add('sidebar-collapsed');
            } else {
                body.classList.remove('sidebar-collapsed');
            }
        };

        const toggle = () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                if (backdrop) {
                    backdrop.classList.toggle('hidden');
                }
                body.classList.toggle('overflow-hidden');
                return;
            }
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(key, body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
        };

        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', toggle);
        });

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                if (window.innerWidth >= 1024) return;
                if (!sidebar.classList.contains('hidden')) {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('flex');
                }
                backdrop.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            });
        }

        window.addEventListener('resize', applyStored);
        applyStored();
    })();

    (function() {
        const toggles = document.querySelectorAll('[data-bell-toggle]');
        if (!toggles.length) return;

        const menus = () => Array.from(document.querySelectorAll('[data-bell-menu]'));
        const closeAll = () => menus().forEach(m => m.classList.add('hidden'));

        toggles.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.getAttribute('data-bell-toggle');
                const menu = id ? document.querySelector('[data-bell-menu="' + id + '"]') : null;
                if (!menu) return;
                e.preventDefault();
                e.stopPropagation();
                const isHidden = menu.classList.contains('hidden');
                closeAll();
                if (isHidden) menu.classList.remove('hidden');
            });
        });

        document.addEventListener('click', () => closeAll());
        window.addEventListener('resize', () => closeAll());
    })();
</script>
</body>
</html>
