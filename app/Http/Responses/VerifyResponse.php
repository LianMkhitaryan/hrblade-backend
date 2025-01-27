<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyResponseContract;

class VerifyResponse implements VerifyResponseContract
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

        dd($user);
        $token = $user->createToken('intrewoo');

        Auth::logout();

        setcookie("token-app", $token->plainTextToken, time()+30, "", "interwoo.com", 0, false);

        return redirect()->away(env('APP_PAGE'));
    }
}