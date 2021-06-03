<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    const DEFAULT_LANG = 'en';

    public function getCountry(Request $request): JsonResponse
    {

    }
}
