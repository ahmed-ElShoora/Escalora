@extends('layouts.app')

@section('title', 'Admin Register')
@section('meta_description', 'Create an Escalora admin account for your company and verify your email to access the dashboard.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <section class="auth-shell">
        <div class="container auth-grid">
            <aside class="auth-aside reveal">
                <div>
                    <p class="eyebrow">Admin registration</p>
                    <h1>Create your company admin account</h1>
                    <p>
                        Register with your company details, verify your email, then create workspaces and invite agents.
                    </p>
                </div>

                <ul class="auth-points">
                    <li>One admin company workspace hub</li>
                    <li>Invite-only agent onboarding</li>
                    <li>Email verification required before dashboard access</li>
                </ul>
            </aside>

            <div class="auth-panel reveal">
                <h2>Create admin account</h2>
                <p class="lead">Agents cannot self-register. Invite them after setup.</p>

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Please review your details:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-stack" method="POST" action="{{ route('register.store') }}" data-auth-form>
                    @csrf

                    <div class="field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">
                    </div>

                    <div class="field">
                        <label for="company">Company name</label>
                        <input id="company" name="company" type="text" value="{{ old('company') }}" required autocomplete="organization">
                    </div>

                    <div class="field">
                        <label for="email">Work email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
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

                    <button class="btn btn-primary btn-block" type="submit">Create admin account</button>
                </form>

                <p class="auth-switch">
                    Already registered?
                    <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
