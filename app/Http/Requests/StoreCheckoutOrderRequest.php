<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('customer_phone')) {
            $phone = trim($this->input('customer_phone'));
            // Remove any spaces
            $phone = preg_replace('/\s+/', '', $phone);
            
            // Normalize +2 or 2 or 002 prefix if followed by 01
            if (str_starts_with($phone, '+201')) {
                $phone = substr($phone, 2); // Keeps '01...'
            } elseif (str_starts_with($phone, '201')) {
                $phone = '0' . substr($phone, 1); // Keeps '01...'
            } elseif (str_starts_with($phone, '00201')) {
                $phone = substr($phone, 4); // Keeps '01...'
            }
            
            $this->merge([
                'customer_phone' => $phone,
            ]);
        }

        // 🛡️ توحيد صيغ المنتجات لضمان التوافق التام (qty / quantity و size و color)
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as &$item) {
                if (is_array($item)) {
                    if (!isset($item['qty']) && isset($item['quantity'])) {
                        $item['qty'] = (int) $item['quantity'];
                    }
                    if (!isset($item['selectedSize']) && isset($item['size'])) {
                        $item['selectedSize'] = (string) $item['size'];
                    }
                    if (!isset($item['selectedColor']) && isset($item['color'])) {
                        $item['selectedColor'] = (string) $item['color'];
                    }
                }
            }
            unset($item);
            $this->merge(['items' => $items]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'regex:/^01[0125]\d{8}$/'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'customer_address' => ['required', 'string', 'max:1000'],
            'governorate_id'   => ['required', 'exists:shipping_governorates,id'],
            'payment_method'   => ['nullable', 'string', 'max:50'],
            'coupon_code'      => ['nullable', 'string', 'max:100'],
            'save_address'     => ['nullable', 'boolean'],
            'terms'            => ['nullable'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.id'       => ['required', 'integer'],
            'items.*.name'     => ['required', 'string'],
            'items.*.price'    => ['required', 'numeric', 'min:0'],
            'items.*.qty'      => ['required', 'integer', 'min:1'],
            'items.*.selectedSize'  => ['nullable', 'string'],
            'items.*.selectedColor' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'خطأ في البيانات المدخلة',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
