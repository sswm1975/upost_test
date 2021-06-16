<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Country;
use App\Models\City;

class CountryController extends Controller
{
    /**
     * Получить наименование страниы по её коду.
     *
     * @param int $country_id
     * @return JsonResponse
     */
    public function getCountry(int $country_id): JsonResponse
    {
        $country = Country::query()
            ->where('country_id', $country_id)
            ->language(App::getLocale())
            ->first();

        if (empty($country)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.country_not_found')],
            ], 404);
        }

        return response()->json([
            'status' => true,
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
        $countries = Country::query()
            ->language(App::getLocale())
            ->addSelect('country_id')
            ->oldest('country_id')
            ->get();

        return response()->json([
            'status' => true,
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
        $city = City::query()
            ->where('city_id', $city_id)
            ->language(App::getLocale())
            ->first();

        if (empty($city)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.city_not_found')],
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'result' => $city->city_name,
        ]);
    }

    /**
     * Получить список всех городов по всем странам или выбранной стране.
     *
     * @param int $country_id
     * @return JsonResponse
     */
    public function getCities(int $country_id = 0): JsonResponse
    {
        $countries = Country::query()
            ->with('cities:country_id,city_id,city_name_' . App::getLocale() . ' as city_name' )
            ->when(!empty($country_id), function ($query) use ($country_id) {
                return $query->where('country_id', $country_id);
            })
            ->language(App::getLocale())
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
            'status' => true,
            'result' => $countries,
        ]);
    }
}
