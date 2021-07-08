<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

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

        if (app()->environment('local') || config('app.debug')) {
            $data['sql'] = getSQLForFixDatabase();
        }

        return response()->json($data, 404);
    }
}
