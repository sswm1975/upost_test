<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Получить список всех категорий или выбранной категории.
     *
     * @param int $category_id
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategories(int $category_id, Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'lang' => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            config('validation.messages'),
            config('validation.attributes')
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors()->all(),
            ]);
        }

        $categories = Category::query()
            ->when(!empty($category_id), function ($query) use ($category_id) {
                return $query->where('category_id', $category_id);
            })
            ->language($request->get('lang', config('app.default_language')))
            ->addSelect('category_id')
            ->oldest('category_id')
            ->get();

        if (empty($categories)) {
            return response()->json([
                'status' => 404,
                'errors' => 'category_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => $categories,
        ]);
    }
}
