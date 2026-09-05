<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('إدارة الطلبات') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-blue-100 text-xs font-medium mb-1">إجمالي الطلبات</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['total'] }}</p>
                        </div>
                        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-yellow-100 text-xs font-medium mb-1">قيد الانتظار</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['pending'] }}</p>
                        </div>
                        <div class="bg-gradient-to-r from-green-500 to-green-600 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-green-100 text-xs font-medium mb-1">مؤكدة</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['confirmed'] }}</p>
                        </div>
                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-indigo-100 text-xs font-medium mb-1">مع شركة الشحن</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['shipped'] }}</p>
                        </div>
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-purple-100 text-xs font-medium mb-1">تم التسليم</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['delivered'] }}</p>
                        </div>
                        <div class="bg-gradient-to-r from-red-500 to-red-600 p-4 rounded-xl shadow-lg text-white text-center">
                            <p class="text-red-100 text-xs font-medium mb-1">ملغية</p>
                            <p class="text-3xl font-bold">{{ $statusCounts['cancelled'] }}</p>
                        </div>
                    </div>

                    <!-- Search and Filter Bar -->
                    <div class="bg-gray-50 p-5 rounded-xl mb-4">
                        <form method="GET" action="{{ route('orders.index') }}" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                            <!-- Search -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">البحث</label>
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="اسم، رقم مرجعي، هاتف..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Date From -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">من تاريخ</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">إلى تاريخ</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">الحالة</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">كل الحالات</option>
                                    <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>قيد الانتظار</option>
                                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>مؤكد</option>
                                    <option value="shipped"   {{ request('status') == 'shipped'   ? 'selected' : '' }}>مع شركة الشحن</option>
                                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                                </select>
                            </div>

                            <!-- Product Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">المنتج</label>
                                <select name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">كل المنتجات</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center justify-center gap-1">
                                    <i class="fas fa-filter"></i> فلترة
                                </button>
                                <a href="{{ route('orders.index') }}" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm flex items-center" title="إلغاء الفلتر">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Export Buttons -->
                    <div class="flex justify-end gap-3 mb-4">
                        <a href="{{ route('orders.export', array_merge(request()->all(), ['format' => 'excel'])) }}"
                           class="inline-flex items-center gap-2 bg-emerald-600 text-white px-5 py-2.5 rounded-lg hover:bg-emerald-700 transition duration-200 font-medium shadow">
                            <i class="fas fa-file-excel text-lg"></i>
                            تحميل Excel
                            @if(request()->hasAny(['search','date_from','date_to','status','product_id']))
                                <span class="text-xs bg-emerald-500 px-2 py-0.5 rounded-full">(مفلتر)</span>
                            @endif
                        </a>
                        <a href="{{ route('orders.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" target="_blank"
                           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow">
                            <i class="fas fa-print text-lg"></i>
                            طباعة / PDF
                            @if(request()->hasAny(['search','date_from','date_to','status','product_id']))
                                <span class="text-xs bg-indigo-500 px-2 py-0.5 rounded-full">(مفلتر)</span>
                            @endif
                        </a>
                    </div>

                    <!-- Orders Table -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-900">قائمة الطلبات</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">#</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">الرقم المرجعي</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">العميل</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">الهاتف</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">المحافظة</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">الإجمالي</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">الحالة</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">التاريخ</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($orders as $index => $order)
                                        <tr class="hover:bg-gray-50 transition duration-150 {{ $order->status === 'cancelled' ? 'bg-red-50 opacity-75' : '' }}" id="order-row-{{ $order->id }}">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                                                {{ $orders->firstItem() + $index }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-bold text-indigo-600">{{ $order->reference_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $order->customer_name }}
                                                @if($order->status === 'cancelled')
                                                    <span class="mr-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded">ملغي</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $order->customer_phone }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $order->governorate }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-green-600">
                                                {{ number_format($order->total, 0) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <select onchange="updateOrderStatus({{ $order->id }}, this.value)"
                                                        data-original-value="{{ $order->status }}"
                                                        class="text-xs font-semibold rounded-full px-3 py-1 border-0 focus:ring-2 focus:ring-blue-500 status-select-{{ $order->status }}">
                                                    <option value="pending"   {{ $order->status == 'pending'   ? 'selected' : '' }}>قيد الانتظار</option>
                                                    <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>مؤكد</option>
                                                    <option value="shipped"   {{ $order->status == 'shipped'   ? 'selected' : '' }}>مع شركة الشحن</option>
                                                    <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>تم التسليم</option>
                                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                <div>{{ $order->created_at->format('Y/m/d') }}</div>
                                                <div class="text-xs text-gray-400">{{ $order->created_at->format('h:i A') }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-1">
                                                    <button onclick="viewOrder({{ $order->id }})"
                                                            class="text-blue-600 hover:text-blue-900 p-1.5 rounded-lg hover:bg-blue-50 transition" title="عرض التفاصيل">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="printInvoice({{ $order->id }})"
                                                            class="text-green-600 hover:text-green-900 p-1.5 rounded-lg hover:bg-green-50 transition" title="طباعة الفاتورة">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    @if($order->status !== 'cancelled')
                                                    <button onclick="cancelOrder({{ $order->id }})"
                                                            class="text-orange-500 hover:text-orange-700 p-1.5 rounded-lg hover:bg-orange-50 transition" title="إلغاء الطلب">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    @else
                                                    <span class="text-gray-300 p-1.5" title="الطلب ملغي بالفعل">
                                                        <i class="fas fa-ban"></i>
                                                    </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                                    <p class="text-lg font-medium">لا توجد طلبات</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Total Amount -->
                        <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 border-t border-emerald-200">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-emerald-800">إجمالي المبالغ المعروضة:</span>
                                <div class="text-2xl font-bold text-emerald-700 bg-white px-4 py-2 rounded-lg shadow-sm border border-emerald-200">
                                    {{ number_format($totalAmount, 0) }} <span class="text-sm text-emerald-600">EGP</span>
                                </div>
                            </div>
                        </div>

                        @if($orders->hasPages())
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .status-select-pending   { background-color:#fef3c7; color:#92400e; appearance:none; padding-left:2rem; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2392400e' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position:left 0.5rem center; background-repeat:no-repeat; background-size:1.5em 1.5em; }
        .status-select-confirmed { background-color:#dbeafe; color:#1e40af; appearance:none; padding-left:2rem; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%231e40af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position:left 0.5rem center; background-repeat:no-repeat; background-size:1.5em 1.5em; }
        .status-select-shipped   { background-color:#e9d5ff; color:#6b21a8; appearance:none; padding-left:2rem; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b21a8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position:left 0.5rem center; background-repeat:no-repeat; background-size:1.5em 1.5em; }
        .status-select-delivered { background-color:#d1fae5; color:#065f46; appearance:none; padding-left:2rem; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23065f46' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position:left 0.5rem center; background-repeat:no-repeat; background-size:1.5em 1.5em; }
        .status-select-cancelled { background-color:#fee2e2; color:#991b1b; appearance:none; padding-left:2rem; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23991b1b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position:left 0.5rem center; background-repeat:no-repeat; background-size:1.5em 1.5em; }
    </style>

    <script>
        function viewOrder(id) {
            fetch(`/orders/${id}/show`)
                .then(r => r.json())
                .then(data => { if (data.success) showOrderModal(data.order); else alert('خطأ: ' + data.message); })
                .catch(() => alert('حدث خطأ'));
        }

        function showOrderModal(order) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            const statusLabels = { pending:'في الانتظار', confirmed:'مؤكد', shipped:'مع شركة الشحن', delivered:'تم التسليم', cancelled:'ملغي' };
            const statusColors = { pending:'bg-yellow-100 text-yellow-800', confirmed:'bg-blue-100 text-blue-800', shipped:'bg-purple-100 text-purple-800', delivered:'bg-green-100 text-green-800', cancelled:'bg-red-100 text-red-800' };
            let itemsHtml = '';
            (order.items || []).forEach(item => {
                const img = item.image_url || '';
                itemsHtml += `<div class="bg-gray-50 p-3 rounded-lg mb-2 flex items-center gap-3">
                    ${img ? `<img src="${img}" class="w-14 h-14 object-cover rounded">` : '<div class="w-14 h-14 bg-gray-200 rounded flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>'}
                    <div class="flex-1">
                        <div class="font-bold">${item.name || ''}</div>
                        ${item.selectedSize ? `<span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded mr-1">مقاس: ${item.selectedSize}</span>` : ''}
                        ${item.selectedColor ? `<span class="text-xs bg-pink-100 text-pink-700 px-2 py-0.5 rounded mr-1">لون: ${item.selectedColor}</span>` : ''}
                        ${item.options && typeof item.options === 'object' ? Object.entries(item.options).map(([k, v]) => v ? `<span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded mr-1">${k}: ${v}</span>` : '').join('') : ''}
                        <div class="text-sm text-gray-600 mt-1">${Number(item.price||0).toLocaleString()} × ${item.quantity||1}</div>
                    </div>
                    <div class="font-bold text-green-600">${Number((item.price||0)*(item.quantity||1)).toLocaleString()}</div>
                </div>`;
            });
            modal.innerHTML = `
                <div class="relative top-16 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white max-h-screen overflow-y-auto" dir="rtl">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">تفاصيل الطلب ${order.reference_number}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-blue-50 p-3 rounded-lg text-sm">
                            <div class="font-bold text-blue-900 mb-2">معلومات الطلب</div>
                            <div class="flex justify-between mb-1"><span>التاريخ:</span><span class="font-medium">${order.created_at}</span></div>
                            <div class="flex justify-between"><span>الحالة:</span><span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[order.status]||''}">${statusLabels[order.status]||''}</span></div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg text-sm">
                            <div class="font-bold text-green-900 mb-2">معلومات العميل</div>
                            <div class="font-medium">${order.customer_name}</div>
                            <div>${order.customer_phone}</div>
                            <div class="text-xs text-gray-600">${order.governorate} - ${order.customer_address}</div>
                        </div>
                    </div>
                    ${order.notes ? `<div class="bg-yellow-50 p-3 rounded-lg mb-4"><div class="font-bold text-yellow-900 mb-1">ملاحظات</div><p class="text-sm">${order.notes}</p></div>` : ''}
                    <div class="mb-4"><div class="font-bold mb-2">المنتجات</div>${itemsHtml}</div>
                    <div class="bg-blue-50 p-3 rounded-lg mb-4 text-sm">
                        <div class="flex justify-between mb-1"><span>المجموع الفرعي:</span><span class="font-bold">${Number(order.subtotal||0).toLocaleString()}</span></div>
                        <div class="flex justify-between mb-1"><span>الشحن:</span><span class="font-bold ${order.shipping_cost==0?'text-green-600':''}">${order.shipping_cost==0?'مجاني':Number(order.shipping_cost).toLocaleString()}</span></div>
                        <hr class="border-blue-200 my-1">
                        <div class="flex justify-between text-base"><span class="font-bold">الإجمالي:</span><span class="font-bold text-blue-600">${Number(order.total||0).toLocaleString()}</span></div>
                    </div>
                    <div class="flex justify-center gap-3">
                        <button onclick="printInvoice(${order.id})" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center gap-2"><i class="fas fa-print"></i>طباعة</button>
                        <button onclick="this.closest('.fixed').remove()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 flex items-center gap-2"><i class="fas fa-times"></i>إغلاق</button>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }

        function printInvoice(id) {
            window.open(`/orders/${id}/invoice`, '_blank', 'width=800,height=600,scrollbars=yes');
        }

        function cancelOrder(id) {
            if (confirm('هل تريد إلغاء هذا الطلب؟ سيتم تغيير حالته إلى "ملغي".')) {
                fetch(`/orders/${id}/cancel`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        const row = document.getElementById('order-row-' + id);
                        if (row) {
                            row.classList.add('bg-red-50', 'opacity-75');
                            const btn = row.querySelector('[onclick*="cancelOrder"]');
                            if (btn) btn.outerHTML = '<span class="text-gray-300 p-1.5"><i class="fas fa-ban"></i></span>';
                        }
                        setTimeout(() => location.reload(), 800);
                    } else {
                        alert('حدث خطأ: ' + data.message);
                    }
                });
            }
        }

        function updateOrderStatus(id, newStatus) {
            const selectElement = document.querySelector(`select[onchange*="${id}"]`);
            fetch(`/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    if (selectElement) selectElement.className = selectElement.className.replace(/status-select-\w+/, `status-select-${newStatus}`);
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('حدث خطأ: ' + (data.message || ''));
                    if (selectElement) selectElement.value = selectElement.getAttribute('data-original-value') || 'pending';
                }
            }).catch(() => {
                if (selectElement) selectElement.value = selectElement.getAttribute('data-original-value') || 'pending';
            });
        }
    </script>
</x-app-layout>
