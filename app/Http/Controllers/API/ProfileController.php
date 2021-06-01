<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\User;

class ProfileController extends Controller
{
    /**
     * List fields for public profile.
     *
     * @var array
     */
    const PUBLIC_FIELDS = [
        'user_id',
        'user_name',
        'user_surname',
        'user_location',
        'user_register_date',
        'user_last_active',
        'user_status',
        'user_birthday',
        'user_gender',
        'user_photo',
        'user_resume',
        'user_freelancer_rating',
        'user_creator_rating',
    ];

    /**
     * Get private user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPrivateProfile(Request $request): JsonResponse
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

    /**
     * Get public user's profile.
     *
     * @param  int     $id
     * @param  Request $request
     * @return JsonResponse
     */
    public function getPublicProfile(int $id, Request $request): JsonResponse
    {
        $user = User::query()
            ->where('user_id', $id)
            ->first(self::PUBLIC_FIELDS)
            ->toArray();

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'errors' => 'user_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => null_to_blank($user),
        ]);
    }
}
