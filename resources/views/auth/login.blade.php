@extends('layouts.app')

@section('title', 'Admin Login')
@section('meta_description', 'Sign in to Escalora as a company admin to manage workspaces and agents.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <section class="auth-shell">
        <div class="container auth-grid">
            <aside class="auth-aside reveal">
                <div>
                    <p class="eyebrow">Admin access</p>
                    <h1>Sign in to your company command center</h1>
                    <p>
                        Manage workspaces, invite agents, and keep every support queue isolated to your company.
                    </p>
                </div>

                <ul class="auth-points">
                    <li>Email-verified admin accounts only</li>
                    <li>Workspace-level agent management</li>
                    <li>Strict multi-company data isolation</li>
                </ul>
            </aside>

            <div class="auth-panel reveal">
                <h2>Admin login</h2>
                <p class="lead">Use your admin email and password.</p>

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

                <form class="form-stack" method="POST" action="{{ route('login.store') }}" data-auth-form>
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
                    New company admin?
                    <a href="{{ route('register') }}">Create an account</a>
                </p>
                <p class="auth-switch">
                    Support agent?
                    <a href="{{ route('agent.login') }}">Go to agent login</a>
                </p>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
