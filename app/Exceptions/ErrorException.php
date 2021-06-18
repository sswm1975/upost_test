<?php

namespace App\Exceptions;

use Exception;

class ErrorException extends Exception
{
    //
    //
    private $error;
    public function __construct(string $error)
    {
        parent::__construct();
        $this->error = $error;
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
            'errors' => [$this->error]
        ], 404);
    }
}
