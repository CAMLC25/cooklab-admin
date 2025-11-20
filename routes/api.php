<?php

use App\Contracts\NlpAdapter;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Ai\AiSearchController;
use Illuminate\Http\Request;

// Nhóm route API Authentication
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // Đăng ký
    Route::post('login', [AuthController::class, 'login']);       // Đăng nhập

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']); // Đăng xuất
        Route::get('me', [AuthController::class, 'me']);          // Lấy thông tin user
    });
});

// Nhóm route API cho User, phải đăng nhập mới được phép update
// Cập nhật thông tin người dùng
Route::middleware('auth:sanctum')->group(function () {
    Route::post('user/update/{id}', [UserController::class, 'updateUser']);
});

// Hiển thị danh mục
Route::get('/categories', [CategoryController::class, 'index']);

// Hiển thị danh sách công thức nấu ăn thuộc về danh mục
Route::get('/categories/{id}/recipes', [CategoryController::class, 'recipes']);

// Tìm kiếm công thức nấu ăn
Route::get('/recipes/search/guest', [RecipeController::class, 'searchGuestRecipes']);
// Tìm kiếm công thức nấu ăn bằng người dùng đã đăng nhập
Route::middleware('auth:sanctum')->get('/recipes/search/auth', [RecipeController::class, 'searchAuthRecipes']);

// Hiển thị chi tiết công thức nấu ăn
Route::get('/recipes/{id}', [RecipeController::class, 'show']);

// Tạo mới và cập nhật công thức nấu ăn
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::post('/recipes/{id}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']); // Xóa công thức
});

// Hiển thị danh sách công thức nấu ăn
Route::get('/recipes', [RecipeController::class, 'index']);
// Hiện thị công thức nấu ăn trending
Route::get('/trending', [RecipeController::class, 'trending']);

// Thả biểu tượng cảm xúc và bình luận
Route::middleware('auth:sanctum')->prefix('recipes')->group(function () {
    Route::post('{id}/react', [RecipeController::class, 'react']);
    Route::post('{id}/comment', [RecipeController::class, 'comment']);
    Route::post('{id}/react/remove', [RecipeController::class, 'removeReaction']);
    Route::delete('{recipeId}/comment/{commentId}', [RecipeController::class, 'removeComment']);
});

// Luu công thức nấu ăn yêu thích
Route::middleware('auth:sanctum')->prefix('recipes')->group(function () {
    Route::post('{id}/save', [RecipeController::class, 'saveRecipe']); // Lưu công thức
    Route::delete('{id}/unsave', [RecipeController::class, 'removeSavedRecipe']); // Xóa công thức
    Route::get('saved-recipes/{id}', [RecipeController::class, 'getSavedRecipes']); // Lấy danh sách công thức đã lưu
});

// Follow và Unfollow
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::post('{followeeId}/follow', [RecipeController::class, 'follow']); // follower
    Route::delete('{followeeId}/unfollow', [RecipeController::class, 'unFollow']); // Bỏ follower
    Route::get('{userId}/follow-stats', [RecipeController::class, 'getFollowStats']); // Danh sách người theo dõi
    Route::get('/check-follow/{recipeOwnerId}', [RecipeController::class, 'checkIfUserFollows']);
});

// Lấy lịch sử tìm kiếm của người dùng theo userId
Route::middleware('auth:sanctum')->get('/search-history/{userId}', [RecipeController::class, 'getSearchHistory']);

// Xóa lịch sử tìm kiếm (có thể xóa theo ID hoặc xóa tất cả)
Route::middleware('auth:sanctum')->delete('/search-history/{userId}/{id?}', [RecipeController::class, 'deleteSearchHistory']);

// Hiện thông tin người dùng

// Hiển thị profile user theo ID
Route::get('users/{id}', [UserController::class, 'show']);
Route::get('customer/{id}', [UserController::class, 'showCustomer']);

/** ========== AI endpoints ========== */
// Dump từ điển nguyên liệu cho service AI (nếu bạn vẫn dùng)
Route::get('/ai/ingredients-dump', function () {
    $rows = DB::table('recipe_ingredients')
        ->selectRaw('LOWER(TRIM(name)) as name')
        ->groupBy('name')
        ->pluck('name');
    return ['ok' => true, 'data' => $rows];
});

// Tìm món theo nguyên liệu/text (AI search - KHÔNG dùng LLM, chạy trực tiếp DB)
Route::post('/ai/search', [AiSearchController::class, 'search']);

Route::post('/chat', [ChatController::class, 'handle']);
