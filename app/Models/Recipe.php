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
        'user_id'     => 'integer',
        'category_id' => 'integer',
        'servings'    => 'integer',
        'cook_time'   => 'string',
        'status'      => 'string',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
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

    /* =========================
     * Accessors / Helpers
     * ========================= */

    // URL ảnh hoàn chỉnh (nếu bạn muốn hiển thị trên app)
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;

        // Nếu đã là URL tuyệt đối thì trả nguyên
        if (preg_match('~^https?://~', $this->image)) {
            return $this->image;
        }
        // Mặc định file nằm trong storage/app/public
        return url('storage/' . ltrim($this->image, '/'));
    }

    /* =========================
     * Query Scopes cho AI/Search
     * ========================= */

    // Chỉ lấy recipe đã duyệt (tùy logic status của bạn)
    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    // Sắp xếp theo “độ phổ biến”: reactions -> views -> mới nhất
    public function scopeOrderPopular($q)
    {
        return $q->withCount(['reactions', 'views'])
                 ->orderByDesc('reactions_count')
                 ->orderByDesc('views_count')
                 ->latest('created_at');
    }

    // Lọc theo danh sách nguyên liệu (LIKE bất kỳ)
    public function scopeMatchIngredients($q, array $ings)
    {
        $ings = collect($ings)->filter()->map(fn($x) => mb_strtolower(trim($x)))->unique()->values();

        if ($ings->isEmpty()) return $q;

        return $q->whereExists(function ($sub) use ($ings) {
            $sub->from('recipe_ingredients as ri')
                ->whereColumn('ri.recipe_id', 'recipes.id')
                ->where(function ($w) use ($ings) {
                    foreach ($ings as $ing) {
                        $w->orWhere('ri.name', 'like', '%' . $ing . '%');
                    }
                });
        });
    }
}
