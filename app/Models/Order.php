<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\carbon;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "order";

    protected $fillable = [
        "transaction_id",
        "requestor_id",

        "customer_code",

        "material_id",
        "material_code",
        "material_name",

        "category_id",
        "category_name",

        "uom_id",
        "uom_code",

        "uom_type_id",
        "uom_type_code",

        "quantity",
        "remarks",
    ];

    protected $hidden = ["created_at", "updated_at"];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
    public function time()
    {
        return $this->belongsTo(Order::class);
    }
}
