<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagwarehouse extends Model
{
    use HasFactory;

    protected $table = "tagwarehouse";

    protected $fillable = ["warehouse_id", "material_id", "material_code", "material_name"];

    protected $hidden = ["created_at", "updated_at", "deleted_at"];

   
}
