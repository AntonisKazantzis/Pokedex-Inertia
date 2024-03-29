<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);
        $status = $response->status();

        if (in_array($status, [404, 500, 503, 403, 401])) {
            return Inertia::render('ErrorMessage', compact('status'))->toResponse($request)->setStatusCode($status);
        } elseif ($status === 419) {
            return redirect()->back()->withErrors(['status' => __('The page expired, please try again.')]);
        }

        return $response;
    }
}
