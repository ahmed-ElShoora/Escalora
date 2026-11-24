@extends('layouts.dashboard')

@section('title', 'Workspaces')
@section('heading', 'Workspaces')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}" class="is-active">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}">Agents</a>
@endsection

@section('actions')
    <a href="{{ route('admin.workspaces.create') }}" class="btn btn-primary btn-sm">New workspace</a>
@endsection

@section('content')
    <div class="page-intro">
        <p>Each workspace keeps its own agents and data isolated inside your company.</p>
    </div>

    <section class="panel-card">
        @if ($workspaces->isEmpty())
            <div class="empty-state">
                <strong>No workspaces found</strong>
                <p>Create a workspace for a team, brand, or support channel.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Workspace</th>
                            <th>Agents</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workspaces as $workspace)
                            <tr>
                                <td>
                                    <strong>{{ $workspace->name }}</strong>
                                    @if ($workspace->description)
                                        <div style="color:var(--muted);font-size:0.85rem;">{{ $workspace->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $workspace->agents_count }}</td>
                                <td>{{ $workspace->created_at?->format('M j, Y') }}</td>
                                <td class="inline-actions">
                                    <a href="{{ route('admin.workspaces.show', $workspace) }}" class="btn btn-ghost btn-sm">Manage</a>
                                    <a href="{{ route('admin.workspaces.edit', $workspace) }}" class="btn btn-ghost btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1rem;">{{ $workspaces->links() }}</div>
        @endif
    </section>
@endsection
