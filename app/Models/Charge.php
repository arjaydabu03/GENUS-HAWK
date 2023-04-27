<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "charge";

    protected $fillable = ["code", "sync_id", "name", "deleted_at"];

    protected $hidden = ["created_at", "deleted_at"];
}
