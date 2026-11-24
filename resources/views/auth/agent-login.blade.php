@extends('layouts.app')

@section('title', 'Agent Login')
@section('meta_description', 'Sign in to Escalora as an invited support agent.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <section class="auth-shell">
        <div class="container auth-grid">
            <aside class="auth-aside reveal">
                <div>
                    <p class="eyebrow">Agent access</p>
                    <h1>Sign in to your assigned workspace</h1>
                    <p>
                        Agents join Escalora by invitation only. Use the email and password you set when activating your account.
                    </p>
                </div>

                <ul class="auth-points">
                    <li>No public agent registration</li>
                    <li>Access limited to your workspace</li>
                    <li>Activate via the invite email first</li>
                </ul>
            </aside>

            <div class="auth-panel reveal">
                <h2>Agent login</h2>
                <p class="lead">Enter the credentials from your invitation activation.</p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Unable to sign in:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-stack" method="POST" action="{{ route('agent.login.store') }}" data-auth-form>
                    @csrf

                    <div class="field">
                        <label for="email">Work email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                    </div>

                    <label class="remember-row">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>

                    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
                </form>

                <p class="auth-switch">
                    Company admin?
                    <a href="{{ route('login') }}">Go to admin login</a>
                </p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
