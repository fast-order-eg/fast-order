<x-guest-layout 
    title="فاست أوردر | إنشاء متجر إلكتروني جديد مجاناً - بديل ايزي اوردر وشوبيفاي"
    description="سجل حسابك الآن في فاست أوردر (Fast Order) وابدأ متجرك الإلكتروني في أقل من دقيقة. شيك أوت سريع بنظام الصفحة الواحدة، ربط بيكسل فيسبوك وتيك توك، 0% عمولة، وبديل أسرع وأوفر لمنصة ايزي اوردر وشوبيفاي في مصر."
    keywords="فاست اوردر, فاست أوردر, Fast Order, ايزي اوردر, بديل ايزي اوردر, Easy Order, إنشاء متجر إلكتروني, عمل متجر الكتروني في مصر, ستور اونلاين, انشاء ستور, متجر الكتروني, تصميم متجر الكتروني, منصة تجارة الكترونية, شوبيفاي مصر, دفع عند الاستلام, بيكسل فيسبوك, بيكسل تيك توك"
    canonical-url="{{ url()->current() }}"
>
    <div style="text-align: center; margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 8px;">إنشاء حساب تاجر جديد</h2>
        <p style="font-size: 13px; color: #94a3b8; line-height: 1.5;">انضم إلينا وابدأ متجرك الإلكتروني في دقائق</p>
    </div>

    <!-- Google Sign-in Button -->
    <div style="margin-bottom: 20px;">
        <a href="{{ route('auth.google') }}" 
           style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 16px; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; background: rgba(255,255,255,0.06); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer;"
           onmouseover="this.style.background='rgba(255,255,255,0.1)'"
           onmouseout="this.style.background='rgba(255,255,255,0.06)'">
            <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5.04c1.66 0 3.2.57 4.38 1.69l3.27-3.27C17.67 1.48 14.98 1 12 1 7.35 1 3.37 3.68 1.4 7.62l3.87 3c.92-2.75 3.51-4.58 6.73-4.58z"/>
                <path fill="#4285F4" d="M23.49 12.27c0-.81-.07-1.59-.2-2.34H12v4.44h6.44c-.28 1.47-1.11 2.72-2.36 3.56l3.66 2.84c2.14-1.97 3.39-4.87 3.39-8.5z"/>
                <path fill="#FBBC05" d="M5.27 14.18A7.16 7.16 0 0 1 4.9 12c0-.77.13-1.52.37-2.22V6.78H1.4C.51 8.56 0 10.43 0 12s.51 3.44 1.4 5.22l3.87-3.04z"/>
                <path fill="#34A853" d="M12 23c3.24 0 5.97-1.07 7.96-2.91l-3.66-2.84c-1.01.68-2.31 1.09-4.3 1.09-3.22 0-5.81-1.83-6.73-4.58l-3.87 3C3.37 20.32 7.35 23 12 23z"/>
            </svg>
            <span>التسجيل السريع عبر Google</span>
        </a>
        <div style="position: relative; display: flex; padding: 16px 0; align-items: center;">
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08);"></div>
            <span style="flex-shrink: 0; margin: 0 16px; color: #a5b4fc; font-size: 12px; font-weight: 600;">أو بالطريقة التقليدية</span>
            <div style="flex-grow: 1; border-top: 1px solid rgba(255,255,255,0.08);"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('register') }}" class="rtl" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf

        <!-- Name -->
        <div>
            <label for="name">الاسم الكامل</label>
            <input id="name" 
                   type="text" 
                   name="name" 
                   value="{{ old('name') }}" 
                   required 
                   autofocus 
                   autocomplete="name" 
                   placeholder="أدخل اسمك الكامل" />
            @if ($errors->has('name'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <!-- Email -->
        <div>
            <label for="email">البريد الإلكتروني</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autocomplete="email"
                   placeholder="أدخل بريدك الإلكتروني" />
            @if ($errors->has('email'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <!-- Phone (Optional) -->
        <div>
            <label for="phone">رقم الهاتف (اختياري)</label>
            <input id="phone" 
                   type="text" 
                   name="phone" 
                   value="{{ old('phone') }}" 
                   autocomplete="tel"
                   placeholder="أدخل رقم هاتفك" />
            @if ($errors->has('phone'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('phone') }}</p>
            @endif
        </div>

        <!-- Store Name -->
        <div>
            <label for="store_name">اسم المتجر</label>
            <input id="store_name" 
                   type="text" 
                   name="store_name" 
                   value="{{ old('store_name') }}" 
                   required 
                   autocomplete="off"
                   placeholder="أدخل اسم متجرك (بحد أقصى 4 كلمات)"
                   oninput="let words = this.value.trim().split(/\s+/); if(words.length > 4) { this.value = words.slice(0, 4).join(' '); }" />
            @if ($errors->has('store_name'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('store_name') }}</p>
            @endif
        </div>

        <!-- Store Subdomain -->
        <div>
            <label for="subdomain">رابط المتجر (السب دومين)</label>
            <div style="display: flex; align-items: center; direction: ltr; width: 100%;">
                <input id="subdomain" 
                       type="text" 
                       name="subdomain" 
                       autocomplete="off"
                       value="{{ old('subdomain', 'store-' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 4)) }}" 
                       required 
                       placeholder="store-link"
                       style="flex: 1; min-width: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; text-align: right; direction: ltr;" />
                @php
                    $currentHost = request()->getHost();
                    if ($currentHost && $currentHost !== '127.0.0.1' && $currentHost !== 'localhost') {
                        $cleanHost = str_starts_with($currentHost, 'app.') ? substr($currentHost, 4) : $currentHost;
                        $parts = explode('.', $cleanHost);
                        if (count($parts) >= 3) {
                            array_shift($parts);
                            $baseDomain = implode('.', $parts);
                        } else {
                            $baseDomain = $cleanHost;
                        }
                    } else {
                        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'fastorder.localhost';
                        if (str_starts_with($baseDomain, 'app.')) {
                            $baseDomain = substr($baseDomain, 4);
                        }
                    }
                    $port = request()->getPort();
                    $portStr = ($port && $port != 80 && $port != 443) ? ':' . $port : '';
                    $displayDomain = '.' . $baseDomain . $portStr;
                @endphp
                <span style="flex-shrink: 0; white-space: nowrap; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-left: none; padding: 14px 8px; border-top-right-radius: 12px; border-bottom-right-radius: 12px; color: #a5b4fc; font-weight: 600; font-size: 12px; font-family: sans-serif;" dir="ltr">{{ $displayDomain }}</span>
            </div>
            <p id="subdomain-check-msg" style="font-size: 12px; margin-top: 6px; font-weight: 600; color: #a5b4fc; text-align: right;"></p>
            @if ($errors->has('subdomain'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('subdomain') }}</p>
            @endif
        </div>

        <!-- Password -->
        <div>
            <label for="password">كلمة المرور</label>
            <div style="position: relative;">
                <input id="password" 
                       style="padding-left: 40px;"
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="new-password" 
                       placeholder="أدخل كلمة المرور" />
                <button type="button" 
                        onclick="var p=document.getElementById('password'); p.type=p.type==='password'?'text':'password';"
                        style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: #6b7280; padding: 0;"
                        title="إظهار / إخفاء">
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @if ($errors->has('password'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation">تأكيد كلمة المرور</label>
            <div style="position: relative;">
                <input id="password_confirmation" 
                       style="padding-left: 40px;"
                       type="password" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password" 
                       placeholder="أعد إدخال كلمة المرور" />
                <button type="button" 
                        onclick="var p=document.getElementById('password_confirmation'); p.type=p.type==='password'?'text':'password';"
                        style="position: absolute; top: 50%; left: 12px; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: #6b7280; padding: 0;"
                        title="إظهار / إخفاء">
                    <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @if ($errors->has('password_confirmation'))
                <p style="color: #f87171; font-size: 12px; font-weight: 600; margin-top: 6px; text-align: right;">{{ $errors->first('password_confirmation') }}</p>
            @endif
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 8px;">
            إنشاء حساب تاجر جديد
        </button>

        <div style="margin-top: 20px; text-align: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px;">
            <a href="{{ route('login') }}" style="color: #a5b4fc; text-decoration: none; font-size: 13px; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#c7d2fe'" onmouseout="this.style.color='#a5b4fc'">
                لديك حساب بالفعل؟ تسجيل الدخول
            </a>
        </div>
    </form>

    <!-- Subdomain Generation & Check Script -->
    <script>
        document.getElementById('store_name').addEventListener('input', function() {
            const subdomainInput = document.getElementById('subdomain');
            if (!subdomainInput.dataset.manual) {
                let slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s\-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
                
                subdomainInput.value = slug;
                checkSubdomainAvailability(slug);
            }
        });

        let checkTimeout;
        const subdomainInput = document.getElementById('subdomain');
        subdomainInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
            let slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9\-]/g, '');
            this.value = slug;
            
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(() => {
                checkSubdomainAvailability(slug);
            }, 450);
        });

        function checkSubdomainAvailability(slug) {
            const msg = document.getElementById('subdomain-check-msg');
            if (!slug) {
                msg.innerHTML = '';
                return;
            }
            msg.innerHTML = 'جاري التحقق من توافر الرابط...';
            msg.style.color = '#a5b4fc';

            fetch(`/check-subdomain?subdomain=${slug}`)
                .then(res => res.json())
                .then(data => {
                    msg.innerHTML = data.message;
                    if (data.available) {
                        msg.style.color = '#34d399';
                    } else {
                        msg.style.color = '#f87171';
                    }
                })
                .catch(() => {
                    msg.innerHTML = 'خطأ في الاتصال بالخادم';
                    msg.style.color = '#f87171';
                });
        }
    </script>
</x-guest-layout>
