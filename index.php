<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KaziHub Tanzania | Pata Huduma. Pata Mteja.</title>
<link rel="stylesheet" href="style.css">
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    html {
        scroll-behavior: smooth;
    }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                     Roboto, Helvetica, Arial, sans-serif;
        background: #f7f9fc;
        color: #0f172a;
        overflow-x: hidden;
    }
    a {
        text-decoration: none;
        color: inherit;
    }
    .container {
        width: min(1180px, 92%);
        margin: auto;
    }
    /* =========================
       NAVBAR
    ========================= */
    .navbar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(255,255,255,.92);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid #e8edf4;
    }
    .nav-container {
        height: 76px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }
    .logo {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 25px;
        font-weight: 800;
        letter-spacing: -1px;
        color: #0f172a;
    }
    .logo span {
        color: #0f172a;
    }
    .logo small {
        font-size: 19px;
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    .nav-links a {
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        transition: .25s;
    }
    .nav-links a:hover,
    .nav-links .active {
        color: #2563eb;
    }
    .nav-buttons {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .login-btn {
        padding: 11px 18px;
        color: #334155;
        font-size: 14px;
        font-weight: 700;
    }
    .register-btn {
        background: #2563eb;
        color: white;
        padding: 12px 21px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(37,99,235,.22);
        transition: .25s;
    }
    .register-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(37,99,235,.30);
    }
    /* =========================
       HERO
    ========================= */
    .hero {
        position: relative;
        padding: 95px 0 90px;
        background:
            radial-gradient(circle at 85% 20%, rgba(37,99,235,.13), transparent 28%),
            radial-gradient(circle at 10% 80%, rgba(14,165,233,.08), transparent 25%),
            #ffffff;
        overflow: hidden;
    }
    .hero::before {
        content: "";
        position: absolute;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: rgba(37,99,235,.04);
        right: -180px;
        top: 40px;
    }
    .hero-container {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        align-items: center;
        gap: 80px;
    }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 50px;
        color: #2563eb;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 22px;
    }
    .hero h1 {
        font-size: clamp(42px, 5vw, 68px);
        line-height: 1.04;
        letter-spacing: -3px;
        max-width: 700px;
        color: #0f172a;
    }
    .hero h1 span {
        color: #2563eb;
        position: relative;
    }
    .hero h1 span::after {
        content: "";
        position: absolute;
        height: 7px;
        width: 100%;
        left: 0;
        bottom: -4px;
        border-radius: 20px;
        background: #93c5fd;
        opacity: .7;
    }
    .hero-content > p {
        margin-top: 25px;
        max-width: 620px;
        color: #64748b;
        font-size: 17px;
        line-height: 1.8;
    }
    .hero-actions {
        display: flex;
        gap: 13px;
        margin-top: 32px;
        flex-wrap: wrap;
    }
    .primary-action,
    .secondary-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 15px 24px;
        border-radius: 12px;
        font-weight: 750;
        font-size: 14px;
        transition: .25s;
    }
    .primary-action {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 25px rgba(37,99,235,.25);
    }
    .primary-action:hover {
        transform: translateY(-3px);
    }
    .secondary-action {
        background: white;
        color: #334155;
        border: 1px solid #dbe2ea;
    }
    .secondary-action:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .hero-trust {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-top: 35px;
        color: #64748b;
        font-size: 13px;
    }
    .hero-trust strong {
        display: block;
        color: #0f172a;
        font-size: 20px;
    }
    /* =========================
       HERO CARD
    ========================= */
    .hero-card {
        background: rgba(255,255,255,.95);
        border: 1px solid #e5eaf1;
        border-radius: 25px;
        padding: 25px;
        box-shadow: 0 30px 70px rgba(15,23,42,.10);
        animation: floating 5s ease-in-out infinite;
    }
    @keyframes floating {
        0%,100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }
    .hero-card-top {
        display: flex;
        align-items: center;
        gap: 9px;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 24px;
    }
    .online-dot {
        width: 9px;
        height: 9px;
        background: #22c55e;
        border-radius: 50%;
        box-shadow: 0 0 0 5px #dcfce7;
    }
    .provider-preview {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .provider-avatar {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 18px;
        background: #eff6ff;
        font-size: 32px;
    }
    .provider-preview h3 {
        font-size: 18px;
        margin-bottom: 4px;
    }
    .provider-preview p {
        color: #64748b;
        font-size: 13px;
    }
    .rating {
        margin-top: 7px;
        color: #f59e0b;
        font-size: 13px;
        font-weight: 700;
    }
    .rating span {
        color: #94a3b8;
        font-weight: 500;
    }
    .provider-info {
        display: flex;
        justify-content: space-between;
        margin: 24px 0;
        padding: 14px 0;
        border-top: 1px solid #edf1f5;
        border-bottom: 1px solid #edf1f5;
        color: #64748b;
        font-size: 12px;
    }
    .provider-info span:last-child {
        color: #16a34a;
        font-weight: 700;
    }
    .view-provider {
        width: 100%;
        border: none;
        background: #0f172a;
        color: white;
        padding: 14px;
        border-radius: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: .25s;
    }
    .view-provider:hover {
        background: #2563eb;
    }
    /* =========================
       STATS
    ========================= */
    .stats-section {
        background: #0f172a;
        padding: 34px 0;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(4,1fr);
        text-align: center;
    }
    .stat {
        border-right: 1px solid rgba(255,255,255,.1);
    }
    .stat:last-child {
        border-right: none;
    }
    .stat h3 {
        color: white;
        font-size: 29px;
    }
    .stat p {
        color: #94a3b8;
        margin-top: 5px;
        font-size: 13px;
    }
    /* =========================
       SERVICES
    ========================= */
    .services-section {
        padding: 100px 0;
        background: #f8fafc;
    }
    .section-heading {
        display: flex;
        justify-content: space-between;
        align-items: end;
        margin-bottom: 40px;
    }
    .section-label {
        color: #2563eb;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 2px;
    }
    .section-heading h2,
    .center-heading h2 {
        margin-top: 10px;
        font-size: 38px;
        letter-spacing: -1.5px;
    }
    .view-all {
        color: #2563eb;
        font-size: 14px;
        font-weight: 700;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 18px;
    }
    .service-card {
        background: white;
        border: 1px solid #e7edf4;
        border-radius: 18px;
        padding: 25px;
        transition: .3s;
    }
    .service-card:hover {
        transform: translateY(-7px);
        border-color: #bfdbfe;
        box-shadow: 0 18px 35px rgba(15,23,42,.08);
    }
    .service-icon {
        width: 52px;
        height: 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        border-radius: 15px;
        font-size: 25px;
        margin-bottom: 20px;
    }
    .service-card h3 {
        font-size: 17px;
        margin-bottom: 8px;
    }
    .service-card p {
        color: #64748b;
        font-size: 13px;
        line-height: 1.6;
    }
    /* =========================
       HOW IT WORKS
    ========================= */
    .how-section {
        padding: 100px 0;
        background: white;
    }
    .center-heading {
        text-align: center;
        max-width: 700px;
        margin: auto;
    }
    .center-heading p {
        color: #64748b;
        margin-top: 13px;
    }
    .steps {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 25px;
        margin-top: 60px;
    }
    .step {
        position: relative;
        padding: 30px;
        border: 1px solid #e7edf4;
        border-radius: 20px;
        background: #fff;
    }
    .step-number {
        position: absolute;
        top: 18px;
        right: 20px;
        color: #dbeafe;
        font-size: 30px;
        font-weight: 900;
    }
    .step-icon {
        width: 55px;
        height: 55px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        border-radius: 15px;
        font-size: 25px;
        margin-bottom: 22px;
    }
    .step h3 {
        margin-bottom: 10px;
    }
    .step p {
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
    }
    /* =========================
       PROVIDER CTA
    ========================= */
    .provider-cta {
        padding: 90px 0;
        background:
            linear-gradient(135deg,#0f172a,#172554);
        color: white;
    }
    .provider-cta-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 40px;
    }
    .provider-cta h2 {
        font-size: 40px;
        margin: 10px 0;
        letter-spacing: -1px;
    }
    .provider-cta p {
        color: #cbd5e1;
        max-width: 650px;
        line-height: 1.7;
    }
    .section-label.light {
        color: #93c5fd;
    }
    .cta-button {
        white-space: nowrap;
        background: white;
        color: #0f172a;
        padding: 15px 23px;
        border-radius: 12px;
        font-weight: 800;
        transition: .25s;
    }
    .cta-button:hover {
        transform: translateY(-3px);
        background: #eff6ff;
    }
    /* =========================
       FOOTER
    ========================= */
    footer {
        background: #020617;
        color: white;
        padding-top: 70px;
    }
    .footer-container {
        display: flex;
        justify-content: space-between;
        gap: 70px;
        padding-bottom: 55px;
    }
    .footer-brand {
        max-width: 300px;
    }
    .footer-brand .logo {
        color: white;
    }
    .footer-brand p {
        color: #94a3b8;
        margin-top: 15px;
        line-height: 1.7;
        font-size: 14px;
    }
    .footer-links {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 70px;
    }
    .footer-links h4 {
        margin-bottom: 17px;
    }
    .footer-links a {
        display: block;
        color: #94a3b8;
        font-size: 13px;
        margin-bottom: 11px;
        transition: .2s;
    }
    .footer-links a:hover {
        color: white;
    }
    .footer-bottom {
        border-top: 1px solid #1e293b;
        text-align: center;
        padding: 20px;
        color: #64748b;
        font-size: 12px;
    }
    /* =========================
       MOBILE
    ========================= */
    @media (max-width: 850px) {
        .nav-links {
            display: none;
        }
        .hero {
            padding: 65px 0;
        }
        .hero-container {
            grid-template-columns: 1fr;
            gap: 50px;
        }
        .hero h1 {
            letter-spacing: -2px;
        }
        .stats {
            grid-template-columns: repeat(2,1fr);
            gap: 25px;
        }
        .stat:nth-child(2) {
            border-right: none;
        }
        .services-grid,
        .steps {
            grid-template-columns: 1fr 1fr;
        }
        .provider-cta-container {
            flex-direction: column;
            align-items: flex-start;
        }
        .footer-container {
            flex-direction: column;
        }
    }
    @media (max-width: 560px) {
        .nav-container {
            height: 68px;
        }
        .logo {
            font-size: 21px;
        }
        .nav-buttons {
            gap: 4px;
        }
        .login-btn {
            padding: 9px;
        }
        .register-btn {
            padding: 10px 13px;
        }
        .hero h1 {
            font-size: 42px;
        }
        .hero-content > p {
            font-size: 15px;
        }
        .hero-actions {
            flex-direction: column;
        }
        .primary-action,
        .secondary-action {
            width: 100%;
        }
        .hero-trust {
            gap: 15px;
        }
        .services-grid,
        .steps {
            grid-template-columns: 1fr;
        }
        .section-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 15px;
        }
        .section-heading h2,
        .center-heading h2 {
            font-size: 30px;
        }
        .provider-cta h2 {
            font-size: 31px;
        }
        .footer-links {
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
    }
</style>
</head>
<body>
<!-- =========================
     NAVIGATION
========================= -->
<header class="navbar">
    <div class="container nav-container">
    <div class="logo">
    <span>KaziHub</span>
    <small>🇹🇿</small>
</div>
    </a>
    <nav class="nav-links">
        <a href="index.php" class="active">Home</a>
        <a href="#services">Services</a>
        <a href="#how-it-works">How It Works</a>
    </nav>
    <div class="nav-buttons">
        <a href="login.php" class="login-btn">Login</a>
        <a href="register.php" class="register-btn">Register</a>
    </div>
</div>
</header>
<!-- =========================
     HERO
========================= -->
<main>
<section class="hero">
<div class="container hero-container">
    <div class="hero-content">
        <div class="hero-badge">
            🇹🇿 Tanzania's Trusted Service Marketplace
        </div>
        <h1>
            Find trusted
            <span>professionals</span>
            for your everyday needs.
        </h1>
        <p>
            KaziHub Tanzania connects customers with trusted
            service providers across Tanzania. Find the right
            professional, book a service and get the job done
            with confidence.
        </p>
        <div class="hero-actions">
            <a href="register.php" class="primary-action">
                Find a Service&nbsp; →
            </a>
            <a href="register.php" class="secondary-action">
                Become a Provider
            </a>
        </div>
        <div class="hero-trust">
            <div>
                <strong>500+</strong>
                Service Providers
            </div>
            <div>
                <strong>50+</strong>
                Service Categories
            </div>
            <div>
                <strong>100%</strong>
                Tanzania Focused
            </div>
        </div>
    </div>
    <!-- PROFESSIONAL PROVIDER CARD -->
    <div class="hero-card">
        <div class="hero-card-top">
            <span class="online-dot"></span>
            Professionals available now
        </div>
        <div class="provider-preview">
            <div class="provider-avatar">
                👨🏾‍🔧
            </div>
            <div>
                <h3>John Mwita</h3>
                <p>
                    Professional Electrician
                </p>
                <div class="rating">
                    ⭐ 4.9
                    <span>127 reviews</span>
                </div>
            </div>
        </div>
        <div class="provider-info">
            <span>
                📍 Dar es Salaam
            </span>
            <span>
                ✓ Verified Provider
            </span>
        </div>
        <a href="register.php" class="view-provider">
            View Provider
        </a>
    </div>
</div>
</section>
<!-- =========================
     STATS
========================= -->
<section class="stats-section">
<div class="container stats">
    <div class="stat">
        <h3>500+</h3>
        <p>Registered Providers</p>
    </div>
    <div class="stat">
        <h3>1,000+</h3>
        <p>Services Available</p>
    </div>
    <div class="stat">
        <h3>50+</h3>
        <p>Categories</p>
    </div>
    <div class="stat">
        <h3>24/7</h3>
        <p>Platform Access</p>
    </div>
</div>
</section>
<!-- =========================
     SERVICES
========================= -->
<section class="services-section" id="services">
<div class="container">
    <div class="section-heading">
        <div>
            <span class="section-label">
                EXPLORE SERVICES
            </span>
            <h2>
                Everything you need,
                in one place.
            </h2>
        </div>
        <a href="register.php" class="view-all">
            Explore services →
        </a>
    </div>
    <div class="services-grid">
        <a href="register.php" class="service-card">
            <div class="service-icon">
                🔧
            </div>
            <h3>
                Home Repairs
            </h3>
            <p>
                Electricians, plumbers, painters and skilled fundis.
            </p>
        </a>
        <a href="register.php" class="service-card">
            <div class="service-icon">
                🚗
            </div>
            <h3>
                Auto Services
            </h3>
            <p>
                Mechanics, car electricians and vehicle specialists.
            </p>
        </a>
        <a href="register.php" class="service-card">
            <div class="service-icon">
                💻
            </div>
            <h3>
                IT & Technology
            </h3>
            <p>
                Developers, designers and technology professionals.
            </p>
        </a>
        <a href="register.php" class="service-card">
            <div class="service-icon">
                🧹
            </div>
            <h3>
                Cleaning
            </h3>
            <p>
                Reliable home, office and commercial cleaning.
            </p>
        </a>
        <a href="register.php" class="service-card">
            <div class="service-icon">
                📸
            </div>
            <h3>
                Photography
            </h3>
            <p>
                Professional photographers and videographers.
            </p>
        </a>
        <a href="register.php" class="service-card">
            <div class="service-icon">
                💄
            </div>
            <h3>
                Beauty & Wellness
            </h3>
            <p>
                Salons, makeup artists and beauty professionals.
            </p>
        </a>
    </div>
</div>
</section>
<!-- =========================
     HOW IT WORKS
========================= -->
<section class="how-section" id="how-it-works">
<div class="container">
    <div class="center-heading">
        <span class="section-label">
            HOW KAZIHUB WORKS
        </span>
        <h2>
            Get your job done
            in three simple steps.
        </h2>
        <p>
            From finding the right professional to completing
            your job, KaziHub keeps everything simple.
        </p>
    </div>
    <div class="steps">
        <div class="step">
            <div class="step-number">
                01
            </div>
            <div class="step-icon">
                🔎
            </div>
            <h3>
                Find a Service
            </h3>
            <p>
                Choose the service you need from our wide range
                of professional services.
            </p>
        </div>
        <div class="step">
            <div class="step-number">
                02
            </div>
            <div class="step-icon">
                👤
            </div>
            <h3>
                Choose a Professional
            </h3>
            <p>
                View provider profiles, experience, ratings,
                portfolio and service information.
            </p>
        </div>
        <div class="step">
            <div class="step-number">
                03
            </div>
            <div class="step-icon">
                🤝
            </div>
            <h3>
                Book & Get It Done
            </h3>
            <p>
                Book your preferred professional and get your
                work completed with confidence.
            </p>
        </div>
    </div>
</div>
</section>
<!-- =========================
     PROVIDER CTA
========================= -->
<section class="provider-cta">
<div class="container provider-cta-container">
    <div>
        <span class="section-label light">
            ARE YOU A PROFESSIONAL?
        </span>
        <h2>
            Your skills can become
            your next opportunity.
        </h2>
        <p>
            Join KaziHub Tanzania, create your professional profile,
            showcase your work, connect with customers and grow
            your business.
        </p>
    </div>
    <a href="register.php" class="cta-button">
        Join as a Provider →
    </a>
</div>
</section>
</main>
<!-- =========================
     FOOTER
========================= -->
<footer>
<div class="container footer-container">
    <div class="footer-brand">
        <div class="logo">
    <span>KaziHub</span>
    <small>🇹🇿</small>
</div>
        <p>
            Pata Huduma. Pata Mteja. Fanya Kazi.
        </p>
    </div>
    <div class="footer-links">
        <div>
            <h4>Platform</h4>
            <a href="#services">
                Services
            </a>
            <a href="#how-it-works">
                How It Works
            </a>
            <a href="register.php">
                Get Started
            </a>
        </div>
        <div>
            <h4>For Providers</h4>
            <a href="register.php">
                Become a Provider
            </a>
            <a href="login.php">
                Provider Login
            </a>
            <a href="register.php">
                Create Profile
            </a>
        </div>
        <div>
            <h4>Account</h4>
            <a href="login.php">
                Login
            </a>
            <a href="register.php">
                Register
            </a>
            <a href="#">
                Contact Us
            </a>
        </div>
    </div>
</div>
<div class="footer-bottom">
    <p>
        ©️ 2026 KaziHub Tanzania. All rights reserved.
    </p>
</div>
</footer>
</body>
</html>