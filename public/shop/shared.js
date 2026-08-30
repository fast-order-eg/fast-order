// ======================================
// Meta & TikTok Pixel - يتحمل تلقائياً من الـ settings
// ======================================
(function() {
  function initFBPixel(pixelIdRaw) {
    if (!pixelIdRaw) return;
    var ids = String(pixelIdRaw).split(/[\r\n,]+/).map(function(s){ return s.trim(); }).filter(Boolean);
    if (!ids.length) return;

    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');

    ids.forEach(function(pid) {
      fbq('init', pid);
    });
    fbq('track', 'PageView');
  }

  function initTTPixel(pixelIdRaw) {
    if (!pixelIdRaw) return;
    var ids = String(pixelIdRaw).split(/[\r\n,]+/).map(function(s){ return s.trim(); }).filter(Boolean);
    if (!ids.length) return;

    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
      ids.forEach(function(pid) {
        ttq.load(pid);
      });
      ttq.page();
    }(window, document, 'ttq');
  }

  function initSnapPixel(pixelId) {
    if (!pixelId) return;
    (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
    a.queue=[];var s='script';r=t.createElement(s);r.async=!0;
    r.src=n;var u=t.getElementsByTagName(s)[0];
    u.parentNode.insertBefore(r,u);})(window,document,'https://sc-static.net/scevent.min.js');
    snaptr('init', pixelId);
    snaptr('track', 'PAGE_VIEW');
  }

  // دالة موحدة لتتبع حدث الشراء (Purchase) لكل البيكسلات (فيسبوك، تيك توك، سناب شات، وجوجل أناليتكس)
  window.trackPurchaseEvent = function(totalValue, items, orderId) {
    var val = Number(totalValue) || 0;
    var contentIds = (items || []).map(function(i) { return String(i.id || i.product_id || ''); });

    // 1. Facebook Pixel
    if (typeof fbq !== 'undefined') {
      try {
        var fbParams = {
          value: val,
          currency: 'EGP',
          content_type: 'product',
          content_ids: contentIds
        };
        if (orderId) {
          fbq('track', 'Purchase', fbParams, { eventID: String(orderId) });
        } else {
          fbq('track', 'Purchase', fbParams);
        }
      } catch (e) { console.error('FB Purchase Error', e); }
    }

    // 2. TikTok Pixel
    if (typeof ttq !== 'undefined') {
      try {
        ttq.track('PlaceAnOrder', {
          value: val,
          currency: 'EGP'
        });
        ttq.track('CompletePayment', {
          value: val,
          currency: 'EGP'
        });
      } catch (e) { console.error('TikTok Purchase Error', e); }
    }

    // 3. Snapchat Pixel
    if (typeof snaptr !== 'undefined') {
      try {
        snaptr('track', 'PURCHASE', {
          price: val,
          currency: 'EGP',
          transaction_id: String(orderId || '')
        });
      } catch (e) { console.error('Snapchat Purchase Error', e); }
    }

    // 4. Google Analytics
    if (typeof gtag !== 'undefined') {
      try {
        gtag('event', 'purchase', {
          transaction_id: String(orderId || ''),
          value: val,
          currency: 'EGP'
        });
      } catch (e) { console.error('GA Purchase Error', e); }
    }
  };

  // نجيب الـ settings من الـ API ونحمّل الـ Pixels ونحدث الواتس العائم تلقائياً
  fetch('/public-api/settings')
    .then(function(r){ return r.json(); })
    .then(function(json){
      var data = json && json.data ? json.data : {};
      // حفظ الـ settings عشان باقي الصفحة تستخدمهم
      window.__SITE_SETTINGS__ = data;
      renderCustomMenus();

      if (data.facebook_pixel_id) {
        initFBPixel(String(data.facebook_pixel_id).trim());
      }
      if (data.tiktok_pixel_id) {
        initTTPixel(String(data.tiktok_pixel_id).trim());
      }
      if (data.snapchat_pixel_id) {
        initSnapPixel(String(data.snapchat_pixel_id).trim());
      }

      // تحديث رقم زر الواتساب العائم في كل صفحات المتجر أوتوماتيكياً
      if (data.whatsapp) {
        var cleanWa = String(data.whatsapp).replace(/[^0-9]/g, '');
        if (cleanWa.startsWith('01') && cleanWa.length === 11) cleanWa = '2' + cleanWa;
        var waBtns = document.querySelectorAll('a.wapp-float, a[href*="wa.me"]');
        waBtns.forEach(function(el) {
          el.href = 'https://wa.me/' + cleanWa;
        });
      }

      // تحديث زر اتصل الآن أيضاً
      if (data.phone) {
        var callBtns = document.querySelectorAll('a.call-now, a[href^="tel:"]');
        callBtns.forEach(function(el) {
          el.href = 'tel:' + data.phone;
        });
      }
    })
    .catch(function(){});
})();

