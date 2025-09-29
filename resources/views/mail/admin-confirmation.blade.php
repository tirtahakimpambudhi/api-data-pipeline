@php
    /** @var \App\Models\PendingAdminRegistration $pending */
    $acceptUrl = URL::temporarySignedRoute(
        'admin.register.approve',
        $pending->expires_at,
        ['id' => $pending->id, 'nonce' => $pending->nonce]
    );
    $rejectUrl = URL::temporarySignedRoute(
        'admin.register.reject',
        $pending->expires_at,
        ['id' => $pending->id, 'nonce' => $pending->nonce]
    );
@endphp

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Admin Registration</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header .icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        .email-content {
            padding: 40px 30px;
        }
        .user-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            border-left: 4px solid #667eea;
        }
        .user-info h3 {
            margin: 0 0 16px 0;
            color: #1a202c;
            font-size: 18px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 80px;
        }
        .info-value {
            color: #1a202c;
            font-weight: 500;
        }
        .message {
            text-align: center;
            margin: 32px 0;
            font-size: 16px;
            color: #4a5568;
        }
        .action-buttons {
            text-align: center;
            margin: 40px 0;
        }
        .btn {
            display: inline-block;
            padding: 16px 32px;
            margin: 0 8px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease;
            min-width: 120px;
            text-align: center;
        }
        .btn-accept {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(72, 187, 120, 0.3);
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -1px rgba(72, 187, 120, 0.4);
        }
        .btn-reject {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(245, 101, 101, 0.3);
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px -1px rgba(245, 101, 101, 0.4);
        }
        .expiry-notice {
            background: #fef5e7;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 16px;
            margin-top: 32px;
            text-align: center;
        }
        .expiry-notice .icon {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        .expiry-text {
            color: #c05621;
            font-size: 14px;
            font-weight: 500;
        }
        .footer {
            background: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0;
            color: #718096;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .email-container {
                margin: 20px;
                border-radius: 8px;
            }
            .email-header, .email-content {
                padding: 24px 20px;
            }
            .btn {
                display: block;
                margin: 8px auto;
                width: 200px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        <span class="icon">👤</span>
        <h1>Admin Registration Request</h1>
    </div>

    <div class="email-content">
        <div class="user-info">
            <h3>📋 Registration Details</h3>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $pending->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $pending->email }}</span>
            </div>
        </div>

        <div class="message">
            <p>A new admin registration request has been submitted. Please review the details above and choose your action below.</p>
        </div>

        <div class="action-buttons">
            <a href="{{ $acceptUrl }}" class="btn btn-accept">
                ✅ Accept Registration
            </a>
            <a href="{{ $rejectUrl }}" class="btn btn-reject">
                ❌ Reject Registration
            </a>
        </div>

        <div class="expiry-notice">
            <span class="icon">⏰</span>
            <div class="expiry-text">
                This request expires on {{ $pending->expires_at->setTimezone(config('app.timezone'))->toDayDateTimeString() }}
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</div>
</body>
</html>
