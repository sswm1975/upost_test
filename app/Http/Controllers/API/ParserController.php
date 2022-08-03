<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Parsers\Parser;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParserController
{
    /**
     * Контроллер парсера интернет-магазинов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException|ErrorException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $params = validateOrExit(['url' => 'required|url']);
        $max_images_count = config('parser.max_images_count', 3);

        $parser = (new Parser($params['url']))->handler();

        if (empty($images_base64 = $parser->getProductImages())) {
            $images_base64[] = $parser->getProductImage();
        }
        $images = [];
        foreach (array_slice($images_base64, 0, $max_images_count) as $image_base64) {
            $images[] = (new ImageLoaderController)->uploadImage4Order($image_base64, $request->user()->id);
        }

        return response()->json([
            'status'   => true,
            'name'     => Str::substr($parser->getProductName(), 0, 100),
            'price'    => $parser->getProductPrice(),
            'currency' => $parser->getProductCurrency(),
            'images'   => $images,
            'favicon'  => getFavicon($params['url']),
        ]);
    }
}
