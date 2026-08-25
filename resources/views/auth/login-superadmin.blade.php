<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Order — دخول المشرف</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            min-height: 100vh;
            min-height: 100dvh;
        }
        body {
            font-family: 'Cairo', sans-serif;
            background: #0f0c29;
            background: linear-gradient(135deg, #0f0c29 0%, #1a1a4e 50%, #24243e 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 12px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }
        /* Animated background circles */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, rgba(99,102,241,0.12) 0%, transparent 50%),
                        radial-gradient(circle at 70% 60%, rgba(139,92,246,0.10) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite alternate;
            pointer-events: none;
        }
        @keyframes pulse {
            0% { transform: scale(1) rotate(0deg); }
            100% { transform: scale(1.05) rotate(3deg); }
        }

        .card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 40px 32px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
            position: relative;
            z-index: 1;
            margin: auto;
        }

        @media (max-width: 480px) {
            body { padding: 16px 8px; }
            .card { padding: 28px 18px; border-radius: 20px; }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99,102,241,0.15);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 50px;
            padding: 4px 14px;
            font-size: 11px;
            color: #a5b4fc;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .badge::before {
            content: '';
            width: 6px; height: 6px;
            background: #6366f1;
            border-radius: 50%;
            box-shadow: 0 0 6px #6366f1;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 32px;
            text-align: center;
        }
        .logo-icon {
            width: 110px; height: 110px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #ec4899);
            border-radius: 50%;
            padding: 3px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.25);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        @media (min-width: 640px) {
            .logo-icon {
                width: 130px; height: 130px;
                padding: 4px;
            }
        }
        .logo-icon-inner {
            background: #111026;
            border-radius: 50%;
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            padding: 8px;
            overflow: hidden;
        }
        @media (min-width: 640px) {
            .logo-icon-inner {
                padding: 10px;
            }
        }
        .logo-icon-inner img {
            width: 100%; height: 100%;
            object-fit: contain;
            transform: scale(1.46);
        }
        .logo-text h1 { font-size: 24px; font-weight: 900; color: #fff; line-height: 1.2; }

        h2 { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 32px; text-align: center; }

        .field { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #a5b4fc; margin-bottom: 8px; }

        .input-wrap { position: relative; }
        input[type="email"], input[type="password"], input[type="text"] {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 16px;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            color: #fff;
            outline: none;
            transition: all 0.2s;
            direction: ltr;
            text-align: right;
        }
        input::placeholder { color: #4b5680; }
        input:focus {
            border-color: #6366f1;
            background: rgba(99,102,241,0.08);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #1c1a3b !important;
        }
        .eye-btn {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #4b5680; cursor: pointer;
            padding: 0; display: flex;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: #a5b4fc; }
        .eye-btn svg { width: 18px; height: 18px; }
        .pw-field { padding-left: 44px !important; }

        .row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
        .remember { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember input { width: 16px; height: 16px; accent-color: #6366f1; cursor: pointer; }
        .remember span { font-size: 13px; color: #7c8db5; }
        .forgot { font-size: 13px; color: #6366f1; text-decoration: none; font-weight: 600; }
        .forgot:hover { color: #a5b4fc; }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 12px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
            letter-spacing: 0.3px;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(99,102,241,0.5);
        }
        .submit-btn:active { transform: translateY(0); }

        .error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 13px;
        }
        .status {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            color: #86efac;
            font-size: 13px;
        }
        .footer-note {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: #3d4a6b;
        }
        .footer-note span { color: #6366f1; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrap">
        <div class="logo-icon">
            <div class="logo-icon-inner">
                <img src="{{ asset('images/logo.png') }}" alt="Fast Order Logo">
            </div>
        </div>
        <div class="logo-text">
            <h1>Fast Order</h1>
        </div>
    </div>

    <h2>تسجيل الدخول</h2>

    @if(session('status'))
    <div class="status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
    <div class="error">
        @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ url('login') }}">
        @csrf

        <div class="field">
            <label for="email">البريد الإلكتروني</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="admin@example.com"
                   required autofocus autocomplete="email">
        </div>

        <div class="field">
            <label for="password">كلمة المرور</label>
            <div class="input-wrap">
                <input id="password" type="password" name="password"
                       class="pw-field"
                       placeholder="••••••••"
                       required autocomplete="current-password">
                <button type="button" class="eye-btn"
                        onclick="var p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="row">
            <label class="remember">
                <input type="checkbox" name="remember" checked>
                <span>تذكرني (جلسة مستمرة 60 يوماً)</span>
            </label>
            @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="forgot">نسيت كلمة المرور؟</a>
            @endif
        </div>

        <button type="submit" class="submit-btn">تسجيل الدخول</button>
    </form>

    {{-- Google Sign-in for Merchants (Moved to bottom) --}}
    <div style="margin-top: 10px;">
        <div style="display: flex; align-items: center; margin: 24px 0 18px 0;">
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08); height: 1px;"></div>
            <span style="margin: 0 15px; color: #5f6f96; font-size: 12px; font-weight: 600;">أو الدخول بـ</span>
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08); height: 1px;"></div>
        </div>
        <a href="{{ route('auth.google') }}"
           style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 12px; padding: 12px; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; background: rgba(255,255,255,0.03); text-decoration: none; color: #fff; font-size: 14px; font-weight: 600; transition: all 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.borderColor='rgba(99,102,241,0.3)';"
           onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.borderColor='rgba(255,255,255,0.1)';">
            <svg style="width: 20px; height: 20px; flex-shrink: 0;" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.48 14.98 1 12 1 7.35 1 3.37 3.68 1.4 7.62l3.87 3c.92-2.75 3.51-4.58 6.73-4.58z"/>
                <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.47-1.11 2.72-2.36 3.56l3.66 2.84c2.14-1.97 3.39-4.87 3.39-8.5z"/>
                <path fill="#FBBC05" d="M5.27 14.18A7.16 7.16 0 0 1 4.9 12c0-.77.13-1.52.37-2.22V6.78H1.4C.51 8.56 0 10.43 0 12s.51 3.44 1.4 5.22l3.87-3.04z"/>
                <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.66-2.84c-1.01.68-2.31 1.09-4.3 1.09-3.22 0-5.81-1.83-6.73-4.58l-3.87 3C3.37 20.32 7.35 23 12 23z"/>
            </svg>
            <span>تسجيل الدخول بواسطة Google</span>
        </a>
    </div>

    {{-- Register Link for Merchants --}}
    <div style="margin-top: 20px; text-align: center;">
        <div style="display: flex; align-items: center; margin: 20px 0 15px 0;">
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08); height: 1px;"></div>
            <span style="margin: 0 15px; color: #5f6f96; font-size: 11px;">مستخدم جديد؟</span>
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08); height: 1px;"></div>
        </div>
        <a href="{{ route('register') }}"
           style="width: 100%; display: inline-flex; justify-content: center; align-items: center; gap: 8px; padding: 12px; border: 2px solid #6366f1; border-radius: 12px; text-decoration: none; color: #a5b4fc; font-size: 14px; font-weight: 600; transition: all 0.2s;"
           onmouseover="this.style.background='rgba(99,102,241,0.1)';"
           onmouseout="this.style.background='transparent';">
            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            إنشاء متجر جديد (تاجر)
        </a>
    </div>

    <p class="footer-note">محمي بواسطة <span>Fast Order Security</span> © {{ date('Y') }}</p>
</div>
</body>
</html>
