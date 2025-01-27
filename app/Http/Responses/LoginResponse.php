<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = Auth::user();

        if(!$user->email_verified_at) {
            return redirect()->back()->with(['status' => 'Check your E-mail']);
        }

        return redirect()->to(route('dashboardapp'));
    }
}