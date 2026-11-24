@extends('layouts.app')

@section('title', 'Contact')
@section('meta_description', 'Contact the Escalora team about demos, onboarding, and customer service escalation setups for your company.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Contact us</p>
            <h1>Let’s design your escalation flow</h1>
            <p>
                Tell us about your support channels, team roles, and the severity signals that matter most.
                We’ll follow up with a clear next step.
            </p>
        </div>
    </section>

    <section class="content-section">
        <div class="container contact-layout">
            <aside class="panel contact-info reveal">
                <h2>Talk with Escalora</h2>
                <p>Whether you need a product demo or an enterprise rollout plan, our team is ready.</p>

                <ul class="contact-list">
                    <li>
                        <strong>Email</strong>
                        <span>support@escalora.com</span>
                    </li>
                    <li>
                        <strong>Phone</strong>
                        <span>+201061171316</span>
                    </li>
                    <li>
                        <strong>Hours</strong>
                        <span>Sunday – Thursday · 9:00AM–2:00PM</span>
                    </li>
                    <li>
                        <strong>Headquarters</strong>
                        <span>Himit, Kafrelsheikh, Egypt</span>
                    </li>
                </ul>
            </aside>

            <div class="panel contact-form-wrap reveal">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Please fix the following:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-stack" method="POST" action="{{ route('contact.store') }}">
                    @csrf

                    <div class="form-row two">
                        <div class="field">
                            <label for="name">Full name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        </div>
                        <div class="field">
                            <label for="email">Work email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="form-row two">
                        <div class="field">
                            <label for="company">Company</label>
                            <input id="company" name="company" type="text" value="{{ old('company') }}">
                        </div>
                        <div class="field">
                            <label for="subject">Subject</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="message">How can we help?</label>
                        <textarea id="message" name="message" required>{{ old('message') }}</textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Send message</button>
                </form>
            </div>
        </div>
    </section>
@endsection
