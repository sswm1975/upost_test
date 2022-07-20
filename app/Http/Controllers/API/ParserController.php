<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Parsers\Parser;
use App\Exceptions\ValidatorException;
use Illuminate\Validation\ValidationException;

class ParserController extends Controller
{
    /**
     * Парсер интернет-магазинов.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        validateOrExit(['url' => 'required|url']);

        $url = $request->get('url');

        $parser = (new Parser($url))->handler();

        # обработка главного рисунка
        $image_base64 = $parser->getProductImage();
        if ($image_base64) {
            $image = (new ImageLoaderController)->uploadImage4Order($image_base64, $request->user()->id);
        } else {
            $image = '';
        }

        # обработка нескольких рисунков
        $images_base64 = $parser->getProductImages();
        $images = [];
        if (!empty($images_base64)) {
            foreach ($images_base64 as $image_base64) {
                $images[] = (new ImageLoaderController)->uploadImage4Order($image_base64, $request->user()->id);
            }
        }

        return response()->json([
            'status'   => true,
            'name'     => $parser->getProductName(),
            'category' => $parser->getProductCategory(),
            'price'    => $parser->getProductPrice(),
            'currency' => $parser->getProductCurrency(),
            'size'     => $parser->getProductSize(),
            'weight'   => $parser->getProductWeight(),
            'image'    => $image,
            'images'   => $images,
            'favicon'  => getFavicon($url),
        ]);
    }
}
