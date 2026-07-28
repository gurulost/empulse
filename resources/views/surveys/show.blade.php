<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey - Empulse</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body style="background: #f1f5f9; font-family: 'DM Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased;">
<div id="survey-app" class="container py-4"
     data-definition-url="{{ route('survey.definition', $accessToken) }}"
     data-submit-url="{{ route('survey.submit', $accessToken) }}"
     data-autosave-url="{{ route('survey.autosave', $accessToken) }}"
     data-privacy-acknowledgment-url="{{ route('survey.privacy.acknowledge', $accessToken) }}"
></div>
</body>
</html>
