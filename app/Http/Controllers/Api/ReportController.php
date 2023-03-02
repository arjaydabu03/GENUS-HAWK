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


class ReportController extends Controller
{
    public function view(Request $request)
    {
        $search = $request->input("search", "");
        $status = $request->input("status", "");
        $rows = $request->input("rows", 10);
        $paginate=$request->input('paginate', 1);
        $from = $request->from;
        $to = $request->to;

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
            ->when(isset($request->from)&& isset($request->to),function($query) use ($from,$to){
                $query->where(function($query) use ($from,$to){
                    $query->whereDate('date_ordered', '>=', $from)
                   ->whereDate('date_ordered','<=',$to); 
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
            ->orderByDesc("updated_at");
           
            $order=$paginate?$order->orderByDesc('updated_at')
            ->paginate($rows):$order->orderByDesc('updated_at')->with("orders")->get();

        $is_empty = $order->isEmpty();
        if ($is_empty) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        TransactionResource::collection($order);

        return GlobalFunction::display_response(Status::ORDER_DISPLAY,$order);
    }
}
