<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey - Empulse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    @vite('resources/js/app.js')
</head>
<body style="background: #f1f5f9; font-family: 'DM Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased;">
<div id="survey-app" class="container py-4"
     data-definition-url="{{ route('survey.definition', $assignment->token) }}"
     data-submit-url="{{ route('survey.submit', $assignment->token) }}"
     data-autosave-url="{{ route('survey.autosave', $assignment->token) }}"
></div>
</body>
</html>