// ======================================
// Localization / Translation Core
// ======================================
const translationMap = {
  // Navigation & General
  "الرئيسية": "Home",
  "الأقسام": "Categories",
  "المنتجات": "Products",
  "اتصل بنا": "Contact Us",
  "اتصل الآن": "Call Now",
  
  // Section Headings & General Buttons
  "أفضل العروض والخصومات": "Best Offers & Discounts",
  "أحدث المنتجات": "Latest Products",
  "عرض الكل": "View All",
  "شحن مجاناً": "Free Shipping",
  "إضافة إلى السلة": "Add to Cart",
  "تمت الإضافة": "Added",
  "جميع الحقوق محفوظة لـ": "All Rights Reserved to",
  "بيرد تكنولوجي": "Bird Technology",
  "لا توجد منتجات لعرضها.": "No products to display.",
  "لا توجد عروض متاحة حالياً.": "No offers available currently.",
  "تعذر تحميل العروض الآن.": "Failed to load offers.",
  "تعذر تحميل المنتجات الآن.": "Failed to load products.",
  
  // Product details
  "اللون": "Color",
  "المقاس": "Size",
  "الكمية": "Quantity",
  "الوصف": "Description",
  "التفاصيل": "Details",
  "تحديد المقاس": "Select Size",
  "تحديد اللون": "Select Color",
  "شراء الآن": "Buy Now",
  
  // Cart
  "سلة المشتريات": "Shopping Cart",
  "السلة فارغة": "Your cart is empty",
  "متابعة التسوق": "Continue Shopping",
  "إجمالي السلة": "Cart Total",
  "الذهاب للدفع": "Proceed to Checkout",
  "سعر المنتج": "Product Price",
  "السعر": "Price",
  "المنتج": "Product",
  "إلغاء": "Cancel",
  
  // Checkout
  "إتمام الطلب": "Complete Order",
  "معلومات التوصيل": "Delivery Information",
  "الاسم الكامل": "Full Name",
  "رقم الهاتف": "Phone Number",
  "رقم هاتف إضافي": "Alternative Phone Number",
  "المحافظة": "Governorate",
  "العنوان بالتفصيل": "Detailed Address",
  "الشحن والتوصيل": "Shipping & Delivery",
  "الدفع عند الاستلام (COD)": "Cash on Delivery (COD)",
  "مراجعة الطلب": "Review Order",
  "تفاصيل الطلب": "Order Details",
  "سعر الشحن": "Shipping Cost",
  "الإجمالي": "Total",
  "طلبك قيد المعالجة...": "Your order is processing...",
  "رجاء الانتظار": "Please wait",
  "ملاحظات إضافية للطلب (اختياري)": "Alternative notes (optional)",
  
  // Success page
  "شكراً لك! تم استلام طلبك بنجاح.": "Thank you! Your order was received successfully.",
  "رقم الطلب الخاص بك هو:": "Your order number is:",
  "سيقوم فريقنا بالتواصل معك قريباً لتأكيد الطلب وشحنه.": "Our team will contact you soon to confirm and ship your order.",
  "تفاصيل الطلب المستلم:": "Received Order Details:",
  "اسم العميل:": "Customer Name:",
  "العنوان:": "Address:",
  "تاريخ الطلب:": "Order Date:",
  "مجموع المنتجات:": "Products Total:",
  "تكلفة الشحن:": "Shipping Cost:",
  "المجموع الكلي:": "Grand Total:",
  "تصفح المزيد من المنتجات": "Browse more products",
  "طلب ناجح": "Successful Order",
  
  // Filters & Sorting (Phase 53)
  "ترتيب حسب": "Sort By",
  "ترتيب حسب: الأحدث": "Sort By: Newest",
  "ترتيب حسب: الأرخص سعراً": "Sort By: Cheapest",
  "ترتيب حسب: الأغلى سعراً": "Sort By: Most Expensive",
  "ترتيب حسب: الأكثر خصماً": "Sort By: Most Discounted",
  "الأحدث (الافتراضي)": "Newest (Default)",
  "الأرخص سعراً": "Cheapest",
  "الأغلى سعراً": "Most Expensive",
  "الأكثر خصماً": "Most Discounted",
  "السعر الأقصى:": "Max Price:",
  "الكل": "All",
  "تحميل المزيد": "Load More",
  "جاري التحميل...": "Loading..."
};

function getStorefrontLang() {
  return localStorage.getItem('bird_lang') || 'ar';
}

function translateDOM() {
  const lang = getStorefrontLang();
  if (lang === 'ar') return; // Default is Arabic

  // Translate all text nodes in common elements
  const selectors = 'a, button, h1, h2, h3, h4, h5, h6, span, p, label, td, th, option, div, input[placeholder]';
  document.querySelectorAll(selectors).forEach(el => {
    // 1. Translate text nodes directly if they match
    for (let child of el.childNodes) {
      if (child.nodeType === Node.TEXT_NODE) {
        const val = child.nodeValue.trim();
        if (translationMap[val]) {
          child.nodeValue = child.nodeValue.replace(val, translationMap[val]);
        }
      }
    }
    // 2. Translate placeholders for inputs
    if (el.tagName === 'INPUT' && el.placeholder) {
      const val = el.placeholder.trim();
      if (translationMap[val]) {
        el.placeholder = translationMap[val];
      }
    }
  });

  // Specific overrides for input value buttons
  document.querySelectorAll('input[type="submit"], input[type="button"]').forEach(btn => {
    const val = btn.value.trim();
    if (translationMap[val]) {
      btn.value = translationMap[val];
    }
  });
}

function injectStorefrontLangSwitcher() {
  const navContainer = document.querySelector('.header .nav');
  if (!navContainer) return;

  const currentLang = getStorefrontLang();
  
  if (document.getElementById('langToggleBtn')) return;

  const btn = document.createElement('a');
  btn.id = 'langToggleBtn';
  btn.href = '#';
  btn.className = 'lang-nav-link';
  btn.style.cssText = `
    display: none !important;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
    font-family: var(--font-family, 'Cairo', sans-serif);
    color: var(--primary-color, #4f46e5);
    border: 1px solid var(--primary-color, #4f46e5);
    background: var(--primary-light, #eef2ff);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
  `;
  btn.innerHTML = `<i class="fa-solid fa-globe"></i> ${currentLang === 'ar' ? 'English (EN)' : 'العربية (AR)'}`;
  btn.title = currentLang === 'ar' ? 'English' : 'العربية';

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    const newLang = currentLang === 'ar' ? 'en' : 'ar';
    localStorage.setItem('bird_lang', newLang);
    
    // Sync with backend session
    fetch('/lang/' + newLang)
      .finally(() => {
        window.location.reload();
      });
  });

  navContainer.appendChild(btn);
}

function createEl(tag, cls){const el=document.createElement(tag); if(cls) el.className=cls; return el;}

function formatEGP(amount){
  const val = Math.round(Number(amount||0));
  const currentLang = getStorefrontLang();
  if (currentLang === 'en') {
    return new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(val) + ' EGP';
  }
  return new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(val) + ' جنيه';
}

function formatPrice(after, before){
  const now = Math.round(Number(after ?? 0));
  const priceNow = formatEGP(now);
  const beforeVal = (before!=null)? Math.round(Number(before)) : null;
  const hasDiscount = beforeVal && beforeVal>now;
  
  if (hasDiscount) {
    return `<div class='price-block'>
      <div class='price-now'>${priceNow}</div>
      <div class='price-row'><span class='old'>${formatEGP(beforeVal)}</span></div>
    </div>`;
  } else {
    return `<div class='price-block'><div class='price-now'>${priceNow}</div></div>`;
  }
}

