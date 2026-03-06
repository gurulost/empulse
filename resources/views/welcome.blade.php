@extends('layouts.app')

@section('title')
    Empulse - Transform Employee Feedback
@endsection

@section('content')
<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center position-relative overflow-hidden">
        <div class="hero-ambient"></div>

        <div class="container position-relative z-2">
            <div class="row align-items-center min-vh-75 py-5">
                <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0">
                    <div class="animate-fade-in-up">
                        <span class="hero-badge">
                            <span class="hero-badge-dot"></span>
                            New: Visual Survey Builder
                        </span>
                    </div>
                    <h1 class="hero-title animate-fade-in-up delay-1">
                        Transform Employee
                        <span class="hero-title-accent">Feedback</span>
                        into Action
                    </h1>
                    <p class="hero-description animate-fade-in-up delay-2">
                        Empulse helps you measure engagement, understand culture, and drive meaningful change with powerful analytics and intuitive surveys.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start animate-fade-in-up delay-3">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg hover-lift fw-bold">
                            Get Started Free
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-white btn-lg rounded-pill px-5 py-3 shadow-sm hover-lift fw-bold text-dark">
                            Log In
                        </a>
                    </div>
                    <div class="hero-trust-signals animate-fade-in-up delay-4">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i>No credit card required</span>
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i>14-day free trial</span>
                    </div>
                </div>
                <div class="col-lg-6 position-relative animate-scale-in delay-3">
                    <div class="hero-glow"></div>
                    <div class="hero-dashboard-card">
                        <div class="hero-dashboard-header">
                            <div class="d-flex gap-2">
                                <div class="hero-dot bg-danger"></div>
                                <div class="hero-dot bg-warning"></div>
                                <div class="hero-dot bg-success"></div>
                            </div>
                            <div class="hero-dashboard-label">Empulse Dashboard</div>
                        </div>
                        <div class="hero-dashboard-body">
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="hero-stat-card">
                                        <div class="hero-stat-label">Engagement Score</div>
                                        <div class="d-flex align-items-end gap-2">
                                            <div class="hero-stat-value">8.4</div>
                                            <div class="text-success small fw-bold mb-1"><i class="bi bi-arrow-up"></i> 12%</div>
                                        </div>
                                        <div class="progress mt-3" style="height: 5px;">
                                            <div class="progress-bar bg-primary rounded-pill" style="width: 84%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="hero-stat-card">
                                        <div class="hero-stat-label">Response Rate</div>
                                        <div class="d-flex align-items-end gap-2">
                                            <div class="hero-stat-value">92%</div>
                                            <div class="text-success small fw-bold mb-1"><i class="bi bi-arrow-up"></i> 5%</div>
                                        </div>
                                        <div class="progress mt-3" style="height: 5px;">
                                            <div class="progress-bar bg-success rounded-pill" style="width: 92%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-stat-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="hero-stat-label mb-0">Recent Trends</div>
                                    <span class="badge bg-light text-dark fw-semibold" style="font-size: 0.7rem;">Last 30 Days</span>
                                </div>
                                <div class="hero-chart-bars">
                                    <div class="hero-bar" style="height: 40%; animation-delay: 0.6s;"></div>
                                    <div class="hero-bar" style="height: 55%; animation-delay: 0.7s;"></div>
                                    <div class="hero-bar" style="height: 45%; animation-delay: 0.8s;"></div>
                                    <div class="hero-bar" style="height: 65%; animation-delay: 0.9s;"></div>
                                    <div class="hero-bar" style="height: 50%; animation-delay: 1.0s;"></div>
                                    <div class="hero-bar" style="height: 72%; animation-delay: 1.1s;"></div>
                                    <div class="hero-bar hero-bar-active" style="height: 85%; animation-delay: 1.2s;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container py-5">
            <div class="text-center mb-5 pt-3">
                <h2 class="features-heading">Everything you need to build a better culture</h2>
                <p class="features-subheading">
                    Powerful tools designed to help you listen, understand, and act on employee feedback.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-primary">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <h3 class="feature-title">Advanced Analytics</h3>
                        <p class="feature-text">
                            Visualize trends, compare benchmarks, and uncover deep insights with our interactive dashboards.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-success">
                            <i class="bi bi-ui-checks"></i>
                        </div>
                        <h3 class="feature-title">Visual Survey Builder</h3>
                        <p class="feature-text">
                            Create beautiful, engaging surveys in minutes with our drag-and-drop builder and logic editor.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon feature-icon-info">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="feature-title">Culture Pulse</h3>
                        <p class="feature-text">
                            Monitor team sentiment in real-time and identify areas for improvement before they become issues.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section position-relative overflow-hidden">
        <div class="cta-ambient"></div>
        <div class="container py-5 position-relative z-2 text-center">
            <h2 class="cta-heading">Ready to transform your workplace?</h2>
            <p class="cta-subheading">
                Join thousands of companies using Empulse to build happier, more productive teams.
            </p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg hover-lift fw-bold">
                Start Your Free Trial
                <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </section>
</div>

