<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\carbon;

use App\Http\Resources\TransactionResource;
use App\Http\Resources\OrderResource;

use App\Models\Transaction;
use App\Models\Order;
use App\Models\Material;
use App\Models\Category;
use App\Models\User;
use App\Models\Cutoff;

use App\Functions\GlobalFunction;
use App\Functions\SmsFunction;

use App\Response\Status;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Requests\Order\UpdateRequest;
use App\Http\Requests\Order\DisplayRequest;
use App\Http\Requests\Order\Validation\ReasonRequest;
use App\Http\Requests\SMS\SMSValidationRequest;

class OrderController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $search = $request->input("search", "");
        $status = $request->input("status", "");
        $rows = $request->input("rows", 10);
        $from = $request->from;
        $to = $request->to;

        $order = Transaction::with("orders")
            ->where("requestor_id", Auth::id())
            ->where(function ($query) use ($search) {
                $query
                    ->where("date_ordered", "like", "%" . $search . "%")
                    ->orWhere("order_no", "like", "%" . $search . "%")
                    ->orWhere("date_needed", "like", "%" . $search . "%")
                    ->orWhere("date_approved", "like", "%" . $search . "%")
                    ->orWhere("company_name", "like", "%" . $search . "%")
                    ->orWhere("department_name", "like", "%" . $search . "%")
                    ->orWhere("location_name", "like", "%" . $search . "%")
                    ->orWhere("customer_code", "like", "%" . $search . "%")
                    ->orWhere("customer_name", "like", "%" . $search . "%");
            })
            ->when(isset($request->from) && isset($request->to), function ($query) use (
                $from,
                $to
            ) {
                $query->where(function ($query) use ($from, $to) {
                    $query
                        ->whereDate("date_ordered", ">=", $from)
                        ->whereDate("date_ordered", "<=", $to);
                });
            })
            ->when($status === "pending", function ($query) {
                $query->whereNull("date_approved");
            })
            ->when($status === "approve", function ($query) {
                $query->whereNotNull("date_approved");
            })
            ->when($status === "disapprove", function ($query) {
                $query->whereNotNull("date_approved")->onlyTrashed();
            })
            ->when($status === "all", function ($query) {
                $query->withTrashed();
            })
            ->orderByRaw("CASE WHEN rush IS NULL THEN 0 ELSE 1 END DESC")
            ->orderByDesc("updated_at")
            ->paginate($rows);

        $is_empty = $order->isEmpty();
        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        TransactionResource::collection($order);

        return GlobalFunction::display_response(Status::ORDER_DISPLAY, $order);
    }

    public function show($id)
    {
        $order = Transaction::with("orders")
            ->where("id", $id)
            ->get();
        if ($order->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $order_collection = TransactionResource::collection($order);

        return GlobalFunction::display_response(Status::USER_DISPLAY, $order_collection->first());
    }

    public function store(StoreRequest $request)
    {
        $time_now = Carbon::now()
            ->timezone("Asia/Manila")
            ->format("H:i");
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");
        $cutoff = date("H:i", strtotime(Cutoff::get()->value("time")));

        // $is_rush =
        //     date("Y-m-d", strtotime($request->date_needed)) == $date_today && $time_now > $cutoff;

        // if ($time_now > $cutoff && !$is_rush && empty($request->rush)) {
        //     return GlobalFunction::cutoff(Status::CUT_OFF);
        // }

        $transaction = Transaction::create([
            "order_no" => $request["order_no"],
            "cip_no" => $request["cip_no"],
            "helpdesk_no" => $request["helpdesk_no"],
            "date_needed" => date("Y-m-d", strtotime($request["date_needed"])),
            "rush" => $request["rush"],

            "company_id" => $request["company"]["id"],
            "company_code" => $request["company"]["code"],
            "company_name" => $request["company"]["name"],

            "department_id" => $request["department"]["id"],
            "department_code" => $request["department"]["code"],
            "department_name" => $request["department"]["name"],

            "location_id" => $request["location"]["id"],
            "location_code" => $request["location"]["code"],
            "location_name" => $request["location"]["name"],

            "customer_id" => $request["customer"]["id"],
            "customer_code" => $request["customer"]["code"],
            "customer_name" => $request["customer"]["name"],

            "requestor_id" => $request["requestor"]["id"],
            "requestor_name" => $request["requestor"]["name"],
        ]);

        foreach ($request->order as $key => $value) {
            Order::create([
                "transaction_id" => $transaction->id,
                "requestor_id" => $request["requestor"]["id"],

                "order_no" => $request["order_no"],

                "customer_code" => $request["customer"]["code"],

                "material_id" => $request["order"][$key]["material"]["id"],
                "material_code" => $request["order"][$key]["material"]["code"],
                "material_name" => $request["order"][$key]["material"]["name"],

                "uom_id" => $request["order"][$key]["uom"]["id"],
                "uom_code" => $request["order"][$key]["uom"]["code"],

                "category_id" => $request["order"][$key]["category"]["id"],
                "category_name" => $request["order"][$key]["category"]["name"],

                "quantity" => $request["order"][$key]["quantity"],
                "remarks" => $request["order"][$key]["remarks"],
            ]);
        }

        return GlobalFunction::save(Status::ORDER_SAVE, $request->toArray());
    }

    public function update(UpdateRequest $request, $id)
    {
        $time_now = Carbon::now()
            ->timezone("Asia/Manila")
            ->format("H:i");
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");
        $is_rush =
            date("Y-m-d", strtotime($request->date_needed)) == $date_today && $time_now > $cutoff;

        if ($time_now > $cutoff && !$is_rush && empty($request->rush)) {
            return GlobalFunction::cutoff(Status::CUT_OFF);
        }

        $transaction = Transaction::find($id);

        $orders = $request->order;

        $not_found = Transaction::where("id", $id)->get();
        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $transaction->update([
            "order_no" => $request["order_no"],
            "cip_no" => $request["cip_no"],
            "helpdesk_no" => $request["helpdesk_no"],
            "date_needed" => date("Y-m-d", strtotime($request["date_needed"])),
        ]);

        $newOrders = collect($orders)
            ->pluck("id")
            ->toArray();
        $currentOrders = Order::where("transaction_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        foreach ($currentOrders as $order_id) {
            if (!in_array($order_id, $newOrders)) {
                Order::where("id", $order_id)->forceDelete();
            }
        }

        foreach ($orders as $index => $value) {
            Order::withTrashed()->updateOrCreate(
                [
                    "id" => $value["id"] ?? null,
                ],
                [
                    "transaction_id" => $transaction["id"],
                    "requestor_id" => $transaction["requestor_id"],

                    "order_no" => $request["order_no"],
                    "customer_code" => $request["customer"]["code"],

                    "material_id" => $value["material"]["id"],
                    "material_code" => $value["material"]["code"],
                    "material_name" => $value["material"]["name"],

                    "category_id" => $value["category"]["id"],
                    "category_name" => $value["category"]["name"],

                    "uom_id" => $value["uom"]["id"],
                    "uom_code" => $value["uom"]["code"],

                    "quantity" => $value["quantity"],
                    "remarks" => $value["remarks"],
                ]
            );
        }

        $order_collection = new TransactionResource($transaction);

        return GlobalFunction::update_response(Status::TRANSACTION_UPDATE, $order_collection);
    }

    // Cancel transaction
    public function cancelTransaction(ReasonRequest $request, $id)
    {
        $user = Auth()->user();
        $user_scope = User::where("id", $user->id)
            ->with("scope_approval")
            ->first()
            ->scope_approval->pluck("location_id");
        $reason = $request->reason;
        $transaction = Transaction::where("id", $id);

        $not_found = $transaction->get();
        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $not_allowed = $transaction
            ->when($user->role_id == 3, function ($query) use ($user) {
                return $query->where("requestor_id", $user->id);
            })
            ->when($user->role_id == 2, function ($query) use ($user_scope) {
                return $query->whereIn("location_id", $user_scope);
            })
            ->get();
        if ($not_allowed->isEmpty()) {
            return GlobalFunction::denied(Status::ACCESS_DENIED);
        }

        $result = $transaction
            ->get()
            ->first()
            ->update([
                "approver_id" => $user->id,
                "approver_name" => $user->account_name,
                "date_approved" => date("Y-m-d H:i:s"),
                "reason" => $request->reason,
            ]);

        Transaction::withTrashed()
            ->where("id", $id)
            ->delete();
        Order::where("transaction_id", $id)->delete();

        return GlobalFunction::delete_response(Status::ARCHIVE_STATUS, $result);
    }
    //cancel order
    public function cancelOrder(Request $request, $id)
    {
        $user = Auth()->user();
        $user_scope = User::where("id", $user->id)
            ->with("scope_approval")
            ->first()
            ->scope_approval->pluck("location_code");

        $order = Order::where("id", $id);

        $not_found = $order->get();
        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $not_allowed = $order
            ->when($user->role_id == 2, function ($query) use ($user_scope) {
                return $query->whereIn("customer_code", $user_scope);
            })
            ->get();
        if ($not_allowed->isEmpty()) {
            return GlobalFunction::delete_response(Status::ACCESS_DENIED);
        }

        $check_siblings = Order::where(
            "transaction_id",
            $order->get()->first()->transaction_id
        )->get();
        if ($check_siblings->count() > 1) {
            $order = $order->get()->first();

            Order::where("id", $id)->delete();

            return GlobalFunction::delete_response(Status::ARCHIVE_STATUS, $order);
        }
        Transaction::where("id", $order->get()->first()->transaction_id)
            ->get()
            ->first()
            ->update([
                "approver_id" => $user->id,
                "approver_name" => $user->account_name,
                "date_approved" => date("Y-m-d H:i:s"),
            ]);

        Transaction::withTrashed()
            ->where("id", $order->get()->first()->transaction_id)
            ->delete();
        Order::where("transaction_id", $order->get()->first()->transaction_id)->delete();

        return GlobalFunction::delete_response(
            Status::ARCHIVE_STATUS,
            $order
                ->withTrashed()
                ->get()
                ->first()
        );
    }

    public function sms_order(Request $request)
    {
        $requestor_no = current($request->results)["from"];
        $content = current($request->results)["cleanText"];

        $header = current(preg_split("/\\r\\n|\\r|\\n/", $content));
        $validate_header = SmsFunction::validate_header($header, $requestor_no);

        if (!empty($validate_header)) {
            return SmsFunction::send($requestor_no, $validate_header);
        }

        $body = explode("#", $content)[1];
        $validate_body = SmsFunction::validate_body($header, $body, $requestor_no);

        if (!empty($validate_body)) {
            return SmsFunction::send($requestor_no, $validate_body);
        }

        return SmsFunction::save_sms_order($header, $body, $requestor_no);
        //    explode('',$header) ;
    }
}
