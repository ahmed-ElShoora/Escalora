@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'Escalora is a modern customer service escalation system that detects message severity and sentiment, then routes urgent tickets to admins and agents.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
    {{-- Hero --}}
    <section class="home-hero">
        <div class="container hero-layout">
            <div class="hero-copy">
                <p class="hero-brand">Escalora</p>
                <h1>Escalate the right messages before customers escalate the conflict.</h1>
                <p>
                    A modern support operations platform that scores severity and positivity in every
                    message, then routes critical conversations to admins and agents in seconds.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('register') }}" class="btn btn-primary">Start free trial</a>
                    <a href="{{ route('services') }}" class="btn btn-ghost">Explore services</a>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="hero-visual-glow"></div>
                <div class="severity-stage">
                    <div class="severity-top">
                        <span class="live-dot">Live triage</span>
                        <span>Queue · 14 open</span>
                    </div>

                    <article class="ticket">
                        <div class="ticket-meta">
                            <span class="badge badge-critical">Critical</span>
                            <span class="badge badge-negative">Negative</span>
                        </div>
                        <p>“Our production API is down and orders are failing. Need a senior now.”</p>
                    </article>

                    <article class="ticket">
                        <div class="ticket-meta">
                            <span class="badge badge-high">High</span>
                            <span class="badge badge-negative">Frustrated</span>
                        </div>
                        <p>“Second follow-up. Billing still incorrect after three days.”</p>
                    </article>

                    <div class="meters">
                        <div class="meter">
                            <span>Severity</span>
                            <div class="meter-bar severity"><i style="--value: 92%"></i></div>
                        </div>
                        <div class="meter">
                            <span>Sentiment</span>
                            <div class="meter-bar sentiment"><i style="--value: 28%"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container logo-strip">
            <p>Trusted by support-led teams</p>
            <div class="logo-row">
                <span>AhmedSami</span>
                <span>MostafaOmar</span>
                <span>AhmedEssam</span>
                <span>EbrahimMashaly</span>
                <span>yossefElsaeide</span>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section" id="how-it-works">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">Workflow</p>
                <h2 class="section-title">From inbox noise to clear escalation paths</h2>
                <p class="section-copy">
                    Escalora watches every customer message, scores urgency and tone, then pushes the
                    right cases into admin and agent queues automatically.
                </p>
            </div>

            <div class="pipeline">
                <article class="pipeline-step reveal">
                    <span class="step-num">01</span>
                    <h3>Ingest</h3>
                    <p>Pull tickets, chats, and emails into one operational stream for your support team.</p>
                </article>
                <article class="pipeline-step reveal">
                    <span class="step-num">02</span>
                    <h3>Score</h3>
                    <p>Detect severity and positivity signals so critical messages never look routine.</p>
                </article>
                <article class="pipeline-step reveal">
                    <span class="step-num">03</span>
                    <h3>Route</h3>
                    <p>Escalate to admins or specialized agents based on risk, topic, and SLA rules.</p>
                </article>
                <article class="pipeline-step reveal">
                    <span class="step-num">04</span>
                    <h3>Resolve</h3>
                    <p>Track ownership, response time, and outcome with clear escalation visibility.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Intelligence --}}
    <section class="section alt" id="intelligence">
        <div class="container intel-grid">
            <div class="reveal">
                <p class="eyebrow">Intelligence layer</p>
                <h2 class="section-title">Severity and sentiment, scored in real time</h2>
                <p class="section-copy">
                    Stop guessing which message needs a manager. Escalora highlights urgency, tone shifts,
                    and repeated complaints so your team responds with context—not panic.
                </p>
            </div>

            <div class="intel-board reveal">
                <div class="score-row">
                    <div class="score-item critical">
                        <strong>Outage language detected</strong>
                        <span>Keywords + customer impact</span>
                        <em>98</em>
                    </div>
                    <div class="score-item high">
                        <strong>Billing frustration rising</strong>
                        <span>Sentiment dropped in 2 replies</span>
                        <em>81</em>
                    </div>
                    <div class="score-item ok">
                        <strong>Feature praise message</strong>
                        <span>Positive tone · no escalation</span>
                        <em>12</em>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="section" id="features">
        <div class="container">
            <div class="section-head centered reveal">
                <p class="eyebrow">Capabilities</p>
                <h2 class="section-title">Built for serious support operations</h2>
                <p class="section-copy">
                    Everything your admins and agents need to prioritize risk, protect SLAs, and keep
                    customers informed.
                </p>
            </div>

            <div class="feature-list">
                <article class="feature-item reveal">
                    <div class="feature-icon">⚡</div>
                    <h3>Smart escalation rules</h3>
                    <p>Define thresholds for severity, sentiment, VIP accounts, and SLA breach windows.</p>
                </article>
                <article class="feature-item reveal">
                    <div class="feature-icon">◎</div>
                    <h3>Role-aware workspaces</h3>
                    <p>Admins oversee queues and policy. Agents focus on assigned escalations and replies.</p>
                </article>
                <article class="feature-item reveal">
                    <div class="feature-icon">▣</div>
                    <h3>Message intelligence</h3>
                    <p>Surface tone, urgency, and risk signals beside every conversation timeline.</p>
                </article>
                <article class="feature-item reveal">
                    <div class="feature-icon">◷</div>
                    <h3>SLA timers</h3>
                    <p>Watch countdown clocks on critical tickets before response windows slip.</p>
                </article>
                <article class="feature-item reveal">
                    <div class="feature-icon">☰</div>
                    <h3>Audit-ready history</h3>
                    <p>Know who escalated what, when, and why—with a clean operational trail.</p>
                </article>
                <article class="feature-item reveal">
                    <div class="feature-icon">↗</div>
                    <h3>Team performance</h3>
                    <p>Track resolution speed, reopen rates, and escalation quality across squads.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Roles --}}
    <section class="section alt" id="teams">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">Built for your team</p>
                <h2 class="section-title">One system for admins and frontline agents</h2>
                <p class="section-copy">
                    Clear permissions. Shared context. Faster decisions when a conversation turns critical.
                </p>
            </div>

            <div class="roles-grid">
                <article class="role-panel admin reveal">
                    <h3>For Admins</h3>
                    <p>Own escalation policy, staffing, and high-risk conversations across the company.</p>
                    <ul>
                        <li>Configure severity thresholds and routing maps</li>
                        <li>Monitor enterprise accounts and VIP queues</li>
                        <li>Review escalation quality and agent load</li>
                        <li>Approve overrides and escalation closures</li>
                    </ul>
                </article>

                <article class="role-panel reveal">
                    <h3>For Agents</h3>
                    <p>Work a focused queue with the signals you need to reply confidently and quickly.</p>
                    <ul>
                        <li>See severity and sentiment beside each ticket</li>
                        <li>Escalate with one click when risk spikes</li>
                        <li>Collaborate with admins on critical cases</li>
                        <li>Stay inside SLA with live countdown cues</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- Metrics --}}
    <section class="section" id="impact">
        <div class="container">
            <div class="section-head centered reveal">
                <p class="eyebrow">Impact</p>
                <h2 class="section-title">Support that moves at the speed of risk</h2>
            </div>

            <div class="metrics reveal">
                <div class="metric">
                    <strong data-count="47" data-suffix="%">0%</strong>
                    <span>Faster critical response</span>
                </div>
                <div class="metric">
                    <strong data-count="3" data-suffix="x">0x</strong>
                    <span>Clearer triage accuracy</span>
                </div>
                <div class="metric">
                    <strong data-count="92" data-suffix="%">0%</strong>
                    <span>Escalations within SLA</span>
                </div>
                <div class="metric">
                    <strong data-count="24" data-suffix="/7">0/7</strong>
                    <span>Always-on signal scoring</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="section alt" id="stories">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">Customer stories</p>
                <h2 class="section-title">Teams that refuse to miss the warning signs</h2>
            </div>

            <div class="quote-grid">
                <blockquote class="quote reveal">
                    <p>“Escalora stopped our ‘everything is urgent’ culture. Critical tickets now look critical.”</p>
                    <footer>
                        <strong>Ahmed Sami</strong>
                        Head of Support, Himit
                    </footer>
                </blockquote>
                <blockquote class="quote reveal">
                    <p>“Admins finally see risk before customers tweet. The severity scoring is our early warning system.”</p>
                    <footer>
                        <strong>Abo menna</strong>
                        Operations Lead, Himit
                    </footer>
                </blockquote>
                <blockquote class="quote reveal">
                    <p>“Agents trust the queue again. Sentiment drops get routed, and positive chats stay light.”</p>
                    <footer>
                        <strong>Ahmed Essam</strong>
                        Support Manager, Himit
                    </footer>
                </blockquote>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="cta-band">
        <div class="container">
            <div class="panel reveal">
                <div>
                    <h2>Ready to put severity and sentiment at the center of support?</h2>
                    <p>
                        Create an admin or agent account and start routing the conversations that actually
                        need escalation.
                    </p>
                </div>
                <div class="cta-actions">
                    <a href="{{ route('register') }}" class="btn btn-primary">Create account</a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost">Talk to sales</a>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/home.js') }}"></script>
@endpush
