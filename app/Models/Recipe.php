<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'image',
        'description',
        'servings',
        'cook_time',
        'status',
        'reason_rejected',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 🔗 Người đăng công thức
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Danh mục (loại món ăn)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 🔗 Các bước làm
    public function steps()
    {
        return $this->hasMany(RecipeStep::class)->orderBy('step_number');
    }

    // 🔗 Nguyên liệu
    public function ingredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    // 🔗 Các comment
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // 🔗 Các reaction (thả tim, mlem, vỗ tay)
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    // 🔗 Danh sách người đã lưu
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_recipes');
    }

    // 🔗 Thống kê lượt xem
    public function views()
    {
        return $this->hasMany(View::class);
    }

    // 🔗 Thông báo liên quan
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
