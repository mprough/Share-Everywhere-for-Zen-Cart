(() => {
    'use strict';

    const root = document.getElementById('share-everywhere');
    if (!root) return;

    const target = document.querySelector('#addToCart-card, #cartAdd');
    const fallback = document.querySelector('[id$="ProductInfoDisplay"], #productGeneral, #productName');
    if (target) {
        target.after(root);
    } else if (fallback) {
        if (fallback.matches('h1, h2')) fallback.after(root);
        else fallback.append(root);
    }

    const title = root.querySelector('.se-title');
    const bar = root.querySelector('.se-bar');
    const toggle = root.querySelector('.se-toggle');
    const panel = root.querySelector('.se-panel');
    const label = toggle.querySelector('.se-toggle-label');
    const status = root.querySelector('.se-status');
    const qrBox = root.querySelector('.se-qr');
    const mobile = window.matchMedia('(max-width: 767px)');
    const hover = window.matchMedia('(hover: hover) and (pointer: fine)');
    const originalLabel = label.textContent;
    const groups = [...panel.querySelectorAll('.se-group')].map(group => ({
        group,
        list: group.querySelector('.se-items'),
        actions: [...group.querySelectorAll('.se-action')],
    }));
    const product = { title: root.dataset.seTitle, url: root.dataset.seUrl };
    const actions = groups.flatMap(group => group.actions);
    let expanded = false;
    let clickedOpen = false;
    let leaveTimer;
    let qrTrigger;

    function announce(message) {
        status.hidden = false;
        status.textContent = message;
    }

    function setExpanded(open, restoreFocus = false) {
        if (toggle.hidden) return;
        expanded = open;
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (!open) clickedOpen = false;
        if (restoreFocus) toggle.focus();
    }

    function available(action) {
        return action.dataset.seAction !== 'share' || typeof navigator.share === 'function';
    }

    function layout() {
        const mode = mobile.matches ? root.dataset.seMobile : root.dataset.seDesktop;
        root.dataset.seLayout = mode;
        let pinned = 0;
        groups.forEach(({group, list, actions: items}) => {
            items.forEach(action => {
                action.hidden = !available(action);
                if (!action.hidden && mode === 'compact' && action.dataset.sePinned === '1'
                    && pinned < Number(root.dataset.seVisible)) {
                    bar.insertBefore(action, toggle);
                    pinned += 1;
                } else {
                    list.append(action);
                }
            });
            group.hidden = ![...list.children].some(action => !action.hidden);
        });
        const hasRemaining = groups.some(({group}) => !group.hidden);
        toggle.hidden = !hasRemaining || (mode !== 'single' && mode !== 'compact');
        const showMore = mode === 'compact' && pinned > 0;
        const toggleLabel = showMore ? 'More ways to share' : originalLabel;
        const showTitle = root.dataset.seShowTitle === '1';
        title.hidden = !showTitle || (!toggle.hidden && !showMore);
        label.hidden = !showTitle && !showMore;
        label.textContent = toggleLabel;
        toggle.setAttribute('aria-label', toggleLabel);
        panel.hidden = !hasRemaining || !toggle.hidden;
        toggle.setAttribute('aria-expanded', 'false');
        expanded = false;
        clickedOpen = false;
        root.hidden = !actions.some(available);
    }

    async function copyLink() {
        try {
            await navigator.clipboard.writeText(product.url);
            announce('Product link copied.');
        } catch {
            const input = document.createElement('input');
            input.type = 'text';
            input.readOnly = true;
            input.value = product.url;
            input.setAttribute('aria-label', 'Product link');
            announce('Copy this product link:');
            status.append(input);
            input.select();
        }
    }

    function showQr(button) {
        try {
            const code = window.qrcode(0, 'M');
            // Byte-mode QR data must be ASCII or UTF-8. URL normalizes non-ASCII paths.
            code.addData(new URL(product.url).href);
            code.make();
            const modules = code.getModuleCount();
            const scale = Math.max(2, Math.floor(224 / (modules + 8)));
            const canvas = qrBox.querySelector('canvas');
            canvas.width = canvas.height = (modules + 8) * scale;
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = '#111';
            for (let row = 0; row < modules; row += 1) {
                for (let column = 0; column < modules; column += 1) {
                    if (code.isDark(row, column)) {
                        context.fillRect((column + 4) * scale, (row + 4) * scale, scale, scale);
                    }
                }
            }
            qrTrigger = button;
            qrBox.hidden = false;
            qrBox.querySelector('button').focus();
        } catch {
            announce('The QR code is unavailable. Use Copy link to share this product.');
        }
    }

    function closeQr() {
        qrBox.hidden = true;
        if (qrTrigger && qrTrigger.getClientRects().length) qrTrigger.focus();
        else if (!toggle.hidden) toggle.focus();
    }

    toggle.addEventListener('click', () => {
        if (expanded && clickedOpen) {
            setExpanded(false);
        } else {
            clickedOpen = true;
            setExpanded(true);
        }
    });

    if (root.dataset.seTrigger === 'hover') {
        toggle.addEventListener('pointerenter', event => {
            if (hover.matches && event.pointerType === 'mouse') {
                window.clearTimeout(leaveTimer);
                setExpanded(true);
            }
        });
        root.addEventListener('pointerenter', () => window.clearTimeout(leaveTimer));
        root.addEventListener('pointerleave', event => {
            if (event.pointerType !== 'mouse' || clickedOpen) return;
            leaveTimer = window.setTimeout(() => {
                if (!root.contains(document.activeElement)) setExpanded(false);
            }, 180);
        });
    }

    root.addEventListener('focusout', event => {
        if (expanded && !clickedOpen && !root.contains(event.relatedTarget) && !root.matches(':hover')) {
            setExpanded(false);
        }
    });
    root.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        if (!qrBox.hidden) closeQr();
        else if (expanded) setExpanded(false, true);
        else return;
        event.stopPropagation();
    });
    document.addEventListener('click', event => {
        if (!root.contains(event.target)) setExpanded(false);
    });
    qrBox.querySelector('button').addEventListener('click', closeQr);

    actions.forEach(button => {
        const action = button.dataset.seAction;
        if (action === 'copy') button.addEventListener('click', copyLink);
        if (action === 'print') button.addEventListener('click', () => window.print());
        if (action === 'qr') button.addEventListener('click', () => showQr(button));
        if (action === 'share') {
            button.addEventListener('click', async () => {
                try {
                    await navigator.share(product);
                } catch (error) {
                    if (error.name !== 'AbortError') await copyLink();
                }
            });
        }
    });
    mobile.addEventListener('change', layout);
    layout();
})();
