<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\OrderResource;
use App\Http\Resources\TransactionResource;

use App\Models\Order;
use App\Models\Transaction;

use App\Response\Status;
use App\Functions\GlobalFunction;
use Carbon\carbon;

class ReportController extends Controller
{
    public function view(Request $request)
    {
        $search = $request->input("search", "");
        $status = $request->input("status", "");
        $rows = $request->input("rows", 10);
        $paginate = $request->input("paginate", 1);
        $from = $request->from;
        $to = $request->to;
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");

        $order = Transaction::with("orders")
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
                        ->whereDate("date_needed", ">=", $from)
                        ->whereDate("date_needed", "<=", $to);
                });
            })
            ->when($status === "today", function ($query) use ($date_today) {
                $query->whereNull("date_approved")->whereDate("date_needed", $date_today);
            })
            ->when($status === "pending", function ($query) use ($date_today) {
                $query->whereDate("date_needed", ">", $date_today)->whereNull("date_approved");
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
            ->orderByDesc("updated_at");

        $order = $paginate
            ? $order->orderByDesc("updated_at")->paginate($rows)
            : $order
                ->orderByDesc("updated_at")
                ->with("orders")
                ->get();

        $is_empty = $order->isEmpty();
        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        TransactionResource::collection($order);

        return GlobalFunction::display_response(Status::ORDER_DISPLAY, $order);
    }
    public function count(Request $request)
    {
        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")

            ->format("Y-m-d");

        $all = Transaction::withTrashed()
            ->get()
            ->count();
        $today = Transaction::whereNull("date_approved")
            ->whereDate("date_needed", $date_today)
            ->get()
            ->count();
        $pending = Transaction::whereNull("date_approved")
            ->whereDate("date_needed", ">", $date_today)
            ->get()
            ->count();
        $approve = Transaction::whereNotNull("date_approved")
            ->get()
            ->count();
        $disapprove = Transaction::onlyTrashed()
            ->whereNotNull("date_approved")
            ->get()
            ->count();

        $count = [
            "all" => $all,
            "today" => $today,
            "pending" => $pending,
            "approve" => $approve,
            "disapprove" => $disapprove,
        ];

        return GlobalFunction::display_response(Status::COUNT_DISPLAY, $count);
    }
    public function export(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $date_today = Carbon::now()
            ->timeZone("Asia/Manila")
            ->format("Y-m-d");

        $order = Order::with("transaction")
            ->where(function ($query) use ($date_today) {
                $query->whereHas("transaction", function ($query) use ($date_today) {
                    $query->whereNotNull("date_approved");
                });
            })
            ->when(isset($request->from) && isset($request->to), function ($query) use (
                $from,
                $to
            ) {
                $query->whereHas("transaction", function ($query) use ($from, $to) {
                    $query
                        ->whereDate("date_needed", ">=", $from)
                        ->whereDate("date_needed", "<=", $to);
                });
            })
            ->get();
        return $order;
    }
}
