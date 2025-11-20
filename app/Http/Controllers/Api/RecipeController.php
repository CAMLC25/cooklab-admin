<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\SavedRecipe;
use App\Models\SearchLog;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\Follow;
use App\Models\User;
use Auth;
use File;
use Illuminate\Http\Request;
use Log;

class RecipeController extends Controller
{
    public function show($id)
    {
        $recipe = Recipe::with([
            'user:id,name,avatar,id_cooklab',
            'category:id,name',
            'ingredients:id,recipe_id,name',
            'steps:id,recipe_id,step_number,description,image',
            'reactions',
            'comments.user:id,name,avatar',
            'savedByUsers:id,name',
        ])->find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // ✅ Chuyển đường dẫn ảnh chính sang dạng URL
        $recipe->image = $recipe->image ? asset($recipe->image) : null;

        // ✅ Chuyển đường dẫn ảnh từng bước
        foreach ($recipe->steps as $step) {
            $step->image = $step->image ?: null;
        }

        // Avatar user chủ công thức
        $recipe->user->avatar = $recipe->user->avatar ?: null;
        // Avatar user bình luận
        foreach ($recipe->comments as $comment) {
            $comment->user->avatar = $comment->user->avatar ?: null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'recipe' => $recipe,
            ]
        ]);
    }

    // Tạo mới công thức nấu ăn
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'cook_time' => 'required|string',
            'servings' => 'required|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'required|string',
            'steps' => 'required|array|min:1',
            'steps.*.description' => 'required|string',
            'steps.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // 1. Lưu ảnh đại diện
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('admin-assets/images/cook_lab/recipes'), $imageName);
            $imagePath = 'admin-assets/images/cook_lab/recipes/' . $imageName;
        }

        // 2. Tạo công thức
        $recipe = Recipe::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imagePath,
            'category_id' => $request->category_id,
            'user_id' => auth()->id(),
            'cook_time' => $request->cook_time,
            'servings' => $request->servings,
            'status' => 'pending',
        ]);

        // 3. Nguyên liệu
        foreach ($request->ingredients as $ingredient) {
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'name' => $ingredient,
            ]);
        }

        // 4. Bước làm
        $stepsDirectory = public_path('admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps');
        if (!file_exists($stepsDirectory)) {
            mkdir($stepsDirectory, 0777, true);
        }

        foreach ($request->steps as $index => $step) {
            $stepImagePath = null;
            if (isset($step['image'])) {
                $image = $step['image'];
                $stepImageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move($stepsDirectory, $stepImageName);
                $stepImagePath = 'admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps/' . $stepImageName;
            }

            RecipeStep::create([
                'recipe_id' => $recipe->id,
                'step_number' => $index + 1,
                'description' => $step['description'],
                'image' => $stepImagePath,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recipe created successfully',
            'data' => $recipe
        ]);
    }

    // Cập nhật công thức nấu ăn
    public function update(Request $request, $id)
    {
        $recipe = Recipe::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'sometimes|required|exists:categories,id',
            'cook_time' => 'sometimes|required|string',
            'servings' => 'sometimes|required|string',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'required|string',
            'steps' => 'nullable|array',
            'steps.*.description' => 'required|string',
            'steps.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Cập nhật ảnh nếu có
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('admin-assets/images/cook_lab/recipes'), $imageName);
            $recipe->image = 'admin-assets/images/cook_lab/recipes/' . $imageName;
        }

        // Cập nhật các trường cơ bản
        $recipe->fill($request->only([
            'title',
            'description',
            'category_id',
            'cook_time',
            'servings'
        ]));
        $recipe->status = 'pending'; // đặt lại trạng thái chờ duyệt
        $recipe->save();

        // Cập nhật nguyên liệu nếu gửi lên
        if ($request->has('ingredients')) {
            $recipe->ingredients()->delete();
            foreach ($request->ingredients as $ingredient) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'name' => $ingredient,
                ]);
            }
        }

        // Cập nhật bước nếu gửi lên
        if ($request->has('steps')) {
            $recipe->steps()->delete();

            $stepsDirectory = public_path('admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps');
            if (!file_exists($stepsDirectory)) {
                mkdir($stepsDirectory, 0777, true);
            }

            foreach ($request->steps as $index => $step) {
                $stepImagePath = null;
                if (isset($step['image'])) {
                    $image = $step['image'];
                    $stepImageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move($stepsDirectory, $stepImageName);
                    $stepImagePath = 'admin-assets/images/cook_lab/recipes/' . $recipe->id . '/steps/' . $stepImageName;
                }

                RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'step_number' => $index + 1,
                    'description' => $step['description'],
                    'image' => $stepImagePath,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Recipe updated successfully',
            'data' => $recipe
        ]);
    }



    public function destroy($id)
    {
        // Kiểm tra xem công thức có tồn tại không
        $recipe = Recipe::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy công thức hoặc bạn không có quyền xóa công thức này.'
            ], 404);
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

        return response()->json([
            'success' => true,
            'message' => 'Công thức đã được xóa thành công.'
        ]);
    }

    public function index()
    {
        // Lấy danh sách công thức đã duyệt
        $recipes = Recipe::with([
            'category:id,name',
            'user:id,name,avatar',
            'ingredients:id,recipe_id,name',
            'steps:id,recipe_id,step_number,description,image',
            'reactions',
            'comments.user:id,name,avatar',
            // 'views'
        ])
            ->where('status', 'approved') // Lọc chỉ các công thức có trạng thái approved
            ->latest() // Sắp xếp theo công thức mới nhất
            ->get();

        // Nếu không có công thức nào đã duyệt, trả về thông báo
        if ($recipes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No approved recipes found.'
            ], 404);
        }

        // Không dùng asset(), giữ đường dẫn tương đối
        foreach ($recipes as $recipe) {
            // Chỉ gán lại nếu cần kiểm tra null
            $recipe->image = $recipe->image ?: null;

            // Avatar user chủ công thức
            $recipe->user->avatar = $recipe->user->avatar ?: null;
            // Avatar user bình luận
            foreach ($recipe->comments as $comment) {
                $comment->user->avatar = $comment->user->avatar ?: null;
            }

            foreach ($recipe->steps as $step) {
                $step->image = $step->image ?: null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }

    /**
     * Thả biểu tượng cảm xúc (reaction) cho 1 công thức.
     */
    public function react(Request $request, $id)
    {
        $recipe = Recipe::find($id);
        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // Kiểm tra loại biểu tượng cảm xúc
        $request->validate([
            'type' => 'required|in:heart,mlem,clap'
        ]);

        // Kiểm tra xem user đã thả biểu tượng cảm xúc này chưa
        $existingReaction = Reaction::where('recipe_id', $id)
            ->where('user_id', auth()->id())
            ->where('type', $request->type)
            ->first();

        if ($existingReaction) {
            // Nếu có rồi, xóa biểu tượng cảm xúc
            $existingReaction->delete();
            return response()->json([
                'success' => true,
                'message' => 'Reaction removed',
                'data' => null
            ]);
        } else {
            // Nếu chưa có, tạo mới
            $reaction = Reaction::create([
                'recipe_id' => $id,
                'user_id' => auth()->id(),
                'type' => $request->type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reaction added',
                'data' => $reaction
            ]);
        }
    }


    /**
     * Gửi bình luận cho 1 công thức.
     */
    public function comment(Request $request, $id)
    {
        $recipe = Recipe::find($id);
        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $request->validate([
            'content' => 'required|string|min:1'
        ]);

        // Giả sử bạn có model RecipeComment quan hệ với RecipeComment::user()
        $comment = Comment::create([
            'recipe_id' => $id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        // load relation user để trả về luôn thông tin người comment
        $comment->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'data' => $comment
        ]);
    }

    // Thêm phương thức xóa phản ứng
    public function removeReaction(Request $request, $id)
    {
        $recipe = Recipe::find($id);
        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // Kiểm tra loại biểu tượng cảm xúc
        $request->validate([
            'type' => 'required|in:heart,mlem,clap'
        ]);

        // Tìm phản ứng của user
        $reaction = Reaction::where('recipe_id', $id)
            ->where('user_id', auth()->id())
            ->where('type', $request->type)
            ->first();

        if (!$reaction) {
            return response()->json([
                'success' => false,
                'message' => 'Reaction not found'
            ], 404);
        }

        // Xóa phản ứng
        $reaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed',
            'data' => null
        ]);
    }

    // Xóa bình luận
    public function removeComment($recipeId, $commentId)
    {
        // Kiểm tra sự tồn tại của công thức
        $recipe = Recipe::find($recipeId);
        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // Kiểm tra sự tồn tại của bình luận
        $comment = Comment::where('recipe_id', $recipeId)->find($commentId);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }

        // Kiểm tra quyền xóa bình luận
        if ($comment->user_id != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own comment'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Lưu công thức vào danh sách yêu thích.
     */
    public function saveRecipe($id)
    {
        $userId = auth()->id(); // Lấy id người dùng hiện tại

        // Kiểm tra xem công thức đã được lưu chưa
        $existing = SavedRecipe::where('recipe_id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete(); // Nếu đã lưu, xóa bản ghi
            return response()->json([
                'success' => true,
                'message' => 'Recipe removed from saved list.'
            ]);
        } else {
            // Nếu chưa lưu, tạo mới bản ghi trong bảng SavedRecipe
            SavedRecipe::create([
                'user_id' => $userId,
                'recipe_id' => $id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recipe saved successfully.'
        ]);
    }

    /**
     * Xóa công thức khỏi danh sách yêu thích.
     */
    public function removeSavedRecipe($id)
    {
        $userId = auth()->id(); // Lấy id người dùng hiện tại

        // Kiểm tra xem công thức có trong danh sách yêu thích không
        $existing = SavedRecipe::where('user_id', $userId)
            ->where('recipe_id', $id)
            ->first();

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found in saved list.'
            ], 400);  // Trả về lỗi nếu công thức không có trong danh sách yêu thích
        }

        // Nếu có, xóa công thức khỏi danh sách yêu thích
        $existing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipe removed from saved list.'
        ]);
    }

    // HIỂN THỊ DANH SÁCH CÔNG THỨC ĐÃ LƯU
    public function getSavedRecipes($id)
    {
        // Lấy userId từ tham số $id (không cần gọi như hàm)
        $userId = $id;

        // Lấy danh sách các ID công thức đã lưu của người dùng và eager load các thông tin liên quan
        $savedRecipes = SavedRecipe::where('user_id', $userId)
            ->with('recipe') // Eager load thông tin công thức từ bảng recipes
            ->get(['recipe_id']); // Trả về chỉ các trường cần thiết (ở đây là recipe_id)

        if ($savedRecipes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No saved recipes found.'
            ], 404);  // Trả về lỗi nếu không có công thức nào đã lưu
        }

        // Lấy tất cả thông tin công thức từ mỗi SavedRecipe
        $recipes = $savedRecipes->map(function ($savedRecipe) {
            return $savedRecipe->recipe; // Trả về công thức liên quan đến mỗi savedRecipe
        });

        return response()->json([
            'success' => true,
            'data' => $recipes
        ]);
    }


    public function follow($followeeId)
    {
        $followerId = auth()->id(); // Lấy id người dùng hiện tại

        // Kiểm tra xem người dùng đã theo dõi người này chưa
        $existing = Follow::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->first();

        if ($existing) {
            $existing->delete(); // Nếu đã lưu, xóa bản ghi
            return response()->json([
                'success' => true,
                'message' => 'Recipe removed from saved list.'
            ]);
        } else {
            // Nếu chưa theo dõi, tạo mới bản ghi trong bảng Follow
            Follow::create([
                'follower_id' => $followerId,
                'followee_id' => $followeeId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'You are now following this user.'
        ]);
    }

    public function unFollow($followeeId)
    {
        $followerId = auth()->id(); // Lấy id người dùng hiện tại

        // Kiểm tra xem người dùng có đang theo dõi người này không
        $existing = Follow::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->first();

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'You are not following this user.'
            ], 400);  // Trả về lỗi nếu người dùng chưa theo dõi
        }

        // Nếu có, xóa người dùng khỏi danh sách theo dõi
        $existing->delete();

        return response()->json([
            'success' => true,
            'message' => 'You have unfollowed this user.'
        ]);
    }

    public function getFollowStats($userId)
    {
        // Lấy số lượng người theo dõi
        $followersCount = Follow::where('followee_id', $userId)->count();

        // Lấy số lượng người mà người dùng đang theo dõi
        $followingCount = Follow::where('follower_id', $userId)->count();

        return response()->json([
            'followersCount' => $followersCount,
            'followingCount' => $followingCount
        ]);
    }

    public function checkIfUserFollows($recipeOwnerId)
    {
        $followerId = auth()->id(); // ID của người dùng hiện tại

        // Kiểm tra trong bảng 'follows' xem có mối quan hệ theo dõi giữa người dùng hiện tại và người đăng tải công thức hay không
        $isFollowing = Follow::where('follower_id', $followerId)
            ->where('followee_id', $recipeOwnerId)
            ->exists(); // Trả về true nếu có, false nếu không

        return response()->json([
            'isFollowing' => $isFollowing
        ]);
    }

    // API để tìm kiếm công thức
    public function searchGuestRecipes(Request $request)
    {

        // Lấy giá trị tìm kiếm từ request
        $search = $request->get('search');

        $query = Recipe::query();
        // Chỉ lấy các công thức có status = 'approved'
        $query->where('status', 'approved');

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

        $recipes = $query->get();

        // Nếu không tìm thấy công thức nào, trả về thông báo
        if ($recipes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No recipes found.'
            ], 404);
        }

        // Trả về dữ liệu theo định dạng mong muốn
        $data = $recipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'user_id' => $recipe->user_id,
                'category_id' => $recipe->category_id,
                'title' => $recipe->title,
                'image' => $recipe->image,
                'description' => $recipe->description,
                'servings' => $recipe->servings,
                'cook_time' => $recipe->cook_time,
                'status' => $recipe->status,
                'reason_rejected' => $recipe->reason_rejected,
                'created_at' => $recipe->created_at->toISOString(),
                'updated_at' => $recipe->updated_at->toISOString(),
                'user' => [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name,
                    'avatar' => $recipe->user->avatar,
                    'id_cooklab' => $recipe->user->id_cooklab,
                ],
                'category' => [
                    'id' => $recipe->category->id,
                    'name' => $recipe->category->name,
                ],
                'ingredients' => $recipe->ingredients->map(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'recipe_id' => $ingredient->recipe_id,
                        'name' => $ingredient->name
                    ];
                }),
                'steps' => $recipe->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'recipe_id' => $step->recipe_id,
                        'step_number' => $step->step_number,
                        'description' => $step->description,
                        'image' => $step->image
                    ];
                }),
                'reactions' => $recipe->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'user_id' => $reaction->user_id,
                        'recipe_id' => $reaction->recipe_id,
                        'type' => $reaction->type,
                        'created_at' => $reaction->created_at->toISOString(),
                        'updated_at' => $reaction->updated_at->toISOString(),
                    ];
                }),
                'comments' => $recipe->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'recipe_id' => $comment->recipe_id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar
                        ]
                    ];
                }),
            ];
        });

        // Trả về dữ liệu công thức nếu có kết quả tìm thấy
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function searchAuthRecipes(Request $request)
    {

        // Lấy giá trị tìm kiếm từ request
        $search = $request->get('search');


        // Lưu lại từ khóa tìm kiếm vào bảng search_logs nếu có
        if (auth()->check()) {
            SearchLog::create([
                'user_id' => auth()->id(),
                'keyword' => $search,
                'searched_at' => now(),
            ]);
        }

        $query = Recipe::query();
        // Chỉ lấy các công thức có status = 'approved'
        $query->where('status', 'approved');

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

        $recipes = $query->get();

        // Nếu không tìm thấy công thức nào, trả về thông báo
        if ($recipes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No recipes found.'
            ], 404);
        }

        // Trả về dữ liệu theo định dạng mong muốn
        $data = $recipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'user_id' => $recipe->user_id,
                'category_id' => $recipe->category_id,
                'title' => $recipe->title,
                'image' => $recipe->image,
                'description' => $recipe->description,
                'servings' => $recipe->servings,
                'cook_time' => $recipe->cook_time,
                'status' => $recipe->status,
                'reason_rejected' => $recipe->reason_rejected,
                'created_at' => $recipe->created_at->toISOString(),
                'updated_at' => $recipe->updated_at->toISOString(),
                'user' => [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name,
                    'avatar' => $recipe->user->avatar,
                    'id_cooklab' => $recipe->user->id_cooklab,
                ],
                'category' => [
                    'id' => $recipe->category->id,
                    'name' => $recipe->category->name,
                ],
                'ingredients' => $recipe->ingredients->map(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'recipe_id' => $ingredient->recipe_id,
                        'name' => $ingredient->name
                    ];
                }),
                'steps' => $recipe->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'recipe_id' => $step->recipe_id,
                        'step_number' => $step->step_number,
                        'description' => $step->description,
                        'image' => $step->image
                    ];
                }),
                'reactions' => $recipe->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'user_id' => $reaction->user_id,
                        'recipe_id' => $reaction->recipe_id,
                        'type' => $reaction->type,
                        'created_at' => $reaction->created_at->toISOString(),
                        'updated_at' => $reaction->updated_at->toISOString(),
                    ];
                }),
                'comments' => $recipe->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'recipe_id' => $comment->recipe_id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar
                        ]
                    ];
                }),
            ];
        });

        // Trả về dữ liệu công thức nếu có kết quả tìm thấy
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Lấy lịch sử tìm kiếm của người dùng theo userId
    public function getSearchHistory($userId)
    {
        // Kiểm tra nếu người dùng có quyền truy cập vào lịch sử của mình (nếu cần)
        if ($userId != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403); // Không có quyền truy cập
        }

        // Lấy danh sách lịch sử tìm kiếm của người dùng
        $searchHistory = SearchLog::where('user_id', $userId)
            ->orderBy('searched_at', 'desc') // Sắp xếp theo thời gian tìm kiếm gần nhất
            ->get();

        if ($searchHistory->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No search history found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $searchHistory
        ]);
    }


    // Xóa lịch sử tìm kiếm của người dùng theo userId và id
    public function deleteSearchHistory($userId, $id = null)
    {
        // Kiểm tra nếu người dùng có quyền xóa lịch sử của mình
        if ($userId != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403); // Không có quyền truy cập
        }

        // Nếu có id tìm kiếm cụ thể, chỉ xóa lịch sử tìm kiếm đó
        if ($id) {
            $history = SearchLog::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$history) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search history not found.'
                ], 404);
            }

            $history->delete();
            return response()->json([
                'success' => true,
                'message' => 'Search history deleted successfully.'
            ]);
        }

        // Nếu không có id, xóa toàn bộ lịch sử tìm kiếm của người dùng
        SearchLog::where('user_id', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All search history deleted successfully.'
        ]);
    }

    public function trending()
    {
        // Lấy các công thức được yêu thích nhiều nhất
        $popularRecipes = Recipe::withCount('reactions')
            ->orderBy('reactions_count', 'desc')
            ->take(10)
            ->get();


        // Trả về dữ liệu theo định dạng mong muốn
        $data = $popularRecipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'user_id' => $recipe->user_id,
                'category_id' => $recipe->category_id,
                'title' => $recipe->title,
                'image' => $recipe->image,
                'description' => $recipe->description,
                'servings' => $recipe->servings,
                'cook_time' => $recipe->cook_time,
                'status' => $recipe->status,
                'reason_rejected' => $recipe->reason_rejected,
                'created_at' => $recipe->created_at->toISOString(),
                'updated_at' => $recipe->updated_at->toISOString(),
                'user' => [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name,
                    'avatar' => $recipe->user->avatar,
                    'id_cooklab' => $recipe->user->id_cooklab,
                ],
                'category' => [
                    'id' => $recipe->category->id,
                    'name' => $recipe->category->name,
                ],
                'ingredients' => $recipe->ingredients->map(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'recipe_id' => $ingredient->recipe_id,
                        'name' => $ingredient->name
                    ];
                }),
                'steps' => $recipe->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'recipe_id' => $step->recipe_id,
                        'step_number' => $step->step_number,
                        'description' => $step->description,
                        'image' => $step->image
                    ];
                }),
                'reactions' => $recipe->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'user_id' => $reaction->user_id,
                        'recipe_id' => $reaction->recipe_id,
                        'type' => $reaction->type,
                        'created_at' => $reaction->created_at->toISOString(),
                        'updated_at' => $reaction->updated_at->toISOString(),
                    ];
                }),
                'comments' => $recipe->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'recipe_id' => $comment->recipe_id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar
                        ]
                    ];
                }),
            ];
        });


        return response()->json([
            'success' => true,
            'data' => $popularRecipes
        ]);
    }

}
