<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    @vite('resources/js/app.js')
</head>
<body class="bg-light">
<div id="survey-app" class="container py-4"
     data-definition-url="{{ route('survey.definition', $assignment->token) }}"
     data-submit-url="{{ route('survey.submit', $assignment->token) }}"
     data-autosave-url="{{ route('survey.autosave', $assignment->token) }}"
></div>
</body>
</html>
