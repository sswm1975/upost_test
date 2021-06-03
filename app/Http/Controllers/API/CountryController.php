<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Country;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    const DEFAULT_LANG = 'en';

    /**
     * Получить наименование страниы по её коду.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountry(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'country_id' => 'required|numeric|exists:country,country_id',
                'lang'       => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'required'   => 'required_field',
                'numeric'    => 'field_must_be_a_number',
                'exists'     => 'country_not_found',
                'in'         => ':attribute_not_exist',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $country = Country::query()
            ->where('country_id', $request->get('country_id'))
            ->language($request->get('lang', self::DEFAULT_LANG))
            ->first();

        return response()->json([
            'status' => 200,
            'result' => $country->country_name,
        ]);
    }

    /**
     * Получить список всех стран.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountries(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'lang' => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'in'   => ':attribute_not_exist',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $countries = Country::query()
            ->language($request->get('lang', self::DEFAULT_LANG))
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get();

        return response()->json([
            'status' => 200,
            'result' => $countries,
        ]);
    }
}
