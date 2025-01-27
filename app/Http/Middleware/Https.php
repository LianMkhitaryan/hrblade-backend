<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Https
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        }

        elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $isSecure = true;
        }

        if (!$isSecure && env('APP_ENV') === 'production') {//optionally disable for localhost development
            return redirect()->secure($request->getRequestUri());
        }


//        header("Access-Control-Allow-Origin: *");
//
//        // ALLOW OPTIONS METHOD
//        $headers = [
//            'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS, PUT, DELETE',
//            'Access-Control-Allow-Headers'=> 'Content-Type, X-Auth-Token, Origin, Accept, X-LS-CORS-Template, X-LS-Auth-Token,X-LS-Auth-User-Token,Content-Type,X-LS-Sync-Result,X-LS-Sequence,token'
//        ];
//        if($request->getMethod() == "OPTIONS") {
//            // The client-side application can set only headers allowed in Access-Control-Allow-Headers
//            return Response::make('OK', 200, $headers);
//        }
//
//        $response = $next($request);
//        foreach($headers as $key => $value)
//            $response->header($key, $value);

        return $next($request);
    }
}
