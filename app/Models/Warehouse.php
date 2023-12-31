<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "warehouse";

    protected $fillable = ["code", "name"];

    protected $hidden = ["created_at"];

    function material()
    {
        return $this->belongsToMany(Material::class, "tagwarehouse", "warehouse_id", "material_id");
    }
}
