<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\carbon;
class UpdateRequest extends FormRequest
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
        $requestor_role = $this->user()->role_id;

        return [
            "order_no" => [
                "required",
                Rule::unique("transactions", "order_no")
                    ->ignore($this->route("order"))
                    ->when($requestor_role == 3, function ($query) use ($requestor_id) {
                        return $query->where("requestor_id", $requestor_id);
                    })
                    // ->when($requestor_role == 2, function ($query) use ($requestor_id) {
                    //     return $query->where("requestor_id", $requestor_id);
                    // })
                    ->where(function ($query) {
                        return $query->whereDate("date_ordered", date("Y-m-d"));
                    })
                    ->whereNull("deleted_at"),
            ],
            "date_needed" => "required",
            "customer.id" => "required",
            "customer.code" => "required",
            "customer.name" => "required",
            "rush" => "nullable",

            "order.*.id" => "nullable",

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
                        ->whereNot(function ($query) {
                            return $query->whereIn("id", $this->input("order.*.id"));
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
            // $validator->errors()->add("custom", $this->input("order.*.material.id"));
            // $validator->errors()->add("custom", $this->user()->id);
            // $validator->errors()->add("custom", $this->input("order.*.id"));

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

            if ($is_rush) {
                $validator->errors()->add("rush", "The rush field is required.");
            }
        });
    }
}
