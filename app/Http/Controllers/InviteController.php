<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function form($hash)
    {
        $invite = Invite::where('hash', $hash)->first();

        $view = false;

        return view('landing.register-hash',compact('view','invite'));
    }
}
