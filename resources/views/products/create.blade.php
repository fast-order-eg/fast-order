<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">إضافة منتج</h2>
    </x-slot>

    <div class="py-6">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <form method="POST" action="{{ route('products.store') }}" class="text-right" enctype="multipart/form-data" id="productForm">
                @csrf
                <div class="mb-4">
                    <label class="block mb-1 font-medium">اسم المنتج <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200" required>
                    @error('name')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium">القسم <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select name="category_id" class="w-full border rounded-lg p-2 pr-8 focus:ring-2 focus:ring-indigo-200" required style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                            <option value="">اختر قسمًا</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}" @selected(old('category_id')==$id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <!-- Custom dropdown arrow pointing down on the right -->
                        <div class="absolute right-2 top-1/2 transform -translate-y-1/2 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    @error('category_id')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">السعر قبل الخصم</label>
                        <input type="number" step="0.01" name="price_before" value="{{ old('price_before', 0) }}" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200">
                        @error('price_before')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">السعر بعد الخصم <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="price_after" value="{{ old('price_after', 0) }}" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200" required>
                        @error('price_after')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- قسم أسعار الكميات (الخصم حسب الكمية) -->
                <div class="mb-4 p-4 border border-blue-100 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="font-bold text-blue-800">أسعار الكميات (اختياري)</h3>
                            <p class="text-xs text-blue-600">حدد سعر مختلف في حالة شراء أكثر من قطعة</p>
                        </div>
                        <button type="button" onclick="addPriceTier()" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            إضافة شريحة سعرية
                        </button>
                    </div>
                    
                    <div id="priceTiersContainer" class="space-y-3">
                        <!-- شرائح الأسعار تضاف هنا -->
                    </div>
                    <input type="hidden" name="price_tiers_json" id="price_tiers_json" value="">
                </div>

                
                <!-- خيارات الشحن -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
                    <label class="block mb-3 font-medium text-gray-700">خيارات الشحن <span class="text-red-500">*</span></label>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="radio" id="free_shipping" name="shipping_type" value="free" 
                                   class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300" 
                                   {{ old('shipping_type', 'free') == 'free' ? 'checked' : '' }}>
                            <label for="free_shipping" class="mr-3 text-sm font-medium text-gray-700">الشحن مجاناً</label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="radio" id="governorate_shipping" name="shipping_type" value="governorate" 
                                       class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                       {{ old('shipping_type') == 'governorate' ? 'checked' : '' }}>
                                <label for="governorate_shipping" class="mr-3 text-sm font-medium text-gray-700">الشحن حسب المحافظة</label>
                            </div>
                            <a href="{{ route('shipping.index') }}" 
                               class="inline-flex items-center px-2 py-1 text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-300 hover:border-indigo-500 rounded-md hover:bg-indigo-50 transition-all duration-200"
                               title="إدارة أسعار الشحن">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3 ml-1">
                                    <path fill-rule="evenodd" d="M11.828 2.25c-.916 0-1.699.663-1.85 1.567l-.091.549a.798.798 0 01-.517.608 7.45 7.45 0 00-.478.198.798.798 0 01-.796-.064l-.453-.324a1.875 1.875 0 00-2.416.2l-.243.243a1.875 1.875 0 00-.2 2.416l.324.453a.798.798 0 01.064.796 7.448 7.448 0 00-.198.478.798.798 0 01-.608.517l-.549.091A1.875 1.875 0 002.25 11.828v.344c0 .916.663 1.699 1.567 1.85l.549.091c.281.047.508.25.608.517.06.162.127.321.198.478a.798.798 0 01-.064.796l-.324.453a1.875 1.875 0 00.2 2.416l.243.243a1.875 1.875 0 002.416.2l.453-.324a.798.798 0 01.796-.064c.157.071.316.137.478.198.267.1.47.327.517.608l.091.549a1.875 1.875 0 001.85 1.567h.344c.916 0 1.699-.663 1.85-1.567l.091-.549a.798.798 0 01.517-.608 7.52 7.52 0 00.478-.198.798.798 0 01.796.064l.453.324a1.875 1.875 0 002.416-.2l.243-.243a1.875 1.875 0 00.2-2.416l-.324-.453a.798.798 0 01-.064-.796c.071-.157.137-.316.198-.478.1-.267.327-.47.608-.517l.549-.091A1.875 1.875 0 0021.75 12.172v-.344c0-.916-.663-1.699-1.567-1.85l-.549-.091a.798.798 0 01-.608-.517 7.507 7.507 0 00-.198-.478.798.798 0 01.064-.796l.324-.453a1.875 1.875 0 00-.2-2.416l-.243-.243a1.875 1.875 0 00-2.416-.2l-.453.324a.798.798 0 01-.796.064 7.462 7.462 0 00-.478-.198.798.798 0 01-.517-.608l-.091-.549A1.875 1.875 0 0012.172 2.25h-.344zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" clip-rule="evenodd" />
                                </svg>
                                إعدادات
                            </a>
                        </div>
                    </div>
                    @error('shipping_type')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium">الكمية</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200">
                    @error('stock')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium">الوصف</label>
                    <textarea name="description" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200" rows="4">{{ old('description') }}</textarea>
                    @error('description')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>

                <!-- قسم المقاسات -->
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="enableSizes" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded ml-2">
                        <label for="enableSizes" class="font-medium">المقاسات</label>
                    </div>
                    <div id="sizesContainer" class="hidden">
                        <div id="sizesList" class="space-y-2 mb-2">
                            <!-- المقاسات ستضاف هنا ديناميكيًا -->
                        </div>
                        <button type="button" onclick="addSize()" class="inline-flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M12 4.5a.75.75 0 01.75.75V11h5.75a.75.75 0 010 1.5H12.75v5.75a.75.75 0 01-1.5 0V12.5H5.5a.75.75 0 010-1.5h5.75V5.25A.75.75 0 0112 4.5z"/>
                            </svg>
                            إضافة مقاس
                        </button>
                    </div>
                </div>

                <!-- قسم الألوان -->
                <div class="mb-4">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="enableColors" class="w-4 h-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded ml-2">
                        <label for="enableColors" class="font-medium">الألوان</label>
                    </div>
                    <div id="colorsContainer" class="hidden">
                        <div id="colorsList" class="space-y-2 mb-2">
                            <!-- الألوان ستضاف هنا ديناميكيًا -->
                        </div>
                        <button type="button" onclick="addColor()" class="inline-flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                <path d="M12 4.5a.75.75 0 01.75.75V11h5.75a.75.75 0 010 1.5H12.75v5.75a.75.75 0 01-1.5 0V12.5H5.5a.75.75 0 010-1.5h5.75V5.25A.75.75 0 0112 4.5z"/>
                            </svg>
                            إضافة لون
                        </button>
                    </div>
                </div>

                <!-- زر تفاصيل مخزون المقاسات والألوان -->
                <div class="mb-4 mt-6 border-t pt-4">
                    <input type="hidden" name="variants_stock" id="variants_stock_json" value="[]">
                    <button type="button" onclick="openVariantsModal()" class="inline-flex items-center gap-2 px-5 py-2.5 font-semibold rounded-lg shadow-md transition-colors" style="background-color: #9333ea; color: white;">
                        <i class="fas fa-list"></i>
                        تفاصيل أكثر (كميات المقاسات والألوان)
                    </button>
                    <p class="mt-2 text-sm text-gray-500">استخدم هذا الخيار إذا كنت ترغب في تحديد كمية متاحة لكل مقاس ولون (مثلاً: أحمر L متاح 3 قطع فقط). إذا تركته فارغاً ستعتبر جميع المقاسات والألوان متوفرة دائماً.</p>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 font-medium">الصورة الرئيسية (اختياري)</label>
                    <input type="file" name="main_image" accept="image/*" class="w-full border rounded-lg p-2" onchange="previewMain(event)">
                    <div class="mt-2"><img id="mainPreview" class="hidden h-24 w-24 object-cover rounded-lg border shadow-sm" alt="معاينة الصورة الرئيسية"></div>
                    @error('main_image')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium">صور فرعية (يمكن اختيار أكثر من صورة)</label>
                    <input type="file" name="gallery[]" accept="image/*" multiple class="w-full border rounded-lg p-2" onchange="previewGallery(event)">
                    <div id="galleryPreview" class="mt-2 flex flex-wrap gap-2"></div>
                    @error('gallery')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                    @error('gallery.*')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('products.index') }}" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">رجوع</a>
                    <button type="submit" id="submitBtn" class="inline-flex items-center gap-2 min-w-[160px] justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M12 4.5a.75.75 0 01.75.75V11h5.75a.75.75 0 010 1.5H12.75v5.75a.75.75 0 01-1.5 0V12.5H5.5a.75.75 0 010-1.5h5.75V5.25A.75.75 0 0112 4.5z"/></svg>
                        <span>إضافة المنتج</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Variants Modal -->
    <div id="variantsModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">كميات المقاسات والألوان</h3>
                <button type="button" onclick="closeVariantsModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto flex-1" id="variantsModalBody">
                <!-- Dynamic Content Here -->
            </div>
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeVariantsModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">إلغاء</button>
                <button type="button" onclick="saveVariantsModal()" class="px-6 py-2 font-semibold rounded-lg shadow" style="background-color: #16a34a; color: white;">حفظ المخزون</button>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
