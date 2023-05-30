<?php 

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use DB;

class DBTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        DB::beginTransaction();
        try {
            $response = $next($request);
            // dd($response);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        // if (($response instanceof Response || $response instanceof JsonResponse) && $response->getStatusCode() > 399) {
        if ($response->getStatusCode() > 399) {
            DB::rollBack();
        } else {
            DB::commit();
        }
        return $response;
    }
}