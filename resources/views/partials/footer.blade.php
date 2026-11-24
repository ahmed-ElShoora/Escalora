<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="brand">
                    <span class="brand-mark">E</span>
                    <span>Escalora</span>
                </a>
                <p>
                    The customer service escalation platform that scores severity and sentiment,
                    then routes critical conversations to the right people instantly.
                </p>
            </div>

            <div class="footer-col">
                <h4>Product</h4>
                <a href="{{ route('services') }}">Services</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('contact') }}">Contact</a>
            </div>

            <div class="footer-col">
                <h4>Workspace</h4>
                <a href="{{ route('agent.login') }}">Agent Login</a>
                <a href="{{ route('login') }}">Admin Login</a>
                <a href="{{ route('register') }}">Create Admin Account</a>
            </div>

            <div class="footer-col">
                <h4>Contact</h4>
                <a href="mailto:support@escalora.com">support@escalora.com</a>
                <a href="tel:+201061177777">+20 1061177777</a>
                <span style="color: var(--muted);">Himit · Remote-first</span>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} Escalora. All rights reserved.</span>
            <span>Built for modern support operations.</span>
        </div>
    </div>
</footer>
