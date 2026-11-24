@extends('layouts.app')

@section('title', 'About')
@section('meta_description', 'Learn why Escalora exists: to help companies detect severity and sentiment in customer messages and escalate with confidence.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">About Escalora</p>
            <h1>Support should escalate risk, not chaos</h1>
            <p>
                Escalora was built for companies that outgrew shared inboxes and need a sharper way to
                decide what deserves immediate attention.
            </p>
        </div>
    </section>

    <section class="content-section">
        <div class="container about-split">
            <div class="about-copy reveal">
                <h2>Our mission</h2>
                <p>
                    Customer support teams are flooded with volume. The danger is not missing a message—
                    it is missing the message that can damage trust, revenue, or uptime.
                </p>
                <p>
                    We built Escalora so admins and agents can see severity and positivity clearly,
                    escalate with shared rules, and resolve critical conversations before they become
                    public incidents.
                </p>
                <p>
                    The product is designed for modern support operations: English-first workplaces,
                    distributed teams, and companies that need accountable escalation history.
                </p>
            </div>

            <div class="values reveal">
                <article class="value-item">
                    <h3>Clarity over noise</h3>
                    <p>Every score and route should make the next action obvious for the people on shift.</p>
                </article>
                <article class="value-item">
                    <h3>Speed with judgment</h3>
                    <p>Escalate fast when risk is real. Keep routine conversations out of emergency queues.</p>
                </article>
                <article class="value-item">
                    <h3>Shared ownership</h3>
                    <p>Admins set policy. Agents execute. Everyone sees the same severity and sentiment context.</p>
                </article>
                <article class="value-item">
                    <h3>Operational trust</h3>
                    <p>Escalations leave an audit trail so teams can learn, coach, and improve continuously.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head centered reveal">
                <p class="eyebrow">What we believe</p>
                <h2 class="section-title">Great support is a risk management system</h2>
                <p class="section-copy">
                    Escalora sits between your customers and your internal responders—scoring signals,
                    protecting SLAs, and keeping high-stakes conversations visible.
                </p>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="container">
            <div class="panel reveal">
                <div>
                    <h2>Join the teams building calmer, sharper support ops</h2>
                    <p>Create an admin or agent account and bring Escalora into your escalation playbook.</p>
                </div>
                <div class="cta-actions">
                    <a href="{{ route('register') }}" class="btn btn-primary">Get started</a>
                    <a href="{{ route('services') }}" class="btn btn-ghost">View services</a>
                </div>
            </div>
        </div>
    </section>
@endsection
