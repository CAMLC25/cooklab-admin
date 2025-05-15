<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Recipe;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('role', 'user')->count();
        $totalRecipes = Recipe::count();
        $pendingRecipes = Recipe::where('status', 'pending')->count();
        $approvedRecipes = Recipe::where('status', 'approved')->count();
        $rejectedRecipes = Recipe::where('status', 'rejected')->count();

        return view('admins.dashboard', compact('totalUsers', 'totalRecipes', 'pendingRecipes', 'approvedRecipes', 'rejectedRecipes'));
    }

    public function all_admins()
    {
        $admins = User::where('role', 'admin')->paginate(10);
        return view('admins.all-admins', compact('admins'));
    }

    // Hiển thị form tạo Admin
    public function create_admin()
    {
        return view('admins.create-admin');
    }

    // Lưu Admin mới vào database
    public function store_admin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'id_cooklab' => $this->generateUniqueCooklabId(),
        ]);

        return redirect()->route('admins.all-admins')->with('success', 'Admin đã được tạo thành công.');
    }

    // Xóa Admin
    public function destroy_admin($id)
    {
        $admin = User::findOrFail($id);

        // Bảo vệ admin chính (nếu cần)
        if ($admin->role !== 'admin') {
            return back()->with('error', 'Không thể xóa người không phải admin!');
        }

        $admin->delete();
        return back()->with('success', 'Xóa admin thành công!');
    }

    // Hiển thị form chỉnh sửa Admin
    public function edit_admin($id)
    {
        $admin = User::where('role', 'admin')->findOrFail($id);
        return view('admins.edit', compact('admin'));
    }

    // Cập nhật thông tin admin
    public function update_admin(Request $request, $id)
    {
        $admin = User::where('role', 'admin')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->route('admins.all-admins')->with('success', 'Thông tin admin đã được cập nhật.');
    }

    //
    // Thống kê User
    public function all_users()
    {
        $users = User::where('role', 'user')->paginate(10);

        return view('admins.all-users', compact('users'));
    }

    // Hiển thị form tạo user
    public function create_user()
    {
        return view('admins.users.create');
    }

    // Lưu user mới vào database
    public function store_user(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $avatarPath = null;

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

            // Lưu vào public/admin-assets/images/cook_lab/avata_users
            $avatar->move(public_path('admin-assets/images/cook_lab/avata_users'), $avatarName);
            $avatarPath = 'admin-assets/images/cook_lab/avata_users/' . $avatarName;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'avatar' => $avatarPath,
            'id_cooklab' => $this->generateUniqueCooklabId(),
        ]);

        return redirect()->route('admins.all-users')->with('success', 'Tạo user thành công!');
    }

    // Hiển thị form sửa thông tin user
    public function edit_user($id)
    {
        $user = User::findOrFail($id);
        return view('admins.users.edit', compact('user'));
    }

    // Cập nhật thông tin user
    public function update_user(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6|confirmed',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'status' => 'required|in:active,locked',
            'id_cooklab' => [
                'required',
                'regex:/^[A-Za-z0-9_]+$/',
                'min:5',
                'max:15',
                'unique:users,id_cooklab,' . $user->id,
            ],

        ]);

        // Cập nhật thông tin người dùng
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->id_cooklab = $validated['id_cooklab'];


        if ($request->filled('password')) {
            $user->password = bcrypt($validated['password']);
        }

        // Cập nhật trạng thái
        $user->status = $validated['status'];

        // Xử lý ảnh đại diện
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu tồn tại
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            // Lưu ảnh mới vào đúng thư mục
            $file = $request->file('avatar');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'admin-assets/images/cook_lab/avata_users/' . $fileName;
            $file->move(public_path('admin-assets/images/cook_lab/avata_users'), $fileName);

            // Lưu đường dẫn vào DB
            $user->avatar = $filePath;
        }

        $user->save();

        return redirect()->route('admins.all-users')->with('success', 'Thông tin người dùng đã được cập nhật.');
    }

    // Xóa user
    public function destroy_user($id)
    {
        $user = User::findOrFail($id);

        // Xóa ảnh đại diện nếu có
        if ($user->avatar && file_exists(public_path($user->avatar))) {
            unlink(public_path($user->avatar));
        }
        // Xóa người dùng
        $user->delete();

        return redirect()->route('admins.all-users')->with('success', 'Người dùng đã được xóa thành công.');
    }

    private function generateUniqueCooklabId()
    {
        do {
            $random = 'cook_' . Str::random(10);
        } while (User::where('id_cooklab', $random)->exists());

        return $random;
    }

    // Hiển thị dashboard
    // public function dashboard()
    // {
    //     $totalUsers = User::count();
    //     $totalRecipes = Recipe::count();
    //     $pendingRecipes = Recipe::where('status', 'pending')->count();

    //     return view('admin.dashboard', compact('totalUsers', 'totalRecipes', 'pendingRecipes'));
    // }

}
