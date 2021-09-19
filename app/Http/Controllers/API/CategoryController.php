<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий или выбранной категории.
     *
     * @param int $category_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function getCategories(int $category_id = 0): JsonResponse
    {
        $categories = Category::getCategories($category_id);

        if (empty($categories)) throw new ErrorException(__('message.category_not_found'));

        return response()->json([
            'status' => true,
            'result' => $categories,
        ]);
    }
}
