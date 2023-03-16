<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagStoreLocation extends Model
{
    use HasFactory;

    protected $table = "tag_store_location";

    protected $fillable = ["account_id", "location_id", "location_code"];
}
