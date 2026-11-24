(() => {
    const openBtn = document.querySelector('[data-dash-menu]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    const closeBtn = document.querySelector('[data-close-menu]');

    if (!openBtn || !mobileNav) {
        return;
    }

    const close = () => mobileNav.classList.remove('is-open');
    const open = () => mobileNav.classList.add('is-open');

    openBtn.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);

    mobileNav.addEventListener('click', (event) => {
        if (event.target === mobileNav) {
            close();
        }
    });

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const field = document.getElementById(button.dataset.copyTarget);

            if (!field) {
                return;
            }

            const text = field.value;

            try {
                await navigator.clipboard.writeText(text);
                button.textContent = 'Copied';
            } catch {
                field.focus();
                field.select();
                const copied = typeof document.execCommand === 'function' && document.execCommand('copy');
                button.textContent = copied ? 'Copied' : 'Copy failed';
            }

            window.setTimeout(() => {
                button.textContent = button.dataset.copyLabel || 'Copy';
            }, 1800);
        });
    });

    const presencePanel = document.querySelector('[data-workspace-presence]');

    if (presencePanel) {
        const refreshPresence = async () => {
            try {
                const response = await fetch(presencePanel.dataset.workspacePresence, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Could not refresh agent presence.');
                }

                const onlineCount = document.querySelector('[data-online-count]');

                if (onlineCount) {
                    onlineCount.textContent = `${result.online_count} / ${presencePanel.dataset.totalAgents}`;
                }

                for (const agent of result.agents) {
                    const row = presencePanel.querySelector(`[data-agent-row="${CSS.escape(String(agent.id))}"]`);
                    const pill = row?.querySelector('[data-agent-presence]');
                    const lastSeen = row?.querySelector('[data-agent-last-seen]');

                    if (pill) {
                        pill.classList.toggle('online', agent.online);
                        pill.classList.toggle('offline', !agent.online);
                        pill.textContent = agent.availability;
                    }

                    if (lastSeen) {
                        lastSeen.textContent = agent.last_seen;
                    }
                }
            } catch (error) {
                console.error(error);
            }
        };

        window.setInterval(refreshPresence, 10000);
    }
})();
