<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class TokenCheck
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

        if($request->has('api_token')){

            //Comprobar que existe un usuario con ese token
            $apiToken = $request->input('api_token');
            $user = User::where('api_token', $apiToken)->first();

            if(!$user){

                $answer['msg'] = "El usuario no existe";

            }else{

                $request->user = $user;
                return $next($request);

            }

        }else{
            $answer['msg'] = "Token vacio";
        }

        return response()->json($answer);

    }
}
