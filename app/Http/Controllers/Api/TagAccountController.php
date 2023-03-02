<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TagAccount;

class TagAccountController extends Controller
{
    public function index()
    {
      $tag = TagAccount::get();
      return $tag;
    }
}
