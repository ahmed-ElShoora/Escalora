@extends('layouts.dashboard')

@section('title', 'Agent Dashboard')
@section('heading', 'Live support')

@section('sidebar')
    <a href="{{ route('agent.dashboard') }}" class="is-active">Live support</a>
@endsection

@section('actions')
    <label class="agent-presence-toggle">
        <input type="checkbox" data-agent-presence @checked($agent->is_available)>
        <span data-presence-label>{{ $agent->isOnline() ? 'Online' : 'Go online' }}</span>
    </label>
@endsection

@section('content')
    <div
        class="agent-chat-layout"
        data-agent-chat
        data-inbox-url="{{ route('agent.conversations.index') }}"
        data-presence-url="{{ route('agent.presence.update') }}"
        data-messages-url-template="{{ route('agent.conversations.messages', '__CONVERSATION__') }}"
        data-send-url-template="{{ route('agent.conversations.send', '__CONVERSATION__') }}"
        data-close-url-template="{{ route('agent.conversations.close', '__CONVERSATION__') }}"
        data-broadcast-auth-url="{{ route('support.broadcasting.auth') }}"
        data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
        data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
        data-workspace-id="{{ $workspace?->id }}"
        data-agent-id="{{ $agent->id }}"
    >
        <section class="panel-card">
            <div class="panel-head">
                <div>
                    <h2>My conversations</h2>
                    <p class="panel-subtitle">Chats assigned to you update automatically.</p>
                </div>
            </div>
            <p class="queue-note" data-queue-note>{{ $waitingCount }} waiting in your workspace queue</p>
            <div class="agent-conversation-list" data-conversation-list>
                @forelse ($conversations as $conversation)
                    <button class="agent-conversation-item" type="button" data-conversation-id="{{ $conversation->id }}" data-customer-name="{{ $conversation->customer_name }}">
                        <strong>{{ $conversation->customer_name }}</strong>
                        <span>Started {{ $conversation->created_at?->diffForHumans() }}</span>
                    </button>
                @empty
                    <p class="empty-state" data-empty-conversations>No active chats. Go online to receive new conversations.</p>
                @endforelse
            </div>
        </section>

        <section class="panel-card" data-agent-chat-panel>
            <div class="panel-head">
                <div>
                    <h2 data-selected-customer>Select a conversation</h2>
                    <p class="panel-subtitle" data-agent-count>Checking workspace presence…</p>
                </div>
                <button class="btn btn-danger btn-sm" type="button" data-close-conversation hidden>End chat</button>
            </div>
            <div class="agent-chat-messages" data-agent-messages aria-live="polite">
                <div class="empty-state" data-agent-chat-empty>Select a customer to read and reply to their messages.</div>
            </div>
            <form class="agent-chat-composer" data-agent-reply-form hidden>
                <label class="sr-only" for="agent-reply">Reply to customer</label>
                <textarea id="agent-reply" name="message" rows="2" maxlength="5000" placeholder="Write a reply..." required></textarea>
                <button class="btn btn-primary" type="submit">Send</button>
            </form>
            <div class="alert alert-error" data-agent-error hidden></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="{{ asset('js/agent-chat.js') }}"></script>
@endpush
