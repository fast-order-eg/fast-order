<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = session('tenant_id') ?: config('tenant.id');

        return [
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'price_before'        => ['nullable', 'numeric', 'min:0'],
            'price_after'         => ['required', 'numeric', 'min:0'],
            'stock'               => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'category_id'         => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($tenantId) {
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }
                })
            ],
            'shipping_type'       => ['required', 'in:free,governorate'],
            'main_image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'gallery'             => ['nullable', 'array'],
            'gallery.*'           => ['image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'اسم المنتج مطلوب',
            'price_after.required'   => 'السعر مطلوب',
            'stock.required'         => 'الكمية مطلوبة',
            'category_id.required'   => 'التصنيف مطلوب',
            'category_id.exists'     => 'التصنيف المحدد غير موجود أو لا ينتمي لمتجرك',
            'shipping_type.required' => 'نوع الشحن مطلوب',
            'shipping_type.in'       => 'نوع الشحن غير صحيح',
            'main_image.image'       => 'يجب أن تكون الصورة الرئيسية ملف صورة صالح',
            'main_image.mimes'       => 'يجب أن تكون الصورة الرئيسية بتنسيق jpg, jpeg, png, أو webp',
            'main_image.max'         => 'الحد الأقصى لحجم الصورة 20 ميجابايت',
            'gallery.*.image'        => 'يجب أن تكون الصور الفرعية ملفات صور صالحة',
            'gallery.*.mimes'        => 'يجب أن تكون الصور الفرعية بتنسيق jpg, jpeg, png, أو webp',
            'gallery.*.max'          => 'الحد الأقصى لحجم أي صورة فرعية هو 20 ميجابايت',
        ];
    }
}