function productCard(p){
  const card = createEl('div','card');
  card.style.position = 'relative';
  
  const link = createEl('a'); link.href = `/shop/product.html?id=${p.id}`; link.style.textDecoration='none'; link.style.color='inherit';
  const img = createEl('img'); img.src = p.image_url || 'https://dummyimage.com/600x400/e5e7eb/9ca3af.png&text=No+Image'; img.alt=p.name||''; link.appendChild(img);
  
  // بادج الخصم والشحن المجاني مع فحص وجود الاثنين معاً
  const nowVal = Math.round(Number(p.price_after ?? 0));
  const beforeVal = p.price_before != null ? Math.round(Number(p.price_before)) : null;
  const hasDiscount = Boolean(beforeVal && beforeVal > nowVal && Math.round(((beforeVal - nowVal) / beforeVal) * 100) > 0);
  const hasFreeShipping = (p.shipping_type === 'free');

  if (hasDiscount && hasFreeShipping) {
    card.classList.add('has-dual-badges');
  }

  if (hasDiscount) {
    const pct = Math.round(((beforeVal - nowVal) / beforeVal) * 100);
    const discountBadge = createEl('div', 'discount-badge');
    const currentLang = getStorefrontLang();
    discountBadge.textContent = currentLang === 'en' ? `${pct}% OFF` : `خصم ${pct}%`;
    card.appendChild(discountBadge);
  }

  // إضافة بادج الشحن المجاني
  if (hasFreeShipping) {
    const freeShippingBadge = createEl('div', 'free-shipping-badge');
    const currentLang = getStorefrontLang();
    freeShippingBadge.textContent = currentLang === 'en' ? 'Free Shipping' : 'شحن مجاناً';
    card.appendChild(freeShippingBadge);
  }
  
  card.appendChild(link);
  const body = createEl('div','body');
  const titleLink = createEl('a'); titleLink.href = `/shop/product.html?id=${p.id}`; titleLink.style.textDecoration='none'; titleLink.style.color='inherit';
  const title = createEl('h3','title'); title.textContent = p.name || ''; titleLink.appendChild(title);
  const price = createEl('div'); price.innerHTML = formatPrice(p.price_after, p.price_before);
  
  const addBtn = createEl('button','btn btn-primary btn-add');
  const currentLang = getStorefrontLang();
  const addText = currentLang === 'en' ? 'Add to Cart' : 'أضف إلى السلة';
  addBtn.textContent = addText;
  addBtn.dataset.id=p.id; addBtn.dataset.name=p.name||''; addBtn.dataset.price=p.price_after||0; addBtn.dataset.image=p.image_url||''; addBtn.dataset.shipping=p.shipping_type||'free'; addBtn.dataset.priceBefore=p.price_before||0; addBtn.dataset.hasSizes=p.sizes && p.sizes.length ? 'yes':'no'; addBtn.dataset.hasColors=p.colors && p.colors.length ? 'yes':'no';
  
  body.append(titleLink, price, addBtn); card.appendChild(body);
  return card;
}

function enableDragScroll(sliderEl) {
  let isDown = false;
  let startX = 0;
  let scrollLeft = 0;
  let moved = false;

  sliderEl.addEventListener('mousedown', (e) => {
    isDown = true;
    moved = false;
    sliderEl.classList.add('dragging');
    startX = e.pageX - sliderEl.offsetLeft;
    scrollLeft = sliderEl.scrollLeft;
  });

  window.addEventListener('mouseleave', () => {
    if (!isDown) return;
    isDown = false;
    sliderEl.classList.remove('dragging');
  });

  window.addEventListener('mouseup', () => {
    if (!isDown) return;
    isDown = false;
    sliderEl.classList.remove('dragging');
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - sliderEl.offsetLeft;
    const walk = (x - startX) * 1.5;
    if (Math.abs(walk) > 5) {
      moved = true;
    }
    sliderEl.scrollLeft = scrollLeft - walk;
  });

  sliderEl.addEventListener('click', (e) => {
    if (moved) {
      e.preventDefault();
      e.stopPropagation();
      moved = false;
    }
  }, true);
}

function makeProductSlider(gridEl, isCategories = false) {
  if (!gridEl) return;
  gridEl.classList.add('product-slider-track');
  
  let wrapper = gridEl.parentElement;
  if (!wrapper || !wrapper.classList.contains('product-slider-wrapper')) {
    wrapper = document.createElement('div');
    wrapper.className = 'product-slider-wrapper';
    gridEl.parentNode.insertBefore(wrapper, gridEl);
    wrapper.appendChild(gridEl);
    
    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'slider-arrow-btn prev-arrow';
    prevBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    prevBtn.setAttribute('aria-label', 'السابق');
    
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'slider-arrow-btn next-arrow';
    nextBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    nextBtn.setAttribute('aria-label', 'التالي');
    
    prevBtn.onclick = (e) => {
      e.preventDefault();
      const scrollAmt = gridEl.clientWidth * 0.75;
      gridEl.scrollBy({ left: scrollAmt, behavior: 'smooth' });
    };
    nextBtn.onclick = (e) => {
      e.preventDefault();
      const scrollAmt = gridEl.clientWidth * 0.75;
      gridEl.scrollBy({ left: -scrollAmt, behavior: 'smooth' });
    };
    
    wrapper.appendChild(prevBtn);
    wrapper.appendChild(nextBtn);
    
    enableDragScroll(gridEl);
    
    let autoTimer = null;
    let isInteracting = false;
    
    function startAuto() {
      if (autoTimer) clearInterval(autoTimer);
      autoTimer = setInterval(() => {
        if (!isInteracting && gridEl && gridEl.children.length > 2) {
          const maxScroll = gridEl.scrollWidth - gridEl.clientWidth;
          const currentScroll = Math.abs(gridEl.scrollLeft);
          if (currentScroll >= maxScroll - 20) {
            gridEl.scrollTo({ left: 0, behavior: 'smooth' });
          } else {
            gridEl.scrollBy({ left: -240, behavior: 'smooth' });
          }
        }
      }, 3500);
    }
    
    wrapper.addEventListener('mouseenter', () => { isInteracting = true; });
    wrapper.addEventListener('mouseleave', () => { isInteracting = false; });
    wrapper.addEventListener('touchstart', () => { isInteracting = true; }, { passive: true });
    wrapper.addEventListener('touchend', () => { setTimeout(() => { isInteracting = false; }, 2500); }, { passive: true });
    
    startAuto();
  }
}

function renderProducts(root, items){
  root.innerHTML='';
  const currentLang = getStorefrontLang();
  if(!items || !items.length){ 
    root.innerHTML='<p class="muted">' + (currentLang === 'en' ? 'No products to display.' : 'لا توجد منتجات لعرضها.') + '</p>'; 
    return; 
  }
  const frag = document.createDocumentFragment(); 
  items.forEach(p=> frag.appendChild(productCard(p))); 
  root.appendChild(frag);

  // Auto-convert to horizontal slider on Homepage only
  const isHomepage = window.location.pathname.endsWith('index.html') || window.location.pathname === '/shop' || window.location.pathname === '/shop/' || !window.location.pathname.includes('.html');
  const isTargetGrid = root.id === 'offersGrid' || root.id === 'bestSellersGrid' || root.id === 'productsGrid' || root.id === 'homeProductsGrid';
  if (isHomepage && isTargetGrid && items.length > 0) {
    makeProductSlider(root, false);
  }
}

