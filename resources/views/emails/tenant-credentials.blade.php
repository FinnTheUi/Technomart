<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f7; color: #333; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 32px; text-align: center; }
        .header h1 { color: #fff; font-size: 24px; margin: 0; }
        .header p { color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 8px; }
        .body { padding: 32px; }
        .body h2 { font-size: 18px; color: #1e293b; margin-bottom: 16px; }
        .credentials { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .credentials .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .credentials .value { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; font-family: monospace; }
        .credentials .value:last-child { margin-bottom: 0; }
        .receipt { border-top: 1px solid #e2e8f0; margin-top: 24px; padding-top: 24px; }
        .receipt table { width: 100%; border-collapse: collapse; }
        .receipt td { padding: 8px 0; font-size: 14px; }
        .receipt td:first-child { color: #64748b; }
        .receipt td:last-child { text-align: right; font-weight: 600; color: #0f172a; }
        .receipt .total td { border-top: 2px solid #e2e8f0; padding-top: 12px; font-size: 16px; }
        .footer { padding: 24px 32px; background: #f8fafc; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Welcome to Tecnomart!</h1>
            <p>Your tenant account has been created successfully.</p>
        </div>
        <div class="body">
            <h2>Account Activated</h2>
            <p style="font-size: 14px; color: #475569;">Your rental application has been approved and your tenant account is now active. You may log in using the email address and password you provided during application.</p>

            <div class="credentials">
                <div class="label">Email Address</div>
                <div class="value">{{ $application->email }}</div>
            </div>

            <div class="receipt">
                <h2>Payment Receipt</h2>
                <table>
                    <tr>
                        <td>Stall</td>
                        <td>{{ $stall->name }}</td>
                    </tr>
                    <tr>
                        <td>Location</td>
                        <td>{{ $stall->location ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Monthly Rate</td>
                        <td>₱{{ number_format($stall->price, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Duration</td>
                        <td>{{ $application->rental_duration }} month(s)</td>
                    </tr>
                    <tr>
                        <td>GCash Reference</td>
                        <td>{{ $application->gcash_reference }}</td>
                    </tr>
                    <tr class="total">
                        <td>Total Paid</td>
                        <td>₱{{ number_format($stall->price * $application->rental_duration, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Tecnomart. All rights reserved.
        </div>
    </div>
</body>
</html>
