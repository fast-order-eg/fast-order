<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'فاست أوردر (Fast Order) | أنشئ متجرك الإلكتروني المتكامل في ثوانٍ')</title>
    <meta name="description" content="@yield('meta_description', 'المنصة الأسرع والأذكى في الوطن العربي لإدارة تجارتك الإلكترونية دون تعقيد برمجيات، مع عمولة 0% وباقات تناسب نمو عملك وتجربة مجانية لمدة 7 أيام.')">
    <meta name="keywords" content="تجارة إلكترونية, إنشاء متجر إلكتروني, منصة متاجر, فاست أوردر, Fast Order, بدون عمولة, متجر إلكتروني مصر, ربط بيكسل فيسبوك, استرجاع السلات المتروكة, تسويق إلكتروني">
    <meta name="author" content="Fast Order Platform">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Open Graph / Facebook / WhatsApp Meta Tags -->
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Fast Order — فاست أوردر" />
    <meta property="og:title" content="@yield('og_title', 'فاست أوردر (Fast Order) | منصة التجارة الإلكترونية الأسرع في الوطن العربي')" />
    <meta property="og:description" content="@yield('og_description', 'أنشئ متجرك الإلكتروني المتكامل مجاناً في أقل من 3 دقائق بدون عمولات على مبيعاتك وبتقنيات سرعة فائقة.')" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:locale" content="ar_EG" />
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'فاست أوردر | أنشئ متجرك الإلكتروني المتكامل')">
    <meta name="twitter:description" content="@yield('og_description', 'المنصة الأسرع والأذكى لإدارة تجارتك الإلكترونية بدون عمولات على المبيعات وبتقنيات حديثة.')">

    <!-- JSON-LD Structured Data Schema for Google -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Fast Order",
      "alternateName": "فاست أوردر",
      "operatingSystem": "All",
      "applicationCategory": "BusinessApplication",
      "description": "منصة متكاملة لبناء وتطوير المتاجر الإلكترونية في الوطن العربي بدون عمولات وبتقنيات سرعة فائقة.",
      "url": "https://fast-order-eg.tech",
      "offers": {
        "@type": "Offer",
        "price": "500",
        "priceCurrency": "EGP",
        "availability": "https://schema.org/InStock"
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "ratingCount": "1450"
      }
    }
    </script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f3ff',
                            100: '#e0e8ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        },
                        accent: {
                            50: '#fff1f2',
                            500: '#f43f5e',
                            600: '#e11d48',
                        },
                        dark: {
                            bg: '#08090e',
                            card: '#11131d',
                            border: '#1e2132'
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Outfit', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 3s infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-15px)' },
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 15px -5px rgba(99, 102, 241, 0.4)' },
                            '100%': { boxShadow: '0 0 30px 8px rgba(99, 102, 241, 0.8)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #08090e;
            color: #f3f4f6;
            overflow-x: hidden;
        }
        .glass-header {
            background: rgba(8, 9, 14, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(17, 19, 29, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card-hover {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .glass-card-hover:hover {
            transform: translateY(-6px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.25);
        }
        .text-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 50%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-gradient-primary {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #f43f5e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-grid-pattern {
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #08090e;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e2132;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #6366f1;
        }
        /* Focus visible indicators for WCAG 2.1 AA Compliance */
        *:focus-visible {
            outline: 3px solid #6366f1 !important;
            outline-offset: 2px !important;
        }
    </style>
</head>
<body class="bg-dark-bg text-gray-100 antialiased selection:bg-brand-500 selection:text-white" x-data="{ mobileMenuOpen: false }">
    <!-- Accessibility: Skip to main content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:right-2 bg-brand-600 text-white px-4 py-2 z-[60] rounded-lg font-bold shadow-lg">
        تخطي إلى المحتوى الرئيسي
    </a>

    <!-- Background Glow Orbs -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[600px] overflow-hidden pointer-events-none z-0">
        <div class="absolute top-[-100px] right-[10%] w-[450px] h-[450px] rounded-full bg-brand-600/20 blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] left-[5%] w-[400px] h-[400px] rounded-full bg-pink-600/15 blur-[130px] animate-pulse-slow" style="animation-delay: 2s;"></div>
        <div class="absolute top-[60%] right-[20%] w-[500px] h-[500px] rounded-full bg-purple-600/10 blur-[150px]"></div>
    </div>

    <!-- Navigation Bar -->
    <header class="glass-header sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('main.home') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-brand-600 via-indigo-500 to-pink-500 flex items-center justify-center shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform duration-300">
                    <i class="fa-solid fa-bolt text-white text-xl animate-bounce" style="animation-duration: 2s;"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-black tracking-tight text-white font-sans flex items-center gap-1.5">
                        فاست أوردر <span class="text-xs px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-400 border border-brand-500/30 font-bold">PRO</span>
                    </span>
                    <span class="text-[10px] text-gray-400 -mt-1 tracking-wider uppercase font-semibold">Fast Order Platform</span>
                </div>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-gray-300">
                <a href="{{ route('main.home') }}#features" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-star text-xs text-brand-500"></i> المميزات
                </a>
                <a href="{{ route('main.home') }}#how-it-works" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-route text-xs text-pink-500"></i> كيف تبدأ
                </a>
                <a href="{{ route('main.home') }}#pricing" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-tags text-xs text-amber-500"></i> الباقات والأسعار
                </a>
                <a href="{{ route('main.about') }}" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-xs text-indigo-400"></i> من نحن
                </a>
                <a href="{{ route('main.help') }}" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-question text-xs text-cyan-500"></i> مركز المساعدة
                </a>
                <a href="{{ route('main.contact') }}" class="hover:text-brand-400 transition-colors py-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-envelope text-xs text-rose-500"></i> اتصل بنا
                </a>
            </nav>

            <!-- Action Buttons -->
            <div class="hidden md:flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-dark-card border border-white/10 hover:border-brand-500/50 hover:bg-white/5 transition-all">
                        <i class="fa-solid fa-gauge-high ml-1.5 text-brand-400"></i> لوحة التحكم
                    </a>
                @else
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-all">
                        تسجيل الدخول
                    </a>
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-brand-600 via-indigo-600 to-pink-600 hover:from-brand-500 hover:to-pink-500 shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fa-solid fa-rocket ml-1.5"></i> ابدأ متجرك الآن
                    </a>
                @endauth
            </div>

            <!-- Mobile Header Control Center -->
            <div class="flex items-center gap-2 md:hidden">
                <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="h-10 px-3.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-brand-600 via-indigo-600 to-pink-600 shadow-md shadow-brand-500/20 active:scale-95 transition-all whitespace-nowrap flex items-center justify-center gap-1.5 shrink-0">
                    <i class="fa-solid fa-rocket text-xs"></i> ابدأ
                </a>
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="w-10 h-10 aspect-square flex items-center justify-center rounded-xl bg-dark-card border border-white/10 text-gray-300 hover:text-white focus:outline-none shrink-0 transition-all" aria-label="{{ __('القائمة الرئيسية') }}" :aria-expanded="mobileMenuOpen.toString()">
                    <i class="fa-solid text-base leading-none" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-4" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="md:hidden glass-header border-t border-white/5 px-4 pt-4 pb-6 space-y-3 absolute w-full left-0 bg-dark-bg/95 backdrop-blur-lg">
            <a href="{{ route('main.home') }}#features" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                المميزات
            </a>
            <a href="{{ route('main.home') }}#how-it-works" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                كيف تبدأ
            </a>
            <a href="{{ route('main.home') }}#pricing" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                الباقات والأسعار
            </a>
            <a href="{{ route('main.about') }}" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                من نحن
            </a>
            <a href="{{ route('main.help') }}" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                مركز المساعدة
            </a>
            <a href="{{ route('main.contact') }}" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl hover:bg-white/5 hover:text-brand-400 font-semibold text-gray-300 transition-all">
                اتصل بنا
            </a>
            <div class="pt-4 border-t border-white/5 flex flex-col gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="w-full text-center py-3 rounded-xl font-bold text-white bg-dark-card border border-white/10 hover:border-brand-500/50 hover:bg-white/5 transition-all">
                        لوحة التحكم
                    </a>
                @else
                    <a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="w-full text-center py-3 rounded-xl font-bold text-gray-300 hover:text-white hover:bg-white/5 transition-all">
                        تسجيل الدخول
                    </a>
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="w-full text-center py-3 rounded-xl font-bold text-white bg-gradient-to-r from-brand-600 via-indigo-600 to-pink-600 shadow-lg shadow-brand-500/25 transition-all">
                        ابدأ متجرك الآن
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="focus:outline-none relative z-10 min-h-[60vh] py-12">
        @yield('content')
    </main>

    <!-- Integrated Footer Section -->
    <footer class="bg-dark-card/80 border-t border-white/10 pt-16 pb-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 pb-12 border-b border-white/10">
                
                <!-- Col 1: Brand & Bio -->
                <div class="lg:col-span-4 space-y-6">
                    <a href="{{ route('main.home') }}" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-pink-600 flex items-center justify-center text-white shadow-lg shadow-brand-500/30">
                            <i class="fa-solid fa-bolt text-lg"></i>
                        </div>
                        <span class="text-2xl font-black tracking-tight text-white font-sans">
                            فاست أوردر <span class="text-xs px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-400 border border-brand-500/30 font-bold">PRO</span>
                        </span>
                    </a>
                    <p class="text-gray-400 text-sm leading-relaxed pr-2">
                        فاست أوردر هي المنصة المتكاملة لبناء وتطوير المتاجر الإلكترونية في الوطن العربي بسرعات فائقة وتقنيات ذكية، مصممة لتمكين التجار من النمو بدون عمولات أو قيود تقنية.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <a href="#" aria-label="{{ __('فيسبوك') }}" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-brand-600 border border-white/10 hover:border-brand-500 flex items-center justify-center text-gray-300 hover:text-white transition-all">
                            <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                        </a>
                        <a href="#" aria-label="{{ __('إنستغرام') }}" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-brand-600 border border-white/10 hover:border-brand-500 flex items-center justify-center text-gray-300 hover:text-white transition-all">
                            <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                        </a>
                        <a href="#" aria-label="{{ __('إكس تويتر') }}" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-brand-600 border border-white/10 hover:border-brand-500 flex items-center justify-center text-gray-300 hover:text-white transition-all">
                            <i class="fa-brands fa-x-twitter" aria-hidden="true"></i>
                        </a>
                        <a href="#" aria-label="{{ __('تيك توك') }}" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-brand-600 border border-white/10 hover:border-brand-500 flex items-center justify-center text-gray-300 hover:text-white transition-all">
                            <i class="fa-brands fa-tiktok" aria-hidden="true"></i>
                        </a>
                        <a href="#" aria-label="{{ __('لينكد إن') }}" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-brand-600 border border-white/10 hover:border-brand-500 flex items-center justify-center text-gray-300 hover:text-white transition-all">
                            <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="lg:col-span-2 space-y-4">
                    <h4 class="text-base font-bold text-white tracking-wide">روابط سريعة</h4>
                    <ul class="space-y-2.5 text-sm font-medium text-gray-400">
                        <li><a href="{{ route('main.home') }}#features" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-left text-[10px] text-brand-500"></i> المميزات</a></li>
                        <li><a href="{{ route('main.home') }}#how-it-works" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-left text-[10px] text-brand-500"></i> كيف تبدأ</a></li>
                        <li><a href="{{ route('main.home') }}#pricing" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-left text-[10px] text-brand-500"></i> الباقات والأسعار</a></li>
                        <li><a href="{{ route('main.about') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-left text-[10px] text-brand-500"></i> من نحن</a></li>
                        <li><a href="{{ route('main.help') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-left text-[10px] text-brand-500"></i> مركز المساعدة</a></li>
                    </ul>
                </div>

                <!-- Col 3: Support & Resources -->
                <div class="lg:col-span-3 space-y-4">
                    <h4 class="text-base font-bold text-white tracking-wide">المساعدة والدعم الفني</h4>
                    <ul class="space-y-2.5 text-sm font-medium text-gray-400">
                        <li><a href="{{ route('main.help') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-book text-pink-400 text-xs"></i> مركز المساعدة والشروحات</a></li>
                        <li><a href="{{ route('main.contact') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-headset text-emerald-400 text-xs"></i> التواصل مع الدعم الفني</a></li>
                        <li><a href="{{ route('main.sla') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-server text-indigo-400 text-xs"></i> اتفاقية مستوى الخدمة SLA</a></li>
                        <li><a href="{{ route('main.terms') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-file-contract text-amber-400 text-xs"></i> شروط الاستخدام</a></li>
                        <li><a href="{{ route('main.privacy') }}" class="hover:text-white transition-colors flex items-center gap-2"><i class="fa-solid fa-shield-halved text-rose-400 text-xs"></i> سياسة الخصوصية</a></li>
                    </ul>
                </div>

                <!-- Col 4: Contact & Info -->
                <div class="lg:col-span-3 space-y-4">
                    <h4 class="text-base font-bold text-white tracking-wide">تواصل مباشرة</h4>
                    <div class="space-y-3 text-sm text-gray-300">
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5">
                            <div class="w-9 h-9 rounded-lg bg-brand-500/20 text-brand-400 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">رقم الهاتف / الدعم</div>
                                <div class="font-bold text-white font-mono">01146520922</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5">
                            <div class="w-9 h-9 rounded-lg bg-pink-500/20 text-pink-400 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">البريد الإلكتروني</div>
                                <div class="font-bold text-white font-mono text-xs">support@fastorder.test</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5">
                            <div class="w-9 h-9 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">ساعات العمل</div>
                                <div class="font-bold text-white">دعم فني متواصل 24/7</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Copyright & Legal -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-semibold text-gray-400">
                <div>
                    جميع الحقوق محفوظة © {{ date('Y') }} <strong class="text-white">فاست أوردر (Fast Order)</strong>. صُنع بشغف لتمكين التجارة الإلكترونية العربية.
                </div>
                <div class="flex items-center gap-6">
                    <a href="{{ route('main.terms') }}" class="hover:text-white transition-colors">شروط الاستخدام</a>
                    <a href="{{ route('main.privacy') }}" class="hover:text-white transition-colors">سياسة الخصوصية</a>
                    <a href="{{ route('main.sla') }}" class="hover:text-white transition-colors">اتفاقية مستوى الخدمة (SLA)</a>
                </div>
            </div>

        </div>
    </footer>

    <!-- Floating Sticky WhatsApp Button -->
    <a href="{{ $whatsappContact?->action_url ?? '#' }}" 
       target="_blank" 
       rel="noopener noreferrer" 
       class="fixed bottom-6 left-6 z-50 w-14 h-14 rounded-full aspect-square flex items-center justify-center text-white shadow-2xl hover:scale-110 active:scale-95 transition-all duration-300 shadow-emerald-500/50 group"
       style="background-color: #25D366;"
       title="تواصل معنا عبر الواتساب"
       aria-label="WhatsApp">
        <i class="fa-brands fa-whatsapp text-3xl leading-none transition-transform group-hover:scale-110"></i>
    </a>

</body>
</html>
