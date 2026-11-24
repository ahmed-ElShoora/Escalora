@extends('layouts.app')

@section('title', 'Verify Email')
@section('meta_description', 'Verify your Escalora admin email to access the dashboard.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <section class="auth-shell">
        <div class="container" style="max-width: 640px;">
            <div class="auth-panel reveal">
                <h2>Verify your email</h2>
                <p class="lead">
                    We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                    Open it to unlock your admin dashboard.
                </p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('verification.send') }}" class="form-stack" data-auth-form>
                    @csrf
                    <button class="btn btn-primary btn-block" type="submit">Resend verification email</button>
                </form>

                <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
                    @csrf
                    <button class="btn btn-ghost btn-block" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endpush
