<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'name',
    ];

    protected $casts = [
        'recipe_id' => 'integer',
        'name'      => 'string',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];

    // 🔗 Liên kết với công thức
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
