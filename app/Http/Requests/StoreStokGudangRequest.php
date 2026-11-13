<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStokGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app()->environment(['local', 'testing']) && $this->session()->get('mock_role', 'distributor') === 'distributor';
    }

    public function rules(): array
    {
        return [
            'id_distributor' => ['required', 'integer', Rule::exists('distributor', 'id_distributor')->where('status_aktif', true)],
            'id_produk' => ['required', 'integer', 'exists:produk,id_produk'],
            'periode' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'stok_fisik' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'stok_layak_jual' => ['required', 'integer', 'min:0', 'max:2147483647', 'lte:stok_fisik'],
        ];
    }
}
