@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('heading', 'Overview')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}" class="is-active">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}">Agents</a>
@endsection

@section('actions')
    <a href="{{ route('admin.workspaces.create') }}" class="btn btn-primary btn-sm">New workspace</a>
@endsection

@section('content')
    <div class="page-intro">
        <p>Welcome back, {{ $admin->name }}. Manage {{ $admin->company?->name }} workspaces and agents from one place.</p>
    </div>

    <div class="stats-grid">
        <article class="stat-card">
            <span>Workspaces</span>
            <strong>{{ $workspaceCount }}</strong>
        </article>
        <article class="stat-card">
            <span>Total agents</span>
            <strong>{{ $agentCount }}</strong>
        </article>
        <article class="stat-card">
            <span>Active agents</span>
            <strong>{{ $activeAgents }}</strong>
        </article>
        <article class="stat-card">
            <span>Pending invites</span>
            <strong>{{ $pendingInvites }}</strong>
        </article>
    </div>

    <section class="panel-card">
        <div class="panel-head">
            <h2>Recent workspaces</h2>
            <a href="{{ route('admin.workspaces.index') }}" class="btn btn-ghost btn-sm">View all</a>
        </div>

        @if ($recentWorkspaces->isEmpty())
            <div class="empty-state">
                <strong>No workspaces yet</strong>
                <p>Create your first company workspace to start inviting agents.</p>
                <a href="{{ route('admin.workspaces.create') }}" class="btn btn-primary" style="margin-top:1rem;">Create workspace</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Agents</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentWorkspaces as $workspace)
                            <tr>
                                <td>{{ $workspace->name }}</td>
                                <td>{{ $workspace->agents_count }}</td>
                                <td>{{ $workspace->created_at?->format('M j, Y') }}</td>
                                <td><a href="{{ route('admin.workspaces.show', $workspace) }}" class="btn btn-ghost btn-sm">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
