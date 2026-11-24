@php
    $current = request()->route()?->getName();
@endphp

<header class="site-header" data-site-header>
    <div class="container nav-inner">
        <a href="{{ route('home') }}" class="brand" aria-label="Escalora home">
            <span class="brand-mark">E</span>
            <span>Escalora</span>
        </a>

        <nav class="nav-links" aria-label="Primary">
            <a href="{{ route('home') }}" class="{{ $current === 'home' ? 'is-active' : '' }}">Home</a>
            <a href="{{ route('services') }}" class="{{ $current === 'services' ? 'is-active' : '' }}">Services</a>
            <a href="{{ route('about') }}" class="{{ $current === 'about' ? 'is-active' : '' }}">About</a>
            <a href="{{ route('contact') }}" class="{{ $current === 'contact' ? 'is-active' : '' }}">Contact</a>
        </nav>

        <div class="nav-actions">
            <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
        </div>

        <button
            class="nav-toggle"
            type="button"
            aria-label="Toggle menu"
            aria-expanded="false"
            data-nav-toggle
        >
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

</header>
