(() => {
    const root = document.querySelector('[data-support-widget]');

    if (!root) {
        return;
    }

    const startForm = root.querySelector('[data-start-form]');
    const thread = root.querySelector('[data-thread]');
    const replyForm = root.querySelector('[data-reply-form]');
    const messages = root.querySelector('[data-messages]');
    const status = root.querySelector('[data-chat-status]');
    const error = root.querySelector('[data-chat-error]');
    const rating = root.querySelector('[data-rating]');
    const newChat = root.querySelector('[data-new-chat]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const storageKey = `escalora-support-${root.dataset.workspaceToken}`;
    let activeConversation = null;
    let pollTimer = null;
    let polling = false;
    let renderedMessageIds = new Set();
    let lastMessageId = 0;
    let pusher = null;
    let conversationChannel = null;
    let exitRequestSent = false;

    const urlFor = (template, id) => template.replace('__CONVERSATION__', encodeURIComponent(id));
    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
    };
    const clearError = () => {
        error.textContent = '';
        error.hidden = true;
    };

    const updateStatus = (state) => {
        if (state.status === 'waiting') {
            status.textContent = `You are in the queue. Your place in line is #${state.queue_position || 1}.`;
        } else if (state.status === 'assigned') {
            status.textContent = `You are chatting with ${state.agent_name || 'a support agent'}.`;
        } else if (state.status === 'closed') {
            status.textContent = 'This conversation has ended.';
            root.querySelector('[data-reply-form]').hidden = true;
            rating.hidden = !state.agent_name || state.rated || state.rating !== null;
            newChat.hidden = false;
        } else {
            status.textContent = 'AI support assistant';
        }
    };

    const renderMessages = (items) => {
        for (const item of items) {
            if (renderedMessageIds.has(item.id)) {
                continue;
            }

            renderedMessageIds.add(item.id);
            lastMessageId = Math.max(lastMessageId, item.id);
            const bubble = document.createElement('article');
            bubble.className = `support-message ${item.sender}`;
            const label = document.createElement('span');
            label.className = 'support-message-label';
            label.textContent = item.sender_name || ({
                customer: 'You',
                assistant: 'AI assistant',
                system: 'Support',
            }[item.sender] || 'Support agent');
            const body = document.createElement('p');
            body.className = 'support-message-body';
            body.dir = 'auto';
            body.textContent = item.body;
            bubble.append(label, body);
            messages.append(bubble);
        }

        messages.scrollTop = messages.scrollHeight;
    };

    const refresh = async () => {
        if (!activeConversation || polling) {
            return;
        }

        polling = true;

        try {
            const response = await fetch(urlFor(root.dataset.messagesUrlTemplate, activeConversation.id), {
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                method: 'POST',
                body: new URLSearchParams({
                    visitor_token: activeConversation.visitorToken,
                    after_id: String(lastMessageId),
                }),
                credentials: 'same-origin',
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Could not refresh this conversation.');
            }

            clearError();
            updateStatus(result);
            renderMessages(result.messages || []);
        } catch (requestError) {
            showError(requestError.message || 'Connection interrupted. We will keep trying.');
        } finally {
            polling = false;
        }
    };

    const openConversation = (conversation) => {
        activeConversation = conversation;
        renderedMessageIds = new Set();
        lastMessageId = 0;
        messages.replaceChildren();
        localStorage.setItem(storageKey, JSON.stringify(conversation));
        startForm.hidden = true;
        thread.hidden = false;
        connectRealtime(conversation);
        refresh();
        window.clearInterval(pollTimer);
        pollTimer = window.setInterval(refresh, pusher ? 15000 : 3000);
    };

    const connectRealtime = (conversation) => {
        if (!window.Pusher || !root.dataset.pusherKey || pusher) {
            if (!root.dataset.pusherKey) {
                showError('Live updates are not configured yet. Messages will refresh automatically.');
            }

            return;
        }

        pusher = new window.Pusher(root.dataset.pusherKey, {
            cluster: root.dataset.pusherCluster,
            forceTLS: true,
            channelAuthorization: {
                endpoint: root.dataset.broadcastAuthUrl,
                transport: 'ajax',
                params: { visitor_token: conversation.visitorToken },
                headers: { Accept: 'application/json' },
            },
        });

        const channelName = `private-support-conversation.${conversation.id}`;
        conversationChannel = pusher.subscribe(channelName);
        conversationChannel.bind('support.message.sent', (message) => {
            clearError();
            renderMessages([message]);
        });
        conversationChannel.bind('support.conversation.updated', updateStatus);
        pusher.connection.bind('state_change', ({ current }) => {
            if (current !== 'connected') {
                showError('Live connection interrupted. Reconnecting and checking for messages…');
                window.clearInterval(pollTimer);
                pollTimer = window.setInterval(refresh, 3000);
            } else {
                clearError();
                window.clearInterval(pollTimer);
                pollTimer = window.setInterval(refresh, 15000);
            }
        });
    };

    const sendJson = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const result = await response.json();

        if (!response.ok) {
            throw Object.assign(new Error(result.message || 'The message could not be sent.'), { result });
        }

        return result;
    };

    startForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        const submit = startForm.querySelector('button[type="submit"]');
        submit.disabled = true;

        try {
            const form = new FormData(startForm);
            const response = await fetch(root.dataset.createUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(Object.fromEntries(form.entries())),
            });
            const result = await response.json();

            if (result.conversation_id && result.visitor_token) {
                openConversation({ id: result.conversation_id, visitorToken: result.visitor_token });
            }

            if (!response.ok) {
                throw Object.assign(new Error(result.message || 'Could not start the conversation.'), { result });
            }

            updateStatus(result);
            await refresh();
        } catch (requestError) {
            if (requestError.result?.conversation_id && requestError.result?.visitor_token) {
                openConversation({
                    id: requestError.result.conversation_id,
                    visitorToken: requestError.result.visitor_token,
                });
            }

            showError(requestError.message);
        } finally {
            submit.disabled = false;
        }
    });

    replyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const field = replyForm.elements.message;
        const submit = replyForm.querySelector('button[type="submit"]');
        const message = field.value.trim();

        if (!message) {
            return;
        }

        submit.disabled = true;
        clearError();

        try {
            const result = await sendJson(urlFor(root.dataset.sendUrlTemplate, activeConversation.id), {
                message,
                visitor_token: activeConversation.visitorToken,
            });
            field.value = '';
            updateStatus(result);
            await refresh();
        } catch (requestError) {
            showError(requestError.message);
        } finally {
            submit.disabled = false;
        }
    });

    root.querySelectorAll('[data-rating-value]').forEach((button) => {
        button.addEventListener('click', async () => {
            clearError();

            try {
                await sendJson(urlFor(root.dataset.ratingUrlTemplate, activeConversation.id), {
                    rating: Number(button.dataset.ratingValue),
                    visitor_token: activeConversation.visitorToken,
                });
                rating.innerHTML = '<p>Thank you for rating your support experience.</p>';
            } catch (requestError) {
                showError(requestError.message);
            }
        });
    });

    newChat.addEventListener('click', () => {
        localStorage.removeItem(storageKey);
        window.clearInterval(pollTimer);
        window.location.reload();
    });

    window.addEventListener('pagehide', () => {
        if (!activeConversation || exitRequestSent) {
            return;
        }

        exitRequestSent = true;
        const payload = new URLSearchParams({
            _token: csrfToken || '',
            visitor_token: activeConversation.visitorToken,
        });
        const url = urlFor(root.dataset.endUrlTemplate, activeConversation.id);

        if (!navigator.sendBeacon(url, payload)) {
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: payload,
                keepalive: true,
            }).catch(() => {});
        }
    });

    try {
        const savedConversation = JSON.parse(localStorage.getItem(storageKey) || 'null');

        if (savedConversation?.id && savedConversation?.visitorToken) {
            openConversation(savedConversation);
        }
    } catch (storageError) {
        localStorage.removeItem(storageKey);
        showError('We could not restore your previous chat. Please start a new one.');
    }
})();
