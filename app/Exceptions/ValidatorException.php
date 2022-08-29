<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidatorException extends Exception
{
    /** @var array  */
    private array $errors;

    /**
     * ValidatorException constructor.
     *
     * @param array $errors
     */
    public function __construct(Array $errors)
    {
        parent::__construct();
        $this->errors = $errors;
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
            'errors' => $this->errors
        ];

        if (env('APP_ENV') == 'local' || env('APP_DEBUG')) {
            $data['sql'] = getSQLForFixDatabase();
        }

        return response()->json($data, Response::HTTP_BAD_REQUEST);
    }
}
