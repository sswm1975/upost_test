<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WaitRange;
use App\Models\Complaint;
use App\Models\Country;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandbooksController extends Controller
{
    /**
     * Получить список справочников для фильтра.
     * (Страны; Страны с городами; Минимальная и максимальная цены; Категории товаров; Типы нарушений/жалоб)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getHandbooks(Request $request): JsonResponse
    {
        if ($request->has('currency')) {
            $currency = getCurrencySymbol($request->get('currency'));

            $prices = Order::toBase()
                ->where('currency', $currency)
                ->selectRaw('MIN(price) AS price_min, MAX(price) AS price_max')
                ->first();
        } else {
            $prices = Order::toBase()
                ->selectRaw('MIN(price_usd) AS price_min, MAX(price_usd) AS price_max')
                ->first();
        }

        return response()->json([
            'status'                => true,
            'countries'             => Country::getCountries(),
            'countries_with_cities' => Country::getCountriesWithCities(),
            'prices'                => $prices,
            'wait_ranges'           => WaitRange::getWaitRanges(),
            'complaints'            => Complaint::getComplaints(),
        ]);
    }
}
