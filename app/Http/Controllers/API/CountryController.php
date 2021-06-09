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
     * @param int $country_id
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountry(int $country_id, Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'lang' => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $country = Country::query()
            ->where('country_id', $country_id)
            ->language($request->get('lang', config('app.default_language')))
            ->first();

        if (empty($country)) {
            return response()->json([
                'status' => 404,
                'errors' => 'country_not_found',
            ]);
        }

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
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
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
     * @param int $city_id
     * @param Request $request
     * @return JsonResponse
     */
    public function getCity(int $city_id, Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'lang' => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $city = City::query()
            ->where('city_id', $city_id)
            ->language($request->get('lang', config('app.default_language')))
            ->first();

        if (empty($city)) {
            return response()->json([
                'status' => 404,
                'errors' => 'city_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => $city->city_name,
        ]);
    }

    /**
     * Получить список всех городов по всем странам или выбранной страны.
     *
     * @param int $country_id
     * @param Request $request
     * @return JsonResponse
     */
    public function getCities(int $country_id, Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'lang' => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $countries = Country::query()
            ->with('cities:country_id,city_id,city_name_' . $request->get('lang', config('app.default_language')) . ' as city_name' )
            ->when(!empty($country_id), function ($query) use ($country_id) {
                return $query->where('country_id', $country_id);
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
