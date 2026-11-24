@extends('layouts.dashboard')

@section('title', 'Edit Workspace')
@section('heading', 'Edit workspace')

@section('sidebar')
    <a href="{{ route('admin.dashboard') }}">Overview</a>
    <a href="{{ route('admin.workspaces.index') }}" class="is-active">Workspaces</a>
    <a href="{{ route('admin.agents.index') }}">Agents</a>
@endsection

@section('content')
    <section class="panel-card" style="max-width:640px;">
        <form method="POST" action="{{ route('admin.workspaces.update', $workspace) }}" class="form-stack">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Workspace name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $workspace->name) }}" required>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{ old('description', $workspace->description) }}</textarea>
            </div>

            <div class="inline-actions">
                <button class="btn btn-primary" type="submit">Save changes</button>
                <a href="{{ route('admin.workspaces.show', $workspace) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
@endsection
