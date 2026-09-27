<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sedang Pemeliharaan &ndash; NextLog</title>
    <link rel="icon" href="/images/favicon-32.png" type="image/png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'Inter', 'Segoe UI', Helvetica, Arial, sans-serif;
            color: #1a1a2e;
            background-color: #f0f4f2;
            background-image:
                radial-gradient(ellipse 80% 70% at 50% 50%, rgba(16, 185, 129, 0.15), transparent),
                radial-gradient(ellipse 100% 60% at 100% 100%, rgba(45, 212, 191, 0.25), transparent),
                radial-gradient(ellipse 60% 50% at 0% 100%, rgba(16, 185, 129, 0.1), transparent),
                linear-gradient(160deg, #e8e0f0 0%, #dde8f0 100%);
            background-attachment: fixed;
        }
        .card {
            width: 100%;
            max-width: 380px;
            padding: 36px 28px;
            text-align: center;
            border-radius: 16px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.45), rgba(255, 255, 255, 0.15)),
                rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(14px) saturate(160%);
            -webkit-backdrop-filter: blur(14px) saturate(160%);
            box-shadow: 0 10px 30px rgba(6, 78, 59, 0.08);
        }
        .icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 16px;
            color: #059669;
        }
        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #064e3b;
            margin-bottom: 8px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #4b5563;
        }
        .brand {
            margin-top: 22px;
            font-size: 11.5px;
            color: #6b7280;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    @php
        $maintenanceMessage = 'Sistem sedang diperbarui. Silakan coba lagi dalam beberapa menit.';
        if (isset($exception) && method_exists($exception, 'getMessage') && $exception->getMessage() !== '') {
            $maintenanceMessage = $exception->getMessage();
        }
    @endphp
    <div class="card">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
        <h1>Sedang Pemeliharaan</h1>
        <p>{{ $maintenanceMessage }}</p>
        <div class="brand">NextLog &middot; PT Artha Daya Coalindo</div>
    </div>
</body>
</html>
