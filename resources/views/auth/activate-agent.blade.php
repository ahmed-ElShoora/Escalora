@extends('layouts.app')

@section('title', 'Activate Agent Account')
@section('meta_description', 'Activate your Escalora agent invitation and set your password.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <section class="auth-shell">
        <div class="container auth-grid">
            <aside class="auth-aside reveal">
                <div>
                    <p class="eyebrow">Invitation</p>
                    <h1>Activate your agent account</h1>
                    <p>
                        Hi {{ $agent->name }}. Set a secure password to join
                        <strong>{{ $agent->company?->name }}</strong>
                        in the
                        <strong>{{ $agent->workspace?->name }}</strong>
                        workspace.
                    </p>
                </div>
            </aside>

            <div class="auth-panel reveal">
                <h2>Set your password</h2>
                <p class="lead">After activation you can sign in anytime from the agent login page.</p>

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Unable to activate:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-stack" method="POST" action="{{ route('agent.invitation.store', $token) }}" data-auth-form>
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" value="{{ $agent->email }}" disabled>
                    </div>

                    <div class="form-row two">
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">Activate account</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
