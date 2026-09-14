<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['required', 'string', 'max:100'],
            'events' => ['required', 'array'],
            'events.*' => ['string', Rule::in(['order.created', 'order.status_updated', 'product.created', 'customer.created'])],
            'is_active' => ['boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'رابط الاستقبال مطلوب.',
            'url.url' => 'يجب إدخال رابط استقبال صحيح.',
            'url.max' => 'رابط الاستقبال يجب ألا يتجاوز 255 حرفاً.',
            'secret.required' => 'المفتاح السري مطلوب لتأمين البيانات.',
            'secret.string' => 'المفتاح السري يجب أن يكون نصاً.',
            'secret.max' => 'المفتاح السري يجب ألا يتجاوز 100 حرف.',
            'events.required' => 'يجب تحديد حدث واحد على الأقل.',
            'events.array' => 'الحدث المختار غير صحيح.',
            'events.*.in' => 'أحد الأحداث المختارة غير مدعوم.',
        ];
    }
}
