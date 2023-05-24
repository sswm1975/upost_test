<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidatorException extends Exception
{
    /** @var array  */
    private array $errors;

    /**
     * ValidatorException constructor.
     *
     * @param mixed $error
     */
    public function __construct($error)
    {
        parent::__construct();
        if (is_array($error) || $error instanceof Arrayable) {
            $this->errors = $error;
        } else {
            $this->errors = [$error];
        }
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
            $data['request'] = request()->all();
            $data['auth_user'] = request()->user() ?? [];
        }

        return response()->json($data, Response::HTTP_BAD_REQUEST);
    }
}
