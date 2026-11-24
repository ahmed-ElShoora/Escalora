@extends('layouts.dashboard')

@section('title', $workspace->name)
@section('heading', $workspace->name)

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}" class="is-active">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}">Agents</a>
@endsection

@section('actions')
    <a href="{{ route('admin.workspaces.edit', $workspace) }}" class="btn btn-ghost btn-sm">Edit</a>
@endsection

@section('content')
    <div class="page-intro">
        <p>{{ $workspace->description ?: 'No description yet.' }} · Created {{ $workspace->created_at?->format('M j, Y') }}</p>
    </div>

    @php
        $supportUrl = route('support.show', $workspace->public_token);
        $embedCode = '<iframe src="' . route('support.embed', $workspace->public_token) . '" title="Support chat" width="100%" height="600" style="border:0;border-radius:16px" loading="lazy"></iframe>';
        $onlineAgents = $workspace->agents->filter(fn ($agent) => $agent->isOnline())->count();
    @endphp

    <div class="stats-grid">
        <article class="stat-card">
            <span>Agents online now</span>
            <strong data-online-count>{{ $onlineAgents }} / {{ $workspace->agents->count() }}</strong>
        </article>
        <article class="stat-card">
            <span>Chats · {{ $periods[$period]['label'] }}</span>
            <strong>{{ $analytics['chats'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Waiting now</span>
            <strong>{{ $analytics['waiting'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Average rating · {{ $periods[$period]['label'] }}</span>
            <strong>{{ $analytics['rating'] ? number_format((float) $analytics['rating'], 1) . ' / 5' : '—' }}</strong>
        </article>
    </div>

    <div class="split-grid workspace-tools">
        <section class="panel-card">
            <div class="panel-head">
                <h2>Share your support chat</h2>
            </div>
            <div class="field copy-field">
                <label for="support-link">Customer support page</label>
                <div class="copy-row">
                    <input id="support-link" type="text" readonly value="{{ $supportUrl }}">
                    <button class="btn btn-primary btn-sm" type="button" data-copy-target="support-link" data-copy-label="Copy link">Copy link</button>
                </div>
            </div>
            <div class="field copy-field">
                <label for="support-embed">Embed on your website</label>
                <div class="copy-row">
                    <textarea id="support-embed" rows="3" readonly>{{ $embedCode }}</textarea>
                    <button class="btn btn-primary btn-sm" type="button" data-copy-target="support-embed" data-copy-label="Copy HTML">Copy HTML</button>
                </div>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-head">
                <h2>AI and human handoff</h2>
            </div>
            <form method="POST" action="{{ route('admin.workspaces.support-settings.update', $workspace) }}" class="form-stack">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="escalation_keywords">Escalation phrases (one per line)</label>
                    <textarea id="escalation_keywords" name="escalation_keywords" rows="5" placeholder="cancel my subscription&#10;I want to speak to a person">{{ old('escalation_keywords', implode("\n", $workspace->escalation_keywords ?? [])) }}</textarea>
                    <small>When a customer message contains one of these phrases, it goes straight to the human queue.</small>
                </div>
                <div class="field">
                    <label for="max_active_chats">Maximum active chats per agent</label>
                    <input id="max_active_chats" name="max_active_chats" type="number" min="1" max="30" value="{{ old('max_active_chats', $workspace->max_active_chats ?? 5) }}" required>
                </div>
                <button class="btn btn-primary" type="submit">Save support settings</button>
            </form>
        </section>
    </div>

    <section class="panel-card workspace-analytics" data-workspace-presence="{{ route('admin.workspaces.presence', $workspace) }}" data-total-agents="{{ $workspace->agents->count() }}">
        <div class="panel-head">
            <div>
                <h2>Agent activity and ratings</h2>
                <p class="panel-subtitle">Chat totals and average customer ratings for each period.</p>
            </div>
            <form method="GET" class="period-filter">
                <label for="period">Period</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    @foreach ($periods as $value => $option)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Presence</th>
                        <th>Last activity</th>
                        <th>Active chats</th>
                        <th>Chats · period</th>
                        <th>Average rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workspace->agents as $agent)
                        <tr data-agent-row="{{ $agent->id }}">
                            <td>
                                <strong>{{ $agent->name }}</strong>
                                <div class="table-secondary">{{ $agent->email }}</div>
                            </td>
                            <td>
                                <span class="presence-pill {{ $agent->isOnline() ? 'online' : 'offline' }}" data-agent-presence>
                                    {{ $agent->isOnline() ? 'Online' : ($agent->is_available ? 'Away' : 'Offline') }}
                                </span>
                            </td>
                            <td data-agent-last-seen>{{ $agent->last_seen_at?->diffForHumans() ?? 'No activity yet' }}</td>
                            <td>{{ $agent->active_chats_count }}</td>
                            <td>{{ $agent->period_chats_count }}</td>
                            <td>{{ $agent->period_rating ? number_format((float) $agent->period_rating, 1) . ' / 5' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">No agents have been added to this workspace yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="split-grid">
        <section class="panel-card">
            <div class="panel-head">
                <h2>Agents in this workspace</h2>
            </div>

            @if ($workspace->agents->isEmpty())
                <div class="empty-state">
                    <strong>No agents yet</strong>
                    <p>Invite an agent by name and email. They will set a password from the invitation link.</p>
                </div>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last activity</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($workspace->agents as $agent)
                                <tr>
                                    <td>{{ $agent->name }}</td>
                                    <td>{{ $agent->email }}</td>
                                    <td>
                                        <span class="status-pill {{ $agent->isActive() ? 'active' : 'pending' }}">
                                            {{ $agent->isActive() ? 'Active' : 'Pending Invitation' }}
                                        </span>
                                    </td>
                                    <td>{{ $agent->last_seen_at?->diffForHumans() ?? '—' }}</td>
                                    <td>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.workspaces.agents.destroy', [$workspace, $agent]) }}"
                                            class="confirm-form"
                                            onsubmit="return confirm('Remove this agent? Their email will become available for other companies.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel-card">
            <div class="panel-head">
                <h2>Invite agent</h2>
            </div>

            <form method="POST" action="{{ route('admin.workspaces.agents.store', $workspace) }}" class="form-stack">
                @csrf

                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <button class="btn btn-primary" type="submit">Send invitation</button>
            </form>

            <hr class="panel-divider">

            <form
                method="POST"
                action="{{ route('admin.workspaces.destroy', $workspace) }}"
                onsubmit="return confirm('Delete this workspace and all of its agents? This cannot be undone.')"
            >
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete workspace</button>
            </form>
        </section>
    </div>
@endsection
