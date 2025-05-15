<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Hiển thị danh sách danh mục
    public function index()
    {
        $categories = Category::paginate(5);
        return view('admins.categories.all-categories', compact('categories'));
    }

    // Tạo danh mục mới
    public function create()
    {
        return view('admins.categories.create-categories');
    }

    // Lưu danh mục mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admins.all-categories')->with('success', 'Danh mục đã được thêm thành công!');
    }

    // Chỉnh sửa danh mục
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admins.categories.edit-categories', compact('category'));
    }

    // Cập nhật danh mục
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::findOrFail($id);
        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('admins.all-categories')->with('success', 'Danh mục đã được cập nhật!');
    }

    // Xóa danh mục
    public function destroy($id)
    {
        Category::destroy($id);
        return redirect()->route('admins.all-categories')->with('success', 'Danh mục đã được xóa!');
    }
}

