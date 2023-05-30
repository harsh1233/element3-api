<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        $roles = explode("|",$roles);
        $status = false;
        foreach($roles as $role) {
            if($request->user()->type() === $role) {
                $status = true;
                break;
            }
        }
        if(!$status) return response()->json(["error"=>"Access Forbidden"],403);
        return $next($request);
    }
}
