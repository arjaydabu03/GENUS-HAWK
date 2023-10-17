<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend("duplicate_item", function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();

            $ignore = $parameters[0] ?? null;

            $customer_code = $data["customer"]["code"];
            $date_needed = $data["date_needed"];
            $requestor_id = Auth()->user()->id;
            $uom_code = array_map(function ($query) {
                return $query["uom_type"]["code"];
            }, $data["order"]);
            $material_code = array_map(function ($query) {
                return $query["material"]["code"];
            }, $data["order"]);
            // error_log(json_encode($parameters), 3, "log.log");
            // return false;

            return DB::table("order")
                ->join("transactions", "order.transaction_id", "transactions.id")
                ->where("order.customer_code", $customer_code)
                ->where("order.requestor_id", $requestor_id)
                ->whereIn("order.material_code", $material_code)
                ->whereIn("order.uom_type_code", $uom_code)
                ->where("transactions.date_needed", $date_needed)
                ->when($ignore, function ($query) use ($ignore) {
                    return $query->whereNot("transactions.id", $ignore);
                })
                ->doesntExist();
        });
    }
}
