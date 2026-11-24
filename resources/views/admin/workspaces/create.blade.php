@extends('layouts.dashboard')

@section('title', 'Create Workspace')
@section('heading', 'Create workspace')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}" class="is-active">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}">Agents</a>
@endsection

@section('content')
    <section class="panel-card" style="max-width:640px;">
        <form method="POST" action="{{ route('admin.workspaces.store') }}" class="form-stack">
            @csrf

            <div class="field">
                <label for="name">Workspace name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{ old('description') }}</textarea>
            </div>

            <div class="inline-actions">
                <button class="btn btn-primary" type="submit">Create workspace</button>
                <a href="{{ route('admin.workspaces.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
@endsection
