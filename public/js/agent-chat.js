(() => {
    const root = document.querySelector('[data-agent-chat]');

    if (!root) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const presenceToggle = root.querySelector('[data-agent-presence]');
    const presenceLabel = root.querySelector('[data-presence-label]');
    const queueNote = root.querySelector('[data-queue-note]');
    const list = root.querySelector('[data-conversation-list]');
    const messages = root.querySelector('[data-agent-messages]');
    const selectedCustomer = root.querySelector('[data-selected-customer]');
    const agentCount = root.querySelector('[data-agent-count]');
    const replyForm = root.querySelector('[data-agent-reply-form]');
    const closeButton = root.querySelector('[data-close-conversation]');
    const emptyState = root.querySelector('[data-agent-chat-empty]');
    const error = root.querySelector('[data-agent-error]');
    let activeConversation = null;
    let currentMessages = new Set();
    let lastMessageId = 0;
    let inboxBusy = false;
    let pusher = null;
    let workspaceChannel = null;
    let conversationChannel = null;
    let inboxTimer = null;

    const urlFor = (template, id) => template.replace('__CONVERSATION__', encodeURIComponent(id));
    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
    };
    const clearError = () => {
        error.textContent = '';
        error.hidden = true;
    };

    const subscribeToConversation = (id) => {
        if (!pusher) {
            return;
        }

        if (conversationChannel) {
            pusher.unsubscribe(conversationChannel.name);
        }

        conversationChannel = pusher.subscribe(`private-support-conversation.${id}`);
        conversationChannel.bind('support.message.sent', (message) => {
            if (activeConversation?.id === String(message.conversation_id)) {
                clearError();
                renderMessages([message]);
            }
        });
        conversationChannel.bind('support.conversation.updated', (conversation) => {
            if (activeConversation?.id !== String(conversation.id)) {
                return;
            }

            if (conversation.status !== 'assigned' || conversation.agent_id !== Number(root.dataset.agentId)) {
                activeConversation = null;
                currentMessages = new Set();
                messages.replaceChildren();
                selectedCustomer.textContent = 'Select a conversation';
                replyForm.hidden = true;
                closeButton.hidden = true;
                emptyState.hidden = false;
                pusher.unsubscribe(conversationChannel.name);
                conversationChannel = null;
                refreshInbox();
            }
        });
    };

    const request = async (url, method = 'GET', payload = null) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(payload ? { 'Content-Type': 'application/json' } : {}),
                ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            ...(payload ? { body: JSON.stringify(payload) } : {}),
        });
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'The request could not be completed.');
        }

        return result;
    };

    const connectRealtime = () => {
        if (!window.Pusher || !root.dataset.pusherKey || !root.dataset.workspaceId) {
            return;
        }

        pusher = new window.Pusher(root.dataset.pusherKey, {
            cluster: root.dataset.pusherCluster,
            forceTLS: true,
            channelAuthorization: {
                endpoint: root.dataset.broadcastAuthUrl,
                transport: 'ajax',
                headers: { Accept: 'application/json' },
            },
        });
        workspaceChannel = pusher.subscribe(`private-support-workspace.${root.dataset.workspaceId}`);
        workspaceChannel.bind('support.conversation.updated', refreshInbox);
        pusher.connection.bind('state_change', ({ current }) => {
            window.clearInterval(inboxTimer);
            inboxTimer = window.setInterval(refreshInbox, current === 'connected' ? 15000 : 3000);
        });
    };

    const renderMessages = (items) => {
        for (const item of items) {
            const messageId = String(item.id);

            if (currentMessages.has(messageId)) {
                continue;
            }

            currentMessages.add(messageId);
            lastMessageId = Math.max(lastMessageId, Number(item.id));
            const bubble = document.createElement('article');
            bubble.className = `chat-message ${item.sender === 'agent' ? 'mine' : ''}`;
            const label = document.createElement('small');
            label.textContent = item.sender_name || 'Support';
            const body = document.createElement('div');
            body.dir = 'auto';
            body.textContent = item.body;
            bubble.append(label, body);
            messages.append(bubble);
        }

        messages.scrollTop = messages.scrollHeight;
    };

    const loadMessages = async () => {
        if (!activeConversation) {
            return;
        }

        const conversationId = activeConversation.id;
        const result = await request(`${urlFor(root.dataset.messagesUrlTemplate, conversationId)}?after_id=${lastMessageId}`);

        if (activeConversation?.id === conversationId) {
            renderMessages(result.messages || []);
        }
    };

    const selectConversation = async (id, customerName) => {
        activeConversation = { id: String(id), customerName };
        currentMessages = new Set();
        lastMessageId = 0;
        messages.replaceChildren();
        selectedCustomer.textContent = customerName;
        emptyState.hidden = true;
        replyForm.hidden = false;
        closeButton.hidden = false;
        subscribeToConversation(id);
        list.querySelectorAll('[data-conversation-id]').forEach((button) => {
            button.classList.toggle('is-selected', button.dataset.conversationId === String(id));
        });
        clearError();

        try {
            await loadMessages();
        } catch (requestError) {
            showError(requestError.message);
        }
    };

    const renderInbox = (result) => {
        queueNote.textContent = `${result.waiting_count} waiting in your workspace queue`;
        agentCount.textContent = `${result.online_agents} agent${result.online_agents === 1 ? '' : 's'} online`;
        const existingIds = new Set((result.conversations || []).map((conversation) => String(conversation.id)));

        for (const button of list.querySelectorAll('[data-conversation-id]')) {
            if (!existingIds.has(button.dataset.conversationId)) {
                button.remove();
            }
        }

        for (const conversation of result.conversations || []) {
            let button = list.querySelector(`[data-conversation-id="${CSS.escape(String(conversation.id))}"]`);

            if (!button) {
                button = document.createElement('button');
                button.type = 'button';
                button.className = 'agent-conversation-item';
                button.dataset.conversationId = conversation.id;
                button.dataset.customerName = conversation.customer_name;
                const name = document.createElement('strong');
                const time = document.createElement('span');
                name.textContent = conversation.customer_name;
                time.textContent = 'New conversation';
                button.append(name, time);
                button.addEventListener('click', () => selectConversation(conversation.id, conversation.customer_name));
                list.prepend(button);
            }

            if (activeConversation?.id === String(conversation.id)) {
                button.classList.add('is-selected');
            }
        }

        const hasConversations = Boolean(list.querySelector('[data-conversation-id]'));
        const empty = list.querySelector('[data-empty-conversations]');

        if (!hasConversations && !empty) {
            const message = document.createElement('p');
            message.className = 'empty-state';
            message.dataset.emptyConversations = '';
            message.textContent = 'No active chats. Go online to receive new conversations.';
            list.append(message);
        } else if (hasConversations && empty) {
            empty.remove();
        }
    };

    const refreshInbox = async () => {
        if (inboxBusy) {
            return;
        }

        inboxBusy = true;

        try {
            const result = await request(root.dataset.inboxUrl);
            renderInbox(result);

            if (!activeConversation && result.conversations?.length) {
                const conversation = result.conversations[0];
                await selectConversation(conversation.id, conversation.customer_name);
            } else if (activeConversation) {
                await loadMessages();
            }
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            inboxBusy = false;
        }
    };

    const updatePresence = async () => {
        try {
            const result = await request(root.dataset.presenceUrl, 'PUT', {
                is_available: presenceToggle.checked,
            });
            presenceLabel.textContent = result.is_available ? 'Online' : 'Go online';
        } catch (requestError) {
            showError(requestError.message);
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            updatePresence();
        }
    });
    window.addEventListener('focus', updatePresence);
    window.addEventListener('pageshow', updatePresence);

    list.querySelectorAll('[data-conversation-id]').forEach((button) => {
        button.addEventListener('click', () => {
            selectConversation(button.dataset.conversationId, button.dataset.customerName);
        });
    });

    presenceToggle.addEventListener('change', updatePresence);

    replyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const field = replyForm.elements.message;
        const submit = replyForm.querySelector('button[type="submit"]');
        const message = field.value.trim();

        if (!activeConversation || !message) {
            return;
        }

        submit.disabled = true;
        clearError();

        try {
            const conversationId = activeConversation.id;
            const result = await request(urlFor(root.dataset.sendUrlTemplate, conversationId), 'POST', { message });

            if (activeConversation?.id !== conversationId) {
                return;
            }

            field.value = '';
            renderMessages([result.message]);
            field.focus();
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            submit.disabled = false;
        }
    });

    closeButton.addEventListener('click', async () => {
        if (!activeConversation) {
            return;
        }

        closeButton.disabled = true;

        try {
            await request(urlFor(root.dataset.closeUrlTemplate, activeConversation.id), 'POST', {});
            activeConversation = null;
            currentMessages = new Set();
            messages.replaceChildren();
            selectedCustomer.textContent = 'Select a conversation';
            replyForm.hidden = true;
            closeButton.hidden = true;
            emptyState.hidden = false;
            if (conversationChannel && pusher) {
                pusher.unsubscribe(conversationChannel.name);
                conversationChannel = null;
            }
            await refreshInbox();
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            closeButton.disabled = false;
        }
    });

    connectRealtime();
    updatePresence();
    refreshInbox();
    window.setInterval(updatePresence, 20000);
    inboxTimer = window.setInterval(refreshInbox, pusher ? 15000 : 3000);
})();
