<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    protected function redirectTo()
    {
        $user = Auth::user();

        // Nếu là admin thì cho vào dashboard
        if ($user && $user->role === 'admin') {
            return route('home'); // hoặc route đến admin dashboard
        }

        // Nếu không phải admin thì đăng xuất và quay về login
        Auth::logout();
        session()->flash('error', 'Bạn không có quyền truy cập.');
        return route('login');
    }


    /**
     * Ghi đè hàm authenticated để hiển thị thông báo khi login sai quyền
     */
    protected function authenticated($request, $user)
    {
        if ($user->role !== 'admin') {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản không có quyền truy cập. Chỉ dành cho admin.',
            ]);
        }
    }


    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
