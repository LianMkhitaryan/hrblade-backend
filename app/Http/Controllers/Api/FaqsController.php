<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqsController extends BaseController
{
   public function all()
   {
       return $this->success(Faq::where('active',1)->get());
   }
}
