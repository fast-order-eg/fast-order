<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم تأكيد طلبك بنجاح - {{ $storeName ?? 'Store' }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
        
        .success-animation {
            animation: bounceIn 0.8s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .checkmark {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: #059669;
            stroke-miterlimit: 10;
            margin: 10% auto;
            box-shadow: inset 0px 0px 0px #059669;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 4;
            stroke-miterlimit: 10;
            stroke: #059669;
            fill: rgba(16, 185, 129, 0.1);
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke: #ffffff;
            stroke-width: 4;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }
        
        @keyframes scale {
            0%, 100% { transform: none; }
            50% { transform: scale3d(1.1, 1.1, 1); }
        }
        
        @keyframes fill {
            100% { 
                box-shadow: inset 0px 0px 0px 30px #059669;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            }
        }
        
        .copy-btn {
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .copied {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            border-color: #059669 !important;
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Success Animation -->
        <div class="text-center mb-8 success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" fill="none" cx="26" cy="26" r="25"/>
                <path class="checkmark__check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        
        <!-- Success Message Card -->
        <div class="bg-white rounded-3xl shadow-xl p-8 mb-6 fade-in">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">
                    🎉 مبروك! تم تنفيذ طلبك بنجاح
                </h1>
                
                <p class="text-gray-600 text-lg mb-8">
                    شكراً لك على ثقتك بنا. تم استلام طلبك وسيتم معالجته في أقرب وقت ممكن.
                </p>
                
                <!-- Reference Number Section -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">
                        الرقم المرجعي لطلبك
                    </h3>
                    
                    <div class="flex items-center justify-center gap-3 bg-white rounded-xl p-4 shadow-sm">
                        <span class="text-3xl font-bold text-indigo-600 tracking-wider" id="referenceNumber">
                            {{ $order->reference_number }}
                        </span>
                        <button 
                            onclick="copyReference()"
                            class="copy-btn bg-gray-100 hover:bg-indigo-100 text-gray-700 px-4 py-2 rounded-lg border border-gray-200 transition-all duration-300"
                            id="copyBtn"
                        >
                            <i class="fas fa-copy"></i>
                            <span class="ml-2">نسخ</span>
                        </button>
                    </div>
                    
                    <p class="text-sm text-gray-500 mt-3">
                        احتفظ بهذا الرقم للمراجعة أو الاستفسار عن طلبك
                    </p>
                </div>
                
                <!-- Contact Info -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-8">
                    <div class="flex items-center justify-center mb-3">
                        <i class="fas fa-phone text-amber-600 text-xl ml-2"></i>
                        <h3 class="text-lg font-semibold text-amber-800">
                            سيتم التواصل معك من الإدارة لتأكيد الطلب
                        </h3>
                    </div>
                    <p class="text-amber-700 text-sm">
                        سيقوم فريق خدمة العملاء بالتواصل معك خلال 24 ساعة لتأكيد تفاصيل الطلب وموعد التسليم
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="/shop" 
                       class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 hover:shadow-lg transform hover:-translate-y-1">
                        <i class="fas fa-home ml-2"></i>
                        العودة للصفحة الرئيسية
                    </a>
                    
                    <a href="/shop/products.html" 
                       class="flex-1 bg-white border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50 font-semibold py-4 px-6 rounded-xl transition-all duration-300">
                        <i class="fas fa-shopping-bag ml-2"></i>
                        متابعة التسوق
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center text-gray-500 text-sm">
            <p>شكراً لاختيارك {{ $storeName ?? 'Store' }}</p>
            <p class="mt-1">نقدر ثقتك بنا ونتطلع لخدمتك مرة أخرى</p>
        </div>
    </div>

    <script>
        function copyReference() {
            const referenceNumber = document.getElementById('referenceNumber').textContent.trim();
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(referenceNumber).then(function() {
                // تغيير شكل الزر عند النسخ
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fas fa-check"></i><span class="ml-2">تم النسخ!</span>';
                
                // إعادة الزر لحالته الطبيعية بعد 3 ثوان
                setTimeout(function() {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i><span class="ml-2">نسخ</span>';
                }, 3000);
            }).catch(function(err) {
                console.error('فشل في نسخ الرقم المرجعي: ', err);
                // fallback للمتصفحات القديمة
                const textArea = document.createElement('textarea');
                textArea.value = referenceNumber;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fas fa-check"></i><span class="ml-2">تم النسخ!</span>';
                
                setTimeout(function() {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i><span class="ml-2">نسخ</span>';
                }, 3000);
            });
        }
        
        // إضافة بعض الإيفكتات التفاعلية
        document.addEventListener('DOMContentLoaded', function() {
            // مسح السلة بعد النجاح
            if (window.location.search.includes('clear_cart=1')) {
                localStorage.removeItem('bird_cart');
                // إرسال event لتحديث عداد السلة في نافذات أخرى
                window.dispatchEvent(new StorageEvent('storage', {
                    key: 'bird_cart',
                    oldValue: null,
                    newValue: null,
                    url: window.location.href
                }));
            }
            
            // إضافة تأثير hover للعناصر التفاعلية
            const buttons = document.querySelectorAll('a, button');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
    <script src="/shop/shared.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var totalVal = {{ (float) ($order->total ?? 0) }};
            var orderId = {{ (int) ($order->id ?? 0) }};
            var orderRef = "{{ addslashes($order->reference_number ?? '') }}";
            var items = {!! json_encode($order->items ?? []) !!};

            if (typeof window.trackPurchaseEvent === 'function') {
                window.trackPurchaseEvent(totalVal, items, orderId, orderRef);
            }
        });
    </script>
</body>
</html>