function previewMain(evt){
  const file = evt.target.files && evt.target.files[0];
  const img = document.getElementById('mainPreview');
  if(!file){ 
    img.classList.add('hidden'); 
    return; 
  }
  img.src = URL.createObjectURL(file);
  img.classList.remove('hidden');
}

function previewGallery(evt){
  const files = evt.target.files || [];
  const wrap = document.getElementById('galleryPreview');
  wrap.innerHTML = '';
  Array.from(files).forEach(f=>{
    const img = document.createElement('img');
    img.className = 'h-16 w-16 object-cover rounded-lg border shadow-sm';
    img.src = URL.createObjectURL(f);
    wrap.appendChild(img);
  });
}

// التأكد من أن خيار الشحن محدد
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productForm');
    form.addEventListener('submit', function(e) {
        const shippingOptions = document.querySelectorAll('input[name="shipping_type"]');
        const isAnyChecked = Array.from(shippingOptions).some(radio => radio.checked);
        
        if (!isAnyChecked) {
            e.preventDefault();
            alert('يرجى اختيار نوع الشحن');
            return false;
        }
    });
});

// إدارة قسم المقاسات
document.getElementById('enableSizes').addEventListener('change', function() {
    const container = document.getElementById('sizesContainer');
    if (this.checked) {
        container.classList.remove('hidden');
        // إضافة حقل واحد عند التفعيل إذا لم يكن هناك حقول
        if (document.getElementById('sizesList').children.length === 0) {
            addSize();
        }
    } else {
        container.classList.add('hidden');
        // إزالة جميع الحقول عند إلغاء التفعيل
        document.getElementById('sizesList').innerHTML = '';
    }
});

