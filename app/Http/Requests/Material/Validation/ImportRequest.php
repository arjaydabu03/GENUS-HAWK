<?php

namespace App\Http\Requests\Material\Validation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ImportRequest extends FormRequest
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
            '*.code'=> [
                'required',
                'string',
                'unique:materials,code',
                'distinct'
            ],
            '*.name'=>'required|string',
            '*category'=>'required|exists:categories,id,deleted_at,NULL',
            '*uom'=>'required|exists:uom,id,deleted_at,NULL',
            '*warehouse'=>'required|exists:warehouse,id,deleted_at,NULL'
        ];
    }

    public function attributes()
    {
       return [
            "code" => "code",
            "category" => "category",
            "uom" => "uom",
            "warehouse" => "warehouse"

       ];
    }

    public function messages()
    {
        return [
          
            "*.exists"  =>  ":Attribute is not registered.",
            "*.distinct"  =>  ":Attribute is already been taken.",
        ];
    }
}