function categoryCard(c){
  const card = createEl('div','card category-card');
  card.style.cursor = 'pointer';
  card.addEventListener('click', () => {
    window.location.href = `/shop/category-products.html?id=${c.id}&name=${encodeURIComponent(c.name || '')}`;
  });
  
  const img = createEl('img'); 
  img.src = c.image_url || 'https://dummyimage.com/600x400/e5e7eb/9ca3af.png&text=No+Image'; 
  img.alt = c.name || ''; 
  card.appendChild(img);
  
  const body = createEl('div','body');
  const title = createEl('h3','title'); 
  title.textContent = c.name || '';
  
  body.append(title); 
  card.appendChild(body);
  
  return card;
}

function renderCategories(root, items){
  root.innerHTML='';
  const currentLang = getStorefrontLang();
  if(!items || !items.length){ 
    root.innerHTML='<p class="muted">' + (currentLang === 'en' ? 'No categories to display.' : 'لا توجد أقسام لعرضها.') + '</p>'; 
    return; 
  }
  const frag = document.createDocumentFragment();
  items.forEach(c=> frag.appendChild(categoryCard(c))); 
  root.appendChild(frag);

  // Auto-convert to horizontal slider on Homepage only
  const isHomepage = window.location.pathname.endsWith('index.html') || window.location.pathname === '/shop' || window.location.pathname === '/shop/' || !window.location.pathname.includes('.html');
  const isTargetGrid = root.id === 'categoriesGrid';
  if (isHomepage && isTargetGrid && items.length > 2) {
    makeProductSlider(root, true);
  }
}

function renderSkeletonProducts(root, count = 4) {
  if (!root) return;
  root.innerHTML = '';
  const frag = document.createDocumentFragment();
  for (let i = 0; i < count; i++) {
    const card = document.createElement('div');
    card.className = 'card product-card-skeleton';
    card.innerHTML = `
      <div class="skeleton skeleton-img"></div>
      <div class="skeleton-body">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-price"></div>
        <div class="skeleton skeleton-btn"></div>
      </div>
    `;
    frag.appendChild(card);
  }
  root.appendChild(frag);
}

function renderSkeletonCategories(root, count = 4) {
  if (!root) return;
  root.innerHTML = '';
  const frag = document.createDocumentFragment();
  for (let i = 0; i < count; i++) {
    const card = document.createElement('div');
    card.className = 'card product-card-skeleton';
    card.innerHTML = `
      <div class="skeleton skeleton-img"></div>
      <div class="skeleton-body" style="padding: 10px; text-align: center;">
        <div class="skeleton skeleton-title" style="margin: 0 auto; width: 60%;"></div>
      </div>
    `;
    frag.appendChild(card);
  }
  root.appendChild(frag);
}

window.renderProducts = renderProducts; 
window.renderCategories = renderCategories;
window.renderSkeletonProducts = renderSkeletonProducts;
window.renderSkeletonCategories = renderSkeletonCategories;

// Mobile menu toggling
document.addEventListener('DOMContentLoaded', ()=>{
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.nav');
  if(toggle && nav){
    toggle.addEventListener('click', (e)=>{ 
      e.stopPropagation();
      const isOpen = nav.classList.toggle('open'); 
      toggle.classList.toggle('open', isOpen);
    });
    nav.querySelectorAll('a').forEach(a=> a.addEventListener('click', ()=> {
      nav.classList.remove('open');
      toggle.classList.remove('open');
    }));
    document.addEventListener('click', (e)=>{
      if (nav.classList.contains('open')) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
          nav.classList.remove('open');
          toggle.classList.remove('open');
        }
      }
    });
  }
  
  // Inject switcher and translate DOM
  injectStorefrontLangSwitcher();
  translateDOM();
  renderCustomMenus();
  
  // Cart count badge
  updateCartCount();
  window.addEventListener('storage', (e)=>{ if(e.key==='bird_cart') updateCartCount(); });
  
  // Cart: add buttons
  document.body.addEventListener('click', (e)=>{
    const btn = e.target.closest('.btn-add');
    if(btn){
      const hasSizes = btn.dataset.hasSizes === 'yes';
      const hasColors = btn.dataset.hasColors === 'yes';

      if (hasSizes || hasColors) {
        if (typeof openQuickAddModal === 'function') {
          openQuickAddModal(btn.dataset.id);
        } else {
          window.location.href = `/shop/product.html?id=${btn.dataset.id}`;
        }
        return;
      }

      addToCart({
        id: +btn.dataset.id,
        name: btn.dataset.name,
        price: Number(btn.dataset.price)||0,
        image: btn.dataset.image||null,
        shipping_type: btn.dataset.shipping||'free',
        price_before: Number(btn.dataset.priceBefore)||0,
        qty: 1,
      });
      const currentLang = getStorefrontLang();
      btn.textContent = currentLang === 'en' ? 'Added' : 'تمت الإضافة';
      setTimeout(()=> btn.textContent = currentLang === 'en' ? 'Add to Cart' : 'أضف إلى السلة', 1200);
      updateCartCount();
      
      if(typeof fbq !== 'undefined') {
        fbq('track', 'AddToCart', {
          content_name: btn.dataset.name,
          content_ids: [btn.dataset.id],
          content_type: 'product',
          value: Number(btn.dataset.price)||0,
          currency: 'EGP'
        });
      }
      if (typeof ttq !== 'undefined') {
        ttq.track('AddToCart', {
          contents: [{
            content_id: btn.dataset.id,
            content_name: btn.dataset.name,
            quantity: 1,
            price: Number(btn.dataset.price)||0
          }],
          content_type: 'product',
          value: Number(btn.dataset.price)||0,
          currency: 'EGP'
        });
      }
    }
  });
  
  // Search overlay trigger
  const sBtn = document.querySelector('.search-trigger');
  if(sBtn){ sBtn.addEventListener('click', (e)=>{ e.preventDefault(); openSearchOverlay(); }); }
});

// Simple cart utilities (localStorage)
function getCart(){ try{ return JSON.parse(localStorage.getItem('bird_cart')||'[]'); }catch{ return []; } }
function saveCart(items){ localStorage.setItem('bird_cart', JSON.stringify(items)); }
function addToCart(item){
  const items = getCart();
  const idx = items.findIndex(x=> x.id===item.id && (x.selectedSize||null)===(item.selectedSize||null) && (x.selectedColor||null)===(item.selectedColor||null) && JSON.stringify(x.options||{})===JSON.stringify(item.options||{}));
  if(idx>-1){ items[idx].qty += item.qty||1; } else { items.push(item); }
  saveCart(items);
}
function clearCart() {
  saveCart([]);
}
function cartCount(){ return getCart().reduce((s,x)=> s+(Number(x.qty)||0), 0); }
function updateCartCount(){
  const badge = document.getElementById('cartCount');
  if(badge){ const c = cartCount(); badge.textContent = c>0? String(c):''; badge.style.display = c>0? 'inline-flex':'none'; }
}
window.BirdCart = {getCart, getItems: getCart, saveCart, addToCart, clearCart, cartCount, updateCartCount, formatEGP, formatPrice};