// إدارة قسم الألوان
document.getElementById('enableColors').addEventListener('change', function() {
    const container = document.getElementById('colorsContainer');
    if (this.checked) {
        container.classList.remove('hidden');
        // إضافة حقل واحد عند التفعيل إذا لم يكن هناك حقول
        if (document.getElementById('colorsList').children.length === 0) {
            addColor();
        }
    } else {
        container.classList.add('hidden');
        // إزالة جميع الحقول عند إلغاء التفعيل
        document.getElementById('colorsList').innerHTML = '';
    }
});

// إضافة حقل مقاس جديد
function addSize() {
    const sizesList = document.getElementById('sizesList');
    const sizeItem = document.createElement('div');
    sizeItem.className = 'flex items-center gap-2';
    sizeItem.innerHTML = `
        <input type="text" name="sizes[]" placeholder="مثال: L, XL, 42" 
               class="flex-1 border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200">
        <button type="button" onclick="removeItem(this)" 
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clip-rule="evenodd" />
            </svg>
        </button>
    `;
    sizesList.appendChild(sizeItem);
}

// إضافة حقل لون جديد
function addColor() {
    const colorsList = document.getElementById('colorsList');
    const colorItem = document.createElement('div');
    colorItem.className = 'flex items-center gap-2';
    colorItem.innerHTML = `
        <input type="text" name="colors[]" placeholder="مثال: أحمر, أزرق, أسود" 
               class="flex-1 border rounded-lg p-2 focus:ring-2 focus:ring-indigo-200">
        <button type="button" onclick="removeItem(this)" 
                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 013.878.512.75.75 0 11-.256 1.478l-.209-.035-1.005 13.07a3 3 0 01-2.991 2.77H8.084a3 3 0 01-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 01-.256-1.478A48.567 48.567 0 017.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 013.369 0c1.603.051 2.815 1.387 2.815 2.951zm-6.136-1.452a51.196 51.196 0 013.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 00-6 0v-.113c0-.794.609-1.428 1.364-1.452zm-.355 5.945a.75.75 0 10-1.5.058l.347 9a.75.75 0 101.499-.058l-.346-9zm5.48.058a.75.75 0 10-1.498-.058l-.347 9a.75.75 0 001.5.058l.345-9z" clip-rule="evenodd" />
            </svg>
        </button>
    `;
    colorsList.appendChild(colorItem);
}

