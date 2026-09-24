<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - Nextlog</title>

    {{-- Open Graph / link preview (WhatsApp, dll) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NextLog">
    <meta property="og:title" content="Reset Password - NextLog">
    <meta property="og:description" content="Reset password akun NextLog - PT. Artha Daya Coalindo">
    <meta property="og:image" content="{{ asset('images/favicon-512.png') }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('images/favicon-512.png') }}">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preload" as="image" href="{{ asset('images/favicon-512.png') }}" fetchpriority="high">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-512.png') }}">
    <script>
        (function () {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body class="login-page reset-password-page">
    <div class="background-glow-container">
        <div class="nexus-bg"></div>
        <div class="nexus-grid"></div>
    </div>

    <main class="reset-password-shell">
        <div class="login-card w-100" style="max-width:440px;">
            <div class="login-brand text-center mb-2">
                <div class="brand-reset brand-icon mx-auto mb-3">
                    <img src="{{ asset('images/logo-web.png') }}" alt="InvenTrack Logo" style="max-width:240px;" decoding="async" fetchpriority="high">
                    <div class="next-logistic">
                                NEXTLOGISTIC
                    </div>
                </div>
                <p class="reset-password-hint">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <span>Masukkan password baru untuk akun Anda.</span>
                </p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <div class="input-group password-field-group">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            placeholder="Masukkan password baru" required autofocus id="resetPassword"
                            autocomplete="new-password">
                        <button class="input-group-text password-toggle-btn" type="button"
                            data-target="resetPassword" aria-label="Lihat password" title="Lihat password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg"></i> Simpan Password
                </button>
            </form>
        </div>
    </main>

    <script>
        document.querySelectorAll('.password-toggle-btn').forEach(button => {
            button.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                const showPassword = input.type === 'password';

                input.type = showPassword ? 'text' : 'password';
                this.setAttribute('aria-label', showPassword ? 'Sembunyikan password' : 'Lihat password');
                this.setAttribute('title', showPassword ? 'Sembunyikan password' : 'Lihat password');
                this.querySelector('i').className = showPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    </script>
</body>

</html>
