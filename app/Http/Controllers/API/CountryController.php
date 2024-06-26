<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Инкрементный поиск Страны или Города.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ErrorException
     */
    public function searchCountryOrCity(Request $request): JsonResponse
    {
        $name =  strip_tags(strip_unsafe(trim($request->get('name'))));

        if (mb_strlen($name) < 2) throw new ErrorException(__('message.country_or_city_start_search'));

        $found = array_merge(Country::filterByCountryName($name), Country::filterByCityName($name));

        if (!count($found)) throw new ErrorException(__('message.country_or_city_not_found'));

        return response()->json(['result' => $found]);
    }

    /**
     * Получить наименование страны по её коду.
     *
     * @param int $country_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function getCountry(int $country_id): JsonResponse
    {
        $country = Country::getCountries($country_id);

        if (!$country) throw new ErrorException(__('message.country_not_found'));

        return response()->json([
            'status' => true,
            'result' => $country,
        ]);
    }

    /**
     * Получить список всех стран.
     *
     * @return JsonResponse
     */
    public function getCountries(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'result' => Country::getCountries(),
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
        $city = City::language()->addSelect('id')->find($city_id);

        if (!$city) throw new ErrorException(__('message.city_not_found'));

        return response()->json([
            'status' => true,
            'result' => $city,
        ]);
    }

    /**
     * Получить список всех стран или выбранной страны с городами.
     *
     * @param int $country_id
     * @return JsonResponse
     */
    public function getCities(int $country_id = 0): JsonResponse
    {
        return response()->json([
            'status' => true,
            'result' => Country::getCountriesWithCities($country_id),
        ]);
    }
}
