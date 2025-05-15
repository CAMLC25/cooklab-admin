<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_id',
        'type', // type có thể là 'like', 'love', 'clap'...
    ];

    // 🔗 Liên kết với công thức
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    // 🔗 Liên kết với người dùng
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
