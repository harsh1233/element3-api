<?php

namespace App\Exceptions;

use Exception;
use App\Models\ExceptionManagement;
use DB;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        //if (!method_exists($exception, 'getStatusCode')) 
        // if ($exception instanceof \ErrorException) 
        // {
        $user_id = 0;
        if (auth()->user()) 
        {
            $user_id = auth()->user()->id;
        }
        //rollback all the transactions so far
        DB::rollback();
        //begin new transaction for database insert of exception_management_object
        DB::beginTransaction();
        try 
        {
            $exception_management_object = ExceptionManagement::create(
                [
                    'message'=>$exception->getMessage(),
                    'stack_trace'=>json_encode($exception->getTrace()),
                    'file'=>$exception->getFile(),
                    'line'=>$exception->getLine(),
                    'header_info'=>json_encode($request->header()),
                    'ip'=>$request->ip(),'created_by'=>$user_id
                ]);

            DB::commit();
        } 
        catch (\Exception $e) 
        {
            DB::rollback();
        }

        if (config('app.debug') == false) 
        {
            $error = "Error #".$exception_management_object->id;
        }
        else
        {
            $error = $exception->getMessage();
        }

        if(!auth()->user()){
            $code = 401;//Unauthenticated error code
        }else{
            $code = 555;//Authenticated error code
        }
        return response()->json(['error' => $error], $code);
    // }
    // return parent::render($request, $exception);
    }
}
