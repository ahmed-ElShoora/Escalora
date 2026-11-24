@extends('layouts.dashboard')

@section('title', 'Agents')
@section('heading', 'All agents')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}" class="is-active">Agents</a>
@endsection

@section('content')
    <div class="page-intro">
        <p>Company-wide agent list across every workspace you own.</p>
    </div>

    <section class="panel-card">
        @if ($agents->isEmpty())
            <div class="empty-state">
                <strong>No agents yet</strong>
                <p>Open a workspace and send an invitation to get started.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Workspace</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agents as $agent)
                            <tr>
                                <td>{{ $agent->name }}</td>
                                <td>{{ $agent->email }}</td>
                                <td>
                                    @if ($agent->workspace)
                                        <a href="{{ route('admin.workspaces.show', $agent->workspace) }}">{{ $agent->workspace->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="status-pill {{ $agent->isActive() ? 'active' : 'pending' }}">
                                        {{ $agent->isActive() ? 'Active' : 'Pending Invitation' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1rem;">{{ $agents->links() }}</div>
        @endif
    </section>
@endsection
