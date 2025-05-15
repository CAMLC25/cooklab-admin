<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\CategoryController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [AdminController::class, 'index'])->name('home');

Route::middleware(['admin'])->group(function () {
    // all-admins
    Route::get('/all-admins', [AdminController::class, 'all_admins'])->name('admins.all-admins');

    // Route để hiển thị form tạo Admin
    Route::get('/create-admin', [AdminController::class, 'create_admin'])->name('create-admin');

    // Route để lưu Admin mới
    Route::post('/create-admin', [AdminController::class, 'store_admin'])->name('store-admin');

    // Route để xóa Admin
    Route::delete('/{id}', [AdminController::class, 'destroy_admin'])->name('destroy-admin');

    // Route để hiển thị form chỉnh sửa Admim
    Route::get('/edit-admin/{id}', [AdminController::class, 'edit_admin'])->name('admin-edit');
    Route::put('/update-admin/{id}', [AdminController::class, 'update_admin'])->name('admin-update');

    //
    // Route thống kê User

    // all-users
    Route::get('/all-users', [AdminController::class, 'all_users'])->name('admins.all-users');

    // Trang hiển thị form tạo user
    Route::get('/create-user', [AdminController::class, 'create_user'])->name('users-create');

    // Xử lý lưu user mới
    Route::post('/create-user', [AdminController::class, 'store_user'])->name('users-store');

    // Route để hiển thị form chỉnh sửa user
    Route::get('user/{id}/edit', [AdminController::class, 'edit_user'])->name('edit-user');
    Route::put('user/{id}', [AdminController::class, 'update_user'])->name('update-user');

    // Route để xóa user
    Route::delete('/users/{id}', [AdminController::class, 'destroy_user'])->name('destroy-user');



    //
    // Thống kê Recipe
    // all-recipes
    Route::get('/all-recipes', [RecipeController::class, 'all_recipes'])->name('admins.all-recipes');

    // crete-recipe
    Route::get('/create-recipe', [RecipeController::class, 'create_recipe'])->name('create_recipe');
    Route::post('/create-recipe', [RecipeController::class, 'store_recipe'])->name('store_recipe');

    // delete recipe
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy_recipe'])->name('destroy_recipe');

    // Route để hiển thị form chỉnh sửa Recipe
    Route::get('/recipes/{id}/edit', [RecipeController::class, 'edit_recipe'])->name('edit_recipe');
    // Route để lưu Recipe đã chỉnh sửa
    Route::put('/recipes/{id}', [RecipeController::class, 'update_recipe'])->name('update_recipe');

    // Chỉnh sửa trạng thái Recipe
    Route::post('/update-status-rejected', [RecipeController::class, 'updateStatusRejected'])->name('update_status_rejected');
    Route::post('/update-status-direct', [RecipeController::class, 'updateStatusDirect'])->name('update_status_direct');

    // Hiển thị chi tiết Recipe
    Route::get('/recipes/{id}', [RecipeController::class, 'showDetailRecipes'])->name('detail_recipes');

    Route::post('/recipes/{recipe}/comment', [RecipeController::class, 'storeComment'])->name('recipe.comment.store');
    Route::post('/recipes/{recipe}/reaction', [RecipeController::class, 'storeReaction'])->name('recipe.reaction.store');

    // Tìm kiếm Recipe
    Route::any('/recipes', [RecipeController::class, 'searchRecipes'])->name('search_recipes');

    //
    // Quản lý Category
    Route::get('categories', [CategoryController::class, 'index'])->name('admins.all-categories');

    // Trang tạo danh mục mới
    Route::get('categories/create', [CategoryController::class, 'create'])->name('create-categories');
    Route::post('categories', [CategoryController::class, 'store'])->name('store-categories');

    // Trang chỉnh sửa danh mục
    Route::get('categories/{id}/edit', [CategoryController::class, 'edit'])->name('edit-categories');
    Route::put('categories/{id}', [CategoryController::class, 'update'])->name('update-categories');

    // Xóa danh mục
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('destroy-categories');
});
