<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\carbon;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["created_at"];

    protected $fillable = [
        "order_no",
        "cip_no",
        "helpdesk_no",
        "date_needed",
        "date_approved",
        "date_ordered",
        "reason",

        "company_id",
        "company_code",
        "company_name",

        "department_id",
        "department_code",
        "department_name",

        "location_id",
        "location_code",
        "location_name",

        "customer_id",
        "customer_code",
        "customer_name",

        "requestor_id",
        "requestor_name",

        "approver_id",
        "approver_name",
        "rush",
        "date_serve",
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, "transaction_id", "id")->withTrashed();
    }
}
