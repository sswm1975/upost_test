<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ErrorException extends Exception
{
    /** @var string  */
    private string $error;

    /**
     * ErrorException constructor.
     *
     * @param string $error
     * @param int $code
     */
    public function __construct(string $error, int $code = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct();
        $this->error = $error;
        $this->code = $code;
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        $data = [
            'status' => false,
            'errors' => [$this->error]
        ];

        if (env('APP_ENV') == 'local' || env('APP_DEBUG')) {
            $data['sql'] = getSQLForFixDatabase();
        }

        return response()->json($data, $this->code);
    }
}
