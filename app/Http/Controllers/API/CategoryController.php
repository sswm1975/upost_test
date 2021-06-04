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
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategories(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'category_id' => 'sometimes|integer|exists:categories,category_id',
                'lang'        => 'sometimes|in:' . implode(',', config('app.languages')),
            ],
            [
                'integer'     => 'field_must_be_a_number',
                'exists'      => 'category_not_found',
                'in'          => ':attribute_not_exist',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $categories = Category::query()
            ->when($request->filled('category_id'), function ($query) use ($request) {
                return $query->where('category_id', $request->get('category_id'));
            })
            ->language($request->get('lang', config('app.default_language')))
            ->addSelect('category_id')
            ->oldest('category_id')
            ->get();

        return response()->json([
            'status' => 200,
            'result' => $categories,
        ]);
    }
}