// Global search overlay
let SEARCH_DATA = null;
function openSearchOverlay(){ ensureSearchOverlay(); document.getElementById('searchOverlay').classList.add('open'); const input = document.getElementById('globalSearchInput'); input.value=''; input.focus(); loadSearchData().then(()=> renderSearchResults('')); }
function closeSearchOverlay(){ const ov = document.getElementById('searchOverlay'); if(ov) ov.classList.remove('open'); }
function ensureSearchOverlay(){
  if(document.getElementById('searchOverlay')) return;
  const wrap = document.createElement('div');
  wrap.id='searchOverlay';
  wrap.className='search-overlay';
  const currentLang = getStorefrontLang();
  const searchPl = currentLang === 'en' ? 'Search for products or categories...' : 'ابحث عن منتجات أو أقسام...';
  const closeText = currentLang === 'en' ? 'Close' : 'إغلاق';
  wrap.innerHTML = `
    <div class="search-box">
      <div class="search-head">
        <input id="globalSearchInput" type="search" placeholder="${searchPl}" />
        <button class="icon-round" id="searchClose" aria-label="${closeText}">✕</button>
      </div>
      <div id="searchResults" class="search-results"></div>
    </div>`;
  document.body.appendChild(wrap);
  wrap.addEventListener('click', (e)=>{ if(e.target===wrap) closeSearchOverlay(); });
  document.getElementById('searchClose').addEventListener('click', closeSearchOverlay);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeSearchOverlay(); });
  const input = document.getElementById('globalSearchInput');
  input.addEventListener('input', debounce(()=> renderSearchResults(input.value.trim()), 150));
}
function loadSearchData(){
  if(SEARCH_DATA) return Promise.resolve(SEARCH_DATA);
  return Promise.all([
    fetch('/public-api/products').then(r=>r.json()).then(j=> j.data||[]),
    fetch('/public-api/categories').then(r=>r.json()).then(j=> j.data||[])
  ]).then(([products,categories])=>{ SEARCH_DATA = {products, categories}; return SEARCH_DATA; });
}
function renderSearchResults(q){
  const box = document.getElementById('searchResults'); if(!box) return;
  const currentLang = getStorefrontLang();
  if(!SEARCH_DATA){ box.innerHTML = '<p class="muted">' + (currentLang === 'en' ? 'Loading...' : 'جار التحميل...') + '</p>'; return; }
  const query = (q||'').toLowerCase();
  const results = [];
  
  if(!query) {
    SEARCH_DATA.products.slice(0, 5).forEach(p=>{
      results.push({type:'product', id:p.id, title:p.name, image:p.image_url, price:p.price_after, before:p.price_before, shipping_type:p.shipping_type});
    });
  } else {
    SEARCH_DATA.products.forEach(p=>{
      const hay = [p.name,p.description,p.category].join(' ').toLowerCase();
      if(hay.includes(query)) results.push({type:'product', id:p.id, title:p.name, image:p.image_url, price:p.price_after, before:p.price_before, shipping_type:p.shipping_type});
    });
    SEARCH_DATA.categories.forEach(c=>{
      const hay = [c.name,c.description].join(' ').toLowerCase();
      if(hay.includes(query)) results.push({type:'category', id:c.id, title:c.name, subtitle: (currentLang === 'en' ? 'Category' : 'قسم'), image:c.image_url});
    });
  }
  
  if(!results.length){ box.innerHTML = '<p class="muted">' + (currentLang === 'en' ? 'No results found.' : 'لا توجد نتائج.') + '</p>'; return; }
  box.innerHTML = results.slice(0,30).map(r=>{
    const link = r.type==='product' ? `/shop/product.html?id=${r.id}` : `/shop/products.html?q=${encodeURIComponent(r.title)}`;
    
    let priceAndBadges = '';
    if(r.type==='product') {
      const now = Math.round(Number(r.price ?? 0));
      const beforeVal = (r.before!=null)? Math.round(Number(r.before)) : null;
      const hasDiscount = beforeVal && beforeVal>now;
      const pct = hasDiscount ? Math.round(((beforeVal-now)/beforeVal)*100) : 0;
      
      let priceHTML = '';
      if (hasDiscount) {
        priceHTML = `<div class="price-now">${formatEGP(now)} <span class="badge-discount">-${pct}%</span></div>
                     <div class="price-old" style="text-decoration: line-through; color: #999; font-size: 0.9em;">${formatEGP(beforeVal)}</div>`;
      } else {
        priceHTML = `<div class="price-now">${formatEGP(now)}</div>`;
      }
      
      let shippingBadge = '';
      if(r.shipping_type === 'free') {
        const text = currentLang === 'en' ? 'Free Shipping' : 'شحن مجاناً';
        shippingBadge = `<span class="shipping-badge" style="background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7em; margin-right: 4px;">${text}</span>`;
      }
      
      priceAndBadges = `<div class="muted">${shippingBadge}${priceHTML}</div>`;
    }
    
    const subtitle = r.type==='category' ? `<div class="subtitle">${r.subtitle||''}</div>` : '';
    
    return `<a class="search-item" href="${link}">
      ${r.image?`<img src="${r.image}" alt="${r.title}">`:'<div class="img-ph"></div>'}
      <div><div class="title">${r.title}</div>${subtitle}${priceAndBadges}</div>
    </a>`;
  }).join('');
}
function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

// ======================================
// Homepage Builder Sections Renderer
// ======================================
function renderHomepageLayout(settings) {
  const sections = settings.homepage_sections || [
    {id: 'hero_slider', enabled: true, title: 'البانر الإعلاني', title_en: 'Hero Slider'},
    {id: 'featured_categories', enabled: true, title: 'الأقسام المميزة', title_en: 'Featured Categories'},
    {id: 'best_offers', enabled: true, title: 'أفضل العروض والخصومات', title_en: 'Best Offers & Discounts'},
    {id: 'best_sellers', enabled: true, title: 'الأكثر طلباً ومبيعاً', title_en: 'Best Sellers'},
    {id: 'latest_products', enabled: true, title: 'أحدث المنتجات', title_en: 'Latest Products'}
  ];

  const wrapper = document.getElementById('homepageSections');
  if (!wrapper) return;

  const lang = getStorefrontLang();

  sections.forEach(sec => {
    const el = document.getElementById('sec-' + sec.id);
    if (!el) return;

    if (sec.enabled) {
      el.style.display = '';
      
      // Update Title dynamically if customized
      const titleEl = document.getElementById('title-' + sec.id);
      if (titleEl) {
        const titleText = lang === 'en' ? (sec.title_en || sec.title) : (sec.title || sec.title_en);
        if (titleText) {
          titleEl.textContent = titleText;
        }
      }
      
      // Append to wrapper to sort in correct order
      wrapper.appendChild(el);
    } else {
      el.style.display = 'none';
      el.remove();
    }
  });
}
window.renderHomepageLayout = renderHomepageLayout;

