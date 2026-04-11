            </div>
        </main>
    </div>

    <script>
        (function() {
            const sidebar = document.getElementById('cpSidebar');
            const overlay = document.getElementById('cpOverlay');
            const openBtn = document.getElementById('cpOpen');
            const closeBtn = document.getElementById('cpClose');

            const open = () => {
                if (!sidebar || !overlay) return;
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };
            const close = () => {
                if (!sidebar || !overlay) return;
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            if (openBtn) openBtn.addEventListener('click', open);
            if (closeBtn) closeBtn.addEventListener('click', close);
            if (overlay) overlay.addEventListener('click', close);
        })();
    </script>
</body>
</html>

