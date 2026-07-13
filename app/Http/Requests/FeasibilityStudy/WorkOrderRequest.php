<?php

namespace App\Http\Requests\FeasibilityStudy;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Merge JSON body into request input so FormRequest validation works
     * whether the request is sent as application/json or form-data.
     */
    protected function prepareForValidation(): void
    {
        if ($this->isJson()) {
            $this->merge($this->json()->all());
        }
    }

    public function rules(): array
    {
        $rules = [
            'released_at'        => 'required|date',
            'first_sample_date'  => 'nullable|date',
            'due_date_plan'      => 'required|date',
            'priority'           => 'required|string|in:LOW,STANDARD,URGENT',
            'processes'          => 'required|array',
            'processes.*'        => 'required|exists:mng_wo_processes,id',
            'selected_approval_rules' => 'nullable|array',
            'selected_approval_rules.*' => 'nullable|integer',
            'remarks'            => 'nullable|string',
            'process_pics'       => 'nullable',
        ];

        if ($this->isMethod('post')) {
            $rules['wo_number']     = 'required|string|max:100';
            $rules['department_id'] = 'required|exists:departments,id';
        }

        return $rules;
    }
}
