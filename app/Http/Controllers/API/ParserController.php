<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Modules\Parsers\Parser;

class ParserController extends Controller
{
    /**
     * Парсер интернет-магазинов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['url' => 'required|url']);
        $this->returnValidated($validator);

        $parser = (new Parser($request->url))->handler();

        return response()->json([
            'status'   => true,
            'name'     => $parser->getProductName(),
            'category' => $parser->getProductCategory(),
            'price'    => $parser->getProductPrice(),
            'size'     => $parser->getProductSize(),
            'weight'   => $parser->getProductWeight(),
            'image'    => $parser->getProductImage(),
            'favicon'  => $parser->getFavicon(),
        ]);
    }
}