// ======================================
// Wishlist Toggle (Phase 57)
// ======================================
async function toggleWishlist(productId, btnEl) {
    try {
        const res = await fetch('/api/wishlist/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.requires_auth) {
            window.location.href = '/login';
            return;
        }
        if (data.added) {
            btnEl.textContent = '❤️';
            btnEl.title = 'إزالة من المفضلة';
        } else {
            btnEl.textContent = '🤍';
            btnEl.title = 'أضف للمفضلة';
        }
    } catch(e) {
        console.error('Wishlist error:', e);
    }
}
window.toggleWishlist = toggleWishlist;

// ======================================
// Custom Storefront Menus (Phase 88)
// ======================================
function injectMenuDropdownStyles() {
  if (document.getElementById('customMenuStyles')) return;
  var style = document.createElement('style');
  style.id = 'customMenuStyles';
  var isRtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
  var alignSide = isRtl ? 'right' : 'left';
  var paddingDir = isRtl ? 'right' : 'left';
  var textAlign = isRtl ? 'right' : 'left';

  style.textContent = `
    .nav-item-dropdown {
      position: relative;
      display: inline-block;
    }
    .nav-dropdown-toggle {
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .nav-dropdown-menu {
      display: none;
      position: absolute;
      top: 100%;
      ${alignSide}: 0;
      background: white;
      min-width: 180px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      border-radius: 8px;
      border: 1px solid #e5e7eb;
      padding: 6px 0;
      z-index: 1000;
    }
    .nav-dropdown-menu a {
      display: block;
      padding: 8px 16px !important;
      color: #111827 !important;
      text-decoration: none !important;
      font-size: 0.85rem !important;
      border-radius: 0 !important;
      text-align: ${textAlign} !important;
      background: transparent !important;
      transition: all 0.2s;
    }
    .nav-dropdown-menu a:hover {
      background: var(--primary-light) !important;
      color: var(--primary-color) !important;
    }
    .nav-item-dropdown:hover .nav-dropdown-menu {
      display: block;
    }
    @media (max-width: 768px) {
      .nav-item-dropdown {
        display: block;
        width: 100%;
      }
      .nav-dropdown-menu {
        position: static;
        box-shadow: none;
        border: none;
        background: rgba(0,0,0,0.02);
        padding-${paddingDir}: 12px;
        display: block; /* show by default on mobile nav */
        min-width: 100%;
      }
      .nav-dropdown-menu a {
        padding: 6px 12px !important;
      }
    }
  `;
  document.head.appendChild(style);
}

function renderCustomMenus() {
  var settings = window.__SITE_SETTINGS__;
  if (!settings || !settings.menus) return;

  injectMenuDropdownStyles();

  var currentLang = getStorefrontLang();
  var isAr = currentLang === 'ar';

  // 1. Render Header Menu
  var headerMenu = settings.menus.header;
  if (headerMenu && headerMenu.length > 0) {
    var navEl = document.querySelector('.nav');
    if (navEl) {
      // Find call button if it exists to preserve it
      var callBtn = document.getElementById('callBtn');
      var callBtnHtml = '';
      if (callBtn) {
        callBtnHtml = callBtn.outerHTML;
      } else if (settings.phone) {
        var callText = isAr ? 'اتصل الآن' : 'Call Now';
        callBtnHtml = `<a id="callBtn" href="tel:${settings.phone}" class="call-now">${callText}</a>`;
      }

      var menuHtml = '';
      headerMenu.forEach(function(item) {
        var title = isAr ? (item.title_ar || item.title_en) : (item.title_en || item.title_ar);
        var target = item.target_blank ? ' target="_blank"' : '';
        
        if (item.children && item.children.length > 0) {
          // Dropdown Item
          menuHtml += `<div class="nav-item-dropdown">`;
          menuHtml += `<a href="${item.url || '#'}" class="nav-dropdown-toggle"${target}>${title} <i class="fa fa-chevron-down" style="font-size:0.75em; margin-right:4px;"></i></a>`;
          menuHtml += `<div class="nav-dropdown-menu">`;
          item.children.forEach(function(child) {
            var childTitle = isAr ? (child.title_ar || child.title_en) : (child.title_en || child.title_ar);
            var childTarget = child.target_blank ? ' target="_blank"' : '';
            menuHtml += `<a href="${child.url || '#'}"${childTarget}>${childTitle}</a>`;
          });
          menuHtml += `</div></div>`;
        } else {
          // Simple Link
          menuHtml += `<a href="${item.url || '#'}"${target}>${title}</a>`;
        }
      });

      // Inject and append call button
      navEl.innerHTML = menuHtml + callBtnHtml;

      // Re-bind mobile close events
      navEl.querySelectorAll('a').forEach(function(a) {
        a.addEventListener('click', function() { navEl.classList.remove('open'); });
      });
    }
  }

  // 2. Render Footer Menu
  var footerMenu = settings.menus.footer;
  var footerEl = document.querySelector('.footer');
  if (footerMenu && footerMenu.length > 0 && footerEl) {
    // Check if footer links are already rendered to avoid double rendering
    if (!document.getElementById('footerCustomLinks')) {
      var footerLinksWrap = document.createElement('div');
      footerLinksWrap.id = 'footerCustomLinks';
      footerLinksWrap.style.cssText = 'margin-bottom:16px; display:flex; justify-content:center; gap:20px; flex-wrap:wrap;';
      
      var footerHtml = '';
      footerMenu.forEach(function(item) {
        var title = isAr ? (item.title_ar || item.title_en) : (item.title_en || item.title_ar);
        var target = item.target_blank ? ' target="_blank"' : '';
        footerHtml += `<a href="${item.url || '#'}"${target} style="color:var(--secondary-color); text-decoration:none; font-size:0.9rem; transition:color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--secondary-color)'">${title}</a>`;
      });
      footerLinksWrap.innerHTML = footerHtml;
      
      // Insert at the beginning of the footer
      footerEl.insertBefore(footerLinksWrap, footerEl.firstChild);
    }
  }
}

