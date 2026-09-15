<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cookie Policy — Ran Serenity</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/legal/cookies.css') }}" />
</head>
<body>

    <div class="legal-layout">

        <aside class="legal-sidebar">
            <a href="/" class="legal-sidebar-brand">Ran Serenity</a>
            <p class="legal-sidebar-label">On this page</p>
            <nav class="legal-sidebar-nav">
                <a href="#overview">Overview</a>
                <a href="#what-are-cookies">What are cookies?</a>
                <a href="#cookies-we-use">The cookies we use</a>
                <a href="#cookie-summary">Cookie summary</a>
                <a href="#managing-cookies">Managing cookies</a>
                <a href="#changes">Changes to this policy</a>
                <a href="#contact">Contact us</a>
            </nav>
        </aside>

        <main class="legal-content">

            <div class="legal-eyebrow">Legal</div>
            <h1 id="overview">Cookie Policy</h1>
            <div class="legal-updated">Last updated: [Last Updated Date]</div>

            <p>
                This Cookie Policy explains what cookies are, which cookies
                Ran Serenity uses, and why they are used. It should be read
                together with our
                <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.
            </p>

            <h2 id="what-are-cookies">1. What are cookies?</h2>
            <p>
                Cookies are small text files stored on your device when you
                visit a website. They help websites function properly,
                maintain user sessions, and remember information while you
                navigate between pages.
            </p>

            <h2 id="cookies-we-use">2. The cookies we use</h2>
            <p>
                Ran Serenity currently uses cookies that are necessary for
                the Platform to function properly, maintain user sessions,
                and help protect forms and user interactions. We do not
                currently use cookies for advertising or analytics purposes.
            </p>

            <div class="cookie-summary" id="cookie-summary">
                <div>
                    <strong>Cookie Summary</strong>
                    <p>Ran Serenity currently uses only necessary cookies required for authentication, session management, and security.</p>
                </div>
                <div class="cookie-summary-badges">
                    <span class="badge">2 Necessary Cookies</span>
                    <span class="badge">No Advertising</span>
                    <span class="badge">No Analytics</span>
                </div>
            </div>

            <table>
                <tr>
                    <th>Cookie</th>
                    <th>Set by</th>
                    <th>Purpose</th>
                    <th>Type</th>
                    <th>Duration</th>
                </tr>
                <tr>
                    <td><code>laravel_session</code></td>
                    <td>Ran Serenity (first-party)</td>
                    <td>Maintains the user's session while navigating the Platform and helps keep the user signed in.</td>
                    <td>Strictly necessary</td>
                    <td>Session</td>
                </tr>
                <tr>
                    <td><code>XSRF-TOKEN</code></td>
                    <td>Ran Serenity (first-party)</td>
                    <td>Helps protect forms and requests from cross-site request forgery (CSRF) attacks.</td>
                    <td>Security / Necessary</td>
                    <td>Session</td>
                </tr>
            </table>

            <div class="legal-note">
                <strong>Do we use a cookie consent pop-up?</strong>
                <p style="margin: 8px 0 0;">
                    Ran Serenity currently uses only cookies that are
                    necessary for the Platform to function, maintain
                    user sessions, and provide security. We do not
                    currently use cookies for advertising or analytics.
                </p>
            </div>

            <h2 id="managing-cookies">3. Managing cookies</h2>
            <p>
                Most web browsers allow users to view, delete, or block
                cookies through their browser settings. Please note that
                blocking necessary cookies may prevent certain features
                of the Platform, such as signing in or submitting forms,
                from functioning properly.
            </p>

            <h2 id="changes">4. Changes to this policy</h2>
            <p>
                We may update this Cookie Policy when the cookies or
                technologies used by the Platform change. Any changes
                will be reflected on this page with an updated
                "Last updated" date.
            </p>

            <h2 id="contact">5. Contact us</h2>
            <p>
                If you have questions about this Cookie Policy,
                you may contact us at:
            </p>
            <p>
                <strong>Ran Serenity</strong><br>
                Email: [Contact Email]
            </p>

            <div class="legal-academic-notice">
                <strong>Academic Use Notice</strong>
                <p>
                    This document is provided for the purposes of the
                    Ran Serenity Lending System capstone project and is
                    intended for academic and system demonstration purposes
                    only. It does not constitute legal advice.
                </p>
            </div>

        </main>

    </div>

    <script>
        // Highlights the sidebar link for whichever section is on screen.
        const sections = document.querySelectorAll('main h1[id], main h2[id], main .cookie-summary[id]');
        const navLinks = document.querySelectorAll('.legal-sidebar-nav a');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                navLinks.forEach((link) => link.classList.remove('active'));
                const activeLink = document.querySelector('.legal-sidebar-nav a[href="#' + entry.target.id + '"]');
                if (activeLink) activeLink.classList.add('active');
            });
        }, { rootMargin: '-20% 0px -70% 0px' });

        sections.forEach((section) => observer.observe(section));
    </script>

</body>
</html>