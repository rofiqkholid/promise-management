<?php

namespace App\Http\Requests\FeasibilityStudy;

use Illuminate\Foundation\Http\FormRequest;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'  => 'required|exists:customers,id',
            'project_id'   => 'required|exists:models,id',
            'project_name' => 'required|string|max:255',
            'inquiry_date' => 'required|date',
            'remarks'      => 'nullable|string',
        ];
    }
}