// حذف حقل (مقاس أو لون)
function removeItem(button) {
    button.parentElement.remove();
}

// إدارة شرائح الأسعار
let priceTiers = [];

function addPriceTier() {
    const defaultQty = priceTiers.length > 0 ? Math.max(...priceTiers.map(t => parseInt(t.min_qty))) + 1 : 2;
    priceTiers.push({ min_qty: defaultQty, price: '' });
    renderPriceTiers();
}

function removePriceTier(index) {
    priceTiers.splice(index, 1);
    renderPriceTiers();
}

function updatePriceTier(index, field, value) {
    priceTiers[index][field] = value;
    document.getElementById('price_tiers_json').value = JSON.stringify(priceTiers);
}

function renderPriceTiers() {
    const container = document.getElementById('priceTiersContainer');
    container.innerHTML = '';
    
    if (priceTiers.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500 text-center py-2 bg-white rounded border border-dashed border-gray-300">لا توجد شرائح سعرية مضافة</p>';
    } else {
        priceTiers.forEach((tier, index) => {
            container.innerHTML += `
                <div class="flex flex-col sm:flex-row items-center gap-3 bg-white p-3 rounded border border-blue-200 shadow-sm">
                    <div class="flex-1 flex items-center gap-2 w-full">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">عند شراء</label>
                        <input type="number" min="2" value="${tier.min_qty}" oninput="updatePriceTier(${index}, 'min_qty', this.value)" class="w-20 border rounded p-2 text-center focus:ring-blue-500">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">قطع، يصبح السعر الإجمالي:</label>
                        <input type="number" step="0.01" value="${tier.price}" oninput="updatePriceTier(${index}, 'price', this.value)" placeholder="السعر الإجمالي للعرض" class="flex-1 border rounded p-2 focus:ring-blue-500 min-w-[100px]">
                    </div>
                    <button type="button" onclick="removePriceTier(${index})" class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition shrink-0" title="حذف">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            `;
        });
    }
    document.getElementById('price_tiers_json').value = JSON.stringify(priceTiers);
}

// إدارة مخزون المتغيرات
let variantsStockData = [];

