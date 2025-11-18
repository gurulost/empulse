<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="col-lg-6 mx-auto">
        <div class="card shadow-sm text-center">
            <div class="card-body py-5">
                <h1 class="h3 mb-3">Thank you!</h1>
                @if(!empty($alreadyCompleted) && $alreadyCompleted)
                    <p class="text-muted">Our records show that this survey has already been submitted. We appreciate your enthusiasm!</p>
                @else
                    <p class="text-muted">We appreciate you taking a moment to share your perspective.</p>
                @endif
                <a href="{{ config('app.url') }}" class="btn btn-primary mt-3">Back to Empulse</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
