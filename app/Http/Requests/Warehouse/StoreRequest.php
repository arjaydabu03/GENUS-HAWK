<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "code" => [
                "required",
                "string",
                $this->route()->warehouse
                    ? "unique:warehouse,code," . $this->route()->warehouse
                    : "unique:warehouse,code",
            ],
            "name" => "required",

            "material" => [
                "required",
                "exists:materials,id,deleted_at,NULL",
                // Rule::exists("materials")->where(function ($query) {
                //     return $query->where("code", $this->input("material.*.code"));
                // }),
            ],
        ];
    }
    public function attributes()
    {
        return [
            "material.*.id" => "material",
        ];
    }

    public function messages()
    {
        return [
            "exists" => "This :attribute is not registered",
        ];
    }
}
