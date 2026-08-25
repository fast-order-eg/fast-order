<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:150'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:5000'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:5000'],
            'snapchat_pixel_id' => ['nullable', 'string', 'max:5000'],
            'google_analytics_id' => ['nullable', 'string', 'max:500'],
            'facebook_page' => ['nullable', 'string', 'max:500'],
            'instagram_page' => ['nullable', 'string', 'max:500'],
            'tiktok_page' => ['nullable', 'string', 'max:500'],
            'google_maps_url' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:20480'],
            'main_categories' => ['nullable', 'array'],
            'main_categories.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'يجب أن يكون الملف المرفوع صورة صالحة.',
            'logo.mimes' => 'الصيغ المسموح بها للشعار هي: jpg, jpeg, png, webp, gif, svg',
            'logo.max' => 'الحد الأقصى لحجم الشعار هو 20 ميجابايت.',
        ];
    }
}
