<?php

namespace App\Exceptions;

use Exception;

class ValidatorException extends Exception
{
    //
    private $errors;
    public function __construct(Array $errors)
    {
        parent::__construct();
        $this->errors = $errors;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render()
    {

        return response()->json([
            'status' => false,
            'errors' => $this->errors
        ], 404);
    }
}
