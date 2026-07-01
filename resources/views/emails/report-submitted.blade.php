<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->subject }}</title>
</head>
<body style="font-family: 'Century Gothic', CenturyGothic, AppleGothic, sans-serif; color: #222222; line-height: 1.5; font-size: 10pt;">
    {!! nl2br(e($report->body)) !!}
</body>
</html>