function openVariantsModal() {
    const sizeInputs = Array.from(document.querySelectorAll('input[name="sizes[]"]')).map(el => el.value.trim()).filter(val => val);
    const colorInputs = Array.from(document.querySelectorAll('input[name="colors[]"]')).map(el => el.value.trim()).filter(val => val);
    
    const sizesEnabled = document.getElementById('enableSizes').checked && sizeInputs.length > 0;
    const colorsEnabled = document.getElementById('enableColors').checked && colorInputs.length > 0;

    const modalBody = document.getElementById('variantsModalBody');

    if (!sizesEnabled && !colorsEnabled) {
        modalBody.innerHTML = '<div class="p-4 text-center text-red-600 bg-red-50 rounded-lg border border-red-200">يجب تفعيل وإضافة مقاسات أو ألوان أولاً قبل تحديد مخزونها.</div>';
    } else {
        let combinations = [];
        if (sizesEnabled && colorsEnabled) {
            sizeInputs.forEach(s => {
                colorInputs.forEach(c => {
                    combinations.push({ size: s, color: c });
                });
            });
        } else if (sizesEnabled) {
            sizeInputs.forEach(s => combinations.push({ size: s, color: null }));
        } else if (colorsEnabled) {
            colorInputs.forEach(c => combinations.push({ size: null, color: c }));
        }

        if (combinations.length === 0) {
            modalBody.innerHTML = '<div class="p-4 text-center text-gray-600">لم يتم العثور على أي مقاسات أو ألوان صحيحة.</div>';
        } else {
            let tableHTML = `
                <table class="min-w-full divide-y divide-gray-200 border rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            ${sizesEnabled ? '<th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">المقاس</th>' : ''}
                            ${colorsEnabled ? '<th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">اللون</th>' : ''}
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">السعر المخصص (ج.م)</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">الكمية المتاحة</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            const defaultPrice = document.querySelector('input[name="price_after"]')?.value || '';

            combinations.forEach((combo, idx) => {
                const existing = variantsStockData.find(v => v.size == combo.size && v.color == combo.color);
                const val = existing ? (existing.qty ?? '') : '';
                const priceVal = existing ? (existing.price ?? '') : '';

                tableHTML += `<tr>`;
                if (sizesEnabled) {
                    tableHTML += `<td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${combo.size}</td>`;
                }
                if (colorsEnabled) {
                    tableHTML += `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 py-1 bg-gray-100 rounded text-gray-700 border">${combo.color}</span>
                    </td>`;
                }
                tableHTML += `<td class="px-4 py-3 whitespace-nowrap">
                    <input type="number" step="0.01" min="0" data-size="${combo.size || ''}" data-color="${combo.color || ''}" class="variant-price-input focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border text-center font-medium" placeholder="${defaultPrice || 'السعر'}" value="${priceVal}">
                </td>`;
                tableHTML += `<td class="px-4 py-3 whitespace-nowrap">
                    <input type="number" min="0" data-size="${combo.size || ''}" data-color="${combo.color || ''}" class="variant-qty-input focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border text-center font-medium" placeholder="100" value="${val}">
                </td>`;
                tableHTML += `</tr>`;
            });

            tableHTML += `</tbody></table>`;
            tableHTML += `<div class="mt-4 text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i> يمكنك تحديد سعر مخصص لكل مقاس أو لون يختلف عن السعر الأساسي للمنتج. إذا تركته فارغاً سيتم استخدام السعر الأساسي.</div>`;
            modalBody.innerHTML = tableHTML;
        }
    }

    document.getElementById('variantsModal').classList.remove('hidden');
}

function closeVariantsModal() {
    document.getElementById('variantsModal').classList.add('hidden');
}

function saveVariantsModal() {
    variantsStockData = [];
    const rows = document.querySelectorAll('#variantsModalBody tbody tr');
    rows.forEach(tr => {
        const qtyInp = tr.querySelector('.variant-qty-input');
        const priceInp = tr.querySelector('.variant-price-input');
        if (qtyInp) {
            const qtyVal = qtyInp.value.trim();
            const priceVal = priceInp ? priceInp.value.trim() : '';
            variantsStockData.push({
                size: qtyInp.dataset.size || null,
                color: qtyInp.dataset.color || null,
                price: priceVal !== '' ? parseFloat(priceVal) : null,
                qty: qtyVal !== '' ? parseInt(qtyVal) : 100
            });
        }
    });
    
    document.getElementById('variants_stock_json').value = JSON.stringify(variantsStockData);
    closeVariantsModal();
    
    // إظهار تنبيه نجاح صغير
    const btn = document.querySelector('button[onclick="openVariantsModal()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> تم حفظ الكميات';
    btn.style.backgroundColor = '#16a34a'; // Green
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.backgroundColor = '#9333ea'; // Purple
    }, 2000);
}

// تهيئة عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    renderPriceTiers();
});
</script>