// ==========================================
// Amazon-like Quick Add (Variant Picker Modal)
// ==========================================
window.openQuickAddModal = function(productId) {
  // Inject modal CSS if not exists
  if (!document.getElementById('quick-add-modal-styles')) {
    const st = document.createElement('style');
    st.id = 'quick-add-modal-styles';
    st.textContent = `
      .quick-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.55);
        display: flex;
        align-items: flex-end;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      }
      .quick-modal-overlay.open {
        opacity: 1;
        pointer-events: auto;
      }
      .quick-modal-content {
        background: #fff;
        width: 100%;
        max-width: 480px;
        border-radius: 20px 20px 0 0;
        padding: 24px 20px 20px;
        position: relative;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        box-shadow: 0 -4px 25px rgba(0,0,0,0.2);
        direction: rtl;
        text-align: right;
        font-family: inherit;
      }
      @media (min-width: 768px) {
        .quick-modal-overlay {
          align-items: center;
        }
        .quick-modal-content {
          border-radius: 16px;
          transform: scale(0.9);
        }
        .quick-modal-overlay.open .quick-modal-content {
          transform: scale(1);
        }
      }
      .quick-modal-overlay.open .quick-modal-content {
        transform: translateY(0);
      }
      .quick-modal-close {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #f1f5f9;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        z-index: 10;
      }
      .quick-modal-close:hover {
        background: #e2e8f0;
      }
      .qm-variant-btn {
        display: inline-block;
        padding: 8px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.9rem;
        color: #334155;
        transition: all 0.2s;
        background: #fff;
        user-select: none;
      }
      .qm-variant-btn:hover {
        border-color: #94a3b8;
        background: #f8fafc;
      }
      input[type="radio"]:checked + .qm-variant-btn {
        border-color: #4f46e5;
        background: #eff6ff;
        color: #4f46e5;
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
      }
      .qm-option-label {
        position: relative;
        display: inline-block;
      }
    `;
    document.head.appendChild(st);
  }

  // Create Modal overlay if not exists
  let modal = document.getElementById('quick-add-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'quick-add-modal';
    modal.className = 'quick-modal-overlay';
    modal.innerHTML = `
      <div class="quick-modal-content">
        <button class="quick-modal-close" onclick="closeQuickAddModal()">&times;</button>
        <div id="quick-modal-body-container"></div>
      </div>
    `;
    document.body.appendChild(modal);

    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeQuickAddModal();
    });
  }

  modal.classList.add('open');
  document.getElementById('quick-modal-body-container').innerHTML = `
    <div style="display:flex; justify-content:center; align-items:center; padding: 40px 0;">
      <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #4f46e5;"></i>
    </div>
  `;

  // Fetch product data from public-api
  var API = window.API_BASE || '/public-api';
  fetch(API + '/products/' + productId)
    .then(r => r.json())
    .then(({ data }) => {
      window.currentQmProduct = data;
      const p = data;
      const hasSizes = p.sizes && p.sizes.length > 0;
      const hasColors = p.colors && p.colors.length > 0;

      const fmt = (n) => {
        return new Intl.NumberFormat('en-EG', { maximumFractionDigits: 0 }).format(Math.round(parseFloat(n || 0))) + ' EGP';
      };

      let html = `
        <div class="quick-modal-product-info" style="display:flex; gap:16px; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:16px; align-items: flex-start;">
          <img id="qm-image" src="${p.image_url || '/shop/placeholder.jpg'}" style="width:90px; height:90px; object-fit:cover; border-radius:12px; border:1px solid #e2e8f0;" onerror="this.src='/shop/placeholder.jpg'">
          <div style="flex:1;">
            <h3 id="qm-name" style="margin:0 0 8px 0; font-size:1.05rem; color:#1e293b; line-height:1.4; font-weight:700;">${p.name}</h3>
            <div style="display:flex; align-items:baseline; gap:8px;">
              <span id="qm-price" style="font-weight:800; color:#4f46e5; font-size:1.15rem;">${fmt(p.price_after)}</span>
              ${p.price_before && p.price_before > p.price_after ? `<span id="qm-price-before" style="text-decoration:line-through; color:#94a3b8; font-size:0.85rem;">${fmt(p.price_before)}</span>` : ''}
            </div>
          </div>
        </div>

        <div class="quick-modal-options" style="max-height: 250px; overflow-y: auto; padding-left: 4px;">
          <!-- Sizes -->
          ${hasSizes ? `
            <div style="margin-bottom:16px;">
              <label style="display:block; font-weight:700; margin-bottom:8px; font-size:0.9rem; color:#334155;">المقاس <span style="color:#ef4444;">*</span></label>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                ${p.sizes.map((sz) => `
                  <label class="qm-option-label" style="cursor:pointer; position:relative;">
                    <input type="radio" name="qm_size" value="${sz}" style="display:none;" onchange="updateQmVariantAvailability()">
                    <span class="qm-variant-btn">${sz}</span>
                  </label>
                `).join('')}
              </div>
              <div id="qm-size-error" style="color:#ef4444; font-size:0.8rem; margin-top:4px; display:none;">يرجى اختيار المقاس</div>
            </div>
          ` : ''}

          <!-- Colors -->
          ${hasColors ? `
            <div style="margin-bottom:16px;">
              <label style="display:block; font-weight:700; margin-bottom:8px; font-size:0.9rem; color:#334155;">اللون <span style="color:#ef4444;">*</span></label>
              <div style="display:flex; gap:8px; flex-wrap:wrap;">
                ${p.colors.map((col) => `
                  <label class="qm-option-label" style="cursor:pointer; position:relative;">
                    <input type="radio" name="qm_color" value="${col}" style="display:none;" onchange="updateQmVariantAvailability()">
                    <span class="qm-variant-btn">${col}</span>
                  </label>
                `).join('')}
              </div>
              <div id="qm-color-error" style="color:#ef4444; font-size:0.8rem; margin-top:4px; display:none;">يرجى اختيار اللون</div>
            </div>
          ` : ''}
        </div>

        <button id="qm-add-btn" onclick="submitQmAddToCart()" style="width:100%; padding:14px; background:#ffda00; color:#000; border:none; border-radius:12px; font-weight:bold; font-size:1.05rem; cursor:pointer; margin-top:16px; transition:opacity 0.2s;" onmouseenter="this.style.opacity='0.9'" onmouseleave="this.style.opacity='1'">
          إضافة إلى عربة التسوق
        </button>
      `;
      document.getElementById('quick-modal-body-container').innerHTML = html;
      updateQmVariantAvailability();
    })
    .catch(err => {
      console.error(err);
      document.getElementById('quick-modal-body-container').innerHTML = `
        <div style="text-align:center; padding: 20px; color:#ef4444;">حدث خطأ أثناء تحميل البيانات</div>
      `;
    });
};

