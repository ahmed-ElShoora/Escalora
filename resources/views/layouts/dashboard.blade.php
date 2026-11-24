<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'Escalora') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
</head>
<body class="dash-body">
    @php
        $user = auth()->user();
        $isAdmin = $user?->isAdmin();
    @endphp

    <div class="dash-shell">
        <aside class="dash-sidebar">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('agent.dashboard') }}" class="dash-brand">
                <span class="brand-mark">E</span>
                <span>Escalora</span>
            </a>

            <nav class="dash-nav" aria-label="Dashboard">
                @yield('sidebar')
            </nav>

            <div class="dash-sidebar-foot">
                <strong>{{ $user?->name }}</strong>
                <span>{{ $user?->company?->name }} · {{ $isAdmin ? 'Admin' : 'Agent' }}</span>
            </div>
        </aside>

        <div class="dash-main">
            <header class="dash-topbar">
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <button class="dash-menu-btn" type="button" data-dash-menu aria-label="Open menu">☰</button>
                    <h1>@yield('heading', 'Dashboard')</h1>
                </div>
                <div class="dash-top-actions">
                    @yield('actions')
                    <form method="POST" action="{{ $isAdmin ? route('logout') : route('agent.logout') }}">
                        @csrf
                        <button class="btn btn-ghost btn-sm" type="submit">Logout</button>
                    </form>
                </div>
            </header>

            <div class="dash-content">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Something needs attention:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <div class="dash-mobile-nav" data-mobile-nav>
        <div class="dash-mobile-panel">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('agent.dashboard') }}" class="dash-brand">
                <span class="brand-mark">E</span>
                <span>Escalora</span>
            </a>
            <nav class="dash-nav">
                @yield('sidebar')
            </nav>
            <button class="btn btn-ghost" type="button" data-close-menu>Close</button>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}"></script>
    @stack('scripts')
</body>
</html>
