<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellOutRequest extends FormRequest
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
            'kanal' => ['required', Rule::in(['Retail', 'General Trade', 'Modern Trade'])],
            'jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }
}
