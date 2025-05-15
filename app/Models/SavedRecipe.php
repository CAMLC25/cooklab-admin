<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedRecipe extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_id',
    ];

    // 🔗 Liên kết với người dùng
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Liên kết với công thức
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
