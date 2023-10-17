<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Response\Status;
use App\Functions\GlobalFunction;

use App\Models\Warehouse;
use App\Models\User;
use App\Models\Material;
use App\Models\Tagwarehouse;
use App\Http\Resources\TagWarehouseResource;
use App\Http\Resources\WarehouseResource;

use App\Http\Requests\Warehouse\StoreRequest;
use App\Http\Requests\Warehouse\DisplayRequest;
use App\Http\Requests\Warehouse\Validation\CodeRequest;

class WarehouseController extends Controller
{
    public function index(DisplayRequest $request)
    {
        // $tagwarehouse = TagWarehouse::get()->first();

        // return $tagresource = new TagWarehouseResource($tagwarehouse);
        $status = $request->status;
        $search = $request->search;
        $paginate = isset($request->paginate) ? $request->paginate : 1;

        $warehouse = Warehouse::with("material")
            ->when($status === "inactive", function ($query) {
                $query->onlyTrashed();
            })
            ->when($search, function ($query) use ($search) {
                $query
                    ->where("code", "like", "%" . $search . "%")
                    ->orWhere("name", "like", "%" . $search . "%");
            });

        $warehouse = $paginate
            ? $warehouse->orderByDesc("updated_at")->paginate($request->rows)
            : $warehouse->orderByDesc("updated_at")->get();

        $is_empty = $warehouse->isEmpty();

        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        // $warehouses = WarehouseResource::collection($warehouse);

        return GlobalFunction::response_function(Status::WAREHOUSE_DISPLAY, $warehouse);
    }

    public function show($id)
    {
        $warehouse = Warehouse::where("id", $id)->get();

        if ($warehouse->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        return GlobalFunction::response_function(Status::WAREHOUSE_DISPLAY, $warehouse->first());
    }

    public function store(StoreRequest $request)
    {
        $material = $request["material"];

        $warehouse = Warehouse::create([
            "code" => $request["code"],
            "name" => $request["name"],
        ]);
        $warehouse->material()->attach($material);

        // foreach ($material as $key => $value) {
        //     Tagwarehouse::create([
        //         "warehouse_id" => $warehouse->id,
        //         "material_id" => $material[$key]["id"],
        //         "material_code" => $material[$key]["code"],
        //         "material_name" => $material[$key]["name"],
        //     ]);
        // }
        $warehouse = $warehouse
            ->with("material")
            ->latest()
            ->first();

        return GlobalFunction::save(Status::WAREHOUSE_SAVE, $warehouse);
    }

    public function update(StoreRequest $request, $id)
    {
        $warehouse = Warehouse::find($id);

        $not_found = Warehouse::where("id", $id)->get();

        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }
        $material = $request["material"];

        $warehouse->update([
            "code" => $request["code"],
            "name" => $request["name"],
        ]);
        $warehouse->material()->sync($material);

        // foreach ($material as $key => $value) {
        //     Tagwarehouse::create([
        //         "warehouse_id" => $warehouse->id,
        //         "material_id" => $material[$key]["id"],
        //         "material_code" => $material[$key]["code"],
        //         "material_name" => $material[$key]["name"],
        //     ]);
        // }
        $warehouse = $warehouse
            ->with("material")
            ->latest()
            ->first();

        return GlobalFunction::response_function(Status::WAREHOUSE_UPDATE, $warehouse);
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::where("id", $id)
            ->withTrashed()
            ->get();

        $warehouse_id = Warehouse::where("id", $id)
            ->withTrashed()
            ->get()
            ->first();

        $taguser = User::whereIn("warehouse_id", $warehouse_id)->exists();

        if ($taguser) {
            return GlobalFunction::invalid(Status::TAG_USER_WAREHOUSE);
        }

        if ($warehouse->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $warehouse = Warehouse::withTrashed()->find($id);
        $is_active = Warehouse::withTrashed()
            ->where("id", $id)
            ->first();
        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $warehouse->delete();
            $message = Status::ARCHIVE_STATUS;
        } else {
            $warehouse->restore();
            $message = Status::RESTORE_STATUS;
        }
        return GlobalFunction::response_function($message, $warehouse);
    }

    public function code_validate(CodeRequest $request)
    {
        return GlobalFunction::response_function(Status::SINGLE_VALIDATION);
    }
}
