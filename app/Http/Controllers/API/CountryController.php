<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Country;
use App\Models\City;

class CountryController extends Controller
{
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
                'country_id' => 'required|integer|exists:country,country_id',
                'lang'       => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'required'   => 'required_field',
                'integer'    => 'field_must_be_a_number',
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
            ->language($request->get('lang', config('app.default_language')))
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
            ->language($request->get('lang', config('app.default_language')))
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get();

        return response()->json([
            'status' => 200,
            'result' => $countries,
        ]);
    }

    /**
     * Получить наименование города по его коду.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCity(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'city_id'    => 'required|integer|exists:city,city_id',
                'lang'       => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'required'   => 'required_field',
                'integer'    => 'field_must_be_a_number',
                'exists'     => 'city_not_found',
                'in'         => ':attribute_not_exist',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $city = City::query()
            ->where('city_id', $request->get('city_id'))
            ->language($request->get('lang', config('app.default_language')))
            ->first();

        return response()->json([
            'status' => 200,
            'result' => $city->city_name,
        ]);
    }

    /**
     * Получить список всех городов по всем странам или выбранной страны.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCities(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'country_id' => 'sometimes|integer|exists:country,country_id',
                'lang'       => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'integer'    => 'field_must_be_a_number',
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

        $countries = Country::query()
            ->with('cities:country_id,city_id,city_name_' . $request->get('lang', config('app.default_language')) . ' as city_name' )
            ->when($request->filled('country_id'), function ($query) use ($request) {
                return $query->where('country_id', $request->get('country_id'));
            })
            ->language($request->get('lang', config('app.default_language')))
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get()
            ->toArray();

        foreach ($countries as $country_key => $country) {
            foreach ($country['cities'] as $city_key => $city) {
                unset($city['country_id']);
                $countries[$country_key]['cities'][$city_key] = $city;
            }
        }

        return response()->json([
            'status' => 200,
            'result' => $countries,
        ]);
    }
}
