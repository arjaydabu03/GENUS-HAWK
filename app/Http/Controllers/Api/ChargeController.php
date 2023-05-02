<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Response\Status;
use App\Functions\GlobalFunction;
use App\Models\Charge;

use App\Http\Requests\Cutoff\DisplayRequest;
use App\Http\Requests\Charge\ImportRequest;
class ChargeController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;
        $search = $request->search;
        $paginate = isset($request->paginate) ? $request->paginate : 1;

        $charge = Charge::when($status === "inactive", function ($query) {
            $query->onlyTrashed();
        })->when($search, function ($query) use ($search) {
            $query
                ->where("code", "like", "%" . $search . "%")
                ->orWhere("name", "like", "%" . $search . "%");
        });

        $charge = $paginate
            ? $charge->orderByDesc("updated_at")->paginate($request->rows)
            : $charge->orderByDesc("updated_at")->get();

        $is_empty = $charge->isEmpty();

        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        return GlobalFunction::response_function(Status::CHARGE_DISPLAY, $charge);
    }
    public function store(ImportRequest $request)
    {
        $sync = $request->all();

        $charge = Charge::upsert($sync, ["sync_id"], ["code", "name", "deleted_at"]);

        return GlobalFunction::save(Status::CHARGE_IMPORT, $request->toArray());
    }
}
