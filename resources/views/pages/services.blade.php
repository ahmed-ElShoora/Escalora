@extends('layouts.app')

@section('title', 'Services')
@section('meta_description', 'Explore Escalora services: severity scoring, sentiment analysis, escalation routing, SLA monitoring, and role-based support workspaces.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Services</p>
            <h1>Everything your escalation workflow needs</h1>
            <p>
                From message intelligence to admin oversight, Escalora gives support teams a clear
                operating system for high-risk customer conversations.
            </p>
        </div>
    </section>

    <section class="content-section">
        <div class="container services-grid">
            <article class="service-block reveal">
                <div class="icon">01</div>
                <h2>Severity Scoring</h2>
                <p>Automatically classify messages by operational risk so critical issues rise first.</p>
                <ul>
                    <li>Critical, high, medium, and low tiers</li>
                    <li>Keyword and impact pattern detection</li>
                    <li>VIP and enterprise account weighting</li>
                </ul>
            </article>

            <article class="service-block reveal">
                <div class="icon">02</div>
                <h2>Sentiment Analysis</h2>
                <p>Track positivity and frustration shifts across the conversation timeline.</p>
                <ul>
                    <li>Positive, neutral, and negative tone scoring</li>
                    <li>Escalation triggers on sudden tone drops</li>
                    <li>Agent coaching insights after resolution</li>
                </ul>
            </article>

            <article class="service-block reveal">
                <div class="icon">03</div>
                <h2>Smart Routing</h2>
                <p>Send the right case to the right admin or specialist without manual sorting.</p>
                <ul>
                    <li>Role-based queues for admins and agents</li>
                    <li>Topic and product routing maps</li>
                    <li>Overflow rules during peak volume</li>
                </ul>
            </article>

            <article class="service-block reveal">
                <div class="icon">04</div>
                <h2>SLA Command</h2>
                <p>Protect response windows with live timers and breach alerts on escalated tickets.</p>
                <ul>
                    <li>Priority-based countdown clocks</li>
                    <li>Admin alerts before SLA risk</li>
                    <li>Post-incident response reporting</li>
                </ul>
            </article>

            <article class="service-block reveal">
                <div class="icon">05</div>
                <h2>Team Workspaces</h2>
                <p>Give managers control and agents focus with clean permission boundaries.</p>
                <ul>
                    <li>Admin policy and oversight tools</li>
                    <li>Agent-ready escalation inbox</li>
                    <li>Shared notes and handoff history</li>
                </ul>
            </article>

            <article class="service-block reveal">
                <div class="icon">06</div>
                <h2>Operations Reporting</h2>
                <p>Understand what gets escalated, how fast you respond, and where quality improves.</p>
                <ul>
                    <li>Severity distribution dashboards</li>
                    <li>Resolution and reopen trends</li>
                    <li>Exportable audit trails</li>
                </ul>
            </article>
        </div>
    </section>

    <section class="cta-band">
        <div class="container">
            <div class="panel reveal">
                <div>
                    <h2>Want a tailored escalation setup for your company?</h2>
                    <p>Tell us about your channels, team size, and SLA targets. We’ll map Escalora to your workflow.</p>
                </div>
                <div class="cta-actions">
                    <a href="{{ route('contact') }}" class="btn btn-primary">Contact us</a>
                    <a href="{{ route('register') }}" class="btn btn-ghost">Create account</a>
                </div>
            </div>
        </div>
    </section>
@endsection