window.closeQuickAddModal = function() {
  const modal = document.getElementById('quick-add-modal');
  if (modal) modal.classList.remove('open');
};

window.updateQmVariantAvailability = function() {
  if (!window.currentQmProduct) return;
  const p = window.currentQmProduct;
  let stock = p.variants_stock || [];
  if (typeof stock === 'string') {
    try { stock = JSON.parse(stock); } catch(e) { stock = []; }
  }
  const hasSizes = p.sizes && p.sizes.length > 0;
  const hasColors = p.colors && p.colors.length > 0;

  const sizeInput = document.querySelector('input[name="qm_size"]:checked');
  const colorInput = document.querySelector('input[name="qm_color"]:checked');
  const selectedSize = sizeInput ? sizeInput.value : null;
  const selectedColor = colorInput ? colorInput.value : null;

  const sizeInputs = document.querySelectorAll('input[name="qm_size"]');
  const colorInputs = document.querySelectorAll('input[name="qm_color"]');

  const applyStyle = (inputEl, qty) => {
    const btn = inputEl.parentNode.querySelector('.qm-variant-btn');
    const isOut = (qty !== null && qty !== undefined && qty !== '' && Number(qty) <= 0);
    if (isOut) {
      inputEl.disabled = true;
      btn.style.opacity = '0.45';
      btn.style.textDecoration = 'line-through';
      btn.style.cursor = 'not-allowed';
      if (inputEl.checked) inputEl.checked = false;
    } else {
      inputEl.disabled = false;
      btn.style.opacity = '1';
      btn.style.textDecoration = 'none';
      btn.style.cursor = 'pointer';
    }
  };

  // If size is selected, check colors stock
  if (selectedSize && hasColors) {
    colorInputs.forEach(inputEl => {
      const found = stock.find(s => s.size === selectedSize && s.color === inputEl.value);
      const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
      applyStyle(inputEl, qty);
    });
  } else if (!selectedSize && hasColors) {
    colorInputs.forEach(inputEl => {
      if (hasSizes) {
        const allOut = p.sizes.every(sz => {
          const found = stock.find(s => s.size === sz && s.color === inputEl.value);
          const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
          return qty !== null && qty !== undefined && qty !== '' && Number(qty) <= 0;
        });
        applyStyle(inputEl, allOut ? 0 : 100);
      } else {
        const found = stock.find(s => s.color === inputEl.value);
        const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
        applyStyle(inputEl, qty);
      }
    });
  }

  // If color is selected, check sizes stock
  if (selectedColor && hasSizes) {
    sizeInputs.forEach(inputEl => {
      const found = stock.find(s => s.size === inputEl.value && s.color === selectedColor);
      const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
      applyStyle(inputEl, qty);
    });
  } else if (!selectedColor && hasSizes) {
    sizeInputs.forEach(inputEl => {
      if (hasColors) {
        const allOut = p.colors.every(col => {
          const found = stock.find(s => s.size === inputEl.value && s.color === col);
          const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
          return qty !== null && qty !== undefined && qty !== '' && Number(qty) <= 0;
        });
        applyStyle(inputEl, allOut ? 0 : 100);
      } else {
        const found = stock.find(s => s.size === inputEl.value);
        const qty = found ? found.qty : (p.stock !== undefined ? p.stock : 100);
        applyStyle(inputEl, qty);
      }
    });
  }
};

window.submitQmAddToCart = function() {
  if (!window.currentQmProduct) return;
  const p = window.currentQmProduct;
  const hasSizes = p.sizes && p.sizes.length > 0;
  const hasColors = p.colors && p.colors.length > 0;

  let selectedSize = null;
  let selectedColor = null;
  let isValid = true;

  if (hasSizes) {
    const sizeInput = document.querySelector('input[name="qm_size"]:checked');
    if (sizeInput) {
      selectedSize = sizeInput.value;
      document.getElementById('qm-size-error').style.display = 'none';
    } else {
      document.getElementById('qm-size-error').style.display = 'block';
      isValid = false;
    }
  }

  if (hasColors) {
    const colorInput = document.querySelector('input[name="qm_color"]:checked');
    if (colorInput) {
      selectedColor = colorInput.value;
      document.getElementById('qm-color-error').style.display = 'none';
    } else {
      document.getElementById('qm-color-error').style.display = 'block';
      isValid = false;
    }
  }

  if (!isValid) return;

  let variantPrice = Number(p.price_after || p.price || 0);
  let variants = p.variants_stock || [];
  if (typeof variants === 'string') { try { variants = JSON.parse(variants); } catch(e) { variants = []; } }
  if (Array.isArray(variants) && variants.length > 0) {
    const match = variants.find(v => (!hasSizes || v.size === selectedSize) && (!hasColors || v.color === selectedColor));
    if (match && match.price && Number(match.price) > 0) {
      variantPrice = Number(match.price);
    }
  }

  if (typeof BirdCart !== 'undefined' && BirdCart.addToCart) {
    window.BirdCart.addToCart({
      id: p.id,
      name: p.name,
      price: variantPrice,
      image: p.image_url || null,
      shipping_type: p.shipping_type || 'free',
      price_before: p.price_before || 0,
      qty: 1,
      selectedSize: selectedSize,
      selectedColor: selectedColor,
    });

    if (typeof updateCartCount === 'function') updateCartCount();
    closeQuickAddModal();

    // Show toast message
    const box = document.getElementById('toastBox');
    if (box) {
      box.textContent = 'تمت إضافة المنتج للسلة بنجاح!';
      box.classList.add('show');
      setTimeout(function() { box.classList.remove('show'); }, 3000);
    } else {
      alert('تمت إضافة المنتج للسلة بنجاح!');
    }
  }
};

// ======================================
// Auto-scale Brand Name for Long Store Names (3+ words)
// ======================================
function adjustBrandFontSize() {
  const nameEl = document.getElementById('siteName');
  if (!nameEl) return;
  const text = (nameEl.textContent || '').trim();
  if (!text) return;
  const words = text.split(/\s+/).filter(Boolean);
  const brandEl = nameEl.closest('.brand');

  nameEl.classList.remove('brand-long-name', 'brand-very-long-name');
  if (brandEl) brandEl.classList.remove('long-name', 'very-long-name');

  if (words.length >= 4 || text.length >= 20) {
    nameEl.classList.add('brand-very-long-name');
    if (brandEl) brandEl.classList.add('very-long-name');
  } else if (words.length >= 3 || text.length >= 12) {
    nameEl.classList.add('brand-long-name');
    if (brandEl) brandEl.classList.add('long-name');
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', adjustBrandFontSize);
} else {
  adjustBrandFontSize();
}
window.adjustBrandFontSize = adjustBrandFontSize;

