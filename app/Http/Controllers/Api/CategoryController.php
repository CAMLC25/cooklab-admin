<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Lấy danh sách tất cả category
    public function index()
    {
        $categories = Category::all(['id', 'name']);

        $data = $categories->map(function ($category) {
            // Thử lấy món ngẫu nhiên trong danh mục
            $randomRecipe = $category->recipes()->inRandomOrder()->first();

            // Nếu không có, lấy món gần nhất
            if (!$randomRecipe) {
                $randomRecipe = $category->recipes()->latest()->first();
            }

            // Trả về ảnh: nếu không có vẫn trả ảnh mặc định
            $image = $randomRecipe?->image ?? asset('admin-assets/images/cook_lab/recipes/default_category.png');

            return [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $image,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }



    // Lấy tất cả recipes theo category ID
    public function recipes($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // Lấy công thức đã duyệt kèm thông tin đầy đủ: user (với avatar), ingredients, steps, reactions, comments (với avatar), category
        $recipes = $category->recipes()
            ->where('status', 'approved')
            ->with([
                'user:id,name,avatar',                   // Thêm avatar của người đăng
                'category:id,name',
                'ingredients:id,recipe_id,name',
                'steps:id,recipe_id,step_number,description,image',
                'reactions',
                'comments.user:id,name,avatar',          // Thêm avatar của người bình luận
                // 'views'
            ])
            ->latest()
            ->get();

        // Đảm bảo luôn có key avatar (null nếu không có)
        foreach ($recipes as $recipe) {
            $recipe->user->avatar = $recipe->user->avatar ?: null;

            foreach ($recipe->comments as $comment) {
                $comment->user->avatar = $comment->user->avatar ?: null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $recipes,
        ]);
    }



}
