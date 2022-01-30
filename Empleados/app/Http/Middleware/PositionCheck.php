<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PositionCheck
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

        $user = $request->user;

        if($user->puesto =='Dirección' || $user->puesto =='RRHH'){
            return $next($request);
        }else{
            $answer['msg'] = "Este usuario no tiene permisos";
        }


        return response()-> json($answer);
    }
}
