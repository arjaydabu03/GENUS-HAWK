<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\carbon;
use App\Models\Cutoff;
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
        $order_no = $this->input("order_no");
        $customer_code = $this->input("customer.code");

        $requestor_id = $this->user()->id;

        return [
            "order_no" => [
                "required",
                Rule::unique("transactions", "order_no")
                    ->where("requestor_id", $requestor_id)
                    ->where(function ($query) {
                        return $query->whereDate("date_ordered", date("Y-m-d"));
                    })
                    ->whereNull("deleted_at"),
            ],
            "date_needed" => "required",
            "rush" => "nullable",

            "company.id" => "required",
            "company.code" => "required",
            "company.name" => "required",

            "department.id" => "required",
            "department.code" => "required",
            "department.name" => "required",

            "location.id" => "required",
            "location.code" => "required",
            "location.name" => "required",

            "requestor.id" => ["required", "exists:users,id,deleted_at,NULL"],
            "requestor.name" => "required",

            "customer.id" => "required",
            "customer.code" => "required",
            "customer.name" => "required",

            "order.*.material.id" => ["required", "distinct"],
            "order.*.material.code" => [
                "required",
                "exists:materials,code,deleted_at,NULL",
                Rule::unique("order", "material_code")->where(function ($query) use (
                    $customer_code,
                    $order_no,
                    $requestor_id
                ) {
                    return $query
                        ->where("order_no", $order_no)
                        ->where("customer_code", $customer_code)
                        ->where("requestor_id", $requestor_id)
                        ->where(function ($query) {
                            return $query->whereDate("created_at", date("Y-m-d"));
                        })
                        ->whereNull("deleted_at");
                }),
            ],
            "order.*.material.name" => "required",

            "order.*.category.id" => ["required", "exists:categories,id,deleted_at,NULL"],
            "order.*.category.name" => "required",

            "order.*.uom.id" => ["required", "exists:uom,id,deleted_at,NULL"],
            "order.*.uom.code" => "required",

            "order.*.quantity" => "required",
            "order.*.remarks" => "nullable",
        ];
    }

    public function attributes()
    {
        return [
            "order_no" => "order no.",
            "order.*.material.code" => "material",
            "order.*.material.id" => "Item",
        ];
    }

    public function messages()
    {
        return [
            "order.*.material.code.unique" => "This :attribute has already been ordered.",
            "order.*.material.id.distinct" => "This :attribute has already been ordered.",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // $validator->errors()->add("custom", $this->user()->id);
            // $validator->errors()->add("custom", $this->route()->id);
            // $validator->errors()->add("custom", "STOP!");
            $time_now = Carbon::now()
                ->timezone("Asia/Manila")
                ->format("H:i");
            $date_today = Carbon::now()
                ->timeZone("Asia/Manila")
                ->format("Y-m-d");
            $cutoff = date("H:i", strtotime(Cutoff::get()->value("time")));

            $is_rush =
                date("Y-m-d", strtotime($this->input("date_needed"))) == $date_today &&
                $time_now > $cutoff;

            $with_rush_remarks = !empty($this->input("rush"));

            if ($is_rush && !$with_rush_remarks) {
                $validator->errors()->add("rush", "The rush field is required.");
            }
        });
    }
}
