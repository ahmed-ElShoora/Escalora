<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat with {{ $workspace->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/support.css') }}">
</head>
<body class="support-page {{ $embedded ? 'support-embedded' : '' }}">
    <main
        class="support-shell"
        data-support-widget
        data-workspace-token="{{ $workspace->public_token }}"
        data-create-url="{{ route('support.conversations.store', $workspace->public_token) }}"
        data-messages-url-template="{{ route('support.conversations.messages', '__CONVERSATION__') }}"
        data-send-url-template="{{ route('support.conversations.send', '__CONVERSATION__') }}"
        data-end-url-template="{{ route('support.conversations.end', '__CONVERSATION__') }}"
        data-rating-url-template="{{ route('support.conversations.rating', '__CONVERSATION__') }}"
        data-broadcast-auth-url="{{ route('support.broadcasting.auth') }}"
        data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
        data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
    >
        <header class="support-header">
            <div class="support-brand-mark" aria-hidden="true">E</div>
            <div>
                <p class="support-eyebrow">Customer care</p>
                <h1>{{ $workspace->name }}</h1>
                <p class="support-intro">Ask us anything. Our AI assistant can help, and a human agent can join when needed.</p>
            </div>
        </header>

        <section class="support-card">
            <div class="support-status" data-chat-status role="status">Start a conversation</div>
            <div class="support-error" data-chat-error role="alert" hidden></div>

            <form class="support-start-form" data-start-form>
                <div class="support-field">
                    <label for="customer-name">Your name</label>
                    <input id="customer-name" name="customer_name" maxlength="120" autocomplete="name" required>
                </div>
                <div class="support-field">
                    <label for="customer-email">Email <span>(optional)</span></label>
                    <input id="customer-email" name="customer_email" type="email" maxlength="255" autocomplete="email">
                </div>
                <div class="support-field">
                    <label for="first-message">How can we help?</label>
                    <textarea id="first-message" name="message" rows="3" maxlength="5000" required></textarea>
                </div>
                <button class="support-button" type="submit">Start chat</button>
            </form>

            <div class="support-thread" data-thread hidden>
                <div class="support-messages" data-messages aria-live="polite"></div>
                <form class="support-reply-form" data-reply-form>
                    <label class="sr-only" for="reply-message">Your message</label>
                    <textarea id="reply-message" name="message" rows="2" maxlength="5000" placeholder="Write a message..." required></textarea>
                    <button class="support-button" type="submit" aria-label="Send message">Send</button>
                </form>
                <div class="support-rating" data-rating hidden>
                    <p>Your chat with our agent is complete. How was the support?</p>
                    <div class="support-stars" role="group" aria-label="Rate your support agent">
                        @for ($star = 1; $star <= 5; $star++)
                            <button type="button" data-rating-value="{{ $star }}" aria-label="{{ $star }} star{{ $star === 1 ? '' : 's' }}">★</button>
                        @endfor
                    </div>
                </div>
                <button class="support-new-chat" data-new-chat hidden type="button">Start a new chat</button>
            </div>
        </section>
        <p class="support-powered">Powered by Escalora Support</p>
    </main>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js" defer></script>
    <script src="{{ asset('js/support.js') }}" defer></script>
</body>
</html>
