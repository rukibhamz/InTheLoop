<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $email->subject }}</title>
</head>
<body style="font-family: 'Century Gothic', CenturyGothic, AppleGothic, sans-serif; color: #222222; line-height: 1.5; font-size: 10pt;">
    <div style="max-width: 640px; margin: 0 auto; padding: 24px;">
        @if ($logoUrl)
            <p style="margin-bottom: 24px;">
                <img src="{{ $logoUrl }}" alt="{{ $orgName }}" style="max-height: 48px;">
            </p>
        @endif

        <h1 style="font-size: 20px; margin: 0 0 16px;">Email {{ $statusLabel }}</h1>

        <p style="margin: 0 0 16px; color: #464554;">
            <strong>{{ $email->subject }}</strong>
            @if ($actorName)
                was updated by {{ $actorName }}.
            @else
                has a new status.
            @endif
        </p>

        <p style="margin: 0 0 24px;">
            <a href="{{ $emailUrl }}"
               style="display: inline-block; background: #4648d4; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600;">
                View Email in InTheLoop
            </a>
        </p>

        <p style="font-size: 12px; color: #767586; margin: 0;">
            Sent via {{ $orgName }} · Professional Internal Email &amp; Approvals
        </p>
    </div>
</body>
</html>
