<!DOCTYPE html>
<html lang="en">
<body style="margin:0; padding:0; background:#f4f7fb; color:#10233b; font-family:Arial, sans-serif;">
    <div style="max-width:640px; margin:0 auto; padding:32px 20px;">
        <div style="background:#10233b; border-radius:20px 20px 0 0; color:#ffffff; padding:28px 32px;">
            <div style="font-size:12px; letter-spacing:0.12em; opacity:0.8; text-transform:uppercase;">Empulse Survey</div>
            <h1 style="margin:12px 0 8px; font-size:30px; line-height:1.2;">Your next internal survey is ready.</h1>
            <p style="margin:0; font-size:16px; line-height:1.6; color:#dbe7f5;">
                {{ $companyName }} invited you to share feedback{{ $waveLabel ? ' for ' . $waveLabel : '' }}.
            </p>
        </div>

        <div style="background:#ffffff; border-radius:0 0 20px 20px; padding:32px; box-shadow:0 20px 45px rgba(16, 35, 59, 0.08);">
            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">Hi {{ $name }},</p>
            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">
                Use the secure link below to open your survey. Your assignment is already tied to your account, so there is no extra company or email entry step.
            </p>

            <div style="margin:28px 0;">
                <a href="{{ $surveyUrl }}" style="display:inline-block; background:#0d6efd; color:#ffffff; text-decoration:none; padding:14px 22px; border-radius:999px; font-weight:bold;">
                    Open Survey
                </a>
            </div>

            <p style="margin:0 0 12px; font-size:14px; line-height:1.6; color:#4b5f7a;">
                If the button does not work, copy and paste this link into your browser:
            </p>
            <p style="margin:0; font-size:14px; line-height:1.6; word-break:break-word;">
                <a href="{{ $surveyUrl }}" style="color:#0d6efd;">{{ $surveyUrl }}</a>
            </p>
        </div>
    </div>
</body>
</html>
