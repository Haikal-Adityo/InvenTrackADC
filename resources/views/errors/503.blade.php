<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEXTLOG | PT ARTHA DAYA COALINDO</title>
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
            padding: 36px 28px 28px;
            position: relative;
            overflow: hidden;
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
        .logo {
            display: block;
            height: 96px;
            width: auto;
            max-width: 100%;
            margin: 0 auto 22px;
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
            padding-top: 16px;
            border-top: 1px solid rgba(6, 78, 59, 0.12);
            font-size: 11.5px;
            color: #6b7280;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    @php
        $maintenanceMessage = 'Mohon maaf, sistem sedang diperbarui. Silakan coba lagi dalam beberapa menit.';
        if (isset($exception) && method_exists($exception, 'getMessage') && $exception->getMessage() !== '') {
            $maintenanceMessage = $exception->getMessage();
        }
    @endphp
    <div class="card">
        <img class="logo" src="/images/logo-web.png" alt="NextLog">
        <h1>Sedang Pemeliharaan</h1>
        <p>{{ $maintenanceMessage }}</p>
        <div class="brand">NextLog &middot; PT Artha Daya Coalindo</div>
    </div>
</body>
</html>
