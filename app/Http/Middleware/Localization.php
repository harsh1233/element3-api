<?php

namespace App\Http\Middleware;

use Closure;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** Get header language value */
         $locale = $request->header('Accept-Language');
         if(!$locale)$locale = 'en';

         /** Set project language */
         \App::setlocale($locale);
        return $next($request);

    }
}
