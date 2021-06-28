<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
     * @throws ErrorException
     */
    public function getCountry(int $country_id): JsonResponse
    {
        $country = Country::language(App::getLocale())
            ->where('country_id', $country_id)
            ->first();

        if (!$country) throw new ErrorException(__('message.country_not_found'));

        return response()->json([
            'status' => true,
            'result' => $country->country_name,
        ]);
    }

    /**
     * Получить список всех стран.
     *
     * @return JsonResponse
     */
    public function getCountries(): JsonResponse
    {
        $countries = Country::language(App::getLocale())
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
     * @return JsonResponse
     * @throws ErrorException
     */
    public function getCity(int $city_id): JsonResponse
    {
        $city = City::language(App::getLocale())
            ->where('city_id', $city_id)
            ->first();

        if (!$city) throw new ErrorException(__('message.city_not_found'));

        return response()->json([
            'status' => true,
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
        $countries = Country::language(App::getLocale())
            ->with('cities:country_id,city_id,city_name_' . App::getLocale() . ' as city_name' )
            ->when($country_id, function ($query) use ($country_id) {
                return $query->where('country_id', $country_id);
            })
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
