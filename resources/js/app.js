// Scoreline — tiny, dependency-free interactions: a tab component and
// copy-to-clipboard buttons. No framework; progressive enhancement only.

function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach((group) => {
        const tabs = Array.from(group.querySelectorAll('[role="tab"]'));
        const panels = Array.from(group.querySelectorAll('[role="tabpanel"]'));

        const select = (id) => {
            tabs.forEach((t) => {
                const active = t.getAttribute('aria-controls') === id;
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                t.tabIndex = active ? 0 : -1;
            });
            panels.forEach((p) => {
                p.hidden = p.id !== id;
            });
        };

        tabs.forEach((tab, i) => {
            tab.addEventListener('click', () => select(tab.getAttribute('aria-controls')));
            tab.addEventListener('keydown', (e) => {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                e.preventDefault();
                const dir = e.key === 'ArrowRight' ? 1 : -1;
                const next = tabs[(i + dir + tabs.length) % tabs.length];
                next.focus();
                select(next.getAttribute('aria-controls'));
            });
        });
    });
}

const COPY_ICON =
    '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
const CHECK_ICON =
    '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';

// Inject a copy-to-clipboard button into every code block. The button shows a
// check mark for 5s on success (icon swap is CSS-driven via [data-copied]).
function initCopy() {
    document.querySelectorAll('pre.codeblock').forEach((pre) => {
        // Idempotent — never wrap a block twice.
        if (pre.parentElement?.classList.contains('codeblock-wrap')) return;

        const code = pre.querySelector('code') || pre;

        // Anchor the button to a non-scrolling wrapper: the <pre> itself scrolls
        // horizontally for wide commands, which would carry the button off-screen.
        const wrap = document.createElement('div');
        wrap.className = 'codeblock-wrap';
        pre.parentNode.insertBefore(wrap, pre);
        wrap.appendChild(pre);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'copybtn';
        btn.setAttribute('aria-label', 'Copy to clipboard');
        btn.innerHTML = `<span class="icon-copy">${COPY_ICON}</span><span class="icon-check">${CHECK_ICON}</span>`;
        wrap.appendChild(btn);

        let timer;
        btn.addEventListener('click', async () => {
            const text = code.innerText.replace(/\n+$/, '');
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            btn.dataset.copied = 'true';
            btn.setAttribute('aria-label', 'Copied!');
            clearTimeout(timer);
            timer = setTimeout(() => {
                btn.dataset.copied = 'false';
                btn.setAttribute('aria-label', 'Copy to clipboard');
            }, 5000);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initCopy();
});
