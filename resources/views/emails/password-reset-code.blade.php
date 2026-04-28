<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.password_reset_code_subject') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #1e293b; max-width: 32rem; margin: 0 auto; padding: 1.5rem;">
    <p>{{ __('mail.password_reset_code_greeting') }}</p>
    <p style="font-size: 1.75rem; letter-spacing: 0.25em; font-weight: 700; direction: ltr; text-align: center;">{{ $code }}</p>
    <p>{{ __('mail.password_reset_code_expiry', ['minutes' => $expiresMinutes]) }}</p>
    <p style="font-size: 0.875rem; color: #64748b;">{{ __('mail.password_reset_code_ignore') }}</p>
</body>
</html>
