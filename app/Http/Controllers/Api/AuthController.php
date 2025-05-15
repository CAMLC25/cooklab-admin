<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Đăng ký
    public function register(Request $request)
    {
        // 1. Tạo validator thủ công với custom message
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.unique' => 'Email này đã được đăng ký trước đó.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        // 2. Nếu validation lỗi, trả về JSON 422
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 3. Tạo user
        $data = $validator->validated();
        $user = User::create([
            'id_cooklab' => 'cook_' . Str::random(8),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'avatar' => 'admin-assets/images/cook_lab/avata_users/avatar_default.png',
            'role' => 'user',
            'status' => 'active',
        ]);

        // 4. Tạo token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Trả về JSON thành công
        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công!',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
    // Đăng nhập
    public function login(Request $request)
    {
        // Validate format đầu vào
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Sai email hoặc mật khẩu
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác.'
            ], 401);
        }

        // 2. Tài khoản bị khóa
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn đã bị khóa.',
            ], 403);
        }

        // Xóa toàn bộ token cũ rồi tạo token mới
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Trả về thông tin
        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'user' => $user,
            'token' => $token,
        ]);
    }


    // Đăng xuất
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Đăng xuất thành công!',
        ]);
    }

    // Lấy thông tin user hiện tại
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
