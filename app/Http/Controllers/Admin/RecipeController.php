<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Category;
use Illuminate\Support\Facades\File;
use App\Models\Comment;
use App\Models\Reaction;
use Auth;
use App\Models\SearchLog;
use DB;

class RecipeController extends Controller
{
    public function all_recipes()
    {
        $recipes = Recipe::with(['category', 'user'])
            ->orderBy('updated_at', 'desc')
            ->paginate(5);

        return view('admins.recipes.all-recipes', compact('recipes'));
    }

    // hiển thị form tạo Recipe
    public function create_recipe()
    {
        $categories = Category::all(); // Lấy danh sách loại món ăn
        return view('admins.recipes.create-recipes', compact('categories'));

    }

    // lưu Recipe mới
    public function store_recipe(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'required|string',
            'steps' => 'required|array|min:1',
            'steps.*' => 'required|string',
            'step_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'user_id' => 'required|exists:users,id',
        ]);

        // 1. Lưu ảnh đại diện chính vào thư mục: public/admin-assets/images/cook_lab/recipes/{recipe_id}
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Tạo tên duy nhất cho ảnh
            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            // Lưu vào thư mục mong muốn
            $request->file('image')->move(public_path('admin-assets/images/cook_lab/recipes'), $imageName);
            $imagePath = 'admin-assets/images/cook_lab/recipes/' . $imageName;
        }

        // 2. Tạo Recipe
        $recipe = Recipe::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'category_id' => $request->category_id,
            'user_id' => $request->user_id,
            'cook_time' => $request->cook_time,
            'servings' => $request->servings,
            'status' => 'pending', // mặc định là chờ duyệt
        ]);

        // 3. Lưu nguyên liệu
        foreach ($request->ingredients as $ingredient) {
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'name' => $ingredient,
            ]);
        }

        // 4. Lưu các bước (và ảnh nếu có)
        $steps = $request->steps;
        $stepImages = $request->file('step_images', []); // Mảng ảnh bước

        // Đảm bảo thư mục cho bước có sẵn
        $stepsDirectory = public_path('admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps');
        if (!file_exists($stepsDirectory)) {
            mkdir($stepsDirectory, 0777, true); // Tạo thư mục nếu chưa có
        }

        foreach ($steps as $index => $stepText) {
            $stepImagePath = null;

            if (isset($stepImages[$index])) {
                // Tạo tên duy nhất cho ảnh bước (sử dụng time() kết hợp với chuỗi ngẫu nhiên)
                $stepImageName = time() . '_' . uniqid() . '.' . $stepImages[$index]->getClientOriginalExtension();
                // Lưu ảnh vào thư mục: public/admin-assets/images/cook_lab/recipes/{recipe_id}/steps
                $stepImages[$index]->move($stepsDirectory, $stepImageName);
                $stepImagePath = 'admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps/' . $stepImageName;
            }

            // Lưu từng bước
            RecipeStep::create([
                'recipe_id' => $recipe->id,
                'step_number' => $index + 1,
                'description' => $stepText,
                'image' => $stepImagePath, // lưu ảnh bước nếu có
            ]);
        }

        // Chuyển hướng về danh sách công thức và hiển thị thông báo thành công
        return redirect()->route('admins.all-recipes')->with('success', 'Đã tạo công thức thành công!');
    }


    // xóa Recipe
    public function destroy_recipe($id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return redirect()->back()->with('error', 'Không tìm thấy công thức.');
        }

        // 1. Xóa ảnh chính của công thức nếu có
        if ($recipe->image && file_exists(public_path($recipe->image))) {
            unlink(public_path($recipe->image));
        }

        // 2. Xóa thư mục chứa ảnh của công thức và ảnh các bước
        $recipeDirectory = public_path('admin-assets/images/cook_lab/recipes/' . $recipe->id);

        if (File::exists($recipeDirectory)) {
            // Xóa thư mục và tất cả tệp tin bên trong
            File::deleteDirectory($recipeDirectory);
        }

        // 3. Xóa công thức và các liên kết liên quan
        $recipe->delete();

        return redirect()->route('admins.all-recipes')->with('success', 'Đã xóa công thức thành công.');
    }


    // hiển thị form chỉnh sửa Recipe
    public function edit_recipe($id)
    {
        $recipe = Recipe::findOrFail($id); // Lấy công thức theo ID
        $categories = Category::all(); // Lấy tất cả các danh mục
        return view('admins.recipes.edit-recipes', compact('recipe', 'categories'));
    }

    // xử lý cập nhật Recipe
    // public function update_recipe(Request $request, $id)
    // {
    //     // Validate dữ liệu nhập vào
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'servings' => 'required|string',
    //         'cook_time' => 'required|string',
    //         'category_id' => 'required|exists:categories,id',
    //         'image' => 'nullable|image|mimes:jpg,jpeg,png',
    //         'ingredients' => 'required|array',
    //         'steps' => 'required|array',
    //     ]);

    //     // Lấy công thức cần chỉnh sửa
    //     $recipe = Recipe::findOrFail($id);

    //     // Cập nhật các trường khác
    //     $recipe->update([
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'servings' => $request->servings,
    //         'cook_time' => $request->cook_time,
    //         'category_id' => $request->category_id,
    //     ]);

    //     // Nếu có thay đổi ảnh, lưu ảnh mới vào đường dẫn mong muốn
    //     if ($request->hasFile('image')) {
    //         $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
    //         $imagePath = public_path('admin-assets/images/cook_lab/recipes/' . $imageName);
    //         $request->file('image')->move(public_path('admin-assets/images/cook_lab/recipes'), $imageName);
    //         $recipe->update(['image' => 'admin-assets/images/cook_lab/recipes/' . $imageName]);
    //     }

    //     // Cập nhật nguyên liệu
    //     $recipe->ingredients()->delete(); // Xóa các nguyên liệu cũ
    //     foreach ($request->ingredients as $ingredient) {
    //         $recipe->ingredients()->create(['name' => $ingredient]);
    //     }

    //     // Cập nhật các bước làm
    //     $steps = $request->steps;
    //     $stepImages = $request->file('step_images', []); // Mảng chứa ảnh từng bước

    //     foreach ($steps as $index => $step) {
    //         $stepImagePath = null;

    //         // Nếu có ảnh cho bước này
    //         if (isset($stepImages[$index])) {
    //             // Tạo tên duy nhất cho ảnh bước
    //             $stepImageName = uniqid('step_', true) . '.' . $stepImages[$index]->getClientOriginalExtension();
    //             $stepImagePath = 'admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps/' . $stepImageName;

    //             // Tạo thư mục cho bước nếu chưa có
    //             $stepDir = public_path('admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps');
    //             if (!file_exists($stepDir)) {
    //                 mkdir($stepDir, 0777, true); // Tạo thư mục nếu chưa tồn tại
    //             }

    //             // Lưu ảnh vào thư mục
    //             $stepImages[$index]->move($stepDir, $stepImageName);
    //         }

    //         // Cập nhật hoặc tạo bước mới
    //         $recipe->steps()->updateOrCreate(
    //             ['step_number' => $index + 1],
    //             [
    //                 'description' => $step,
    //                 'image' => $stepImagePath ?: $recipe->steps[$index]->image, // Giữ ảnh cũ nếu không có ảnh mới
    //             ]
    //         );
    //     }

    //     return redirect()->route('admins.all-recipes')->with('success', 'Recipe updated successfully');
    // }

    // xử lý cập nhật Recipe
    public function update_recipe(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'servings' => 'required|string',
            'cook_time' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
            'ingredients' => 'required|array|min:1',
            'steps' => 'required|array|min:1',
            'step_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'step_ids' => 'nullable|array',  // Dành cho các bước đã tồn tại
        ]);

        $recipe = Recipe::findOrFail($id);

        // Kiểm tra quyền sở hữu
        if (auth()->user()->id !== $recipe->user_id && !auth()->user()->isAdmin()) {
            abort(403, 'Bạn không có quyền sửa công thức này!');
        }

        // Cập nhật thông tin cơ bản
        $recipe->update([
            'title' => $request->title,
            'description' => $request->description,
            'servings' => $request->servings,
            'cook_time' => $request->cook_time,
            'category_id' => $request->category_id,
        ]);

        // Cập nhật ảnh công thức nếu có
        if ($request->hasFile('image')) {
            if ($recipe->image && file_exists(public_path($recipe->image))) {
                unlink(public_path($recipe->image));
            }

            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('admin-assets/images/cook_lab/recipes'), $imageName);
            $recipe->image = 'admin-assets/images/cook_lab/recipes/' . $imageName;
            $recipe->save();
        }

        // Cập nhật nguyên liệu
        $recipe->ingredients()->delete();
        foreach ($request->ingredients as $ingredient) {
            $recipe->ingredients()->create(['name' => $ingredient]);
        }

        // Lấy danh sách step_ids từ request (nếu có) để kiểm tra các bước cũ cần xóa
        $existingStepIds = $request->step_ids ?? [];

        // Xóa các bước không có trong danh sách step_ids
        foreach ($recipe->steps as $step) {
            if (!in_array($step->id, $existingStepIds)) {
                // Xóa ảnh của bước nếu có
                if ($step->image && file_exists(public_path($step->image))) {
                    unlink(public_path($step->image));
                }
                $step->delete();
            }
        }

        // Lưu hoặc cập nhật các bước
        $steps = $request->steps;
        $stepImages = $request->file('step_images', []);

        foreach ($steps as $index => $stepText) {
            $stepImagePath = null;

            // Nếu có ảnh mới cho bước, xử lý
            if (isset($stepImages[$index])) {
                $stepImage = $stepImages[$index];
                $uniqueName = uniqid('step_') . '_' . $stepImage->getClientOriginalName();
                $stepPath = 'admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps';
                $stepImage->move(public_path($stepPath), $uniqueName);
                $stepImagePath = $stepPath . '/' . $uniqueName;
            }

            // Kiểm tra nếu là bước đã tồn tại trong step_ids
            if (isset($existingStepIds[$index])) {
                $step = $recipe->steps()->find($existingStepIds[$index]);
                if ($step) {
                    // Nếu không có ảnh mới, giữ ảnh cũ
                    $step->update([
                        'step_number' => $index + 1,
                        'description' => $stepText,
                        'image' => $stepImagePath ?? $step->image, // Giữ ảnh cũ nếu không có ảnh mới
                    ]);
                }
            } else {
                // Tạo bước mới nếu không tồn tại
                $recipe->steps()->create([
                    'step_number' => $index + 1,
                    'description' => $stepText,
                    'image' => $stepImagePath, // Lưu ảnh nếu có
                ]);
            }
        }

        $updatedAt = now()->toIso8601String();  // Định dạng thời gian thành ISO 8601

        // Lưu lại thời gian cập nhật vào cơ sở dữ liệu
        $recipe->updated_at = $updatedAt;
        $recipe->save();

        return redirect()->route('admins.all-recipes')->with('success', 'Cập nhật công thức thành công!');
    }

    // Chỉnh sửa trạng thái công thức
    public function updateStatusRejected(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'reason_rejected' => 'required|string|max:255',
        ]);

        $recipe = Recipe::findOrFail($request->recipe_id);
        $recipe->status = 'rejected';
        $recipe->reason_rejected = $request->reason_rejected;
        $recipe->save();

        return redirect()->back()->with('success', 'Đã từ chối công thức và lưu lý do.');
    }

    public function updateStatusDirect(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'status' => 'required|in:pending,approved',
        ]);

        $recipe = Recipe::findOrFail($request->recipe_id);
        $recipe->status = $request->status;
        $recipe->reason_rejected = null;
        $recipe->save();

        return redirect()->back()->with('success', 'Trạng thái đã được cập nhật.');
    }

    // Hiển thị chi tiết công thức
    public function showDetailRecipes($id)
    {
        $recipe = Recipe::with(['user', 'category', 'ingredients', 'steps', 'comments.user'])->findOrFail($id);

        // Đếm số lượng phản ứng theo từng loại
        $heartCount = Reaction::where('recipe_id', $id)->where('type', 'heart')->count();
        $mlemCount = Reaction::where('recipe_id', $id)->where('type', 'mlem')->count();
        $clapCount = Reaction::where('recipe_id', $id)->where('type', 'clap')->count();

        // Lấy phản ứng của người dùng hiện tại (nếu đã đăng nhập)
        $userReaction = null;
        if (Auth::check()) {
            $userReactionObj = Reaction::where('recipe_id', $id)
                ->where('user_id', Auth::id())
                ->first();
            if ($userReactionObj) {
                $userReaction = $userReactionObj->type;
            }
        }
        return view('admins.recipes.detail-recipes', compact('recipe', 'heartCount', 'mlemCount', 'clapCount', 'userReaction'));
    }

    public function storeComment(Request $request, $recipeId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->recipe_id = $recipeId;
        $comment->content = $request->content;
        $comment->save();

        return redirect()->back()->with('success', 'Bình luận đã được đăng.');
    }

    public function storeReaction(Request $request, $recipeId)
    {
        // Lấy công thức từ database
        $recipe = Recipe::findOrFail($recipeId);

        // Kiểm tra nếu người dùng chưa đăng nhập, tạo một user giả hoặc bỏ qua logic liên quan đến user
        $user = Auth::check() ? Auth::user() : null;

        // Lấy loại phản ứng
        $reactionType = $request->input('reaction_type');

        // Lưu hoặc cập nhật phản ứng của người dùng vào bảng reactions
        Reaction::updateOrCreate(
            ['user_id' => $user ? $user->id : null, 'recipe_id' => $recipe->id],
            ['type' => $reactionType]
        );

        // Tính lại số lượng phản ứng của từng loại
        $reactionCounts = Reaction::where('recipe_id', $recipe->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn($item) => $item->count);

        // Trả về lại trang với dữ liệu đã cập nhật
        return redirect()->route('detail_recipes', $recipe->id)->with([
            'message' => 'Phản ứng đã được lưu!',
            'reactionCounts' => $reactionCounts
        ]);
    }

    // Tìm kiếm công thức
    public function searchRecipes(Request $request)
    {
        // Lấy giá trị tìm kiếm từ request
        $search = $request->get('search');

        // Lưu lại từ khóa tìm kiếm vào bảng search_logs nếu có
        if (!empty($search)) {
            SearchLog::create([
                'user_id' => auth()->id(),
                'keyword' => $search,
                'searched_at' => now(),
            ]);
        }

        $query = Recipe::query();

        // Tìm kiếm theo tiêu đề và ID công thức
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%$search%")
                    ->orWhere('id', '=', $search);
            })
                ->orWhereHas('ingredients', function ($query) use ($search) {
                    $query->where('name', 'like', "$search%"); // Tìm theo tên nguyên liệu
                });
        }

        // Phân trang kết quả tìm kiếm
        $recipes = $query->paginate(5);

        return view('admins.recipes.search-recipes', compact('recipes', 'search'));
    }



}
