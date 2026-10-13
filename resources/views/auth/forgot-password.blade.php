<x-guest-layout 
    title="استعادة كلمة المرور | فاست أوردر (Fast Order)"
    description="استعادة كلمة المرور لحساب التاجر في منصة فاست أوردر لإنشاء المتاجر الإلكترونية."
>
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 8px;">استعادة كلمة السر</h2>
        <p style="font-size: 13px; color: #94a3b8; line-height: 1.5;">أدخل البريد الإلكتروني وسنرسل لك رابطاً لإعادة التعيين</p>
    </div>

    @if (session('status'))
        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 20px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="rtl">
        @csrf

        <div style="margin-bottom: 24px;">
            <label for="email">البريد الإلكتروني</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   placeholder="أدخل البريد الإلكتروني المسجل" 
                   required 
                   autofocus />
            @if ($errors->has('email'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <button type="submit" class="btn-primary">
            إرسال رابط استعادة كلمة السر
        </button>

        <div style="margin-top: 24px; text-align: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px;">
            <a href="{{ route('login') }}" style="color: #a5b4fc; text-decoration: none; font-size: 13px; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#c7d2fe'" onmouseout="this.style.color='#a5b4fc'">
                العودة لصفحة تسجيل الدخول
            </a>
        </div>
    </form>
</x-guest-layout>
