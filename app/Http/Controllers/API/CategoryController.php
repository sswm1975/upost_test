<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий или выбранной категории.
     *
     * @param int $category_id
     * @return JsonResponse
     */
    public function getCategories(int $category_id = 0): JsonResponse
    {
        $categories = Category::query()
            ->when(!empty($category_id), function ($query) use ($category_id) {
                return $query->where('category_id', $category_id);
            })
            ->language(App::getLocale())
            ->addSelect('category_id')
            ->oldest('category_id')
            ->get()
            ->toArray();

        if (empty($categories)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.category_not_found')],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'result' => $categories,
        ]);
    }
}
