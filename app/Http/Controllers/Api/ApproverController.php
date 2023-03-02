<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Order;
use App\Models\User;
use App\Models\Transaction;

use App\Http\Resources\OrderResource;
use App\Http\Resources\TransactionResource;

use App\Response\Status;
use App\Functions\GlobalFunction;
use App\Http\Requests\Order\DisplayRequest;

class ApproverController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $search = $request->input("search", "");
        $status = $request->input("status", "");
        $rows = $request->input("rows", 10);
        $from = $request->from;
        $to = $request->to;


        $user_scope = User::where("id", Auth::id())
            ->with("scope_approval")
            ->first()
            ->scope_approval->pluck("location_id");

        $order = Transaction::with("orders")
            ->where(function ($query) use ($user_scope) {
                $query->whereIn("location_id", $user_scope)->whereNot("requestor_id", Auth::id());
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
             
            ->orderByDesc("updated_at")
            ->paginate($rows);

        if ($order->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        TransactionResource::collection($order);

        return GlobalFunction::display_response(Status::ORDER_DISPLAY, $order);
    }

    public function update(Request $request, $id)
    {
        $user = Auth()->user();
        $user_scope = User::where("id", $user->id)
            ->with("scope_approval")
            ->first()
            ->scope_approval->pluck("location_id");

        $transaction = Transaction::where("id", $id);

        $not_found = $transaction->get();
        if ($not_found->isEmpty()) {
            return GlobalFunction::not_found(Status::NOT_FOUND);
        }

        $not_allowed = $transaction->whereIn("location_id", $user_scope)->get();
        if ($not_allowed->isEmpty()) {
            return GlobalFunction::denied(Status::ACCESS_DENIED);
        }

        $order = $transaction
            ->get()
            ->first()
            ->update([
                "approver_id" => $user->id,
                "approver_name" => $user->account_name,
                "date_approved" => date("Y-m-d H:i:s"),
            ]);

        return GlobalFunction::save(Status::TRANSACTION_APPROVE, $order);
    }
}
