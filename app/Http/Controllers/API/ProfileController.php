<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (empty($GLOBALS['user'])) {
            return response()->json([
                'status' => 404,
                'errors' => 'user_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => $GLOBALS['user'],
        ]);
    }
}
