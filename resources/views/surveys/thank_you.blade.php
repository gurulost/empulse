<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you - Empulse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
</head>
<body style="background: linear-gradient(145deg, #0c1222 0%, #1a1f3a 50%, #1e293b 100%); min-height: 100vh; font-family: 'DM Sans', sans-serif;">
    <div class="d-flex align-items-center justify-content-center min-vh-100 p-4">
        <div class="col-lg-5 col-md-7 col-11">
            <div class="card border-0 shadow-lg rounded-4 text-center" style="animation: scaleIn 0.4s cubic-bezier(0.22,1,0.36,1) both;">
                <div class="card-body py-5 px-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(5,150,105,0.1), rgba(16,185,129,0.05));">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 700; color: #0c1222; letter-spacing: -0.02em; margin-bottom: 0.75rem;">Thank you!</h1>
                    @if(!empty($alreadyCompleted) && $alreadyCompleted)
                        <p style="color: #64748b; font-size: 1rem; max-width: 360px; margin: 0 auto 2rem; line-height: 1.6;">
                            Our records show that this survey has already been submitted. We appreciate your enthusiasm!
                        </p>
                    @else
                        <p style="color: #64748b; font-size: 1rem; max-width: 360px; margin: 0 auto 2rem; line-height: 1.6;">
                            We appreciate you taking a moment to share your perspective. Your input helps shape a better workplace.
                        </p>
                    @endif
                    <a href="{{ config('app.url') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" style="background: #4f46e5; border-color: #4f46e5;">
                        <i class="bi bi-arrow-left me-2"></i>Back to Empulse
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</body>
</html>
