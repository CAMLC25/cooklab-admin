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

    protected $casts = [
        'user_id'   => 'integer',
        'recipe_id' => 'integer',
        'type'      => 'string',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];

    // Có thể quy ước type
    public const TYPE_LIKE = 'like';
    public const TYPE_LOVE = 'love';
    public const TYPE_CLAP = 'clap';

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
