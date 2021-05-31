<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\User;

class ProfilePublicController extends Controller
{
    /**
     * Public profile fields.
     *
     * @var array
     */
    const FIELDS = [
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
     * Get user's profile (public fields).
     *
     * @param  int                       $id
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $user = User::query()
            ->where('user_id', $id)
            ->first(self::FIELDS)
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
