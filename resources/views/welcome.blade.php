@extends('layouts.app')

@section('title')
    Empulse - Transform Employee Feedback
@endsection

@section('content')
<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center position-relative overflow-hidden">
        <div class="container position-relative z-2">
            <div class="row align-items-center min-vh-75 py-5">
                <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0">
                    <div class="d-inline-block mb-3">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                            <i class="bi bi-stars me-1"></i> New: Visual Survey Builder
                        </span>
                    </div>
                    <h1 class="display-3 fw-bold mb-4 text-dark lh-sm tracking-tight">
                        Transform Employee <br>
                        <span class="text-primary position-relative">
                            Feedback
                            <svg class="position-absolute w-100" style="bottom: 5px; left: 0; height: 8px; z-index: -1; opacity: 0.3;" viewBox="0 0 100 10" preserveAspectRatio="none">
                                <path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="8" fill="none" class="text-primary"/>
                            </svg>
                        </span>
                        into Action
                    </h1>
                    <p class="lead text-muted mb-5 fs-4 fw-normal" style="max-width: 540px;">
                        Empulse helps you measure engagement, understand culture, and drive meaningful change with powerful analytics and intuitive surveys.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg hover-lift fw-bold">
                            Get Started Free
                            <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-white btn-lg rounded-pill px-5 py-3 shadow-sm hover-lift fw-bold text-dark">
                            Log In
                        </a>
                    </div>
                    <div class="mt-5 pt-3 d-flex align-items-center justify-content-center justify-content-lg-start gap-4 text-muted small fw-bold text-uppercase tracking-wide">
                        <span><i class="bi bi-check-circle-fill text-success me-2"></i>No credit card required</span>
                        <span><i class="bi bi-check-circle-fill text-success me-2"></i>14-day free trial</span>
                    </div>
                </div>
                <div class="col-lg-6 position-relative">
                    <div class="position-absolute top-50 start-50 translate-middle w-100 h-100 bg-primary opacity-10 rounded-circle blur-3xl" style="filter: blur(80px); z-index: -1;"></div>
                    <div class="card border-0 shadow-2xl rounded-4 overflow-hidden glass-card tilt-effect">
                        <div class="card-header bg-white border-bottom border-light py-3 px-4 d-flex align-items-center justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></div>
                                <div class="rounded-circle bg-warning" style="width: 10px; height: 10px;"></div>
                                <div class="rounded-circle bg-success" style="width: 10px; height: 10px;"></div>
                            </div>
                            <div class="small text-muted fw-bold">Empulse Dashboard</div>
                        </div>
                        <div class="card-body p-0 bg-light">
                            <!-- Abstract UI Representation -->
                            <div class="p-4">
                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="bg-white p-3 rounded-3 shadow-sm h-100">
                                            <div class="text-muted small mb-2 fw-bold text-uppercase">Engagement Score</div>
                                            <div class="d-flex align-items-end gap-2">
                                                <div class="h2 mb-0 fw-bold text-dark">8.4</div>
                                                <div class="text-success small fw-bold mb-1"><i class="bi bi-arrow-up"></i> 12%</div>
                                            </div>
                                            <div class="progress mt-3" style="height: 6px;">
                                                <div class="progress-bar bg-primary" style="width: 84%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white p-3 rounded-3 shadow-sm h-100">
                                            <div class="text-muted small mb-2 fw-bold text-uppercase">Response Rate</div>
                                            <div class="d-flex align-items-end gap-2">
                                                <div class="h2 mb-0 fw-bold text-dark">92%</div>
                                                <div class="text-success small fw-bold mb-1"><i class="bi bi-arrow-up"></i> 5%</div>
                                            </div>
                                            <div class="progress mt-3" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: 92%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-3 rounded-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-muted small fw-bold text-uppercase">Recent Trends</div>
                                        <div class="badge bg-light text-dark">Last 30 Days</div>
                                    </div>
                                    <div class="d-flex align-items-end justify-content-between gap-1" style="height: 100px;">
                                        <div class="w-100 bg-primary bg-opacity-10 rounded-top" style="height: 40%"></div>
                                        <div class="w-100 bg-primary bg-opacity-25 rounded-top" style="height: 60%"></div>
                                        <div class="w-100 bg-primary bg-opacity-50 rounded-top" style="height: 50%"></div>
                                        <div class="w-100 bg-primary bg-opacity-75 rounded-top" style="height: 70%"></div>
                                        <div class="w-100 bg-primary rounded-top" style="height: 85%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light position-relative">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold mb-3">Everything you need to build a better culture</h2>
                <p class="lead text-muted mx-auto" style="max-width: 600px;">
                    Powerful tools designed to help you listen, understand, and act on employee feedback.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift transition-all">
                        <div class="card-body p-4 p-lg-5 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-bar-chart-fill fs-2"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Advanced Analytics</h3>
                            <p class="text-muted mb-0">
                                Visualize trends, compare benchmarks, and uncover deep insights with our interactive dashboards.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift transition-all">
                        <div class="card-body p-4 p-lg-5 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-ui-checks fs-2"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Visual Survey Builder</h3>
                            <p class="text-muted mb-0">
                                Create beautiful, engaging surveys in minutes with our drag-and-drop builder and logic editor.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-lift transition-all">
                        <div class="card-body p-4 p-lg-5 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-people-fill fs-2"></i>
                            </div>
                            <h3 class="h4 fw-bold mb-3">Culture Pulse</h3>
                            <p class="text-muted mb-0">
                                Monitor team sentiment in real-time and identify areas for improvement before they become issues.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-dark text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background: radial-gradient(circle at top right, #4f46e5, transparent 40%), radial-gradient(circle at bottom left, #06b6d4, transparent 40%);"></div>
        <div class="container py-5 position-relative z-2 text-center">
            <h2 class="display-4 fw-bold mb-4 text-white">Ready to transform your workplace?</h2>
            <p class="lead text-white-50 mb-5 mx-auto" style="max-width: 600px;">
                Join thousands of companies using Empulse to build happier, more productive teams.
            </p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-lg hover-lift fw-bold">
                Start Your Free Trial
            </a>
        </div>
    </section>
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.5);
}
.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
.blur-3xl {
    filter: blur(64px);
}
.tracking-tight {
    letter-spacing: -0.025em;
}
.tracking-wide {
    letter-spacing: 0.05em;
}
</style>
@endsection
