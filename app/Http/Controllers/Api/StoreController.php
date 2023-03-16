<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Response\Status;
use App\Functions\GlobalFunction;

use App\Models\Store;
use App\Models\TagStoreLocation;

use App\Http\Resources\StoreResource;
use App\Http\Resources\TagAccountResource;

class StoreController extends Controller
{

    public function index(){
         $user_store= Store::with('tag_store')->get();

         if ($user_store->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $store_collect = StoreResource::collection($user_store);

        return GlobalFunction::display_response(Status::STORE_DISPLAY,$store_collect);
    }
    public function store(Request $request)
    {
        $user_store = new Store([
            "account_code" => $request["code"],
            "account_name" => $request["name"],

            "location_id" => $request["location"]["id"],
            "location_code" => $request["location"]["code"],
            "location" => $request["location"]["name"],

            "department_id" => $request["department"]["id"],
            "department_code" => $request["department"]["code"],
            "department" => $request["department"]["name"],

            "company_id" => $request["company"]["id"],
            "company_code" => $request["company"]["code"],
            "company" => $request["company"]["name"],
            "mobile_no" => $request["mobile_no"],

        ]);
        $user_store->save();

        $store_order = $request["tag_store"];

        foreach ($store_order as $key => $value) {
            TagStoreLocation::create([
                "account_id" => $user_store->id,
                "location_id" => $store_order[$key]["id"],
                "location_code" => $store_order[$key]["code"],
            ]);
        }


        $store_collect = new StoreResource($user_store);

        return GlobalFunction::save(Status::STORE_REGISTERED,$store_collect);
    }

}