<style>
.hero-section { min-height: 90vh; padding-top: 5rem; }
.hero-ambient {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse 80% 60% at 10% 90%, rgba(79,70,229,0.06), transparent),
                radial-gradient(ellipse 60% 50% at 90% 10%, rgba(99,102,241,0.04), transparent);
}
.hero-badge {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: rgba(79,70,229,0.06); border: 1px solid rgba(79,70,229,0.12);
    color: #4f46e5; font-family: 'Outfit', sans-serif; font-size: 0.8125rem;
    font-weight: 600; padding: 0.5rem 1rem; border-radius: 50rem; margin-bottom: 1.5rem;
}
.hero-badge-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #4f46e5;
    animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.4); }
}
.hero-title {
    font-family: 'Outfit', sans-serif; font-size: clamp(2.25rem, 5vw, 3.75rem);
    font-weight: 800; line-height: 1.1; letter-spacing: -0.035em; color: #0c1222; margin-bottom: 1.5rem;
}
.hero-title-accent { color: #4f46e5; position: relative; display: inline-block; }
.hero-title-accent::after {
    content: ''; position: absolute; bottom: 4px; left: 0; right: 0;
    height: 6px; background: rgba(79,70,229,0.15); border-radius: 3px; z-index: -1;
}
.hero-description { font-size: 1.125rem; line-height: 1.7; color: #64748b; max-width: 520px; margin-bottom: 2rem; }
.hero-trust-signals { margin-top: 2.5rem; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; justify-content: center; }
@media (min-width: 992px) {
    .hero-trust-signals { justify-content: flex-start; }
    .hero-description { margin-left: 0; margin-right: auto; }
}
.hero-trust-signals span { font-size: 0.8125rem; font-weight: 600; color: #64748b; }
.hero-glow {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 120%; height: 120%; background: radial-gradient(circle, rgba(79,70,229,0.08) 0%, transparent 60%);
    z-index: -1; pointer-events: none;
}
.hero-dashboard-card {
    background: #fff; border-radius: 1.25rem; overflow: hidden;
    border: 1px solid rgba(226,232,240,0.8);
    box-shadow: 0 4px 6px rgba(0,0,0,0.02), 0 12px 28px rgba(0,0,0,0.06), 0 40px 80px rgba(0,0,0,0.04);
}
.hero-dashboard-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.875rem 1.25rem; border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.hero-dot { width: 10px; height: 10px; border-radius: 50%; }
.hero-dashboard-label { font-family: 'Outfit', sans-serif; font-size: 0.75rem; font-weight: 600; color: #94a3b8; letter-spacing: 0.02em; }
.hero-dashboard-body { padding: 1.25rem; background: #fafbfc; }
.hero-stat-card { background: #fff; padding: 1rem 1.25rem; border-radius: 0.75rem; border: 1px solid #f1f5f9; }
.hero-stat-label { font-family: 'Outfit', sans-serif; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 0.5rem; }
.hero-stat-value { font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700; color: #0c1222; letter-spacing: -0.02em; line-height: 1; }
.hero-chart-bars { display: flex; align-items: flex-end; justify-content: space-between; gap: 6px; height: 80px; }
.hero-bar {
    flex: 1; background: rgba(79,70,229,0.12); border-radius: 4px 4px 0 0;
    animation: growBar 0.6s cubic-bezier(0.22,1,0.36,1) both; transition: background 0.2s ease;
}
.hero-bar-active { background: #4f46e5; }
@keyframes growBar { from { height: 0 !important; } }

/* Features */
.features-section { background: #fff; padding: 4rem 0; border-top: 1px solid #f1f5f9; }
.features-heading { font-family: 'Outfit', sans-serif; font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; color: #0c1222; letter-spacing: -0.025em; margin-bottom: 0.75rem; }
.features-subheading { font-size: 1.0625rem; color: #64748b; max-width: 540px; margin: 0 auto; }
.feature-card {
    background: #fff; border: 1px solid #f1f5f9; border-radius: 1rem; padding: 2.5rem 2rem;
    text-align: center; height: 100%; transition: all 0.3s cubic-bezier(0.22,1,0.36,1);
}
.feature-card:hover { border-color: rgba(79,70,229,0.15); box-shadow: 0 8px 32px rgba(79,70,229,0.06); transform: translateY(-4px); }
.feature-icon { display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 16px; font-size: 1.5rem; margin-bottom: 1.5rem; }
.feature-icon-primary { background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(99,102,241,0.05)); color: #4f46e5; }
.feature-icon-success { background: linear-gradient(135deg, rgba(5,150,105,0.1), rgba(16,185,129,0.05)); color: #059669; }
.feature-icon-info { background: linear-gradient(135deg, rgba(2,132,199,0.1), rgba(14,165,233,0.05)); color: #0284c7; }
.feature-title { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 700; color: #0c1222; margin-bottom: 0.75rem; letter-spacing: -0.015em; }
.feature-text { font-size: 0.9375rem; color: #64748b; line-height: 1.7; margin-bottom: 0; }

/* CTA */
.cta-section { background: linear-gradient(145deg, #0c1222 0%, #1a1f3a 50%, #1e293b 100%); padding: 5rem 0; color: #fff; }
.cta-ambient {
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse 50% 60% at 70% 0%, rgba(79,70,229,0.15), transparent),
                radial-gradient(ellipse 40% 50% at 20% 100%, rgba(6,182,212,0.08), transparent);
}
.cta-heading { font-family: 'Outfit', sans-serif; font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 700; color: #fff; letter-spacing: -0.025em; margin-bottom: 1rem; }
.cta-subheading { font-size: 1.0625rem; color: rgba(255,255,255,0.6); max-width: 520px; margin: 0 auto 2rem; }
</style>
@endsection
