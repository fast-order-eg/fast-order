<x-guest-layout>
    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('error'))
        <div class="mb-4 font-medium text-sm text-red-600 bg-red-50 p-3 rounded-lg border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-3">تسجيل الدخول</h2>
        <p class="text-gray-600">مرحباً بك في لوحة التحكم</p>
    </div>

    {{-- Google Sign-in --}}
    <div class="mb-5">
        <a href="{{ url('admin/auth/google') }}"
           class="w-full flex items-center justify-center gap-3 py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 text-sm font-semibold text-gray-700 transition duration-150 ease-in-out cursor-pointer">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.48 14.98 1 12 1 7.35 1 3.37 3.68 1.4 7.62l3.87 3c.92-2.75 3.51-4.58 6.73-4.58z"/>
                <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.47-1.11 2.72-2.36 3.56l3.66 2.84c2.14-1.97 3.39-4.87 3.39-8.5z"/>
                <path fill="#FBBC05" d="M5.27 14.18A7.16 7.16 0 0 1 4.9 12c0-.77.13-1.52.37-2.22V6.78H1.4C.51 8.56 0 10.43 0 12s.51 3.44 1.4 5.22l3.87-3.04z"/>
                <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.66-2.84c-1.01.68-2.31 1.09-4.3 1.09-3.22 0-5.81-1.83-6.73-4.58l-3.87 3C3.37 20.32 7.35 23 12 23z"/>
            </svg>
            <span>الدخول السريع عبر Google</span>
        </a>
        <div class="relative flex py-4 items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-xs font-semibold">أو بالطريقة التقليدية</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
    </div>

    <form method="POST" action="{{ url('admin/login') }}" class="rtl">
        @csrf

        <div class="mb-6">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-3">البريد الإلكتروني</label>
            <input id="email" type="email" name="email"
                   class="arabic-input block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                   value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="أدخل البريد الإلكتروني" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-6">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-3">كلمة المرور</label>
            <div style="position: relative;">
                <input id="password" type="password" name="password"
                       class="arabic-input block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200"
                       style="padding-left: 40px;"
                       required autocomplete="current-password"
                       placeholder="أدخل كلمة المرور" />
                <button type="button"
                        onclick="var p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';"
                        style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: #6b7280; padding: 0;">
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="remember" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                تذكرني
            </label>
            @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">نسيت كلمة المرور؟</a>
            @endif
        </div>

        <button type="submit" class="w-full flex justify-center py-3 px-6 border border-transparent rounded-lg shadow-lg text-base font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform hover:scale-105 transition duration-200 ease-in-out">
            تسجيل الدخول
        </button>
    </form>

    {{-- Register Link --}}
    <div class="mt-6 text-center">
        <div class="relative flex py-3 items-center">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="flex-shrink mx-4 text-gray-400 text-xs">مستخدم جديد؟</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>
        <a href="{{ route('register') }}"
           class="w-full inline-flex justify-center items-center gap-2 py-3 px-6 border-2 border-indigo-600 rounded-lg text-base font-semibold text-indigo-600 hover:bg-indigo-50 transition duration-200">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            إنشاء حساب تاجر جديد
        </a>
    </div>
</x-guest-layout>
