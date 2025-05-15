<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bạn có thể thêm bất kỳ Service Provider nào khác nếu cần
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Định nghĩa Gate kiểm tra quyền admin
        Gate::define('admin', function (User $user) {
            return $user->role === 'admin'; // Kiểm tra xem người dùng có role là 'admin'
        });

        // Sử dụng Bootstrap cho phân trang (pagination)
        Paginator::useBootstrap(); // Chỉ cần gọi phương thức này là đủ

    }
}
