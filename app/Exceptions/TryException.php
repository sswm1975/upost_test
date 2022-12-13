<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TryException extends Exception
{
    /** @var string  */
    private string $error;

    /**
     * TryException constructor.
     *
     * @param string $error
     */
    public function __construct(string $error)
    {
        parent::__construct();
        $this->error = $error;
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
            $data['request'] = request()->all();
            $data['auth_user'] = request()->user() ?? [];
        }

        return response()->json($data, Response::HTTP_NOT_FOUND);
    }
}
